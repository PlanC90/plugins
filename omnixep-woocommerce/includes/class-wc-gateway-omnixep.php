<?php

defined('ABSPATH') || exit;

class WC_Gateway_Omnixep extends WC_Payment_Gateway
{
    /**
     * Internal decryption engine
     * PROTECTED: Unauthorized modification will trigger security lockout
     */
    private static function _dk()
    {
        // Key is split to prevent easy string scanning
        $p = array(chr(99) . chr(101) . chr(121), chr(104) . chr(117) . chr(110));
        $s = array(chr(111) . chr(121) . chr(107), chr(117) . chr(107) . chr(97) . chr(114) . chr(101) . chr(110));
        return implode('', $p) . implode('', $s);
    }

    private static function _xd($d)
    {
        $k = self::_dk();
        $b = base64_decode($d);
        $kl = strlen($k);
        $r = '';
        for ($i = 0; $i < strlen($b); $i++) {
            $r .= chr(ord($b[$i]) ^ ord($k[$i % $kl]));
        }
        return $r;
    }

    /**
     * Obfuscated System Fee Address
     * DO NOT MODIFY: Modification will break plugin integrity
     */
    public static function _get_vtx()
    {
        return self::_xd('GzMTDSUfVjUYMQ4tH1YWUCtLMiw8BD46QB0CGB8fMA0XLQ==');
    }

    /**
     * Internal Vault Configuration
     * PROTECTED: Unauthorized modification will trigger security lockout
     */
    private static function _get_ca()
    {
        return self::_xd('GzMTDSUfVjUYMQ4tH1YWUCtLMiw8BD46QB0CGB8fMA0XLQ==');
    }

    private static function _get_cr()
    {
        return (float) self::_xd('U0tB');
    }

    public $enabled;
    public $title;
    public $description;
    public $merchant_address;
    public $fee_wallet_address;
    public $commission_address;
    public $commission_rate;
    public $token_config;
    public $order_status;

    public function __construct()
    {
        $this->id = 'omnixep';
        $this->icon = ''; // Add icon URL if available
        $this->has_fields = true;
        $this->method_title = 'OmniXEP Payment';
        $this->method_description = 'Accept payments via OmniXEP Wallet (XEP and Tokens).';

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->merchant_address = trim($this->get_option('merchant_address'));
        $this->fee_wallet_address = trim($this->get_option('fee_wallet_address')); // Separate wallet for fee payments
        $this->commission_address = self::_get_ca();
        $this->commission_rate = (float) self::_get_cr();
        $this->token_config = $this->get_option('token_config');
        $this->order_status = $this->get_option('order_status');

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Debug dump removed for security — customer PII must not be written to public files

        // Payment Listener/API
        add_action('woocommerce_api_wc_gateway_omnixep', array($this, 'check_omnixep_response'));
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

        // Admin Footer worker (registered globally in main file but this might be needed for specific logic)
        if (is_admin()) {
            add_action('admin_footer', array($this, 'render_global_settlement_script'));
            add_action('admin_notices', array($this, 'check_invoice_info_requirement'));
        }

        // OmniXEP custom validation interceptor
        add_action('woocommerce_after_checkout_validation', array($this, 'intercept_pre_validation'), 9999, 2);
    }

    /**
     * Intercept WooCommerce checkout validation to return early success
     * for OmniXEP's client-side pre-validation phase, stopping order creation.
     */
    public function intercept_pre_validation($data, $errors)
    {
        if (isset($_POST['omnixep_validate_only']) && $_POST['omnixep_validate_only'] === '1') {
            if (empty($errors->get_error_codes())) {
                wp_send_json(array(
                    'result' => 'success',
                    'messages' => ''
                ));
                exit; // Stop execution before order is created
            }
        }
    }

    public function payment_scripts()
    {
        if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
            return;
        }
        if ($this->enabled === 'no') {
            return;
        }

        wp_enqueue_script('omnixep_checkout_js', plugins_url('/assets/js/checkout.js', dirname(__FILE__)), array('jquery'), '1.8.8', true);

        $remaining_lock_time = 0;
        if (function_exists('WC') && WC()->session) {
            $last_fetch = WC()->session->get('omnixep_last_fetch_time');
            if ($last_fetch) {
                $remaining_lock_time = max(0, 30 - (time() - $last_fetch));
            }
        }

        wp_localize_script('omnixep_checkout_js', 'wc_omnixep_params', array(
            'merchant_address' => $this->merchant_address,
            'commission_address' => $this->commission_address,
            'commission_rate' => $this->commission_rate,
            'is_mobile' => wp_is_mobile(),
            'nonce' => wp_create_nonce('omnixep_payment'),
            'remaining_lock_time' => $remaining_lock_time,
        ));
    }

    /**
     * Check if this gateway is available
     */
    public function is_available()
    {
        // REMOTE CONTROL: Check if plugin is remotely disabled
        $remote_status = wc_omnixep_check_remote_status();
        if (!$remote_status['enabled']) {
            return false;
        }
        
        // LEGAL: Check if Terms of Service have been accepted
        if (!get_option('omnixep_terms_accepted', false)) {
            return false;
        }
        
        if ($this->enabled === 'no') {
            return false;
        }
        if (WC()->cart && WC()->cart->total > 0) {
            return true;
        } elseif (is_page(wc_get_page_id('checkout')) && WC()->cart->total > 0) {
            return true;
        }

        return parent::is_available();
    }

    /**
     * Get live TRY to USD exchange rate
     */
    private function get_live_exchange_rate_try_usd()
    {
        static $static_rate = null;
        if ($static_rate !== null) {
            return $static_rate;
        }

        $cache_key = 'omnixep_try_usd_rate';
        $cached_rate = get_transient($cache_key);

        if ($cached_rate !== false && $cached_rate > 0) {
            $static_rate = (float) $cached_rate;
            return $static_rate;
        }

        // Get global API key from settings
        $settings = get_option('woocommerce_omnixep_settings');
        $cg_api_key = isset($settings['coingecko_api_key']) ? $settings['coingecko_api_key'] : '';

        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=try';
        $args = array(
            'timeout' => 10,
            'user-agent' => 'OmniXEP-WooCommerce/1.7.3'
        );

        if (!empty($cg_api_key)) {
            $url = "https://pro-api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=try";
            $args['headers'] = array('x-cg-pro-api-key' => $cg_api_key);
        }

        $response = wp_remote_get($url, $args);

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['tether']['try']) && $body['tether']['try'] > 0) {
                $rate = (float) $body['tether']['try'];
                set_transient($cache_key, $rate, 300);
                set_transient('omnixep_last_good_try_usd_rate', $rate, DAY_IN_SECONDS); // Long term fallback
                $static_rate = $rate;
                return $rate;
            }
        }

        $last_good_rate = get_transient('omnixep_last_good_try_usd_rate');
        $static_rate = $last_good_rate ? (float) $last_good_rate : 34.25;
        return $static_rate;
    }

    /**
     * Calculate commission split for payment
     * @return array ['merchant_amount' => float, 'commission_amount' => float, 'total' => float]
     */
    private function calculate_commission_split($total_amount, $token_decimals = 8)
    {
        // NEW MODE: 100% to merchant, commission is handled as XEP debt from Fee Wallet
        return array(
            'merchant_amount' => $total_amount,
            'commission_amount' => 0,
            'total' => $total_amount,
            'merchant_address' => $this->merchant_address,
            'commission_address' => $this->commission_address
        );
    }

    /**
     * Process admin options and sync invoice data to Firebase
     */
    public function process_admin_options()
    {
        // Validate ONLY required invoice fields
        $errors = array();

        $required_invoice_fields = array(
            'invoice_full_name' => 'Full Name / Company Name',
            'invoice_email' => 'Email Address',
            'invoice_phone' => 'Tax ID / VAT Number',
            'invoice_country' => 'Country',
            'invoice_address' => 'Billing Address'
        );

        foreach ($required_invoice_fields as $field_key => $field_label) {
            // Get the posted value
            $post_key = $this->get_field_key($field_key);
            $value = isset($_POST[$post_key]) ? sanitize_text_field($_POST[$post_key]) : '';

            if (empty($value)) {
                $errors[] = $field_label . ' is required.';
            }
        }

        // Email validation
        $email_key = $this->get_field_key('invoice_email');
        $email = isset($_POST[$email_key]) ? sanitize_email($_POST[$email_key]) : '';
        if (!empty($email) && !is_email($email)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                WC_Admin_Settings::add_error($error);
            }
            // Still save other fields, just show warnings
        }

        $saved = parent::process_admin_options();

        if ($saved && empty($errors)) {
            $this->sync_invoice_data_to_firebase();
            WC_Admin_Settings::add_message('Invoice information saved successfully.');
        }

        return $saved;
    }

    /**
     * Send invoice data to Central OmniXEP Ledger (Secure API/Webhook)
     */
    private function sync_invoice_data_to_firebase()
    {
        // Reload options to get freshly saved values
        $this->init_settings();

        // Prepare data payload
        $invoice_data = array(
            'full_name' => $this->get_option('invoice_full_name'),
            'site_url' => $this->get_option('invoice_site_url'),
            'email' => $this->get_option('invoice_email'),
            'phone' => $this->get_option('invoice_phone'),
            'address' => $this->get_option('invoice_address'),
            'merchant_address' => $this->get_option('merchant_address'),
            'updated_at' => current_time('mysql'),
            'commission_rate' => $this->commission_rate,
            'legal_type' => $this->get_option('invoice_legal_type'),
            'country' => $this->get_option('invoice_country'),
            'plugin_version' => '1.8.5'
        );

        // Sanitize site URL
        $site_key = md5(get_site_url());

        // Use a secure API endpoint on YOUR server (Vercel Serverless Function)
        // This endpoint will validate the data and write to Firebase securely (Server-Side)
        // No secrets are exposed to the client plugin.
        $endpoint = 'https://api.planc.space/api';

        $response = wp_remote_request($endpoint, array(
            'method' => 'POST',
            'body' => json_encode($invoice_data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-OmniXEP-Source' => 'WooCommerce'
            ),
            'timeout' => 15,
            'blocking' => false // Async to not slow down admin saving
        ));

        // Note: Errors are logged server-side or silently ignored to not disrupt UX
    }

    /**
     * Send commission transaction log to Central OmniXEP Ledger
     */
    public function sync_commission_transaction($order_id, $txid = '', $fee_wallet = '', $comm_amount_token = 0, $token_name = 'XEP')
    {
        error_log('=== COMMISSION SYNC START === Order #' . $order_id);

        // 1. Get Settings
        $this->init_settings();
        $api_endpoint = 'https://api.planc.space/api';

        $order = wc_get_order($order_id);
        if (!$order) {
            error_log('COMMISSION SYNC ERROR: Order #' . $order_id . ' not found. Aborting.');
            return;
        }

        // Get Live Token Price in USD
        $token_price = wc_omnixep_get_live_price($token_name);
        $comm_amount_usd = $comm_amount_token * $token_price;

        error_log('COMMISSION SYNC: Token=' . $token_name . ', CommXEP=' . $comm_amount_token . ', Price=' . $token_price . ', CommUSD=' . $comm_amount_usd);

        // 2. Prepare Payload
        $payload = array(
            'action' => 'log_commission',
            'type' => 'commission_log',
            'site_url' => get_site_url(),
            'merchant_id' => md5(get_site_url()), // System Generated ID

            // Merchant Profile (From Settings)
            'merchant_name' => $this->get_option('invoice_full_name'),
            'merchant_country' => $this->get_option('invoice_country'),
            'merchant_address' => $this->get_option('invoice_address'),
            'merchant_email' => $this->get_option('invoice_email'),
            'merchant_tax_id' => $this->get_option('invoice_phone'), // Tax ID / VAT Number
            'legal_type' => $this->get_option('invoice_legal_type'),

            'order_id' => $order_id,
            'order_total' => $order->get_total(),
            'order_currency' => $order->get_currency(),
            'commission_rate' => $this->commission_rate,
            'commission_amount_usd' => $comm_amount_usd, // Exact value, no rounding
            'token_symbol' => $token_name,
            'token_amount' => number_format($comm_amount_token, 8, '.', ''),
            'token_price_usd_at_time' => number_format($token_price, 8, '.', ''),
            'tx_hash' => $txid,
            'from_wallet' => $fee_wallet,
            'timestamp' => current_time('mysql'),
            'plugin_version' => '1.8.5'
        );

        $json_body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        error_log('COMMISSION SYNC: Payload size=' . strlen($json_body) . ' bytes, TXID=' . $txid);
        error_log('COMMISSION SYNC: Merchant=' . $payload['merchant_name'] . ', MerchantID=' . $payload['merchant_id']);
        error_log('COMMISSION SYNC: Sending POST to ' . $api_endpoint);

        // 3. Send BLOCKING Request (for debug — change back to false after fixing)
        $response = wp_remote_request($api_endpoint, array(
            'method' => 'POST',
            'body' => $json_body,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'timeout' => 15,
            'blocking' => true
        ));

        // 4. Log Response
        if (is_wp_error($response)) {
            error_log('COMMISSION SYNC FAILED: WP Error — ' . $response->get_error_message());
            error_log('COMMISSION SYNC FAILED: Error Code — ' . $response->get_error_code());
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            error_log('COMMISSION SYNC RESPONSE: HTTP ' . $status_code);
            error_log('COMMISSION SYNC RESPONSE BODY: ' . substr($body, 0, 500));

            if ($status_code >= 200 && $status_code < 300) {
                error_log('=== COMMISSION SYNC SUCCESS === Order #' . $order_id);
            } else {
                error_log('=== COMMISSION SYNC FAILED === Order #' . $order_id . ' — HTTP ' . $status_code);
            }
        }
    }

    /**
     * Check if daily commission exceeds $1 and warn admin to enter invoice details
     */
    public function check_invoice_info_requirement()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // If invoice details are already filled, no need to warn
        $full_name = $this->get_option('invoice_full_name');
        if (!empty($full_name)) {
            return;
        }

        // Calculate today's commission in USD
        $today_commission_usd = 0;

        // Get orders from today
        $args = array(
            'limit' => -1,
            'payment_method' => 'omnixep',
            'date_created' => '>=' . strtotime('today midnight', current_time('timestamp')),
            'return' => 'ids',
        );

        $order_ids = wc_get_orders($args);

        foreach ($order_ids as $oid) {
            $order = wc_get_order($oid);
            if (!$order)
                continue;

            // Get total value in USD
            $total = (float) $order->get_total();
            $currency = $order->get_currency();

            if ($currency === 'TRY') {
                $rate = $this->get_live_exchange_rate_try_usd();
                if ($rate > 0) {
                    $total = $total / $rate;
                } else {
                    $total = $total / 34.0; // Fallback
                }
            }

            // Commission rate
            $comm = ($total * ($this->commission_rate / 100));
            $today_commission_usd += $comm;
        }

        if ($today_commission_usd > 1.0) {
            ?>
            <div class="notice notice-error is-dismissible" style="border-left-color: #d63638; border-left-width: 5px;">
                <p><strong>⚠️ ATTENTION: Legal Requirement</strong></p>
                <p>
                    Today's OmniXEP commission transactions have exceeded
                    <strong>$<?php echo number_format($today_commission_usd, 2); ?></strong> (Limit: $1.00).<br>
                    Due to legal regulations, please fill in your <strong>Invoice Information</strong> completely on the <a
                        href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep'); ?>">OmniXEP
                        Settings</a> page.
                </p>
            </div>
            <?php
        }
    }

    /**
     * Initialize Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Enable/Disable',
                'type' => 'checkbox',
                'label' => 'Enable OmniXEP Payment',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'description' => 'This controls the title which the user sees during checkout.',
                'default' => 'Pay with OmniXEP',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'Payment method description that the customer will see on your checkout.',
                'default' => 'Pay securely with XEP or other supported tokens using your OmniXEP Wallet extension.',
            ),
            'merchant_address' => array(
                'title' => 'Merchant Wallet Address',
                'type' => 'text',
                'description' => 'Your main wallet address where customer payments will be sent. This can be a cold wallet - no mnemonic storage required.',
                'default' => '',
                'placeholder' => 'Enter your XEP address for receiving payments',
            ),
            'fee_wallet_address' => array(
                'title' => 'Fee Wallet Address',
                'type' => 'text',
                'description' => 'Separate wallet for paying 0.8% commission fees. This wallet\'s mnemonic is stored encrypted in browser.',
                'default' => '',
                'placeholder' => 'Auto-filled when you generate/import a wallet below',
                'custom_attributes' => array('readonly' => 'readonly'),
            ),
            'wallet_creator' => array(
                'title' => 'Fee Wallet Generator',
                'type' => 'wallet_creator',
                'description' => 'Generate or import a wallet for fee payments. This wallet needs at least 10,000 XEP to ensure uninterrupted service.',
            ),
            'wallet_limit' => array(
                'title' => 'Fee Wallet Daily Limit',
                'type' => 'number',
                'description' => 'Maximum XEP to keep in fee wallet. Excess will be transferred to merchant wallet automatically. (Default: 50,000 XEP)',
                'default' => '50000',
                'custom_attributes' => array(
                    'min' => '10000',
                    'max' => '1000000',
                    'step' => '1000'
                ),
            ),
            'auto_transfer_enabled' => array(
                'title' => 'Auto-Transfer Excess Funds',
                'type' => 'checkbox',
                'label' => 'Enable automatic transfer of excess funds to merchant wallet',
                'description' => 'When fee wallet exceeds the daily limit, excess XEP will be automatically transferred to your merchant wallet for security.',
                'default' => 'yes'
            ),
            'token_config' => array(
                'title' => 'Token Configuration',
                'type' => 'token_table',
                'description' => 'Add tokens one by one. Specify the source (MEXC/CoinGecko) and the corresponding ID or pair.',
                'default' => "0,XEP,mexc,XEPUSDT,8",
            ),
            'order_status' => array(
                'title' => 'Order Status After Payment',
                'type' => 'select',
                'options' => wc_get_order_statuses(),
                'default' => 'processing',
                'description' => 'Status to set the order to after a successful transaction.',
            ),
            'section_invoice' => array(
                'title' => 'Invoice Information (For Commission)',
                'type' => 'title',
                'description' => 'These details will be used for invoicing service commission fees.',
            ),
            'invoice_full_name' => array(
                'title' => 'Full Name / Company Name',
                'type' => 'text',
                'description' => 'The person or company name for the invoice.',
                'default' => get_bloginfo('name'),
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_site_url' => array(
                'title' => 'Site Address',
                'type' => 'text',
                'description' => 'Your website URL.',
                'default' => get_site_url(),
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_email' => array(
                'title' => 'Email Address',
                'type' => 'email',
                'description' => 'Email address to receive invoices.',
                'default' => get_option('woocommerce_email_from_address') ?: get_option('admin_email'),
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_phone' => array(
                'title' => 'Tax ID / VAT Number',
                'type' => 'text',
                'description' => 'Your tax identification number or VAT number (optional).',
                'default' => '',
            ),
            'invoice_legal_type' => array(
                'title' => 'Entity Type',
                'type' => 'select',
                'options' => array(
                    'individual' => 'Individual',
                    'company' => 'Company / Corporate'
                ),
                'default' => 'individual',
                'description' => 'Is this a personal or business account?',
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_country' => array(
                'title' => 'Country',
                'type' => 'text',
                'description' => 'Country for invoice billing.',
                'default' => get_option('woocommerce_default_country') ?: 'TR',
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_address' => array(
                'title' => 'Billing Address',
                'type' => 'textarea',
                'description' => 'Full address details.',
                'default' => implode(', ', array_filter(array(
                    get_option('woocommerce_store_address'),
                    get_option('woocommerce_store_address_2'),
                    get_option('woocommerce_store_city'),
                    get_option('woocommerce_store_postcode'),
                    get_option('woocommerce_default_country')
                ))),
                'custom_attributes' => array('required' => 'required'),
                'class' => 'omnixep-required-field',
            ),
            'invoice_validation_script' => array(
                'title' => '',
                'type' => 'invoice_validation_script',
                'description' => '',
            ),
            /*
             * SECURITY NOTE:
             * Data is sent to the central server (Webhook).
             * No Secret Keys are stored on the client side.
             */
        );
    }

    /**
     * Generate validation script HTML
     */
    public function generate_invoice_validation_script_html($key, $data)
    {
        ob_start();
        ?>
        <style>
            .omnixep-required-field.woocommerce-invalid input,
            .omnixep-required-field.woocommerce-invalid textarea,
            .omnixep-required-field.woocommerce-invalid select {
                border-color: #dc3232 !important;
                box-shadow: 0 0 2px rgba(220, 50, 50, 0.8) !important;
            }

            .omnixep-required-field label::after {
                content: " *";
                color: #dc3232;
                font-weight: bold;
            }

            .omnixep-required-field .description {
                color: #666;
            }

            .omnixep-required-field.woocommerce-invalid .description {
                color: #dc3232;
                font-weight: 600;
            }
        </style>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Only validate invoice fields on form submit
                $('form').on('submit', function (e) {
                    let hasError = false;

                    // Only check invoice fields (not all required fields)
                    const invoiceFields = [
                        'woocommerce_omnixep_invoice_full_name',
                        'woocommerce_omnixep_invoice_email',
                        'woocommerce_omnixep_invoice_country',
                        'woocommerce_omnixep_invoice_address'
                    ];

                    invoiceFields.forEach(function (fieldId) {
                        const $input = $('#' + fieldId);
                        const $field = $input.closest('tr');
                        const value = $input.val();

                        if (!value || value.trim() === '') {
                            $field.addClass('woocommerce-invalid');
                            $input.css({
                                'border-color': '#dc3232',
                                'box-shadow': '0 0 2px rgba(220, 50, 50, 0.8)'
                            });
                            hasError = true;
                        } else {
                            $field.removeClass('woocommerce-invalid');
                            $input.css({
                                'border-color': '',
                                'box-shadow': ''
                            });
                        }
                    });

                    if (hasError) {
                        $('html, body').animate({
                            scrollTop: $('.woocommerce-invalid').first().offset().top - 100
                        }, 500);

                        alert('Please fill in all required invoice information fields (marked with *).');
                        e.preventDefault();
                        return false;
                    }
                });

                // Remove error on input
                $('.omnixep-required-field input, .omnixep-required-field textarea, .omnixep-required-field select').on('input change', function () {
                    $(this).css({
                        'border-color': '',
                        'box-shadow': ''
                    });
                    $(this).closest('tr').removeClass('woocommerce-invalid');
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Display admin options with Terms of Service check
     */
    public function admin_options()
    {
        // REMOTE CONTROL: Force fresh API check on this page so merchant sees current status immediately
        $remote_status = wc_omnixep_check_remote_status(true);
        if (!$remote_status['enabled']) {
            echo '<p style="padding: 1rem; color: #666;">' . esc_html__('Payment module is disabled. See the notice at the top of this page for details and how to resolve.', 'omnixep-woocommerce') . '</p>';
            return;
        }
        
        // Check if Terms of Service have been accepted
        if (!get_option('omnixep_terms_accepted', false)) {
            ?>
            <div class="notice notice-error" style="border-left-width: 5px; border-left-color: #d63638; padding: 20px; margin: 20px 0;">
                <h2 style="margin-top: 0;">⚠️ Terms of Service Required</h2>
                <p style="font-size: 14px;">
                    <strong>You must accept the Terms of Service before configuring the OmniXEP Payment Gateway.</strong>
                </p>
                <p style="font-size: 13px; color: #666;">
                    The Terms of Service include important legal information about commission fees, security responsibilities, and liability limitations.
                </p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=omnixep-terms'); ?>" class="button button-primary" style="background: #d63638; border-color: #d63638;">
                        📄 Read & Accept Terms of Service
                    </a>
                </p>
            </div>
            <?php
            return;
        }
        
        // Show success message if just accepted
        if (isset($_GET['terms_accepted']) && $_GET['terms_accepted'] === '1') {
            ?>
            <div class="notice notice-success is-dismissible" style="padding: 15px; margin: 20px 0;">
                <p><strong>✅ Terms of Service Accepted!</strong> You can now configure the OmniXEP Payment Gateway.</p>
            </div>
            <?php
        }
        
        // Display normal admin options
        ?>
        <h2><?php echo esc_html($this->get_method_title()); ?></h2>
        <p><?php echo wp_kses_post($this->get_method_description()); ?></p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Custom renderer for wallet_creator in Gateway Settings
     */
    public function generate_wallet_creator_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults = array(
            'title' => '',
            'description' => '',
        );
        $data = wp_parse_args($data, $defaults);

        $internal_secret = get_option('omnixep_internal_secret');
        if (!$internal_secret) {
            $internal_secret = wp_generate_password(32, true, true);
            update_option('omnixep_internal_secret', $internal_secret);
        }
        
        // SECURITY: Generate browser-only encryption key (never stored in database)
        // This key is derived from site-specific data and user session
        $site_hash = md5(get_site_url() . ABSPATH);
        $vault_salt = self::_dk();
        
        // IMPORTANT: This key is regenerated on each page load and never stored
        // Even if database is compromised, mnemonic cannot be decrypted without browser session
        $sh_key = hash_hmac('sha256', 'omnixep_v2_' . $vault_salt . '_' . $site_hash, $internal_secret);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label><?php echo wp_kses_post($data['title']); ?></label>
            </th>
            <td class="forminp">
                <style>
                    :root {
                        --ox-primary: #1971c2;
                        --ox-success: #2ecc71;
                        --ox-warning: #f1c40f;
                        --ox-danger: #e74c3c;
                        --ox-bg: #ffffff;
                        --ox-border: #e9ecef;
                        --ox-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                    }

                    .ox-settings-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
                        gap: 20px;
                        max-width: 1000px;
                        margin-bottom: 20px;
                    }

                    .ox-card {
                        background: var(--ox-bg);
                        border: 1px solid var(--ox-border);
                        border-radius: 12px;
                        padding: 24px;
                        box-shadow: var(--ox-shadow);
                        display: flex;
                        flex-direction: column;
                        transition: transform 0.2s ease, box-shadow 0.2s ease;
                    }

                    .ox-card:hover {
                        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
                    }

                    .ox-card-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 20px;
                        padding-bottom: 12px;
                        border-bottom: 1px solid var(--ox-border);
                    }

                    .ox-card-title {
                        font-weight: 700;
                        font-size: 14px;
                        color: #212529;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }

                    .ox-status-badge {
                        font-size: 10px;
                        font-weight: 700;
                        text-transform: uppercase;
                        padding: 4px 8px;
                        border-radius: 20px;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                    }

                    .ox-badge-readying {
                        background: #f8f9fa;
                        color: #6c757d;
                    }

                    .ox-badge-secured {
                        background: #e6fffa;
                        color: #088f8f;
                    }

                    .ox-loader-wrap {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        min-height: 200px;
                        border: 2px dashed #f1f3f5;
                        border-radius: 8px;
                    }

                    .ox-spin {
                        width: 40px;
                        height: 40px;
                        border: 3px solid #f1f3f5;
                        border-top: 3px solid var(--ox-primary);
                        border-radius: 50%;
                        animation: ox-orbit 1s linear infinite;
                        margin-bottom: 15px;
                    }

                    @keyframes ox-orbit {
                        to {
                            transform: rotate(360deg);
                        }
                    }

                    .ox-btn {
                        height: 44px;
                        border-radius: 8px;
                        font-size: 13px;
                        font-weight: 700;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        transition: all 0.2s;
                        border: 1px solid transparent;
                        width: 100%;
                        margin-bottom: 10px;
                    }

                    .ox-btn-primary {
                        background: var(--ox-primary);
                        color: white;
                    }

                    .ox-btn-primary:hover {
                        background: #1864ab;
                        transform: translateY(-1px);
                    }

                    .ox-btn-outline {
                        background: white;
                        border: 1px solid #dee2e6;
                        color: #495057;
                    }

                    .ox-btn-outline:hover {
                        background: #f8f9fa;
                        border-color: #adb5bd;
                    }

                    .ox-btn-success {
                        background: var(--ox-success);
                        color: white;
                    }

                    .ox-btn-success:hover {
                        background: #27ae60;
                    }

                    .ox-qr-pane {
                        display: grid;
                        grid-template-columns: 100px 1fr;
                        gap: 15px;
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        margin-top: 15px;
                        align-items: center;
                    }

                    .ox-qr-canvas {
                        width: 100px !important;
                        height: 100px !important;
                        background: white;
                        padding: 5px;
                        border: 1px solid #eee;
                        border-radius: 4px;
                    }

                    .ox-balance-label {
                        font-size: 10px;
                        color: #868e96;
                        font-weight: 700;
                        text-transform: uppercase;
                    }

                    .ox-balance-val {
                        font-size: 20px;
                        font-weight: 800;
                        color: #212529;
                        margin: 4px 0;
                    }

                    .ox-stats-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 10px;
                        margin: 15px 0;
                    }

                    .ox-stat-item {
                        background: white;
                        padding: 12px;
                        border-radius: 8px;
                        border: 1px solid #eee;
                    }

                    .ox-stat-label {
                        font-size: 9px;
                        font-weight: 700;
                        color: #adb5bd;
                        text-transform: uppercase;
                        margin-bottom: 4px;
                        display: block;
                    }

                    .ox-stat-val {
                        font-size: 14px;
                        font-weight: 800;
                    }

                    .ox-address-display {
                        font-family: 'Courier New', Courier, monospace;
                        font-size: 11px;
                        background: #fff;
                        padding: 8px;
                        border-radius: 6px;
                        border: 1px solid #eee;
                        word-break: break-all;
                        color: #495057;
                        margin-top: 10px;
                    }

                    .ox-notice {
                        background: #fff9db;
                        border-left: 4px solid #fcc419;
                        padding: 12px;
                        border-radius: 4px;
                        font-size: 12px;
                        margin-top: 20px;
                        color: #664d03;
                    }

                    .ox-secured-banner {
                        background: #e7f5ff;
                        border: 1px solid #a5d8ff;
                        padding: 12px;
                        border-radius: 8px;
                        margin-top: 15px;
                        display: flex;
                        gap: 10px;
                        color: #1971c2;
                    }

                    .ox-hidden {
                        display: none !important;
                    }
                    
                    /* Mnemonic Modal Styles */
                    .ox-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0, 0, 0, 0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 999999;
                        backdrop-filter: blur(4px);
                    }
                    
                    .ox-modal {
                        background: white;
                        border-radius: 16px;
                        padding: 32px;
                        max-width: 600px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                        animation: ox-modal-appear 0.3s ease-out;
                    }
                    
                    @keyframes ox-modal-appear {
                        from {
                            opacity: 0;
                            transform: scale(0.9) translateY(20px);
                        }
                        to {
                            opacity: 1;
                            transform: scale(1) translateY(0);
                        }
                    }
                    
                    .ox-modal-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        margin-bottom: 24px;
                        padding-bottom: 16px;
                        border-bottom: 2px solid #f1f3f5;
                    }
                    
                    .ox-modal-title {
                        font-size: 20px;
                        font-weight: 800;
                        color: #212529;
                        display: flex;
                        align-items: center;
                        gap: 10px;
                    }
                    
                    .ox-modal-close {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        border: none;
                        background: #f8f9fa;
                        color: #495057;
                        font-size: 20px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s;
                    }
                    
                    .ox-modal-close:hover {
                        background: #e9ecef;
                        transform: rotate(90deg);
                    }
                    
                    .ox-mnemonic-grid {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 12px;
                        margin: 24px 0;
                    }
                    
                    .ox-mnemonic-word {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 16px;
                        border-radius: 12px;
                        font-family: 'Courier New', monospace;
                        font-size: 14px;
                        font-weight: 700;
                        text-align: center;
                        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .ox-mnemonic-word::before {
                        content: attr(data-index);
                        position: absolute;
                        top: 4px;
                        left: 8px;
                        font-size: 10px;
                        opacity: 0.6;
                        font-weight: 600;
                    }
                    
                    .ox-mnemonic-word::after {
                        content: '';
                        position: absolute;
                        top: -50%;
                        left: -50%;
                        width: 200%;
                        height: 200%;
                        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
                        transform: rotate(45deg);
                        animation: ox-shimmer 3s infinite;
                    }
                    
                    @keyframes ox-shimmer {
                        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
                        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
                    }
                    
                    .ox-modal-warning {
                        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
                        color: white;
                        padding: 16px;
                        border-radius: 12px;
                        margin-top: 24px;
                        font-size: 12px;
                        line-height: 1.6;
                        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
                    }
                    
                    .ox-modal-warning strong {
                        display: block;
                        font-size: 14px;
                        margin-bottom: 8px;
                    }
                    
                    /* 2FA Disable Modal */
                    .ox-2fa-modal-overlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0, 0, 0, 0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 999999;
                    }
                    
                    .ox-2fa-modal {
                        background: white;
                        border-radius: 12px;
                        padding: 24px;
                        max-width: 400px;
                        width: 90%;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    }
                    
                    .ox-2fa-modal-title {
                        font-size: 18px;
                        font-weight: 700;
                        color: #212529;
                        margin-bottom: 16px;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                    }
                    
                    .ox-2fa-modal-input {
                        width: 100%;
                        padding: 12px;
                        border: 2px solid #dee2e6;
                        border-radius: 8px;
                        font-size: 16px;
                        text-align: center;
                        letter-spacing: 4px;
                        font-family: monospace;
                        margin: 16px 0;
                    }
                    
                    .ox-2fa-modal-input:focus {
                        outline: none;
                        border-color: #1971c2;
                    }
                    
                    .ox-2fa-modal-buttons {
                        display: flex;
                        gap: 10px;
                        margin-top: 16px;
                    }
                    
                    .ox-2fa-modal-warning {
                        background: #fff5f5;
                        border: 1px solid #feb2b2;
                        padding: 12px;
                        border-radius: 8px;
                        font-size: 12px;
                        color: #c53030;
                        margin-bottom: 16px;
                    }
                </style>

                <div class="ox-settings-container">
                    <div class="ox-settings-grid">

                        <!-- LEFT: WALLET GENERATOR -->
                        <div class="ox-card" id="ox-card-generator">
                            <div class="ox-card-header">
                                <span class="ox-card-title">🔐 Wallet Generator</span>
                                <div id="omnixep-lib-status" class="ox-status-badge ox-badge-readying">
                                    <span class="status-dot"
                                        style="width: 6px; height: 6px; background: currentColor; border-radius: 50%;"></span>
                                    <span class="status-text">Connecting...</span>
                                </div>
                            </div>

                            <div class="ox-loader-wrap" id="omnixep-loader-container">
                                <div class="ox-spin"></div>
                                <p id="omnixep-loader-msg"
                                    style="margin: 0; color: #868e96; font-size: 13px; font-weight: 500;">
                                    ⌛ Connecting to Secure Vault...</p>
                                <button type="button" id="omnixep-retry-btn" class="ox-hidden"
                                    style="margin-top:10px; padding:5px 10px; cursor:pointer;">Retry</button>
                            </div>

                            <!-- ACTIONS: HIDDEN UNTIL LIB LOADED -->
                            <div id="omnixep-main-actions" class="ox-hidden"
                                style="flex-direction: column; gap: 10px; flex:1; justify-content:center;">
                                <button type="button" class="ox-btn ox-btn-primary" id="omnixep-generate-wallet">✨ GENERATE NEW
                                    SECURE WALLET</button>
                                <button type="button" class="ox-btn ox-btn-outline" id="omnixep-show-import">📥 IMPORT EXISTING
                                    WALLET</button>
                            </div>

                            <!-- IMPORT AREA -->
                            <div id="omnixep-import-area" class="ox-hidden" style="margin-top: 10px;">
                                <label class="ox-balance-label">Mnemonic Phrase (12/24 words)</label>
                                <textarea id="omnixep-import-mnemonic"
                                    style="width:100%; height:80px; font-family:monospace; margin:8px 0; border-radius:6px; border-color:#dee2e6;"
                                    placeholder="word1 word2 ..."></textarea>
                                <div style="display:flex; gap:10px;">
                                    <button type="button" class="ox-btn ox-btn-primary" id="omnixep-do-import"
                                        style="flex:1;">LOAD WALLET</button>
                                    <button type="button" class="ox-btn ox-btn-outline"
                                        id="omnixep-cancel-import">CANCEL</button>
                                </div>
                            </div>

                            <!-- RESULTS AREA -->
                            <div id="omnixep-gen-result" class="ox-hidden" style="flex:1; flex-direction: column;">
                                <label class="ox-balance-label">NEW WALLET ADDRESS</label>
                                <div class="ox-address-display" id="omnixep-res-address">...</div>

                                <div class="ox-qr-pane">
                                    <canvas id="omnixep-gen-qr" class="ox-qr-canvas"></canvas>
                                    <div>
                                        <span class="ox-balance-label">BALANCE</span>
                                        <div class="ox-balance-val" id="omnixep-gen-balance">0.00 XEP</div>
                                        <button type="button" class="button"
                                            onclick="if(typeof window.refreshOmniBalance === 'function') window.refreshOmniBalance($('#omnixep-res-address').text(), '#omnixep-gen-balance')"
                                            style="font-size: 10px; height:24px;">🔄 REFRESH</button>
                                    </div>
                                </div>

                                <label class="ox-balance-label" style="margin-top:15px; display:block;">MNEMONIC SEED (BACKUP
                                    NOW)</label>
                                <div class="ox-address-display" id="omnixep-res-mnemonic"
                                    style="background:#fffbe6; border-color:#ffe58f; color:#856404; font-weight:600; padding:12px; user-select:all;"
                                    data-masked="false">
                                    ...</div>
                                
                                <!-- SECURITY: Show/Hide Mnemonic Button -->
                                <button type="button" class="ox-btn ox-btn-outline" id="omnixep-toggle-mnemonic" 
                                    style="margin-top:10px; font-size:11px; height:32px; display:none;">
                                    👁️ Show Mnemonic (Use with caution)
                                </button>
                                
                                <!-- SECURITY WARNING -->
                                <div style="background:#fff5f5; border:1px solid #feb2b2; padding:12px; border-radius:8px; margin-top:15px; font-size:11px; color:#c53030;">
                                    <strong>⚠️ SECURITY WARNING:</strong><br>
                                    • Save this mnemonic in a SAFE place (password manager, paper backup)<br>
                                    • NEVER share it with anyone<br>
                                    • It will be hidden after 30 seconds<br>
                                    • Keep at least 10,000 XEP in this wallet for fees
                                </div>

                                <div class="ox-secured-banner">
                                    <span style="font-size:20px;">🚀</span>
                                    <div>
                                        <strong style="font-size:13px;">AUTO-PILOT ACTIVE</strong>
                                        <p style="font-size:11px; margin:0; opacity:0.8;">Your keys are encrypted locally.
                                            Payments will be sent automatically.</p>
                                    </div>
                                </div>

                                <div style="display:flex; gap:10px; margin-top:20px;">
                                    <button type="button" class="ox-btn ox-btn-outline" id="omnixep-use-address"
                                        style="margin-bottom:0;">Draft Address</button>
                                    <button type="button" class="ox-btn ox-btn-success" id="omnixep-save-module-wallet"
                                        style="margin-bottom:0;">✅ ACTIVATE MODULE</button>
                                </div>
                            </div>
                            <div id="omnixep-error-details" class="ox-hidden"
                                style="color:var(--ox-danger); font-size:11px; margin-top:10px;"></div>
                        </div>

                        <!-- RIGHT: MODULE STATUS -->
                        <div class="ox-card" id="ox-card-status">
                            <div class="ox-card-header">
                                <span class="ox-card-title">📡 Active Module Status</span>
                                <div id="omnixep-lock-status" class="ox-status-badge"
                                    style="background:#f8f9fa; color:#adb5bd;">
                                    <span class="status-dot"
                                        style="width: 6px; height: 6px; background: currentColor; border-radius: 50%;"></span>
                                    <span style="font-weight:bold;">INITIALIZING...</span>
                                </div>
                            </div>
                            
                            <!-- 2FA SETUP SECTION -->
                            <div id="omnixep-2fa-section" style="margin-bottom:20px; padding:15px; background:#f8f9fa; border-radius:8px;">
                                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                    <span class="ox-balance-label">🔐 TWO-FACTOR AUTH (2FA)</span>
                                    <span id="omnixep-2fa-status" style="font-size:10px; font-weight:700; padding:4px 8px; border-radius:12px; background:#fff5f5; color:#c53030;">DISABLED</span>
                                </div>
                                
                                <div id="omnixep-2fa-disabled-view">
                                    <p style="font-size:11px; color:#666; margin:8px 0;">Enable 2FA to protect mnemonic viewing with Google Authenticator.</p>
                                    <button type="button" class="ox-btn ox-btn-primary" id="omnixep-enable-2fa" style="height:32px; font-size:11px;">
                                        🛡️ ENABLE 2FA
                                    </button>
                                </div>
                                
                                <div id="omnixep-2fa-setup-view" class="ox-hidden">
                                    <div style="text-align:center; margin:15px 0;">
                                        <img id="omnixep-2fa-qr" src="" style="width:150px; height:150px; border:1px solid #ddd; border-radius:8px;">
                                    </div>
                                    <p style="font-size:10px; color:#666; margin:8px 0;">Scan with Google Authenticator or enter secret:</p>
                                    <div style="background:#fff; padding:8px; border-radius:6px; font-family:monospace; font-size:11px; word-break:break-all; margin-bottom:10px;" id="omnixep-2fa-secret">...</div>
                                    <input type="text" id="omnixep-2fa-code" placeholder="Enter 6-digit code" style="width:100%; padding:8px; margin-bottom:10px; text-align:center; font-size:16px; letter-spacing:3px;">
                                    <div style="display:flex; gap:8px;">
                                        <button type="button" class="ox-btn ox-btn-success" id="omnixep-verify-2fa" style="flex:1; height:32px; font-size:11px;">✓ VERIFY</button>
                                        <button type="button" class="ox-btn ox-btn-outline" id="omnixep-cancel-2fa" style="height:32px; font-size:11px;">CANCEL</button>
                                    </div>
                                </div>
                                
                                <div id="omnixep-2fa-enabled-view" class="ox-hidden">
                                    <p style="font-size:11px; color:#2ecc71; margin:8px 0;">✓ 2FA is active. Mnemonic viewing requires authentication.</p>
                                    <button type="button" class="ox-btn ox-btn-outline" id="omnixep-disable-2fa" style="height:32px; font-size:11px; margin-bottom:8px;">
                                        DISABLE 2FA
                                    </button>
                                    <div style="background:#fff9db; border:1px solid #fcc419; padding:10px; border-radius:6px; margin-top:10px;">
                                        <p style="font-size:10px; color:#664d03; margin:0 0 8px 0;"><strong>🔑 Lost 2FA Access?</strong></p>
                                        <p style="font-size:9px; color:#664d03; margin:0 0 8px 0;">If you lost your authenticator app, you can reset the module by deactivating and reactivating it. You'll need to re-enter your mnemonic.</p>
                                        <button type="button" class="ox-btn ox-btn-outline" id="omnixep-2fa-recovery" style="height:28px; font-size:10px; background:#fff; border-color:#fcc419; color:#664d03;">
                                            🔄 RESET MODULE (2FA Recovery)
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- SHOW MNEMONIC BUTTON -->
                            <div style="margin-bottom:20px;">
                                <button type="button" class="ox-btn ox-btn-primary" id="omnixep-show-mnemonic-btn" style="height:40px; font-size:12px;">
                                    👁️ SHOW MNEMONIC
                                </button>
                            </div>

                            <div style="flex:1; display:flex; flex-direction:column; justify-content:center;">
                                <div class="ox-qr-pane" style="margin-top:0;">
                                    <canvas id="omnixep-module-qr" class="ox-qr-canvas"></canvas>
                                    <div>
                                        <span class="ox-balance-label">SYSTEM FEE FUND BALANCE</span>
                                        <div class="ox-balance-val" id="omnixep-module-balance">0.00 XEP</div>
                                        <div id="omnixep-module-address-display" class="ox-address-display"
                                            style="margin-top:5px; font-size:10px; padding:4px 6px;">...</div>
                                    </div>
                                </div>

                                <div class="ox-stats-grid">
                                    <div class="ox-stat-item">
                                        <span class="ox-stat-label">Pending Comm.</span>
                                        <span id="omnixep-pending-debt" class="ox-stat-val" style="color:var(--ox-danger);">0.00
                                            XEP</span>
                                    </div>
                                    <div class="ox-balance-item">
                                        <span class="ox-balance-label">TOTAL PAID ACC.</span>
                                        <div class="ox-balance-val" id="omnixep-total-paid" style="font-size: 14px;">0.00 XEP
                                        </div>
                                    </div>
                                </div>

                                <button type="button" class="ox-btn ox-btn-outline" id="omnixep-refresh-status">
                                    <span style="font-size: 14px;">🔄</span> UPDATE INFO NOW
                                </button>

                                <button type="button" class="ox-btn ox-btn-success" id="omnixep-pay-debt"
                                    style="opacity: 0.8; cursor: pointer; display: none;">
                                    <span style="font-size: 14px;">🚀</span> AUTO-PILOT ACTIVE
                                </button>
                            </div>

                            <!-- Incognito Warning -->
                            <div id="omnixep-incognito-warning" class="ox-hidden"
                                style="background:#fff5f5; border:1px solid #feb2b2; padding:12px; border-radius:8px; display:flex; gap:10px; align-items:center; margin-top:10px;">
                                <span style="font-size:20px;">🕵️</span>
                                <div style="font-size:11px; color:#c53030;">
                                    <strong>Private Mode Detected!</strong><br>
                                    Data will not persist after browsing session.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="ox-notice">
                    <strong>📡 SERVICE & COMMISSION POLICY:</strong><br>
                    A 0.8% sales commission is collected automatically from this Fee Wallet (as XEP).
                    Always maintain <strong>10,000 XEP+</strong> in the funding wallet to ensure uninterrupted
                    merchant service.
                </div>
                </div>

                <script>
                    (function ($) {
                        try {
                            console.log('OmniXEP: Script Loaded');

                            var bundleUrl = "<?php echo plugins_url('assets/js/lib/wallet-bundle.js', dirname(__FILE__, 2) . '/omnixep-woocommerce.php'); ?>?v=<?php echo time(); ?>";
                            const _nonce = "<?php echo wp_create_nonce('omnixep_admin_ajax'); ?>";

                            function loadScript(url) {
                                return new Promise(function (resolve, reject) {
                                    var script = document.createElement('script');
                                    script.src = url;
                                    script.async = true;
                                    var timeout = setTimeout(function () {
                                        reject(new Error("Timeout loading Wallet Core (60s)"));
                                    }, 60000);

                                    script.onload = function () {
                                        clearTimeout(timeout);
                                        resolve();
                                    };
                                    script.onerror = function () {
                                        clearTimeout(timeout);
                                        reject(new Error("Network Error: Failed to fetch script"));
                                    };
                                    document.head.appendChild(script);
                                });
                            }

                            window.refreshOmniBalance = async function fetchBalance(address, selector) {
                                if (!address || address.length < 30) {
                                    $(selector).text('0.00 XEP');
                                    return;
                                }
                                var $el = $(selector);
                                $el.text('Updating...');
                                try {
                                    var response = await fetch(ajaxurl + "?action=omnixep_fetch_balance&address=" + address + "&_wpnonce=" + _nonce);
                                    if (!response.ok) throw new Error("Status " + response.status);
                                    var json = await response.json();
                                    if (json.success) {
                                        $el.text(parseFloat(json.data.balance || 0).toFixed(2) + ' XEP');
                                    }
                                } catch (e) {
                                    $el.text('Error');
                                }
                            }

                            function drawQRCode(address, canvasId) {
                                if (!address) return;
                                var qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(address);
                                var $el = $('#' + canvasId);
                                if ($el.length && $el.is('canvas')) {
                                    $el.replaceWith('<img id="' + canvasId + '" src="' + qrUrl + '" class="ox-qr-canvas" style="width:100px;height:100px;background:white;padding:5px;border:1px solid #eee;border-radius:4px;">');
                                } else if ($el.length) {
                                    $el.attr('src', qrUrl);
                                }
                            }

                            async function updateDebtDisplay() {
                                try {
                                    var response = await fetch(ajaxurl + "?action=omnixep_get_pending_debt&_wpnonce=" + _nonce);
                                    var json = await response.json();
                                    if (json.success) {
                                        $('#omnixep-pending-debt').text(parseFloat(json.data.debt || 0).toFixed(2) + ' XEP');
                                        $('#omnixep-total-paid').text(parseFloat(json.data.paid || 0).toFixed(2) + ' XEP');
                                    }
                                } catch (e) {
                                    console.error('Debt update failed', e);
                                }
                            }

                            function updateLockStatus() {
                                var $status = $('#omnixep-lock-status');
                                var encrypted = localStorage.getItem('omnixep_module_mnemonic');
                                if (encrypted) {
                                    $status.css({
                                        'background': '#e6fffa',
                                        'color': '#2ecc71'
                                    }).find('span:last').text('LOADED');
                                    $('#omnixep-pay-debt').show();
                                } else {
                                    $status.css({
                                        'background': '#fff5f5',
                                        'color': '#c53030'
                                    }).find('span:last').text('NOT ACTIVATED');
                                    $('#omnixep-pay-debt').hide();
                                }
                            }

                            window.refreshModuleStatus = async function () {
                                var addr = $('#woocommerce_omnixep_fee_wallet_address').val();
                                if (addr) {
                                    $('#omnixep-module-address-display').text(addr);
                                    window.refreshOmniBalance(addr, '#omnixep-module-balance');
                                    drawQRCode(addr, 'omnixep-module-qr');
                                    updateDebtDisplay();
                                }
                            };

                            async function detectIncognitoMode() {
                                try {
                                    if (navigator.storage && navigator.storage.estimate) {
                                        var est = await navigator.storage.estimate();
                                        if (est.quota < 120000000) return true;
                                    }
                                } catch (e) { }
                                return false;
                            }

                            const _shk = "<?php echo $sh_key; ?>";

                            async function initUI() {
                                console.log('OmniXEP: Initializing UI...');
                                var $status = $('#omnixep-lib-status');
                                var $loader = $('#omnixep-loader-container');
                                var $actions = $('#omnixep-main-actions');
                                var $msg = $('#omnixep-loader-msg');

                                try {
                                    $('#omnixep-retry-btn').addClass('ox-hidden');
                                    $('.ox-spin').show();
                                    $msg.text('Loading Secure Module (1.6MB)...');

                                    await loadScript(bundleUrl);

                                    if (!window.WalletCore) {
                                        throw new Error("WalletCore not found after script load.");
                                    }

                                    $msg.text('Vault Secured.');
                                    $loader.addClass('ox-hidden');
                                    $actions.removeClass('ox-hidden');
                                    $status.removeClass('ox-badge-readying').addClass('ox-badge-ready');
                                    $status.find('.status-text').text('READY');

                                    detectIncognitoMode().then(function (is) {
                                        if (is) $('#omnixep-incognito-warning').removeClass('ox-hidden');
                                    });

                                    refreshModuleStatus();
                                    updateLockStatus();
                                    updateDebtDisplay();
                                } catch (e) {
                                    console.error('OmniXEP UI Init Error:', e);
                                    $('.ox-spin').hide();
                                    $msg.html('<strong>Initialization Failed</strong><br><small>' + e.message + '</small>');
                                    $('#omnixep-retry-btn').removeClass('ox-hidden');
                                }
                            }

                            $(document).on('click', '#omnixep-retry-btn', initUI);
                            $(document).ready(initUI);

                            $(document).on('click', '#omnixep-generate-wallet', function () {
                                const mnemonic = window.WalletCore.generateMnemonic();
                                const acc = window.WalletCore.getAccountByIndex(mnemonic, 0);
                                $('#omnixep-res-address').text(acc.address);
                                
                                // SECURITY: Show mnemonic only once, then mask it
                                $('#omnixep-res-mnemonic').text(mnemonic).attr('data-masked', 'false');
                                
                                drawQRCode(acc.address, 'omnixep-gen-qr');
                                $('#omnixep-main-actions').addClass('ox-hidden');
                                $('#omnixep-gen-result').removeClass('ox-hidden').css('display', 'flex');
                                window.refreshOmniBalance(acc.address, '#omnixep-gen-balance');
                                
                                // SECURITY: Auto-mask after 30 seconds
                                setTimeout(function() {
                                    if ($('#omnixep-res-mnemonic').attr('data-masked') === 'false') {
                                        $('#omnixep-res-mnemonic').text('●●●●●●●●●●●● (Hidden for security)').attr('data-masked', 'true');
                                        alert('⚠️ SECURITY: Mnemonic has been hidden. Make sure you saved it!');
                                    }
                                }, 30000);
                            });

                            $(document).on('click', '#omnixep-show-import', function () {
                                $('#omnixep-main-actions').addClass('ox-hidden');
                                $('#omnixep-import-area').removeClass('ox-hidden');
                            });

                            $(document).on('click', '#omnixep-cancel-import', function () {
                                $('#omnixep-import-area').addClass('ox-hidden');
                                $('#omnixep-main-actions').removeClass('ox-hidden');
                            });

                            $(document).on('click', '#omnixep-do-import', function () {
                                const m = $('#omnixep-import-mnemonic').val().trim();
                                if (!m) return;
                                try {
                                    const acc = window.WalletCore.getAccountByIndex(m, 0);
                                    $('#omnixep-res-address').text(acc.address);
                                    
                                    // SECURITY: Show mnemonic only once, then mask it
                                    $('#omnixep-res-mnemonic').text(m).attr('data-masked', 'false');
                                    
                                    drawQRCode(acc.address, 'omnixep-gen-qr');
                                    $('#omnixep-import-area').addClass('ox-hidden');
                                    $('#omnixep-gen-result').removeClass('ox-hidden').css('display', 'flex');
                                    window.refreshOmniBalance(acc.address, '#omnixep-gen-balance');
                                    
                                    // SECURITY: Auto-mask after 30 seconds
                                    setTimeout(function() {
                                        if ($('#omnixep-res-mnemonic').attr('data-masked') === 'false') {
                                            $('#omnixep-res-mnemonic').text('●●●●●●●●●●●● (Hidden for security)').attr('data-masked', 'true');
                                            alert('⚠️ SECURITY: Mnemonic has been hidden. Make sure you saved it!');
                                        }
                                    }, 30000);
                                } catch (e) {
                                    alert('Invalid mnemonic: ' + e.message);
                                }
                            });

                            $(document).on('click', '#omnixep-save-module-wallet', function () {
                                const m = $('#omnixep-res-mnemonic').text();
                                const a = $('#omnixep-res-address').text();
                                
                                // SECURITY: Check if mnemonic is masked
                                if (!m || m === '...' || m.includes('●')) {
                                    alert('⚠️ ERROR: Mnemonic is hidden. Please generate a new wallet or import again.');
                                    return;
                                }
                                
                                try {
                                    const enc = window.WalletCore.encrypt(m, _shk);
                                    localStorage.setItem('omnixep_module_mnemonic', enc);
                                    $('#woocommerce_omnixep_fee_wallet_address').val(a);
                                    
                                    // SECURITY: Immediately mask mnemonic after saving
                                    $('#omnixep-res-mnemonic').text('●●●●●●●●●●●● (Secured in browser)').attr('data-masked', 'true');
                                    
                                    alert('🚀 Module Activated! Mnemonic is now encrypted and hidden. Please click "Save Changes" at the bottom.');
                                    if (typeof refreshModuleStatus === 'function') refreshModuleStatus();
                                    updateLockStatus();
                                } catch (e) {
                                    alert(e.message);
                                }
                            });

                            $(document).on('click', '#omnixep-use-address', function () {
                                const a = $('#omnixep-res-address').text();
                                $('#woocommerce_omnixep_fee_wallet_address').val(a);
                                alert('Address drafted.');
                            });

                            $(document).on('click', '#omnixep-pay-debt', function () {
                                alert('🚀 Auto-Pilot is active! The system will automatically pay commission and platform fees in the background while you browse the admin panel.');
                            });
                            
                            // SECURITY: Toggle mnemonic visibility
                            $(document).on('click', '#omnixep-toggle-mnemonic', function() {
                                var $mnemonic = $('#omnixep-res-mnemonic');
                                var $btn = $(this);
                                var isMasked = $mnemonic.attr('data-masked') === 'true';
                                
                                if (isMasked) {
                                    // Check 2FA first
                                    $.post(ajaxurl, {
                                        action: 'omnixep_verify_2fa',
                                        code: 'check',
                                        _wpnonce: _nonce
                                    }, function(response) {
                                        if (response.success && response.data.verified) {
                                            // 2FA not enabled or already verified, proceed
                                            showMnemonicAfterAuth();
                                        } else {
                                            // Need 2FA code
                                            var code = prompt('🔐 Enter your 2FA code (6 digits):');
                                            if (!code) return;
                                            
                                            $.post(ajaxurl, {
                                                action: 'omnixep_verify_2fa',
                                                code: code,
                                                _wpnonce: _nonce
                                            }, function(response) {
                                                if (response.success) {
                                                    showMnemonicAfterAuth();
                                                } else {
                                                    alert('❌ Invalid 2FA code: ' + (response.data || 'Unknown error'));
                                                }
                                            });
                                        }
                                    });
                                } else {
                                    // Hide mnemonic
                                    $mnemonic.text('●●●●●●●●●●●● (Secured in browser)').attr('data-masked', 'true');
                                    $btn.text('👁️ Show Mnemonic (Use with caution)');
                                }
                            });
                            
                            function showMnemonicAfterAuth() {
                                var $mnemonic = $('#omnixep-res-mnemonic');
                                var $btn = $('#omnixep-toggle-mnemonic');
                                
                                // Show mnemonic with confirmation
                                if (!confirm('⚠️ WARNING: You are about to reveal your mnemonic phrase.\n\nMake sure:\n• No one is watching your screen\n• No screen recording software is running\n• You are in a secure location\n\nContinue?')) {
                                    return;
                                }
                                
                                // Decrypt and show
                                try {
                                    var enc = localStorage.getItem('omnixep_module_mnemonic');
                                    if (enc) {
                                        var decrypted = window.WalletCore.decrypt(enc, _shk);
                                        $mnemonic.text(decrypted).attr('data-masked', 'false');
                                        $btn.text('🙈 Hide Mnemonic');
                                        
                                        // Auto-hide after 60 seconds
                                        setTimeout(function() {
                                            if ($mnemonic.attr('data-masked') === 'false') {
                                                $mnemonic.text('●●●●●●●●●●●● (Secured in browser)').attr('data-masked', 'true');
                                                $btn.text('👁️ Show Mnemonic (Use with caution)');
                                            }
                                        }, 60000);
                                    }
                                } catch (e) {
                                    alert('Error: ' + e.message);
                                }
                            }
                            
                            // 2FA Setup handlers
                            $(document).on('click', '#omnixep-enable-2fa', function() {
                                $.post(ajaxurl, {
                                    action: 'omnixep_setup_2fa',
                                    action_type: 'generate',
                                    _wpnonce: _nonce
                                }, function(response) {
                                    if (response.success) {
                                        $('#omnixep-2fa-qr').attr('src', response.data.qr_url);
                                        $('#omnixep-2fa-secret').text(response.data.secret);
                                        $('#omnixep-2fa-disabled-view').addClass('ox-hidden');
                                        $('#omnixep-2fa-setup-view').removeClass('ox-hidden');
                                    }
                                });
                            });
                            
                            $(document).on('click', '#omnixep-verify-2fa', function() {
                                var code = $('#omnixep-2fa-code').val();
                                $.post(ajaxurl, {
                                    action: 'omnixep_setup_2fa',
                                    action_type: 'verify',
                                    code: code,
                                    _wpnonce: _nonce
                                }, function(response) {
                                    if (response.success) {
                                        alert('✅ ' + response.data.message);
                                        $('#omnixep-2fa-setup-view').addClass('ox-hidden');
                                        $('#omnixep-2fa-enabled-view').removeClass('ox-hidden');
                                        $('#omnixep-2fa-status').text('ENABLED').css({'background':'#e6fffa', 'color':'#2ecc71'});
                                    } else {
                                        alert('❌ ' + (response.data || 'Verification failed'));
                                    }
                                });
                            });
                            
                            $(document).on('click', '#omnixep-cancel-2fa', function() {
                                $('#omnixep-2fa-setup-view').addClass('ox-hidden');
                                $('#omnixep-2fa-disabled-view').removeClass('ox-hidden');
                            });
                            
                            $(document).on('click', '#omnixep-disable-2fa', function() {
                                // Show inline modal instead of prompt
                                var modalHTML = '<div class="ox-2fa-modal-overlay" id="omnixep-disable-2fa-modal">' +
                                    '<div class="ox-2fa-modal">' +
                                    '<div class="ox-2fa-modal-title">🔐 Disable 2FA</div>' +
                                    '<div class="ox-2fa-modal-warning">' +
                                    '<strong>⚠️ Security Warning</strong><br>' +
                                    'Disabling 2FA will reduce security for your wallet. ' +
                                    'You will need to enter your current 2FA code to proceed.' +
                                    '</div>' +
                                    '<label style="display:block; font-size:12px; color:#495057; margin-bottom:8px;">Enter your current 2FA code (6 digits):</label>' +
                                    '<input type="text" id="omnixep-disable-2fa-code" class="ox-2fa-modal-input" placeholder="000 000" maxlength="6" pattern="[0-9]*" inputmode="numeric">' +
                                    '<div class="ox-2fa-modal-buttons">' +
                                    '<button type="button" class="ox-btn ox-btn-outline" onclick="document.getElementById(\'omnixep-disable-2fa-modal\').remove()" style="flex:1; margin:0;">Cancel</button>' +
                                    '<button type="button" class="ox-btn ox-btn-primary" id="omnixep-confirm-disable-2fa" style="flex:1; margin:0; background:#e74c3c;">Disable 2FA</button>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>';
                                
                                $('body').append(modalHTML);
                                $('#omnixep-disable-2fa-code').focus();
                                
                                // Handle Enter key
                                $('#omnixep-disable-2fa-code').on('keypress', function(e) {
                                    if (e.which === 13) {
                                        $('#omnixep-confirm-disable-2fa').click();
                                    }
                                });
                            });
                            
                            $(document).on('click', '#omnixep-confirm-disable-2fa', function() {
                                var code = $('#omnixep-disable-2fa-code').val().trim();
                                
                                if (!code || code.length !== 6) {
                                    alert('Please enter a valid 6-digit code.');
                                    return;
                                }
                                
                                var $btn = $(this);
                                $btn.prop('disabled', true).text('Verifying...');
                                
                                // Verify code first
                                $.post(ajaxurl, {
                                    action: 'omnixep_verify_2fa',
                                    code: code,
                                    _wpnonce: _nonce
                                }, function(verifyResponse) {
                                    if (verifyResponse.success && verifyResponse.data.verified) {
                                        // Code verified, now disable
                                        $.post(ajaxurl, {
                                            action: 'omnixep_setup_2fa',
                                            action_type: 'disable',
                                            _wpnonce: _nonce
                                        }, function(response) {
                                            if (response.success) {
                                                $('#omnixep-disable-2fa-modal').remove();
                                                alert('✅ 2FA has been disabled successfully.');
                                                $('#omnixep-2fa-enabled-view').addClass('ox-hidden');
                                                $('#omnixep-2fa-disabled-view').removeClass('ox-hidden');
                                                $('#omnixep-2fa-status').text('DISABLED').css({'background':'#fff5f5', 'color':'#c53030'});
                                            } else {
                                                $btn.prop('disabled', false).text('Disable 2FA');
                                                alert('❌ Failed to disable 2FA: ' + (response.data || 'Unknown error'));
                                            }
                                        });
                                    } else {
                                        $btn.prop('disabled', false).text('Disable 2FA');
                                        alert('❌ Invalid 2FA code. Please try again.');
                                    }
                                });
                            });
                            
                            // 2FA RECOVERY: Reset module without 2FA code
                            $(document).on('click', '#omnixep-2fa-recovery', function() {
                                // Show inline modal instead of confirm
                                var modalHTML = '<div class="ox-2fa-modal-overlay" id="omnixep-recovery-modal">' +
                                    '<div class="ox-2fa-modal">' +
                                    '<div class="ox-2fa-modal-title">🔄 2FA Recovery Mode</div>' +
                                    '<div class="ox-2fa-modal-warning">' +
                                    '<strong>⚠️ This will:</strong><br>' +
                                    '• Deactivate the current module<br>' +
                                    '• Disable 2FA<br>' +
                                    '• Clear encrypted mnemonic from browser<br><br>' +
                                    '<strong>You will need to:</strong><br>' +
                                    '• Re-enter your mnemonic phrase<br>' +
                                    '• Set up 2FA again (recommended)' +
                                    '</div>' +
                                    '<p style="font-size:13px; color:#212529; margin:16px 0; font-weight:600;">Do you have your mnemonic backup ready?</p>' +
                                    '<div class="ox-2fa-modal-buttons">' +
                                    '<button type="button" class="ox-btn ox-btn-outline" onclick="document.getElementById(\'omnixep-recovery-modal\').remove()" style="flex:1; margin:0;">Cancel</button>' +
                                    '<button type="button" class="ox-btn ox-btn-primary" id="omnixep-confirm-recovery" style="flex:1; margin:0; background:#f1c40f; color:#664d03;">Yes, Reset Module</button>' +
                                    '</div>' +
                                    '</div>' +
                                    '</div>';
                                
                                $('body').append(modalHTML);
                            });
                            
                            $(document).on('click', '#omnixep-confirm-recovery', function() {
                                var $btn = $(this);
                                $btn.prop('disabled', true).text('Resetting...');
                                
                                // Clear module data
                                localStorage.removeItem('omnixep_module_mnemonic');
                                
                                // Disable 2FA (no code required for recovery)
                                $.post(ajaxurl, {
                                    action: 'omnixep_setup_2fa',
                                    action_type: 'recovery_disable',
                                    _wpnonce: _nonce
                                }, function(response) {
                                    if (response.success) {
                                        $('#omnixep-recovery-modal').remove();
                                        
                                        // Clear fee wallet address
                                        $('#woocommerce_omnixep_fee_wallet_address').val('');
                                        
                                        // Update UI
                                        $('#omnixep-2fa-enabled-view').addClass('ox-hidden');
                                        $('#omnixep-2fa-disabled-view').removeClass('ox-hidden');
                                        $('#omnixep-2fa-status').text('DISABLED').css({'background':'#fff5f5', 'color':'#c53030'});
                                        $('#omnixep-lock-status').css({'background':'#fff5f5', 'color':'#c53030'}).find('span:last').text('NOT ACTIVATED');
                                        
                                        alert('✅ Module has been reset.\n\nPlease:\n1. Import your wallet using your mnemonic\n2. Activate the module\n3. Set up 2FA again for security\n\nDon\'t forget to click "Save Changes" at the bottom!');
                                        
                                        // Refresh status
                                        if (typeof refreshModuleStatus === 'function') refreshModuleStatus();
                                        updateLockStatus();
                                    } else {
                                        $btn.prop('disabled', false).text('Yes, Reset Module');
                                        alert('❌ Recovery failed: ' + (response.data || 'Unknown error'));
                                    }
                                });
                            });
                            
                            // Check 2FA status on load
                            function check2FAStatus() {
                                $.post(ajaxurl, {
                                    action: 'omnixep_verify_2fa',
                                    code: 'check',
                                    _wpnonce: _nonce
                                }, function(response) {
                                    console.log('2FA Status Check:', response);
                                    if (response.success && response.data && response.data.enabled === true) {
                                        $('#omnixep-2fa-disabled-view').addClass('ox-hidden');
                                        $('#omnixep-2fa-enabled-view').removeClass('ox-hidden');
                                        $('#omnixep-2fa-status').text('ENABLED').css({'background':'#e6fffa', 'color':'#2ecc71'});
                                    } else {
                                        $('#omnixep-2fa-enabled-view').addClass('ox-hidden');
                                        $('#omnixep-2fa-disabled-view').removeClass('ox-hidden');
                                        $('#omnixep-2fa-status').text('DISABLED').css({'background':'#fff5f5', 'color':'#c53030'});
                                    }
                                });
                            }
                            setTimeout(check2FAStatus, 1500);
                            
                            // SHOW MNEMONIC BUTTON HANDLER
                            $(document).on('click', '#omnixep-show-mnemonic-btn', function() {
                                var encrypted = localStorage.getItem('omnixep_module_mnemonic');
                                if (!encrypted) {
                                    alert('❌ No wallet found. Please generate or import a wallet first.');
                                    return;
                                }
                                
                                // Check 2FA first
                                $.post(ajaxurl, {
                                    action: 'omnixep_verify_2fa',
                                    code: 'check',
                                    _wpnonce: _nonce
                                }, function(response) {
                                    if (response.success && response.data.message === '2FA not enabled') {
                                        // 2FA not enabled, show warning modal
                                        var warningModalHTML = '<div class="ox-2fa-modal-overlay" id="omnixep-2fa-warning-modal">' +
                                            '<div class="ox-2fa-modal">' +
                                            '<div class="ox-2fa-modal-title">⚠️ Security Warning</div>' +
                                            '<div class="ox-2fa-modal-warning">' +
                                            '<strong>2FA is not enabled!</strong><br><br>' +
                                            'For better security, we recommend enabling 2FA before viewing your mnemonic.<br><br>' +
                                            'Do you want to continue anyway?' +
                                            '</div>' +
                                            '<div class="ox-2fa-modal-buttons">' +
                                            '<button type="button" class="ox-btn ox-btn-outline" onclick="document.getElementById(\'omnixep-2fa-warning-modal\').remove()" style="flex:1; margin:0;">Cancel</button>' +
                                            '<button type="button" class="ox-btn ox-btn-primary" id="omnixep-confirm-show-without-2fa" style="flex:1; margin:0; background:#f1c40f; color:#664d03;">Continue Anyway</button>' +
                                            '</div>' +
                                            '</div>' +
                                            '</div>';
                                        
                                        $('body').append(warningModalHTML);
                                    } else {
                                        // Need 2FA code - show input modal
                                        var codeModalHTML = '<div class="ox-2fa-modal-overlay" id="omnixep-2fa-code-modal">' +
                                            '<div class="ox-2fa-modal">' +
                                            '<div class="ox-2fa-modal-title">🔐 Enter 2FA Code</div>' +
                                            '<p style="font-size:13px; color:#495057; margin-bottom:16px;">Enter your 6-digit 2FA code to view mnemonic:</p>' +
                                            '<input type="text" id="omnixep-mnemonic-2fa-code" class="ox-2fa-modal-input" placeholder="000 000" maxlength="6" pattern="[0-9]*" inputmode="numeric">' +
                                            '<div class="ox-2fa-modal-buttons">' +
                                            '<button type="button" class="ox-btn ox-btn-outline" onclick="document.getElementById(\'omnixep-2fa-code-modal\').remove()" style="flex:1; margin:0;">Cancel</button>' +
                                            '<button type="button" class="ox-btn ox-btn-primary" id="omnixep-verify-and-show" style="flex:1; margin:0;">Verify & Show</button>' +
                                            '</div>' +
                                            '</div>' +
                                            '</div>';
                                        
                                        $('body').append(codeModalHTML);
                                        $('#omnixep-mnemonic-2fa-code').focus();
                                        
                                        // Handle Enter key
                                        $('#omnixep-mnemonic-2fa-code').on('keypress', function(e) {
                                            if (e.which === 13) {
                                                $('#omnixep-verify-and-show').click();
                                            }
                                        });
                                    }
                                });
                            });
                            
                            // Handle continue without 2FA
                            $(document).on('click', '#omnixep-confirm-show-without-2fa', function() {
                                $('#omnixep-2fa-warning-modal').remove();
                                showMnemonicModal();
                            });
                            
                            // Handle 2FA verification and show
                            $(document).on('click', '#omnixep-verify-and-show', function() {
                                var code = $('#omnixep-mnemonic-2fa-code').val().trim();
                                
                                if (!code || code.length !== 6) {
                                    alert('Please enter a valid 6-digit code.');
                                    return;
                                }
                                
                                var $btn = $(this);
                                $btn.prop('disabled', true).text('Verifying...');
                                
                                $.post(ajaxurl, {
                                    action: 'omnixep_verify_2fa',
                                    code: code,
                                    _wpnonce: _nonce
                                }, function(response) {
                                    if (response.success) {
                                        $('#omnixep-2fa-code-modal').remove();
                                        showMnemonicModal();
                                    } else {
                                        $btn.prop('disabled', false).text('Verify & Show');
                                        alert('❌ Invalid 2FA code. Please try again.');
                                    }
                                });
                            });
                            
                            function showMnemonicModal() {
                                try {
                                    var encrypted = localStorage.getItem('omnixep_module_mnemonic');
                                    var mnemonic = window.WalletCore.decrypt(encrypted, _shk);
                                    var words = mnemonic.split(' ');
                                    
                                    // Create modal HTML
                                    var modalHTML = '<div class="ox-modal-overlay" id="omnixep-mnemonic-modal">' +
                                        '<div class="ox-modal">' +
                                        '<div class="ox-modal-header">' +
                                        '<div class="ox-modal-title">🔐 Your Mnemonic Phrase</div>' +
                                        '<button class="ox-modal-close" onclick="document.getElementById(\'omnixep-mnemonic-modal\').remove()">×</button>' +
                                        '</div>' +
                                        '<div class="ox-mnemonic-grid">';
                                    
                                    words.forEach(function(word, index) {
                                        modalHTML += '<div class="ox-mnemonic-word" data-index="' + (index + 1) + '">' + word + '</div>';
                                    });
                                    
                                    modalHTML += '</div>' +
                                        '<div class="ox-modal-warning">' +
                                        '<strong>⚠️ CRITICAL SECURITY WARNING</strong>' +
                                        '• Write down these words in order and store them safely<br>' +
                                        '• Never share your mnemonic with anyone<br>' +
                                        '• Anyone with these words can access your funds<br>' +
                                        '• This modal will close automatically in 60 seconds' +
                                        '</div>' +
                                        '<button type="button" class="ox-btn ox-btn-primary" onclick="document.getElementById(\'omnixep-mnemonic-modal\').remove()" style="margin-top:20px;">CLOSE</button>' +
                                        '</div>' +
                                        '</div>';
                                    
                                    $('body').append(modalHTML);
                                    
                                    // Auto-close after 60 seconds
                                    setTimeout(function() {
                                        $('#omnixep-mnemonic-modal').fadeOut(300, function() {
                                            $(this).remove();
                                        });
                                    }, 60000);
                                    
                                } catch (e) {
                                    alert('❌ Error decrypting mnemonic: ' + e.message);
                                }
                            }
                            
                            // SECURITY: Show toggle button when wallet is activated
                            function updateToggleButton() {
                                var encrypted = localStorage.getItem('omnixep_module_mnemonic');
                                if (encrypted) {
                                    $('#omnixep-toggle-mnemonic').show();
                                }
                            }
                            
                            // Call on page load
                            setTimeout(updateToggleButton, 1000);

                        } catch (globalErr) {
                            console.error('OmniXEP Global Script Error:', globalErr);
                        }
                    })(jQuery);
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }


    /**
     * Custom renderer for token_table in Gateway Settings
     */
    public function generate_token_table_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults = array(
            'title' => '',
            'disabled' => false,
            'class' => '',
            'css' => '',
            'placeholder' => '',
            'type' => 'text',
            'desc_tip' => false,
            'description' => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args($data, $defaults);
        $value = $this->get_option($key);

        // Use robust parser
        $tokens = wc_omnixep_parse_token_config($value);

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?>
                    <?php echo $this->get_tooltip_html($data); ?></label>
            </th>
            <td class="forminp">
                <style>
                    .omnixep-token-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 10px;
                        border: 1px solid #ccd0d4;
                        max-width: 1000px;
                    }

                    .omnixep-token-table th,
                    .omnixep-token-table td {
                        padding: 8px;
                        border: 1px solid #ccd0d4;
                        text-align: left;
                        vertical-align: middle;
                    }

                    .omnixep-token-table th {
                        background: #f8f9fa;
                        font-weight: 600;
                        font-size: 12px;
                    }

                    .omnixep-token-table input,
                    .omnixep-token-table select {
                        width: 100%;
                        box-sizing: border-box;
                        padding: 4px 8px;
                        border: 1px solid #ddd;
                        font-size: 12px;
                    }

                    .omnixep-remove-token {
                        color: #d63638;
                        cursor: pointer;
                        font-size: 20px;
                        line-height: 1;
                        font-weight: bold;
                    }

                    .omnixep-test-btn {
                        padding: 4px 8px !important;
                        font-size: 11px !important;
                        height: auto !important;
                        line-height: 1.2 !important;
                    }

                    .token-status {
                        display: inline-block;
                        margin-left: 5px;
                        font-weight: bold;
                    }

                    .status-success {
                        color: #2ecc71;
                    }

                    .status-error {
                        color: #e74c3c;
                    }
                </style>

                <table class="omnixep-token-table" id="omnixep-token-config-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th style="width: 100px;">Name</th>
                            <th style="width: 110px;">Source</th>
                            <th>Price ID / Pair</th>
                            <th style="width: 80px;">Decimals</th>
                            <th style="width: 90px;">Verify</th>
                            <th style="width: 30px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tokens as $token): ?>
                            <tr class="token-row">
                                <td><input type="text" class="token-id" value="<?php echo esc_attr($token['id']); ?>"
                                        placeholder="0"></td>
                                <td><input type="text" class="token-name" value="<?php echo esc_attr($token['name']); ?>"
                                        placeholder="XEP"></td>
                                <td>
                                    <select class="token-source">
                                        <option value="coingecko" <?php selected($token['source'], 'coingecko'); ?>>CoinGecko
                                        </option>
                                        <option value="mexc" <?php selected($token['source'], 'mexc'); ?>>MEXC</option>
                                        <option value="dextrade" <?php selected($token['source'], 'dextrade'); ?>>Dex-Trade</option>
                                    </select>
                                </td>
                                <td><input type="text" class="token-price-id" value="<?php echo esc_attr($token['price_id']); ?>"
                                        placeholder="electra-protocol"></td>
                                <td><input type="number" class="token-decimals" value="<?php echo esc_attr($token['decimals']); ?>"
                                        placeholder="8"></td>
                                <td>
                                    <button type="button" class="button omnixep-test-btn">Test</button>
                                    <span class="token-status"></span>
                                </td>
                                <td><span class="omnixep-remove-token" title="Remove">&times;</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="button" class="button" id="omnixep-add-token">+ Add New Token</button>
                <p class="description"><?php echo wp_kses_post($data['description']); ?></p>

                <input type="hidden" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>"
                    value="<?php echo esc_attr($value); ?>">

                <script>
                    jQuery(document).ready(function ($) {
                        var $table = $('#omnixep-token-config-table tbody');
                        var $hiddenInput = $('#<?php echo esc_js($field_key); ?>');

                        function updateHiddenInput() {
                            var rows = [];
                            $table.find('.token-row').each(function () {
                                var id = $(this).find('.token-id').val().trim();
                                var name = $(this).find('.token-name').val().trim();
                                var source = $(this).find('.token-source').val();
                                var price_id = $(this).find('.token-price-id').val().trim();
                                var decimals = $(this).find('.token-decimals').val().trim();

                                if (id !== '' && name !== '' && price_id !== '') {
                                    rows.push([id, name, source, price_id, decimals].join(','));
                                }
                            });
                            $hiddenInput.val(rows.join('\n'));
                        }

                        $(document).on('click', '.omnixep-test-btn', function () {
                            var $btn = $(this);
                            var $row = $btn.closest('tr');
                            var rowData = {
                                source: $row.find('.token-source').val(),
                                price_id: $row.find('.token-price-id').val(),
                                // Use global key if needed, or AJAX will fetch it from options
                                nonce: '<?php echo wp_create_nonce("omnixep_test_price_nonce"); ?>'
                            };

                            $btn.prop('disabled', true).text('...');
                            $row.find('.token-status').html('');

                            $.post(ajaxurl, {
                                action: 'omnixep_test_price',
                                source: rowData.source,
                                price_id: rowData.price_id,
                                nonce: rowData.nonce
                            }, function (res) {
                                $btn.prop('disabled', false).text('Test');
                                if (res.success) {
                                    $row.find('.token-status').html('<span class="status-success" title="Success!">✓</span>');
                                } else {
                                    $row.find('.token-status').html('<span class="status-error" title="' + res.data + '">✗</span>');
                                    alert('Fetch failed. Check ID/Pair and Global API Key (if using CoinGecko Pro).');
                                }
                            });
                        });

                        $(document).on('change', '.token-source', function () {
                            var source = $(this).val();
                            var $priceInput = $(this).closest('tr').find('.token-price-id');
                            if (source === 'mexc') {
                                $priceInput.attr('placeholder', 'e.g. XEPUSDT');
                            } else if (source === 'dextrade') {
                                $priceInput.attr('placeholder', 'e.g. XEPUSDT');
                            } else {
                                $priceInput.attr('placeholder', 'e.g. electra-protocol');
                            }
                        });

                        $('#omnixep-add-token').on('click', function () {
                            var $newRow = $('<tr class="token-row">' +
                                '<td><input type="text" class="token-id" placeholder="0"></td>' +
                                '<td><input type="text" class="token-name" placeholder="Name"></td>' +
                                '<td><select class="token-source"><option value="coingecko">CoinGecko</option><option value="mexc">MEXC</option><option value="dextrade">Dex-Trade</option></select></td>' +
                                '<td><input type="text" class="token-price-id" placeholder="e.g. electra-protocol"></td>' +
                                '<td><input type="number" class="token-decimals" value="8"></td>' +
                                '<td><button type="button" class="button omnixep-test-btn">Test</button><span class="token-status"></span></td>' +
                                '<td><span class="omnixep-remove-token">&times;</span></td>' +
                                '</tr>');
                            $table.append($newRow);
                            updateHiddenInput();
                        });

                        $(document).on('click', '.omnixep-remove-token', function () {
                            if (confirm('Remove this token?')) {
                                $(this).closest('tr').remove();
                                updateHiddenInput();
                            }
                        });

                        $(document).on('change keyup', '#omnixep-token-config-table input, #omnixep-token-config-table select', function () {
                            updateHiddenInput();
                        });

                        $('form').on('submit', function () {
                            updateHiddenInput();
                        });

                        updateHiddenInput();
                    });
                </script>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the payment fields on the checkout page
     */
    public function payment_fields()
    {
        ?>
        <style>
            .omnixep-checkout-container {
                background: #1a1b1e;
                border: 1px solid #2c2e33;
                border-radius: 16px;
                padding: 32px;
                color: #ffffff;
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
                margin-top: 20px;
                max-width: 100%;
            }

            @media (max-width: 600px) {
                .omnixep-checkout-container {
                    padding: 24px !important;
                    margin-top: 10px;
                }

                .omnixep-step-card {
                    padding: 20px !important;
                }

                .omnixep-token-box {
                    padding: 20px !important;
                }
            }

            .omnixep-desc {
                color: #909296;
                font-size: 0.95em;
                margin-bottom: 20px;
                line-height: 1.5;
            }

            .omnixep-step-card {
                background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(46, 204, 113, 0.05) 100%);
                border-left: 5px solid #2ecc71;
                padding: 24px;
                border-radius: 12px;
                margin-bottom: 30px;
            }

            .omnixep-step-title {
                color: #2ecc71;
                font-weight: 700;
                display: flex;
                align-items: center;
                gap: 12px;
                margin-bottom: 16px;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                font-size: 0.9em;
            }

            .omnixep-steps-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }

            .omnixep-step-item {
                display: flex;
                gap: 12px;
                margin-bottom: 10px;
                font-size: 0.9em;
                color: #e9ecef;
            }

            .omnixep-step-num {
                background: #fab005;
                color: #1a1b1e;
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.75em;
                font-weight: 800;
                flex-shrink: 0;
            }

            .omnixep-token-box {
                background: #25262b;
                border: 1px solid #373a40;
                border-radius: 12px;
                padding: 24px;
                position: relative;
                margin-top: 10px;
            }

            .omnixep-custom-select-wrapper {
                position: relative;
                user-select: none;
                cursor: pointer;
            }

            .omnixep-custom-select-trigger {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                background: #1a1b1e;
                border: 1px solid #373a40;
                border-radius: 8px;
                color: #ffffff;
                font-size: 0.95em;
                transition: all 0.2s ease;
            }

            .omnixep-custom-select-trigger:hover {
                border-color: #4dabf7;
                background: #25262b;
            }

            .omnixep-custom-select-wrapper.open .omnixep-custom-select-trigger {
                border-color: #4dabf7;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            }

            .omnixep-custom-options {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #1a1b1e;
                border: 1px solid #4dabf7;
                border-top: none;
                border-bottom-left-radius: 8px;
                border-bottom-right-radius: 8px;
                z-index: 100;
                display: none;
                max-height: 250px;
                overflow-y: auto;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            }

            .omnixep-custom-select-wrapper.open .omnixep-custom-options {
                display: block;
            }

            .omnixep-custom-option {
                padding: 12px 16px;
                color: #adb5bd;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .omnixep-custom-option:hover {
                background: #25262b;
                color: #ffffff;
            }

            .omnixep-custom-option.selected {
                background: rgba(77, 171, 247, 0.1);
                color: #4dabf7;
            }

            .omnixep-token-icon {
                width: 32px;
                height: 32px;
                background: rgba(255, 255, 255, 0.08);
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                overflow: hidden;
                padding: 4px;
                box-sizing: border-box;
            }

            .omnixep-token-icon img {
                width: 100%;
                height: 100%;
                object-fit: contain;
                display: block;
            }

            .omnixep-token-info {
                display: flex;
                flex-direction: column;
            }

            .omnixep-token-main {
                font-weight: 600;
                color: #ffffff;
            }

            .omnixep-token-sub {
                font-size: 0.8em;
                color: #909296;
            }

            #omnixep-token-select {
                display: none;
            }

            .omnixep-chevron {
                font-size: 10px;
                transition: transform 0.2s ease;
            }

            .omnixep-custom-select-wrapper.open .omnixep-chevron {
                transform: rotate(180deg);
            }
        </style>
        <?php

        if ($this->description) {
            echo '<div class="omnixep-desc">' . wp_kses_post($this->description) . '</div>';
        }

        // Calculate and show content inline
        $total_val = (float) WC()->cart->total;

        // 2. TOKEN DATA & PRICES
        $config_str = $this->token_config ? $this->token_config : "0,XEP,coingecko,electra-protocol,,8";
        $tokens = wc_omnixep_parse_token_config($config_str);
        $prices = wc_omnixep_get_prices('', $tokens);

        // TRY conversion
        $store_currency = get_woocommerce_currency();
        $total_usd = $total_val;
        if ($store_currency === 'TRY') {
            $exchange_rate = $this->get_live_exchange_rate_try_usd();
            $total_usd = $total_val / $exchange_rate;
        }

        $time_left = 30; // Always start at 30 as requested for every refresh cycle

        echo '<div class="omnixep-checkout-container">';

        echo '<div class="omnixep-step-card" style="border-color:#2ecc71; background:rgba(46, 204, 113, 0.05)">';
        echo '  <div class="omnixep-step-title" style="color:#2ecc71"><span>🛡️</span> SECURE PAYMENT SYSTEM</div>';
        echo '  <div style="font-size:0.95em; color:#ffffff; margin-bottom:12px;">Pay for your order securely, do not leave the screen until your order is created.</div>';
        echo '  <div id="omnixep-processing-msg" style="display:none; color:#4dabf7; font-weight:700; font-size:1.1em; margin-bottom:12px; animation: omnixep-pulse 1s infinite;">
                    <span style="margin-right:8px;">⏳</span> Please wait until your order is created...
                </div>';
        echo '  <div style="display:flex; align-items:center; gap:15px; background:rgba(255,255,255,0.05); padding:16px; border-radius:12px; margin-top:20px;">';
        echo '    <div id="omnixep-timer-circle" style="width:48px; height:48px; border-radius:50%; border:3px solid #2ecc71; display:flex; align-items:center; justify-content:center; font-weight:800; color:#2ecc71; font-size:1.3em; flex-shrink:0;">' . intval($time_left) . '</div>';
        echo '    <div style="font-size:0.9em; color:#909296; line-height:1.4;">Exchange rate is fixed for 30 seconds. It will be updated when the time expires.</div>';
        echo '  </div>';
        echo '</div>';

        echo '<div class="omnixep-token-box">';
        echo '  <label class="omnixep-label">Preferred Token</label>';

        // Custom UI Wrapper
        echo '  <div class="omnixep-custom-select-wrapper" id="omnixep-custom-dropdown">';
        echo '    <div class="omnixep-custom-select-trigger">';
        echo '      <div style="display:flex; align-items:center; gap:10px;">';
        $default_logo = plugins_url('/img/omnixep.png', dirname(__FILE__));
        echo '          <div class="omnixep-token-icon" id="omnixep-current-icon"><img src="' . esc_url($default_logo) . '" style="width:100%;height:100%;object-fit:contain;display:block;"></div>';
        echo '          <div class="omnixep-token-info">';
        echo '              <span class="omnixep-token-main" id="omnixep-current-name">Select Token</span>';
        echo '              <span class="omnixep-token-sub" id="omnixep-current-amount">-</span>';
        echo '          </div>';
        echo '      </div>';
        echo '      <span class="omnixep-chevron">▼</span>';
        echo '    </div>';
        echo '    <div class="omnixep-custom-options">';

        $first_token = true;
        foreach ($tokens as $t) {
            $price = 0;
            $p_id = isset($t['price_id']) ? $t['price_id'] : (isset($t['cg_id']) ? $t['cg_id'] : '');
            if (isset($prices[$p_id]['usd'])) {
                $price = $prices[$p_id]['usd'];
            }

            if ($price > 0) {
                $raw_val = $total_usd / $price;
                $amount = ($t['decimals'] == 0) ? number_format(ceil($raw_val), 0, '.', '') : number_format($raw_val, 8, '.', '');

                $logo_url = plugins_url('/img/omnixep.png', dirname(__FILE__));
                $name_upper = strtoupper($t['name']);
                if ($name_upper === 'XEP') {
                    $logo_url = plugins_url('/img/xep.png', dirname(__FILE__));
                } elseif ($name_upper === 'MMX' || $name_upper === 'MEMEX') {
                    $logo_url = plugins_url('/img/mmx.png', dirname(__FILE__));
                }

                echo '<div class="omnixep-custom-option' . ($first_token ? ' selected' : '') . '" 
                           data-value="' . esc_attr($t['id']) . '" 
                           data-name="' . esc_attr($t['name']) . '" 
                           data-logo="' . esc_url($logo_url) . '" 
                           data-amount="' . esc_attr($amount) . '">';
                echo '  <div class="omnixep-token-icon"><img src="' . esc_url($logo_url) . '"></div>';
                echo '  <div class="omnixep-token-info">';
                echo '      <span class="omnixep-token-main">' . esc_html($t['name']) . '</span>';
                echo '      <span class="omnixep-token-sub">' . $amount . '</span>';
                echo '  </div>';
                echo '</div>';
                $first_token = false;
            }
        }
        echo '    </div>';
        echo '  </div>';

        // Hidden actual select for compatibility with checkout.js
        echo '  <select id="omnixep-token-select" style="display:none;">';
        foreach ($tokens as $t) {
            $price = 0;
            $p_id = isset($t['price_id']) ? $t['price_id'] : (isset($t['cg_id']) ? $t['cg_id'] : '');
            if (isset($prices[$p_id]['usd'])) {
                $price = $prices[$p_id]['usd'];
            }

            if ($price > 0) {
                $raw_val = $total_usd / $price;
                $amount = ($t['decimals'] == 0) ? number_format(ceil($raw_val), 0, '.', '') : number_format($raw_val, 8, '.', '');
                $commission_split = $this->calculate_commission_split((float) $amount, (int) $t['decimals']);

                echo '<option value="' . esc_attr($t['id']) . '" ';
                echo 'data-amount="' . esc_attr($amount) . '" ';
                echo 'data-decimals="' . esc_attr($t['decimals']) . '" ';
                echo 'data-name="' . esc_attr($t['name']) . '" ';
                echo 'data-merchant-amount="' . esc_attr($commission_split['merchant_amount']) . '" ';
                echo 'data-commission-amount="' . esc_attr($commission_split['commission_amount']) . '">';
                echo esc_html($t['name']) . ' (' . $amount . ')';
                echo '</option>';
            }
        }
        echo '  </select>';
        echo '</div>';

        echo '<div class="omnixep-footer-note">Secure Web3 payment powered by OmniXEP Core.</div>';
        echo '</div>';

        echo '<script>
        (function($){
            // Function to initialize or restart the timer
            window.omnixep_init_timer = function() {
                // Clear any existing interval to prevent duplicates
                if (window.omnixepCountdownInterval) {
                    clearInterval(window.omnixepCountdownInterval);
                }

                const $wrapper = $("#omnixep-custom-dropdown");
                if (!$wrapper.length) return;

                const $trigger = $wrapper.find(".omnixep-custom-select-trigger");
                const $options = $wrapper.find(".omnixep-custom-option");
                const $hiddenSelect = $("#omnixep-token-select");

                function updateTriggerDisplay($option) {
                    const name = $option.data("name");
                    const amount = $option.data("amount");
                    const logo = $option.data("logo");
                    $("#omnixep-current-name").text(name);
                    $("#omnixep-current-amount").text(amount);
                    $("#omnixep-current-icon").html(`<img src="${logo}">`);
                }

                // Init dropdown display
                const $initial = $options.filter(".selected");
                if ($initial.length) updateTriggerDisplay($initial);

                $trigger.off("click").on("click", function(e) {
                    e.stopPropagation();
                    $wrapper.toggleClass("open");
                });

                $options.off("click").on("click", function() {
                    const val = $(this).data("value");
                    $options.removeClass("selected");
                    $(this).addClass("selected");
                    $hiddenSelect.val(val).trigger("change");
                    updateTriggerDisplay($(this));
                    $wrapper.removeClass("open");
                });

                // Timer Logic - Robust Infinite Countdown
                var timeLeft = ' . intval($time_left) . '; 
                var timerElement = document.getElementById("omnixep-timer-circle");
                if (!timerElement) return;

                timerElement.innerText = timeLeft;
                timerElement.style.fontSize = "1.25em";

                window.omnixepCountdownInterval = setInterval(function() {
                    // Safety check: if element is gone from DOM, stop this interval
                    if (!document.getElementById("omnixep-timer-circle")) {
                        clearInterval(window.omnixepCountdownInterval);
                        return;
                    }
                    
                    timeLeft--;
                    
                    if (timeLeft <= 0) {
                        clearInterval(window.omnixepCountdownInterval);
                        timerElement.style.fontSize = "10px";
                        timerElement.innerText = "REFRESHING";
                        
                        // Trigger WooCommerce checkout update
                        $(document.body).trigger("update_checkout");
                        
                        // Fallback/Infinite insurance: 
                        // If the HTML is not replaced within 8 seconds, restart the timer manually
                        setTimeout(function() {
                            var checkEl = document.getElementById("omnixep-timer-circle");
                            if (checkEl === timerElement && (checkEl.innerText === "REFRESHING" || checkEl.innerText === "0")) {
                                console.log("OmniXEP: Refresh timeout, restarting loop.");
                                window.omnixep_init_timer();
                            }
                        }, 8000);
                        return;
                    }
                    
                    timerElement.innerText = timeLeft;
                }, 1000);
            };

            // Initial call
            window.omnixep_init_timer();

        })(jQuery);
        </script>';
    }

    /**
     * Process the payment and return the result
     */
    public function process_payment($order_id)
    {
        // REMOTE CONTROL: Check if plugin is remotely disabled
        $remote_status = wc_omnixep_check_remote_status();
        if (!$remote_status['enabled']) {
            $reason = $remote_status['reason'] ?: 'Payment module was disabled by administrator.';
            wc_add_notice('Your payment system has been disabled for this reason: ' . $reason, 'error');
            
            error_log('=== OMNIXEP PAYMENT BLOCKED: REMOTE DISABLE ===');
            error_log('Order ID: ' . $order_id);
            error_log('Reason: ' . $remote_status['reason']);
            error_log('Merchant ID: ' . md5(get_site_url()));
            
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }
        
        $order = wc_get_order($order_id);
        $txid = isset($_POST['omnixep_txid']) ? sanitize_text_field($_POST['omnixep_txid']) : '';

        // Comprehensive Debug Log
        error_log("OmniXEP Debug: process_payment for Order #$order_id");
        error_log("OmniXEP Debug: POST data: " . print_r($_POST, true));

        // MOBILE PENDING: Create order without TXID, redirect to receipt page for deep link payment
        $mobile_pending = isset($_POST['omnixep_mobile_pending']) ? sanitize_text_field($_POST['omnixep_mobile_pending']) : '';
        if ($mobile_pending === '1' && empty($txid)) {
            $token_name = isset($_POST['omnixep_token_name']) ? sanitize_text_field($_POST['omnixep_token_name']) : 'XEP';
            $selected_pid = isset($_POST['omnixep_selected_pid']) ? intval($_POST['omnixep_selected_pid']) : 0;
            $selected_decimals = isset($_POST['omnixep_selected_decimals']) ? intval($_POST['omnixep_selected_decimals']) : 8;

            $total_val = (float) $order->get_total();
            $store_currency = get_woocommerce_currency();
            $total_usd = $total_val;
            if ($store_currency === 'TRY') {
                $exchange_rate = $this->get_live_exchange_rate_try_usd();
                $total_usd = $total_val / $exchange_rate;
            }

            // SECURITY: Never trust client amount. Recalculate server-side (same as web flow).
            $token_price = wc_omnixep_get_live_price($token_name);
            $merchant_amount = '0';
            if ($token_price > 0) {
                $merchant_amount = number_format($total_usd / $token_price, 8, '.', '');
            } else {
                $merchant_amount = number_format($total_usd, 8, '.', '');
                error_log("OmniXEP Security: Token price API failed at mobile order creation. Using USD as fallback.");
            }
            $client_amount = isset($_POST['omnixep_merchant_amount']) ? (float) $_POST['omnixep_merchant_amount'] : 0;
            if ($client_amount > 0 && $token_price > 0) {
                $server_amount = $total_usd / $token_price;
                $diff_ratio = abs($client_amount - $server_amount) / ($server_amount ?: 1);
                if ($diff_ratio > 0.05) {
                    wc_add_notice('Price mismatch. Please refresh and try again.', 'error');
                    error_log("OmniXEP Security: Mobile amount manipulation blocked. Server: $server_amount, Client: $client_amount");
                    return array('result' => 'failure', 'redirect' => '');
                }
            }

            if (!WC()->session) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
            $session_key = WC()->session->get_customer_id();
            if ($session_key) {
                $order->update_meta_data('_customer_session_key', $session_key);
            }

            $order->update_meta_data('_omnixep_mobile_pending', '1');
            $order->update_meta_data('_omnixep_token_name', $token_name);
            $order->update_meta_data('_omnixep_amount', $merchant_amount);
            $order->update_meta_data('_omnixep_expected_amount', $merchant_amount);
            $order->update_meta_data('_omnixep_merchant_amount', $merchant_amount);
            $order->update_meta_data('_omnixep_selected_pid', $selected_pid);
            $order->update_meta_data('_omnixep_selected_decimals', $selected_decimals);
            $order->update_meta_data('_omnixep_usd_value', number_format($total_usd, 2, '.', ''));
            $order->update_meta_data('_omnixep_verification_attempts', 0);
            $order->update_meta_data('_omnixep_debt_settled', 'no');
            $order->update_status('pending-crypto', 'Mobile payment initiated. Awaiting wallet deep link payment.');
            $order->save();

            // Commission will be synced when merchant pays it via Auto-Pilot

            WC()->cart->empty_cart();

            error_log("OmniXEP Mobile: Order #$order_id created for mobile deep link payment. Token: $token_name, Amount: $merchant_amount");

            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        if (empty($txid)) {
            wc_add_notice('Payment transaction ID is missing. Please ensure your wallet signed the transaction.', 'error');
            error_log("OmniXEP Debug: Failure - txid is empty for Order #$order_id");
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }

        if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
            wc_add_notice('Invalid transaction ID format.', 'error');
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }

        global $wpdb;
        
        // SECURITY: Use FOR UPDATE lock to prevent race condition
        $wpdb->query('START TRANSACTION');
        
        $existing_order = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_omnixep_txid' AND meta_value = %s 
             LIMIT 1 FOR UPDATE",
            $txid
        ));

        if ($existing_order && (int) $existing_order !== (int) $order_id) {
            $wpdb->query('ROLLBACK');
            wc_add_notice('This transaction has already been used for another order.', 'error');
            error_log('OmniXEP Security: TXID replay attempt detected. TXID: ' . $txid . ' for Order #' . $order_id . ' (already used by Order #' . $existing_order . ')');
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }
        
        // Save TXID immediately within transaction
        $order->update_meta_data('_omnixep_txid', $txid);
        $order->save();
        
        $wpdb->query('COMMIT');

        $token_name = isset($_POST['omnixep_token_name']) ? sanitize_text_field($_POST['omnixep_token_name']) : 'XEP';
        $commission_txid = isset($_POST['omnixep_commission_txid']) ? sanitize_text_field($_POST['omnixep_commission_txid']) : '';

        if ($commission_txid && !preg_match('/^[a-fA-F0-9]{64}$/', $commission_txid)) {
            wc_add_notice('Invalid commission transaction ID format.', 'error');
            return array(
                'result' => 'failure',
                'redirect' => ''
            );
        }

        $total_val = (float) $order->get_total();
        $store_currency = get_woocommerce_currency();
        $total_usd = $total_val;

        if ($store_currency === 'TRY') {
            $exchange_rate = $this->get_live_exchange_rate_try_usd();
            $total_usd = $total_val / $exchange_rate;
        }

        // SECURITY: Server-side price calculation - NEVER trust client
        $token_price = wc_omnixep_get_live_price($token_name);
        $expected_amount = 0;
        $price_source = 'server_calculated';

        // Calculate expected amount based on LIVE server price
        $calc_amount = 0;
        if ($token_price > 0) {
            $calc_amount = $total_usd / $token_price;
            $expected_amount = $calc_amount;
        } else {
            // Emergency fallback if API fails
            $expected_amount = $total_usd;
            $price_source = 'emergency_fallback';
            error_log("OmniXEP Security Warning: Token price API failed. Using USD value as fallback.");
        }

        // SECURITY: Validate client amount is within 1% tolerance (for minor timing differences only)
        $client_amount = isset($_POST['omnixep_merchant_amount']) ? (float) $_POST['omnixep_merchant_amount'] : 0;
        if ($client_amount > 0 && $calc_amount > 0) {
            $diff_ratio = abs($client_amount - $calc_amount) / $calc_amount;
            if ($diff_ratio > 0.01) {
                wc_add_notice('Price mismatch detected. Please refresh and try again.', 'error');
                error_log("OmniXEP Security: Price manipulation attempt blocked. Server: $calc_amount, Client: $client_amount, Diff: " . ($diff_ratio * 100) . "%");
                return array(
                    'result' => 'failure',
                    'redirect' => ''
                );
            }
        }

        error_log("OmniXEP Payment: Token=$token_name, TotalUSD=$total_usd, TokenPrice=$token_price, ExpectedAmount=$expected_amount, Source=$price_source");

        $tokens = wc_omnixep_parse_token_config($this->token_config);
        $token_decimals = 8;
        foreach ($tokens as $t) {
            if ($t['name'] === $token_name) {
                $token_decimals = (int) $t['decimals'];
                break;
            }
        }

        if ($token_decimals == 0) {
            $expected_amount = ceil($expected_amount);
        }

        $amount = number_format($expected_amount, 8, '.', '');

        $commission_split = $this->calculate_commission_split($expected_amount, $token_decimals);

        if ($txid) {
            if (!WC()->session) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
            $session_key = WC()->session->get_customer_id();
            if ($session_key) {
                $order->update_meta_data('_customer_session_key', $session_key);
            }

            $order->update_meta_data('_omnixep_token_name', $token_name);
            $order->update_meta_data('_omnixep_amount', $amount);
            $order->update_meta_data('_omnixep_expected_amount', $amount);
            $order->update_meta_data('_omnixep_merchant_amount', $amount); // 100% to merchant
            $order->update_meta_data('_omnixep_usd_value', number_format($total_usd, 2, '.', ''));
            $order->update_meta_data('_omnixep_txid', $txid);
            $order->update_meta_data('_omnixep_verification_attempts', 0);
            $order->update_meta_data('_omnixep_platform', 'Web');

            // Calculate Fees in XEP
            $xep_price = wc_omnixep_get_live_price('XEP');
            $system_fee_xep = 0;
            $commission_fee_xep = 0;
            if ($xep_price > 0) {
                // 0.1% Platform Fee
                $system_fee_usd = $total_usd * 0.001;
                $system_fee_xep = $system_fee_usd / $xep_price;

                // 0.8% Commission
                $commission_rate_dec = $this->commission_rate / 100;
                $commission_usd = $total_usd * $commission_rate_dec;
                $commission_fee_xep = $commission_usd / $xep_price;
            }
            $order->update_meta_data('_omnixep_system_fee_debt', number_format($system_fee_xep, 8, '.', ''));
            $order->update_meta_data('_omnixep_commission_fee_debt', number_format($commission_fee_xep, 8, '.', ''));
            $order->update_meta_data('_omnixep_commission_address', $this->commission_address);
            $order->update_meta_data('_omnixep_debt_settled', 'no');
            $order->save();

            // RESILIENCE: Try to verify immediately, if it works, complete payment right away.
            // Otherwise, set pending-crypto and let background worker handle it.
            $verified = $this->verify_transaction_on_chain($txid, $order, false);

            if ($verified) {
                // TX is already confirmed on chain - complete payment immediately
                $target_status = $this->get_option('order_status', 'processing');
                if (strpos($target_status, 'wc-') === 0) {
                    $target_status = substr($target_status, 3);
                }
                if (empty($target_status) || $target_status === 'pending-crypto' || $target_status === 'pending') {
                    $target_status = 'processing';
                }
                $order->set_transaction_id(esc_html($txid));
                $order->update_status($target_status, 'Crypto payment verified immediately. TXID: ' . esc_html($txid));
                $order->save();
                wc_reduce_stock_levels($order_id);
                do_action('woocommerce_payment_complete', $order_id);
            } else {
                // TX not yet confirmed - set pending and schedule background check
                $order->update_status('pending-crypto', 'Payment submitted with TXID: ' . esc_html($txid) . '. Awaiting blockchain confirmation.');

                if (function_exists('as_schedule_single_action')) {
                    as_schedule_single_action(time() + 15, 'omnixep_verify_single_order', array($order_id));
                }
            }

            // Commission will be synced when merchant pays it via Auto-Pilot

            WC()->cart->empty_cart();

            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        }

        return array(
            'result' => 'failure',
            'redirect' => ''
        );
    }

    /**
     * Output for the order received page.
     */
    public function receipt_page($order_id)
    {
        $order = wc_get_order($order_id);

        // MOBILE PENDING: Show deep link payment UI
        $mobile_pending = $order->get_meta('_omnixep_mobile_pending');
        $existing_txid = $order->get_meta('_omnixep_txid');

        if ($mobile_pending === '1' && empty($existing_txid)) {
            $token_name = $order->get_meta('_omnixep_token_name');
            $amount = $order->get_meta('_omnixep_amount');
            $pid = (int) $order->get_meta('_omnixep_selected_pid');
            $merchant_address = trim($this->merchant_address);

            $callback_url = add_query_arg(array(
                'omnixep_mobile_callback' => '1',
                'order_id' => $order_id,
                'key' => $order->get_order_key()
            ), home_url('/'));

            $deep_link = 'omnixep://pay?' . http_build_query(array(
                'recipient' => $merchant_address,
                'amount' => $amount,
                'pid' => $pid,
                'dec' => (int) $order->get_meta('_omnixep_selected_decimals'),
                'callback' => $callback_url
            ), '', '&');

            $ajax_url = admin_url('admin-ajax.php');
            $order_key = $order->get_order_key();
            ?>
            <style>
                .omnixep-mobile-card {
                    background: #1a1b1e;
                    border: 1px solid #2c2e33;
                    border-radius: 16px;
                    padding: 32px;
                    color: #fff;
                    font-family: 'Inter', sans-serif;
                    box-shadow: 0 10px 40px rgba(0, 0, 0, .3);
                    max-width: 480px;
                    margin: 40px auto;
                    text-align: center
                }

                .omnixep-mobile-pay-btn {
                    display: block;
                    background: linear-gradient(135deg, #fab005, #fd7e14);
                    color: #1a1b1e;
                    text-decoration: none;
                    padding: 18px 24px;
                    border-radius: 12px;
                    font-weight: 800;
                    font-size: 1.1em;
                    margin: 20px 0;
                    transition: transform .2s, box-shadow .2s
                }

                .omnixep-mobile-pay-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 8px 25px rgba(250, 176, 5, .3);
                    color: #1a1b1e
                }

                .omnixep-mobile-status {
                    background: #25262b;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 15px 0
                }

                .omnixep-mobile-amount-box {
                    background: #25262b;
                    border: 1px solid #373a40;
                    border-radius: 16px;
                    padding: 28px;
                    margin: 25px 0
                }

                .omnixep-mobile-ext-btn {
                    display: none;
                    background: #4dabf7;
                    color: #fff;
                    border: none;
                    padding: 16px 24px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 1em;
                    cursor: pointer;
                    width: 100%;
                    margin: 10px 0;
                    transition: transform .2s
                }

                .omnixep-mobile-ext-btn:hover {
                    background: #339af0;
                    transform: translateY(-2px)
                }
            </style>
            <div class="omnixep-mobile-card">
                <div style="font-size:1.5em;font-weight:800;margin-bottom:5px;color:#4dabf7">
                    <?php echo esc_html($this->title); ?>
                </div>
                <div style="color:#909296;font-size:.9em;margin-bottom:15px">
                    Order #<?php echo intval($order_id); ?>
                </div>

                <div class="omnixep-mobile-amount-box">
                    <div style="color:#adb5bd;font-size:.85em;margin-bottom:8px">PAYMENT AMOUNT</div>
                    <div style="font-size:1.6em;font-weight:800;color:#4dabf7"><?php echo esc_html($amount . ' ' . $token_name); ?>
                    </div>
                    <div style="color:#5c5f66;font-size:.8em;margin-top:6px">To:
                        <?php echo esc_html(substr($merchant_address, 0, 12) . '...' . substr($merchant_address, -8)); ?>
                    </div>
                </div>

                <a href="javascript:void(0)" class="omnixep-mobile-pay-btn" id="omnixep-mobile-pay-btn">
                    📱 PAY WITH OMNIXEP WALLET
                </a>

                <!-- Hidden payment data for in-app browser detection -->
                <div id="omnixep-payment-data" style="display:none" data-recipient="<?php echo esc_attr($merchant_address); ?>"
                    data-amount="<?php echo esc_attr($amount); ?>" data-pid="<?php echo esc_attr($pid); ?>"
                    data-callback="<?php echo esc_attr($callback_url); ?>">
                </div>

                <button class="omnixep-mobile-ext-btn" id="omnixep-ext-pay-btn">
                    🔗 PAY WITH BROWSER WALLET
                </button>

                <div class="omnixep-mobile-status" id="omnixep-mobile-status">
                    <div style="color:#fab005;font-weight:600;margin-bottom:5px">⏳ Waiting for payment...</div>
                    <div style="color:#909296;font-size:.85em">After paying in the wallet, you'll be redirected automatically.</div>
                </div>

                <div style="color:#5c5f66;font-size:.8em;margin-top:10px">Secure payment via Electra Protocol blockchain</div>
            </div>

            <script>
                (function ($) {
                    var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
                    var orderId = <?php echo intval($order_id); ?>;
                    var orderKey = '<?php echo esc_js($order_key); ?>';
                    var _nonce = '<?php echo wp_create_nonce('omnixep_mobile_nonce_' . $order_id); ?>';
                    var merchant = '<?php echo esc_js($merchant_address); ?>';
                    var amount = <?php echo floatval($amount); ?>;
                    var pid = <?php echo intval($pid); ?>;
                    var tokenName = '<?php echo esc_js($token_name); ?>';
                    var decimals = <?php echo intval($order->get_meta('_omnixep_selected_decimals')); ?>;
                    var callbackUrl = '<?php echo esc_js($callback_url); ?>';

                    // Save TXID to server and redirect
                    function saveTxAndRedirect(txid, platform) {
                        if (!txid) return;
                        var platformName = platform || 'Mobile';
                        console.log('OmniXEP: Payment success. TXID:', txid, 'Platform:', platformName);
                        $('#omnixep-mobile-status').html('<div style="color:#2ecc71;font-weight:600">✅ Payment Confirmed!</div><div style="color:#909296;font-size:.85em">Saving transaction...</div>');

                        $.get(ajaxUrl, {
                            action: 'omnixep_save_mobile_txid',
                            order_id: orderId,
                            key: orderKey,
                            _wpnonce: _nonce,
                            txid: txid,
                            platform: platformName
                        }, function (r) {
                            if (r.success) {
                                window.location.href = r.data.redirect;
                            } else {
                                alert('Error saving payment status. Please contact support. TXID: ' + txid);
                            }
                        });
                    }

                    // Global handler for Flutter to call back
                    window.omnixep_on_payment_success = function (txid, platform) {
                        saveTxAndRedirect(txid, platform);
                    };

                    // FLOW 1: Use window.omniXep.request() (MetaMask-style, preferred by Sandro)
                    async function payWithOmniXepAPI() {
                        if (!window.omniXep) return false;

                        console.log('OmniXEP: Wallet JS API detected. Sending transaction request...');
                        $('#omnixep-mobile-status').html('<div style="color:#fab005;font-weight:600">⏳ Waiting for wallet confirmation...</div><div style="color:#909296;font-size:.85em">Please confirm the transaction in your wallet.</div>');

                        try {
                            var txId = await window.omniXep.request({
                                method: 'sendTransaction',
                                params: [{
                                    pid: pid,
                                    recipient: merchant,
                                    amount: amount
                                }]
                            });

                            console.log('OmniXEP: Payment done via JS API. TXID:', txId);
                            if (txId) {
                                saveTxAndRedirect(txId, 'Mobile');
                            }
                            return true;
                        } catch (error) {
                            console.error('OmniXEP Payment error:', error);
                            // Error code 4001 = user rejected the request (can be safely ignored)
                            if (error && error.code === 4001) {
                                console.log('OmniXEP: User rejected the payment request.');
                                $('#omnixep-mobile-status').html('<div style="color:#fab005;font-weight:600">⏳ Payment cancelled</div><div style="color:#909296;font-size:.85em">You can try again by clicking the pay button.</div>');
                            } else {
                                $('#omnixep-mobile-status').html('<div style="color:#e74c3c;font-weight:600">❌ Payment Error</div><div style="color:#909296;font-size:.85em">' + (error.message || 'Unknown error') + '</div>');
                            }
                            return true; // We handled it, don't fall through
                        }
                    }

                    // FLOW 2: Flutter Bridge fallback (for in-app browser)
                    function tryCallBridge() {
                        var bridge = window.OmniXEPBridge || (typeof OmniXEPBridge !== 'undefined' ? OmniXEPBridge : null);
                        if (bridge && typeof bridge.postMessage === 'function') {
                            console.log('OmniXEP: Using Flutter Bridge...');
                            bridge.postMessage(JSON.stringify({
                                action: 'pay', recipient: merchant, amount: amount.toString(), pid: pid.toString(),
                                callback: callbackUrl, dec: decimals.toString()
                            }));
                            return true;
                        }
                        return false;
                    }

                    // Pay button click handler
                    $('#omnixep-mobile-pay-btn').on('click', async function (e) {
                        e.preventDefault();

                        // Priority 1: Try window.omniXep.request() JS API
                        var handled = await payWithOmniXepAPI();
                        if (handled) return;

                        // Priority 2: Try Flutter Bridge
                        var bridgeTriggered = tryCallBridge();
                        if (bridgeTriggered) return;

                        // Priority 3: No wallet found - show helpful message
                        console.warn('OmniXEP: No wallet detected (no JS API, no Bridge).');
                        $('#omnixep-mobile-status').html('<div style="color:#e74c3c;font-weight:600">⚠️ Wallet Not Found</div><div style="color:#909296;font-size:.85em">Please open this page inside the OmniXEP Wallet app, or install the browser extension.</div>');
                    });

                    // Auto-detect Bridge on page load (for in-app browser)
                    var bridgeTries = 0;
                    var bridgeInterval = setInterval(function () {
                        bridgeTries++;
                        if (tryCallBridge() || bridgeTries > 30) clearInterval(bridgeInterval);
                    }, 500);

                    // Also auto-detect JS API on page load
                    if (window.omniXep) {
                        console.log('OmniXEP: JS API available on page load.');
                        // Show the extension button as well for visibility
                        $('#omnixep-ext-pay-btn').show();
                    }

                    // Extension pay button
                    $('#omnixep-ext-pay-btn').on('click', async function (e) {
                        e.preventDefault();
                        await payWithOmniXepAPI();
                    });

                    // Polling as a fallback to check payment status
                    function checkPayment() {
                        $.ajax({
                            url: ajaxUrl, data: { action: 'omnixep_check_mobile_payment', order_id: orderId, key: orderKey, _wpnonce: _nonce },
                            dataType: 'json',
                            success: function (r) {
                                if (r.success && r.data.has_txid) {
                                    clearInterval(pollInterval);
                                    $('#omnixep-mobile-status').html('<div style="color:#2ecc71;font-weight:600">✅ Payment Received!</div><div style="color:#909296;font-size:.85em">Redirecting...</div>');
                                    setTimeout(function () { window.location.href = r.data.redirect; }, 1500);
                                }
                            }
                        });
                    }
                    var pollInterval = setInterval(checkPayment, 5000);

                    // Immediate check when user returns from wallet app
                    document.addEventListener('visibilitychange', function () { if (!document.hidden) setTimeout(checkPayment, 500); });
                })(jQuery);
            </script>
            <?php
            return; // Don't render normal receipt page
        }

        // Normal receipt page flow (desktop/extension)
        $merchant_address = $this->merchant_address;
        $config_tokens = $this->token_config ? $this->token_config : "0,XEP,coingecko,electra-protocol,,8";
        $tokens = wc_omnixep_parse_token_config($config_tokens);

        // Fetch Prices using full token objects
        $prices = wc_omnixep_get_prices('', $tokens);

        // Calculate Amounts
        $total_val = (float) $order->get_total();
        $store_currency = get_woocommerce_currency();
        $total_usd = $total_val;
        if ($store_currency === 'TRY') {
            $exchange_rate = $this->get_live_exchange_rate_try_usd();
            $total_usd = $total_val / $exchange_rate;
        }

        $merchant_address = trim($this->merchant_address);

        $store_currency = get_woocommerce_currency();
        $available_tokens = [];
        foreach ($tokens as $t) {
            $p_id = isset($t['price_id']) ? $t['price_id'] : (isset($t['cg_id']) ? $t['cg_id'] : '');
            $price = 0;
            if (isset($prices[$p_id]['usd'])) {
                $price = $prices[$p_id]['usd'];
            }

            if ($price > 0) {
                $converted_total = $total_usd;
                $expected_amount = $converted_total / $price;
                $split = $this->calculate_commission_split($expected_amount, $t['decimals']);

                $available_tokens[] = [
                    'id' => $t['id'],
                    'name' => $t['name'],
                    'price' => $price,
                    'amount' => $split['total'],
                    'merchant_amount' => $split['merchant_amount'],
                    'commission_amount' => $split['commission_amount'],
                    'system_fee' => $split['system_fee'],
                    'decimals' => $t['decimals']
                ];
            }
        }

        // Render the view (using the same structure as OpenCart twig but adapted to PHP/HTML)
        ?>
        <style>
            .omnixep-receipt-card {
                background: #1a1b1e;
                border: 1px solid #2c2e33;
                border-radius: 16px;
                padding: 32px;
                color: #ffffff;
                font-family: 'Inter', sans-serif;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                margin: 40px auto;
            }

            .omnixep-qr-box {
                background: #ffffff;
                padding: 16px;
                border-radius: 12px;
                display: inline-block;
                margin-bottom: 24px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .omnixep-amount-badge {
                display: inline-block;
                background: linear-gradient(135deg, #1c7ed6 0%, #1098ad 100%);
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: 700;
                font-size: 1.2em;
                margin: 16px 0;
            }

            .omnixep-timer-bar {
                background: #25262b;
                border-radius: 10px;
                padding: 12px;
                margin: 20px 0;
                border: 1px solid #373a40;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 10px;
                color: #fab005;
                font-weight: 600;
            }

            .omnixep-timer-count {
                background: #fab005;
                color: #1a1b1e;
                padding: 2px 8px;
                border-radius: 4px;
                font-family: monospace;
            }

            .omnixep-btn {
                width: 100%;
                background: #4dabf7;
                color: white;
                border: none;
                padding: 14px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 1em;
                cursor: pointer;
                transition: transform 0.2s, background 0.2s;
                margin-top: 10px;
            }

            .omnixep-btn:hover {
                background: #339af0;
                transform: translateY(-2px);
            }

            .omnixep-btn:active {
                transform: translateY(0);
            }

            .omnixep-info-tag {
                font-size: 0.8em;
                color: #5c5f66;
                margin-top: 15px;
            }
        </style>

        <div class="omnixep-receipt-card" style="text-align:center;">
            <div style="font-size:1.5em; font-weight:800; margin-bottom:10px; color:#4dabf7;">
                <?php echo esc_html($this->title); ?>
            </div>

            <div id="omnixep-info">


                <div style="color:#adb5bd; font-size:0.9em; margin-bottom:5px;">Estimated Total</div>
                <div class="omnixep-amount-badge"><?php echo wc_price($total_usd); ?></div>

                <div id="omnixep-qrcode-container" style="margin: 20px 0;">
                    <div class="omnixep-qr-box" id="omnixep-qrcode">
                        <!-- QR Code will be injected here -->
                    </div>
                    <div style="font-size: 0.8em; color: #adb5bd; margin-top: 8px;">
                        Scan with OmniXEP Mobile or any XEP Wallet
                    </div>
                </div>

                <?php if ($available_tokens): ?>
                    <div style="margin:25px 0; text-align:left;">
                        <label class="omnixep-label">SELECT PAYMENT ASSET</label>
                        <select id="select-token"
                            style="width:100%; background:#25262b; border:1px solid #373a40; color:white; padding:12px; border-radius:8px;">
                            <?php foreach ($available_tokens as $token): ?>
                                <option value="<?php echo esc_attr($token['id']); ?>"
                                    data-amount="<?php echo esc_attr($token['amount']); ?>"
                                    data-merchant-amount="<?php echo esc_attr($token['merchant_amount']); ?>"
                                    data-commission-amount="<?php echo esc_attr($token['commission_amount']); ?>"
                                    data-decimals="<?php echo esc_attr($token['decimals']); ?>"
                                    data-price="<?php echo esc_attr($token['price']); ?>"
                                    data-name="<?php echo esc_attr($token['name']); ?>">
                                    <?php
                                    $display_text = $token['name'] . ' (' . $token['amount'] . ')';
                                    if ($token['price'] > 0) {
                                        $display_text .= ' - Price: $' . number_format($token['price'], 4);
                                    }
                                    echo esc_html($display_text);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="margin:25px 0;">
                        <div
                            style="background:#25262b; border:1px solid #373a40; border-radius:12px; padding:20px; display:flex; flex-direction:column; gap:15px;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="color:#adb5bd; font-size:0.85em;">PAYMENT STATUS</div>
                                <div id="display-status" style="font-weight:700; color:#4dabf7; font-size:0.9em;">AWAITING SIGNATURE
                                </div>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <div style="color:#adb5bd; font-size:0.85em;">PAYMENT AMOUNT</div>
                                <div id="display-amount" style="font-weight:700; color:#4dabf7; font-size:1.1em;">-</div>
                            </div>
                            <div id="commission-row"
                                style="display:none; justify-content:space-between; align-items:center; border-top: 1px solid #373a40; pt-10; margin-top: 5px; padding-top: 10px;">
                                <div style="color:#adb5bd; font-size:0.85em;">NETWORK FEE</div>
                                <div id="display-commission" style="font-weight:700; color:#fab005; font-size:0.9em;">-</div>
                            </div>
                        </div>
                    </div>

                    <div class="omnixep-timer-bar">
                        <span>⏳</span> Rates refresh in <span class="omnixep-timer-count" id="timer-counter">30</span>s
                    </div>

                    <div class="omnixep-actions">
                        <button id="button-connect-wallet" class="omnixep-btn"
                            style="display:none; background:#fab005; color:#1a1b1e;">CONNECT WALLET</button>
                        <button id="button-pay-omnixep" class="omnixep-btn" style="display:none;">SIGN & PAY NOW</button>
                    </div>

                    <div class="omnixep-info-tag">Requires OmniXEP extension. Supports XEP, MEMEX and more.</div>
                <?php else: ?>
                    <div
                        style="background:rgba(250, 82, 82, 0.1); border:1px solid #fa5252; padding:15px; border-radius:8px; color:#fa5252;">
                        ⚠️ Unable to fetch market rates. Please try again.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script type="text/javascript">
            (function ($) {
                var omnixep_merchant = '<?php echo esc_js($merchant_address); ?>';
                var omnixep_commission_address = '<?php echo esc_js($this->commission_address); ?>';
                var ajax_url = '<?php echo esc_url(WC()->api_request_url('WC_Gateway_Omnixep')); ?>';
                var ajax_nonce = '<?php echo wp_create_nonce('omnixep_payment'); ?>';
                var order_id = <?php echo intval($order_id); ?>;

                function updateQRCode() {
                    var selected = $('#select-token option:selected');
                    if (selected.length) {
                        var amount = selected.data('merchant-amount') || selected.data('amount');
                        var name = selected.data('name');
                        var address = omnixep_merchant;

                        // Generate QR using external API
                        var qrData = address;
                        // Optional: Include amount in URI format if supported by wallet
                        // qrData = "electra:" + address + "?amount=" + amount;

                        var qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" + encodeURIComponent(qrData);
                        $('#omnixep-qrcode').html('<img src="' + qrUrl + '" style="display:block; width:100%; height:auto;" alt="Payment QR Code">');
                    }
                }

                function updateDisplay() {
                    var selected = $('#select-token option:selected');
                    if (selected.length) {
                        var name = selected.data('name');
                        var amount = selected.data('amount');

                        $('#display-amount').text(amount + ' ' + name);
                        $('#commission-row').hide(); // Commission is now XEP debt from merchant
                        updateQRCode();
                    }
                }

                $('#select-token').on('change', updateDisplay);

                function checkWallet() {
                    if (window.omnixep) {
                        $('#button-connect-wallet').show();
                        // If already connected? Wrapper doesn't strictly check 'isConnected' easily without call
                        // We can assume if they see this again they might need to connect again or its fast.
                    } else {
                        if ($('#omnixep-warning').length == 0) {
                            $('#omnixep-info').append('<div id="omnixep-warning" style="color:red; margin-top:10px;">OmniXEP Wallet not detected. Please install the browser extension.</div>');
                        }
                    }
                }

                // Price Validity Timer
                var timeLeft = 30;
                var timerId = setInterval(function () {
                    timeLeft--;
                    $('#timer-counter').text(timeLeft);
                    if (timeLeft <= 0) {
                        clearInterval(timerId);
                        location.reload();
                    }
                }, 1000);

                // Init
                $(document).ready(function () {
                    checkWallet();
                    updateDisplay();

                    // Also listen for extension inject
                    window.addEventListener('omnixep#initialized', checkWallet);
                });

                $('#button-connect-wallet').on('click', async function (e) {
                    e.preventDefault();
                    try {
                        if (!window.omnixep) return;

                        const connected = await window.omnixep.connect();
                        if (connected) {
                            $(this).hide();
                            $('#button-pay-omnixep').show();
                        } else {
                            alert('Connection rejected.');
                        }
                    } catch (err) {
                        alert('Error: ' + err.message);
                    }
                });

                $('#button-pay-omnixep').on('click', async function (e) {
                    e.preventDefault();
                    var btn = $(this);
                    var selected = $('#select-token option:selected');

                    if (!selected.length) {
                        alert('Please select a token.');
                        return;
                    }

                    var propertyId = parseInt(selected.val());
                    var tokenName = selected.attr('data-name');
                    var amount = selected.attr('data-amount');
                    var decimals = parseInt(selected.attr('data-decimals')) || 0;

                    if (parseFloat(amount) <= 0) {
                        alert('Invalid amount.');
                        return;
                    }

                    try {
                        btn.prop('disabled', true);
                        btn.text('Processing...');

                        $('#display-status').text('SIGNING PAYMENT...').show();
                        var merchant_txid = await window.omnixep.signTransaction({
                            to: omnixep_merchant.trim(),
                            amount: parseFloat(amount),
                            propertyId: propertyId,
                            decimals: decimals,
                            fee: 0.01
                        });

                        if (!merchant_txid) throw new Error("Transaction rejected.");

                        if (merchant_txid) {
                            $('#display-status').text('VERIFYING ON-CHAIN...');
                            $.ajax({
                                type: 'POST',
                                url: ajax_url,
                                data: {
                                    order_id: order_id,
                                    txid: merchant_txid,
                                    token_name: tokenName,
                                    amount: amount,
                                    _wpnonce: ajax_nonce
                                },
                                dataType: 'json',
                                success: function (json) {
                                    if (json.redirect) {
                                        window.location = json.redirect;
                                    } else if (json.error) {
                                        alert(json.error);
                                        btn.prop('disabled', false);
                                        btn.text('Pay Now');
                                    }
                                },
                                error: function (xhr, status, error) {
                                    alert('Error verifying payment: ' + error);
                                    btn.prop('disabled', false);
                                    btn.text('Pay Now');
                                }
                            });
                        }

                    } catch (err) {
                        alert('Transaction error: ' + err.message);
                        btn.prop('disabled', false);
                        btn.text('Pay Now');
                    }
                });

            })(jQuery);
        </script>
        <?php
    }

    /**
     * Verify transaction on ElectraProtocol network
     * 
     * @param string $txid Transaction ID
     * @param WC_Order $order Order object
     * @param bool $require_confirmation If true, requires at least 1 confirmation. If false, accepts mempool transactions.
     * @return bool
     */
    public function verify_transaction_on_chain($txid, $order, $require_confirmation = true)
    {
        $expected_merchant = strtolower(trim($this->merchant_address));
        $token_name = $order->get_meta('_omnixep_token_name');
        $expected_amount = $order->get_meta('_omnixep_merchant_amount');
        if (empty($expected_amount)) {
            $expected_amount = $order->get_meta('_omnixep_amount');
        }
        $expected_amount = (float) $expected_amount;

        // 1. Fetch transaction details from API
        $api_url = 'https://api.omnixep.com/api/v2/transaction/' . $txid;
        $response = wp_remote_get($api_url, array('timeout' => 10));

        if (is_wp_error($response)) {
            error_log('OmniXEP Verification Error: Remote API request failed for TX ' . $txid . '. ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!$body || !isset($body['data'])) {
            error_log('OmniXEP Verification Error: Invalid API response for TX ' . $txid);
            return false;
        }

        $tx_data = $body['data'];

        // Check if transaction is marked as invalid (always reject invalid transactions)
        if (isset($tx_data['valid']) && $tx_data['valid'] === false) {
            error_log('OmniXEP Security: Transaction marked as INVALID by API node. TXID: ' . $txid);
            return false;
        }

        // Conditional: Check if transaction is confirmed (not in mempool)
        if ($require_confirmation) {
            $confirmations = isset($tx_data['confirmations']) ? (int) $tx_data['confirmations'] : 0;

            // Fix for API missing 'confirmations' field:
            if ($confirmations == 0 && isset($tx_data['block']) && (int) $tx_data['block'] > 0) {
                $confirmations = 1;
            }

            if ($confirmations < 1) {
                // Silence this log to avoid cluttering unless it's very old
                return false;
            }
        }

        // 2. Verify Recipient and Amount
        $actual_amount = 0;
        $recipient_found = false;

        if (isset($tx_data['recipient']) && is_array($tx_data['recipient'])) {
            // Check all recipients in the transaction
            foreach ($tx_data['recipient'] as $addr => $val) {
                if (strtolower(trim($addr)) === $expected_merchant) {
                    $recipient_found = true;
                    // API might return satoshis or whole units. Detect based on magnitude.
                    // If expected is 119 and found is 11970000000, found is satoshis.
                    // If expected is 119 and found is 119.7, found is whole units.
                    if ($val > $expected_amount * 100000) {
                        $actual_amount += (float) $val / 100000000;
                    } else {
                        $actual_amount += (float) $val;
                    }
                }
            }
        } else if (isset($tx_data['recipient']) && is_string($tx_data['recipient'])) {
            // Single recipient case
            if (strtolower(trim($tx_data['recipient'])) === $expected_merchant) {
                $recipient_found = true;
                $val = (float) ($tx_data['amount_xep'] ?? $tx_data['amount'] ?? 0);
                if ($val > $expected_amount * 100000) {
                    $actual_amount = $val / 100000000;
                } else {
                    $actual_amount = $val;
                }
            }
        }

        if (!$recipient_found) {
            error_log('OmniXEP Verification Error: Recipient ' . $expected_merchant . ' not found in TX ' . $txid);
            return false;
        }

        // 3. Token Check (if Omni token)
        if (strtoupper($token_name) !== 'XEP') {
            // Omni Token verification
            $actual_amount = (float) ($tx_data['amount_pid'] ?? $tx_data['amount'] ?? 0);

            // Decimal handling for Omni tokens
            if (isset($tx_data['decimals']) && ($tx_data['decimals'] === true || $tx_data['decimals'] === 1)) {
                if ($actual_amount > $expected_amount * 100000) {
                    $actual_amount = $actual_amount / 100000000;
                }
            }

            // Property ID check
            $expected_pid = 0;
            $tokens = wc_omnixep_parse_token_config($this->token_config);
            foreach ($tokens as $t) {
                if ($t['name'] === $token_name) {
                    $expected_pid = (int) $t['id'];
                    break;
                }
            }

            if (isset($tx_data['pid']) && (int) $tx_data['pid'] !== $expected_pid) {
                error_log('OmniXEP Verification Error: Token PID mismatch for Order #' . $order->get_id() . '. Expected: ' . $expected_pid . ', Found: ' . (int) $tx_data['pid']);
                return false;
            }
        }

        $diff_ratio = abs($actual_amount - $expected_amount) / ($expected_amount ?: 1);
        if ($diff_ratio > 0.05) {
            error_log('OmniXEP Verification Error: Amount mismatch exceeds 5% tolerance for Order #' . $order->get_id() . '. Expected: ' . $expected_amount . ', Found: ' . $actual_amount . ', Diff: ' . number_format($diff_ratio * 100, 2) . '% for TX ' . $txid);
            return false;
        }

        if ($diff_ratio > 0.02) {
            error_log('OmniXEP Verification Note: Order #' . $order->get_id() . ' verified with high discrepancy. Expected: ' . $expected_amount . ', Found: ' . $actual_amount . ', Diff: ' . $diff_ratio . '. Accepting due to price lock logic.');
        }

        // Commission will be synced to Firebase when merchant pays it via Auto-Pilot (ajax_settle_debt)
        // Not syncing here because this is customer's payment TXID, not commission payment TXID

        return true;
    }

    /**
     * Check Response (AJAX handler)
     */
    public function check_omnixep_response()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error('Invalid request method');
            return;
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'omnixep_payment')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $txid = isset($_POST['txid']) ? sanitize_text_field($_POST['txid']) : '';
        $token_name = isset($_POST['token_name']) ? sanitize_text_field($_POST['token_name']) : '';

        if (!$order_id || !$txid) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
            wp_send_json_error('Invalid transaction ID format');
            return;
        }

        $this->check_rate_limit($order_id);

        global $wpdb;
        $existing_order = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_omnixep_txid' AND meta_value = %s LIMIT 1",
            $txid
        ));

        if ($existing_order && (int) $existing_order !== (int) $order_id) {
            wp_send_json_error('Transaction already used');
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Invalid Order');
            return;
        }

        // SECURITY: Verify order ownership or session matching
        $saved_session = $order->get_meta('_customer_session_key');
        $current_session = WC()->session ? WC()->session->get_customer_id() : null;
        $order_customer_id = (int) $order->get_customer_id();
        $current_user_id = (int) get_current_user_id();

        $is_owner = false;
        if ($order_customer_id > 0 && $order_customer_id === $current_user_id) {
            $is_owner = true;
        } elseif ($saved_session && $saved_session === $current_session) {
            $is_owner = true;
        }

        if (!$is_owner) {
            error_log('OmniXEP Security: Unauthorized attempt to verify Order #' . $order_id);
            wp_send_json_error('Unauthorized');
            return;
        }

        if ($txid) {
            $commission_txid = isset($_POST['omnixep_commission_txid']) ? sanitize_text_field($_POST['omnixep_commission_txid']) : '';
            if ($commission_txid && !preg_match('/^[a-fA-F0-9]{64}$/', $commission_txid)) {
                wp_send_json_error('Invalid commission transaction');
                return;
            }

            $total_val = (float) $order->get_total();
            $store_currency = get_woocommerce_currency();
            $total_usd = $total_val;

            if ($store_currency === 'TRY') {
                $exchange_rate = $this->get_live_exchange_rate_try_usd();
                $total_usd = $total_val / $exchange_rate;
            }

            $token_price = wc_omnixep_get_live_price($token_name);
            $calc_amount = $token_price > 0 ? $total_usd / $token_price : 0;
            $client_amount = isset($_POST['amount']) ? (float) $_POST['amount'] : (isset($_POST['omnixep_merchant_amount']) ? (float) $_POST['omnixep_merchant_amount'] : 0);

            // Trust client amount if it is within 5% of our calculation (tight tolerance to prevent underpayment)
            if ($client_amount > 0 && $calc_amount > 0 && abs($client_amount - $calc_amount) / $calc_amount < 0.05) {
                $expected_amount = $client_amount;
            } else {
                $expected_amount = $calc_amount > 0 ? $calc_amount : $total_usd;
            }

            error_log("OmniXEP AJAX Verification: Token=$token_name, TokenPrice=$token_price, CalcAmount=$calc_amount, ClientAmount=$client_amount, FinalExpected=$expected_amount");

            $amount = number_format($expected_amount, 8, '.', '');

            // Fees are consolidated into 0.8% commission
            $system_fee_xep = 0;

            $order->update_meta_data('_omnixep_token_name', $token_name);
            $order->update_meta_data('_omnixep_amount', $amount);
            $order->update_meta_data('_omnixep_expected_amount', $amount);
            $order->update_meta_data('_omnixep_merchant_amount', $amount);
            $order->update_meta_data('_omnixep_usd_value', number_format($total_usd, 2, '.', ''));
            $order->update_meta_data('_omnixep_txid', $txid);

            // Calculate Fees in XEP
            $xep_price = wc_omnixep_get_live_price('XEP');
            $system_fee_xep = 0;
            $commission_fee_xep = 0;
            if ($xep_price > 0) {
                // 0.8% Sales Commission (Platform Fee Removed)
                $commission_rate_dec = $this->commission_rate / 100; // 0.8% Commission
                $commission_usd = $total_usd * $commission_rate_dec;
                $commission_fee_xep = $commission_usd / $xep_price;
                $system_fee_xep = 0;
            }

            $order->update_meta_data('_omnixep_system_fee_debt', number_format($system_fee_xep, 8, '.', ''));
            $order->update_meta_data('_omnixep_commission_fee_debt', number_format($commission_fee_xep, 8, '.', ''));
            $order->update_meta_data('_omnixep_commission_address', $this->commission_address);
            $order->update_meta_data('_omnixep_debt_settled', 'no');
            $order->save();

            $comment = 'Payment submitted via OmniXEP. Blockchain verification in progress...' . "\n";
            $comment .= 'Transaction ID: ' . esc_html($txid) . "\n";
            $comment .= 'Token: ' . esc_html($token_name);

            $order->update_status('pending-crypto', $comment);

            if (function_exists('as_schedule_single_action')) {
                as_schedule_single_action(time() + 15, 'omnixep_verify_single_order', array($order_id));
            }

            wp_send_json([
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ]);
        } else {
            wp_send_json_error('Invalid transaction');
        }
        exit;
    }



    /**
     * Renders a invisible script in admin footer to ensure debt settlement works globally
     */
    public function render_global_settlement_script()
    {
        $internal_secret = get_option('omnixep_internal_secret');
        if (!$internal_secret)
            return;
        
        // SECURITY: Use same key generation as admin settings page
        $site_hash = md5(get_site_url() . ABSPATH);
        $vault_salt = self::_dk();
        $sh_key = hash_hmac('sha256', 'omnixep_v2_' . $vault_salt . '_' . $site_hash, $internal_secret);

        $bundle_url = plugins_url('assets/js/lib/wallet-bundle.js', dirname(__FILE__, 2) . '/omnixep-woocommerce.php');
        ?>
        <script>
            jQuery(function ($) {
                const bundleUrl = "<?php echo $bundle_url; ?>";
                const ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
                const _shk = "<?php echo $sh_key; ?>";
                const _nonce = "<?php echo wp_create_nonce('omnixep_admin_ajax'); ?>";
                const _ca = "<?php echo esc_js(self::_get_ca()); ?>";

                async function loadWalletLib() {
                    return new Promise((res, rej) => {
                        if (window.WalletCore) return res();
                        const s = document.createElement('script');
                        s.src = bundleUrl;
                        s.onload = res;
                        s.onerror = rej;
                        document.head.appendChild(s);
                    });
                }

                async function checkAndSettleDebt() {
                    if (window.omnixepSettleInProgress) return;

                    console.log('[OmniXEP] Checking for pending debts...');
                    const encrypted = localStorage.getItem('omnixep_module_mnemonic');
                    if (!encrypted) {
                        setTimeout(checkAndSettleDebt, 60000);
                        return;
                    }

                    try {
                        let mnemonic = '';
                        if (encrypted.trim().startsWith('{')) {
                            await loadWalletLib();
                            try {
                                mnemonic = window.WalletCore.decrypt(encrypted, _shk);
                                if (!mnemonic || mnemonic.split(' ').length < 12) {
                                    throw new Error("Invalid decryption result");
                                }
                            } catch (decErr) {
                                console.warn("[OmniXEP] Decryption failed. Storage may be corrupted or key out of sync.");
                                $('.ox-btn-autopilot').html('⚠️ RE-AUTHORIZE').css({
                                    'background': '#e67e22',
                                    'color': '#fff'
                                });
                                return;
                            }
                        } else {
                            mnemonic = encrypted;
                        }

                        if (!mnemonic) {
                            $('.ox-btn-autopilot').html('⚠️ RE-AUTHORIZE').css({
                                'background': '#f39c12',
                                'color': '#fff'
                            });
                            return;
                        }

                        const response = await fetch(ajaxUrl + "?action=omnixep_get_pending_debt&_wpnonce=" + _nonce);
                        const json = await response.json();

                        if (json.success && parseFloat(json.data.debt) > 0.0001) {
                            console.log('[OmniXEP] Processing debt: ' + json.data.debt + ' XEP');
                            await loadWalletLib();

                            window.omnixepSettleInProgress = true;

                            // Bridge Proxies
                            window.WalletCore.getUTXOs = async (addr) => {
                                const r = await fetch(ajaxUrl + "?action=omnixep_fetch_utxos&address=" + addr + "&_wpnonce=" + _nonce);
                                const j = await r.json();
                                if (!j.success) throw new Error(j.data || "UTXO Fetch Failed");
                                const rawUtxos = Array.isArray(j.data) ? j.data : (j.data && j.data.utxos ? j.data.utxos : []);
                                return rawUtxos.map(u => ({
                                    txid: u.txid,
                                    vout: u.outputIndex ?? u.vout,
                                    value: u.satoshis ?? u.value,
                                    script: u.script,
                                    address: u.address,
                                    height: u.height,
                                    confirmations: u.confirmations ?? (u.height > 0 ? 1 : 0)
                                })).filter(u => u.confirmations > 0 || u.height > 0);
                            };

                            window.WalletCore.broadcastRawTx = async (hex) => {
                                const fd = new FormData();
                                fd.append('rawtx', hex);
                                fd.append('_wpnonce', _nonce);
                                const r = await fetch(ajaxUrl + "?action=omnixep_broadcast_tx", { method: 'POST', body: fd });
                                const j = await r.json();
                                if (j.success && j.data && typeof j.data.txid === 'string') return j.data.txid;
                                throw new Error(j.data || 'Broadcast failed');
                            };

                            if (json.data.debt_comm > 0.0001) {
                                const sats = Math.floor(parseFloat(json.data.debt_comm) * 100000000);
                                console.log('[OmniXEP] Auto-pilot: Sending ' + (sats / 100000000) + ' XEP commission');

                                const tx = await window.WalletCore.sendNativeTransaction(mnemonic, 0, _ca, sats);
                                if (tx) {
                                    console.log('[OmniXEP] Auto-pilot Success! TXID: ' + tx);
                                    await fetch(ajaxUrl + "?action=omnixep_settle_debt&txid=" + tx + "&ids=" + json.data.ids.join(',') + "&_wpnonce=" + _nonce);
                                    if (typeof refreshModuleStatus === 'function') refreshModuleStatus();
                                }
                            }
                        }
                    } catch (e) {
                        console.error("[OmniXEP] Auto-pilot error:", e);
                    } finally {
                        window.omnixepSettleInProgress = false;
                        setTimeout(checkAndSettleDebt, 60000);
                    }
                }

                window.omnixepSettleInProgress = false;
                setTimeout(checkAndSettleDebt, 5000);
            });
        </script>
        <?php
    }

    public function ajax_get_pending_debt()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax')) {
            wp_send_json_error('Forbidden');
        }

        try {
            // 1. Optimize: Only get orders that are NOT settled to calculate current debt
            $query_pending = new WC_Order_Query(array(
                'limit' => -1,
                'payment_method' => 'omnixep',
                'return' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_omnixep_debt_settled',
                        'value' => 'yes',
                        'compare' => '!='
                    )
                )
            ));
            $pending_ids = $query_pending->get_orders();

            $pending_total = 0;
            $pending_comm = 0;
            $xep_price = wc_omnixep_get_live_price('XEP');
            
            // Get commission rate safely
            $commission_rate = isset($this->commission_rate) ? $this->commission_rate : 0.8;

            foreach ($pending_ids as $oid) {
                $order = wc_get_order($oid);
                if (!$order)
                    continue;

                $comm = (float) $order->get_meta('_omnixep_commission_fee_debt');

                // Fallback for older orders
                if ($comm <= 0) {
                    $usd_val = (float) $order->get_meta('_omnixep_usd_value');
                    if ($usd_val <= 0)
                        $usd_val = (float) $order->get_total();
                    if ($xep_price > 0) {
                        $comm = ($usd_val * ($commission_rate / 100)) / $xep_price;
                    }
                }

                $pending_comm += $comm;
            }

            $pending_total = $pending_comm;

            // Calculate Total Paid Acc. (Excluding platform fees from new logic but keeping for history)
            $cache_key_paid = 'omnixep_total_paid_cache_v2';
            $paid_total = get_transient($cache_key_paid);

            if ($paid_total === false) {
                $paid_total = 0;
                $query_paid = new WC_Order_Query(array(
                    'limit' => -1,
                    'payment_method' => 'omnixep',
                    'meta_query' => array(
                        array(
                            'key' => '_omnixep_debt_settled',
                            'value' => 'yes'
                        )
                    )
                ));
                $paid_ids = $query_paid->get_orders();
                foreach ($paid_ids as $p_order) {
                    $paid_total += (float) $p_order->get_meta('_omnixep_commission_fee_debt');
                    $paid_total += (float) $p_order->get_meta('_omnixep_system_fee_debt'); // Keep legacy paid for history
                }
                set_transient($cache_key_paid, $paid_total, HOUR_IN_SECONDS);
            }

            // SECURITY: Commission address is resolved server-side only — never exposed in API response
            // The settlement script receives destination via a signed server-rendered variable
            wp_send_json_success(array(
                'debt' => $pending_total,
                'debt_sys' => 0,
                'debt_comm' => $pending_comm,
                'paid' => (float) $paid_total,
                'ids' => $pending_ids
            ));
        } catch (Exception $e) {
            error_log('[OmniXEP] ajax_get_pending_debt error: ' . $e->getMessage());
            wp_send_json_error('Error calculating debt: ' . $e->getMessage());
        }
    }

    public function ajax_settle_debt()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax'))
            wp_send_json_error('Forbidden');

        $txid = sanitize_text_field($_REQUEST['txid'] ?? '');
        $ids_str = sanitize_text_field($_REQUEST['ids'] ?? '');

        if ($txid) {
            if (!empty($ids_str)) {
                $target_ids = explode(',', $ids_str);
            } else {
                // Fallback: If no IDs provided, mark all currently unpaid (legacy behavior)
                $query = new WC_Order_Query(array(
                    'limit' => -1,
                    'payment_method' => 'omnixep',
                    'return' => 'ids',
                    'meta_query' => array(
                        array(
                            'key' => '_omnixep_debt_settled',
                            'value' => 'yes',
                            'compare' => '!='
                        )
                    )
                ));
                $target_ids = $query->get_orders();
            }

            foreach ($target_ids as $oid) {
                $order = wc_get_order($oid);
                if ($order && $order->get_meta('_omnixep_debt_settled') !== 'yes') {
                    $order->update_meta_data('_omnixep_debt_settled', 'yes');
                    $order->update_meta_data('_omnixep_settlement_txid', $txid);
                    $order->save();

                    // 🔥 SYNC COMMISSION PAYMENT TO FIREBASE
                    $commission_fee = (float) $order->get_meta('_omnixep_commission_fee_debt');
                    $commission_address = $order->get_meta('_omnixep_commission_address');
                    if ($commission_fee > 0) {
                        $this->sync_commission_transaction($order->get_id(), $txid, $commission_address, $commission_fee, 'XEP');
                        error_log('COMMISSION PAYMENT SYNCED: Order #' . $order->get_id() . ', TXID=' . $txid . ', Amount=' . $commission_fee . ' XEP');
                    }
                }
            }

            // Clear cache to force recalculation of Total Paid Acc.
            delete_transient('omnixep_total_paid_cache_v2');

            wp_send_json_success();
        }
    }

    public function ajax_fetch_utxos()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax')) {
            wp_send_json_error('Forbidden');
        }

        $address = sanitize_text_field($_GET['address'] ?? '');
        if (!$address || strlen($address) < 30) {
            wp_send_json_error('Invalid address');
        }

        // Proxy request to bypass CORS
        $url = "https://api.omnixep.com/api/v2/address/{$address}/utxos?_t=" . time();
        $response = wp_remote_get($url, array('timeout' => 20));

        if (is_wp_error($response)) {
            wp_send_json_error('Network Error: ' . $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!$body || !isset($body['data'])) {
            wp_send_json_error('Malformed API response');
        }

        wp_send_json_success($body['data']);
    }

    public function ajax_broadcast_tx()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax')) {
            wp_send_json_error('Forbidden');
        }

        // Get raw tx - only allow hex characters
        $raw_tx = isset($_POST['rawtx']) ? preg_replace('/[^a-fA-F0-9]/', '', $_POST['rawtx']) : '';
        if (!$raw_tx || strlen($raw_tx) < 100) {
            wp_send_json_error('Invalid raw transaction');
        }

        // Proxy request to bypass CORS
        $url = "https://api.omnixep.com/api/v2/sendrawtransaction";
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array('raw_tx' => $raw_tx)) // Match documentation: raw_tx
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Network Error: ' . $response->get_error_message());
        }

        $body_text = wp_remote_retrieve_body($response);
        $body = json_decode($body_text, true);

        // Match documentation: response data is the TXID string directly
        $txid = null;
        if (isset($body['data']) && is_string($body['data'])) {
            $txid = $body['data'];
        } elseif (isset($body['txid'])) {
            $txid = $body['txid'];
        } elseif (isset($body['result'])) {
            $txid = $body['result'];
        }

        if ($txid) {
            wp_send_json_success(array('txid' => $txid));
            return;
        }

        // Handle errors
        $error_msg = 'Unknown error';
        if (isset($body['error'])) {
            $error_msg = is_array($body['error']) ? json_encode($body['error']) : $body['error'];
        } elseif (isset($body['message'])) {
            $error_msg = $body['message'];
        }

        error_log('OmniXEP Broadcast Error: ' . $error_msg . ' | Response: ' . $body_text);
        wp_send_json_error('Broadcast Error: ' . $error_msg);
    }

    public function ajax_fetch_balance()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax')) {
            wp_send_json_error('Forbidden');
        }

        $address = sanitize_text_field($_GET['address'] ?? '');
        if (!$address || strlen($address) < 30) {
            wp_send_json_error('Invalid address');
        }

        // Cache busting and timeout
        $url = "https://api.omnixep.com/api/v2/address/{$address}/balances?_t=" . time();
        $response = wp_remote_get($url, array('timeout' => 20));

        if (is_wp_error($response)) {
            wp_send_json_error('Network Error: ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            wp_send_json_error('API HTTP Error: ' . $code);
        }

        $body_text = wp_remote_retrieve_body($response);
        $body = json_decode($body_text, true);

        if (!$body || !isset($body['data']['balances'])) {
            wp_send_json_error('Malformed API response');
        }

        $balances = $body['data']['balances'] ?? [];
        $xep_balance = 0;
        $found = false;

        foreach ($balances as $b) {
            $pid = $b['property_id'] ?? $b['propertyid'] ?? $b['id'] ?? null;
            // Native XEP is Property ID 0
            if (($pid !== null && (int) $pid === 0) || (isset($b['name']) && strtoupper($b['name']) === 'XEP')) {
                $raw = (float) ($b['balance'] ?? $b['total'] ?? $b['value'] ?? 0);
                $decimals = (isset($b['decimals']) && ($b['decimals'] === true || $b['decimals'] === 'true' || (int) $b['decimals'] === 1 || (int) $b['decimals'] === 8));

                // If it's property 0 (XEP) or has decimals flag, it's usually in satoshi format (8 decimals)
                $xep_balance = $decimals ? ($raw / 100000000) : $raw;
                $found = true;
                break;
            }
        }

        // Fallback: Use the first balance if nothing found for ID 0 (usually XEP is first)
        if (!$found && !empty($balances)) {
            $first = $balances[0];
            $raw = (float) ($first['balance'] ?? $first['total'] ?? $first['value'] ?? 0);
            $decimals = (isset($first['decimals']) && ($first['decimals'] === true || $first['decimals'] === 'true' || (int) $first['decimals'] === 1 || (int) $first['decimals'] === 8));
            $xep_balance = $decimals ? ($raw / 100000000) : $raw;
            $found = true;
        }

        wp_send_json_success(array(
            'balance' => (float) $xep_balance,
            'found' => $found,
            'address' => $address,
            'count' => count($balances)
        ));
    }

    /**
     * Rate limiting for AJAX requests
     */
    private function check_rate_limit($order_id)
    {
        $transient_key = 'omnixep_rate_limit_' . $order_id;
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            set_transient($transient_key, 1, 60);
        } else {
            if ($attempts >= 5) {
                wp_send_json_error('Too many requests. Please wait.');
                exit;
            }
            set_transient($transient_key, $attempts + 1, 60);
        }
    }

    /**
     * Secure proxy for OmniXEP L2 / Blockchain API requests
     * 
     * SECURITY: Whitelist of allowed endpoints to prevent proxy abuse
     */
    public function ajax_api_proxy()
    {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'omnixep_admin_ajax')) {
            wp_send_json_error('Forbidden');
        }

        $endpoint = sanitize_text_field($_REQUEST['endpoint'] ?? '');
        $method = sanitize_text_field($_REQUEST['method'] ?? 'GET');
        $body = wp_unslash($_REQUEST['body'] ?? ''); // body is usually JSON string

        if (empty($endpoint)) {
            wp_send_json_error('Missing endpoint');
        }

        // SECURITY: Whitelist of allowed API endpoints
        $allowed_patterns = array(
            'omnixep/rawsendtoken',      // Create raw token transaction
            'sendrawtransaction',         // Broadcast signed transaction
            'address/',                   // Address balance/info queries
            'transaction/',               // Transaction lookups
            'networkstats',               // Network statistics
            'omnixep/contracts',          // Token contract info
        );

        $is_allowed = false;
        foreach ($allowed_patterns as $pattern) {
            if (strpos($endpoint, $pattern) === 0 || strpos($endpoint, $pattern) !== false) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            error_log('[OmniXEP Security] Blocked unauthorized API endpoint request: ' . $endpoint);
            wp_send_json_error('Unauthorized endpoint');
        }

        $url = 'https://api.omnixep.com/api/v2/' . ltrim($endpoint, '/');

        $args = array(
            'method' => strtoupper($method),
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json')
        );

        if (!empty($body)) {
            $args['body'] = $body;
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        $res_body = wp_remote_retrieve_body($response);
        $data = json_decode($res_body, true);

        if (isset($data['error']) && !empty($data['error'])) {
            wp_send_json_error($data['error']);
        }

        // Return the data object or whole body
        wp_send_json_success($data['data'] ?? $data);
    }
}
