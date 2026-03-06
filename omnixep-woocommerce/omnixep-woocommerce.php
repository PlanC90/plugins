<?php
/**
 * Plugin Name: OmniXEP WooCommerce Payment Gateway
 * Plugin URI: https://www.electraprotocol.com/omnixep/
 * Description: Accept XEP and Tokens via OmniXEP Wallet.
 * Version: 2.0.0
 * Author: XEPMARKET
 * Author URI: https://xepmarket.com
 * Text Domain: omnixep-woocommerce
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * WC requires at least: 5.8
 * WC tested up to: 8.5
 */

defined('ABSPATH') || exit;

// Add a quick link on the Plugins page.
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep');
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});

/**
 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage)
 */
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// GitHub update checker: günde 1 kez https://github.com/PlanC90/omnixep-woocommerce kontrol
require_once plugin_dir_path(__FILE__) . 'includes/class-omnixep-github-updater.php';
$omnixep_github_updater = new OmniXEP_GitHub_Plugin_Updater(__FILE__);

// Admin encoding fix tool
// Temporarily disabled for debugging
// if (is_admin()) {
//     require_once plugin_dir_path(__FILE__) . 'includes/admin-fix-encoding.php';
// }

/**
 * Canonical JSON: recursive key sort so PHP and FAPI produce the same string for HMAC.
 *
 * @param array $data
 * @return array
 */
function wc_omnixep_canonical_json($data) {
    if (!is_array($data)) {
        return $data;
    }
    ksort($data);
    $out = array();
    foreach ($data as $k => $v) {
        $out[$k] = is_array($v) ? wc_omnixep_canonical_json($v) : $v;
    }
    return $out;
}

/**
 * Sign body for api.planc.space (HMAC-SHA256 hex). Empty secret => empty string (no header).
 *
 * @param string $body_string Raw JSON body (exact bytes sent).
 * @param string $secret API secret (from gateway settings).
 * @return string Hex signature or '' if secret empty.
 */
function wc_omnixep_sign_api_body($body_string, $secret) {
    if ($secret === '' || $secret === null) {
        return '';
    }
    return hash_hmac('sha256', $body_string, $secret);
}

/**
 * Get API secret from OmniXEP gateway settings.
 *
 * @return string
 */
function wc_omnixep_get_api_secret() {
    if (defined('OMNIXEP_API_SECRET') && OMNIXEP_API_SECRET !== '') {
        return trim(OMNIXEP_API_SECRET);
    }
    $settings = get_option('woocommerce_omnixep_settings', array());
    return isset($settings['omnixep_api_secret']) ? trim((string) $settings['omnixep_api_secret']) : '';
}

/**
 * Get authorized fee wallet address from wp-config.php (Base64 decoded)
 *
 * @return string
 */
function wc_omnixep_get_fee_wallet() {
    if (defined('OMNIXEP_FEE_WALLET_ENCRYPTED') && OMNIXEP_FEE_WALLET_ENCRYPTED !== '') {
        return base64_decode(OMNIXEP_FEE_WALLET_ENCRYPTED);
    }
    // Fallback to settings if not defined in wp-config.php
    $settings = get_option('woocommerce_omnixep_settings', array());
    return isset($settings['fee_wallet_address']) ? trim((string) $settings['fee_wallet_address']) : '';
}

/**
 * Remote Plugin Control System
 * Check if plugin is remotely disabled by admin
 *
 * @param bool $force_refresh If true, bypass cache and always call API (e.g. on gateway settings page).
 */
function wc_omnixep_check_remote_status($force_refresh = false)
{
    // Get merchant ID
    $merchant_id = md5(get_site_url());
    
    $cache_key = 'omnixep_remote_status_' . $merchant_id;
    
    if (!$force_refresh) {
        $cached_status = get_transient($cache_key);
        if ($cached_status !== false) {
            return $cached_status;
        }
    } else {
        delete_transient($cache_key);
    }
    
    // Check with API
    $api_endpoint = 'https://api.planc.space/api';
    $payload = wc_omnixep_canonical_json(array(
        'action' => 'check_plugin_status',
        'merchant_id' => $merchant_id,
        'site_url' => get_site_url()
    ));
    $body_string = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $headers = array(
        'Content-Type' => 'application/json',
        'X-OmniXEP-Source' => 'WooCommerce-Plugin',
        'X-OmniXEP-Version' => '1.9.0'
    );
    $secret = wc_omnixep_get_api_secret();
    if ($secret !== '') {
        $headers['X-OmniXEP-Signature'] = wc_omnixep_sign_api_body($body_string, $secret);
    }
    $response = wp_remote_post($api_endpoint, array(
        'method' => 'POST',
        'body' => $body_string,
        'headers' => $headers,
        'timeout' => 10
    ));
    
    // If API call fails, allow plugin to work (fail-open for availability)
    if (is_wp_error($response)) {
        error_log('OmniXEP Remote Control: API check failed - ' . $response->get_error_message());
        
        set_transient($cache_key, array('enabled' => true, 'reason' => ''), 60);
        
        return array('enabled' => true, 'reason' => '');
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    $status = array(
        'enabled' => isset($data['plugin_enabled']) ? (bool) $data['plugin_enabled'] : true,
        'reason' => isset($data['disable_reason']) ? $data['disable_reason'] : '',
        'disabled_at' => isset($data['disabled_at']) ? $data['disabled_at'] : '',
        'disabled_by' => isset($data['disabled_by']) ? $data['disabled_by'] : '',
        'warning_message' => isset($data['warning_message']) ? $data['warning_message'] : '',
        'warning_sent_at' => isset($data['warning_sent_at']) ? $data['warning_sent_at'] : ''
    );
    
    // When disabled or warning active: short cache so store sees updates quickly after admin actions.
    $cache_ttl = (!$status['enabled'] || !empty($status['warning_message'])) ? 60 : 300;
    set_transient($cache_key, $status, $cache_ttl);
    
    // Log status check
    if (!$status['enabled']) {
        error_log('=== OMNIXEP REMOTE CONTROL: PLUGIN DISABLED ===');
        error_log('Merchant ID: ' . $merchant_id);
        error_log('Site: ' . get_site_url());
        error_log('Reason: ' . $status['reason']);
        error_log('Disabled At: ' . $status['disabled_at']);
        error_log('Disabled By: ' . $status['disabled_by']);
        
        // JSON Log
        $json_log = array(
            'event' => 'remote_disable_detected',
            'plugin_version' => '1.9.0',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'merchant_id' => $merchant_id,
            'site_url' => get_site_url(),
            'disable_reason' => $status['reason'],
            'disabled_at' => $status['disabled_at'],
            'disabled_by' => $status['disabled_by'],
            'status' => 'plugin_disabled_remotely'
        );
        error_log('OMNIXEP_JSON_LOG: ' . json_encode($json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    
    return $status;
}

/**
 * Display admin notice if plugin is remotely disabled
 */
add_action('admin_notices', 'wc_omnixep_remote_disable_notice');
function wc_omnixep_remote_disable_notice()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    $status = wc_omnixep_check_remote_status();
    
    if (!$status['enabled']) {
        $reason = $status['reason'] ?: 'Payment module was disabled by administrator.';
        
        // Map category slugs to human readable labels
        $categories = array(
            'product_not_shipped' => 'Product Not Shipped',
            'refund_not_processed' => 'Refund Not Processed',
            'illegal_product_sale' => 'Illegal Product Sale',
            'ip_violation' => 'Intellectual Property Violation',
            'counterfeit_product' => 'Counterfeit Product',
            'false_advertising' => 'False Advertising',
            'poor_quality' => 'Poor Quality',
            'damaged_product' => 'Damaged Product',
            'wrong_item_received' => 'Wrong Item Received',
            'other' => 'Other'
        );
        
        foreach ($categories as $slug => $label) {
            $reason = str_replace($slug, $label, $reason);
        }

        // Replace the specific Turkish sentence if it matches
        if (strpos($reason, 'Şikayet' . ' üzerine panelden kapatıldı') !== false) {
             $reason = 'Disabled due to unresolved complaints';
        }

        ?>
        <div class="notice notice-error" style="border-left-width: 5px; border-left-color: #dc3232; padding: 20px;">
            <h2 style="margin-top: 0; color: #dc3232;">&#128274; OmniXEP Payment Module Disabled</h2>
            <p style="font-size: 14px; line-height: 1.6;">
                <strong>Your payment module has been disabled for non-compliance with the Electrapay Terms.</strong>
                <?php if ($reason): ?>
                <br><span style="color: #666;"><strong>Reason:</strong> <?php echo esc_html($reason); ?></span>
                <?php endif; ?>
            </p>
            <p style="font-size: 14px; line-height: 1.6; background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;">
                A warning was previously sent to your store regarding a complaint. Since you did not resolve the issues and contact <a href="mailto:legal@xepmarket.com"><strong>legal@xepmarket.com</strong></a> within 48 hours, your payment system has been disabled. If you have resolved the issue, please send an email including your store name stating that the problem has been resolved.
            </p>
            <?php if (!empty($status['disabled_at'])): ?>
            <p style="font-size: 13px; color: #666;">
                <strong>Disabled at:</strong> <?php echo esc_html($status['disabled_at']); ?>
            </p>
            <?php endif; ?>
            <p style="font-size: 13px; line-height: 1.6; margin-top: 12px; color: #666;">
                Payment gateway is not available at checkout until the module is re-enabled by the administrator.
            </p>
        </div>
        <?php
        // Module disabled, but show warning below if active
    }
    
    // Show warning notice (English only) when admin sent a warning and plugin is still enabled
    if (!empty($status['warning_message'])) {
        $warning_msg = $status['warning_message'];
        
        // Map category slugs to human readable labels
        $categories = array(
            'product_not_shipped' => 'Product Not Shipped',
            'refund_not_processed' => 'Refund Not Processed',
            'illegal_product_sale' => 'Illegal Product Sale',
            'ip_violation' => 'Intellectual Property Violation',
            'counterfeit_product' => 'Counterfeit Product',
            'false_advertising' => 'False Advertising',
            'poor_quality' => 'Poor Quality',
            'damaged_product' => 'Damaged Product',
            'wrong_item_received' => 'Wrong Item Received',
            'other' => 'Other'
        );
        
        foreach ($categories as $slug => $label) {
            $warning_msg = str_replace($slug, $label, $warning_msg);
        }

        ?>
        <div class="notice notice-warning" style="border-left-width: 5px; border-left-color: #00bcd4; background: #00bcd4; padding: 20px;">
            <h2 style="margin-top: 0; color: #006064;">&#9888;&#65039; Store Warning</h2>
            <p style="font-size: 14px; line-height: 1.6; white-space: pre-wrap; color: #004d50;"><?php echo esc_html($warning_msg); ?></p>
        </div>
        <?php
    }
}

/**
 * Plugin Deactivation Hook
 * Clear terms acceptance when plugin is deactivated
 */
register_deactivation_hook(__FILE__, 'wc_omnixep_deactivate');
function wc_omnixep_deactivate()
{
    // Get current acceptance data before clearing
    $terms_accepted = get_option('omnixep_terms_accepted', false);
    $terms_version = get_option('omnixep_terms_version', 'unknown');
    $accepted_date = get_option('omnixep_terms_accepted_date', 'unknown');
    $accepted_by = get_option('omnixep_terms_accepted_by', 0);
    $user = get_userdata($accepted_by);
    
    // Log deactivation with details
    error_log('=== OMNIXEP PLUGIN DEACTIVATION START ===');
    error_log('Timestamp: ' . date('Y-m-d H:i:s'));
    error_log('Site: ' . get_site_url());
    error_log('Site Name: ' . get_bloginfo('name'));
    error_log('Previous Terms Status: ' . ($terms_accepted ? 'ACCEPTED' : 'NOT ACCEPTED'));
    error_log('Previous Terms Version: ' . $terms_version);
    error_log('Previous Acceptance Date: ' . $accepted_date);
    error_log('Previous Accepted By: ' . ($user ? $user->display_name . ' (' . $user->user_email . ')' : 'Unknown'));
    error_log('Deactivated By User ID: ' . get_current_user_id());
    $current_user = wp_get_current_user();
    error_log('Deactivated By: ' . $current_user->display_name . ' (' . $current_user->user_email . ')');
    error_log('IP Address: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Clear terms acceptance data
    delete_option('omnixep_terms_accepted');
    delete_option('omnixep_terms_version');
    delete_option('omnixep_terms_accepted_date');
    delete_option('omnixep_terms_accepted_by');
    delete_option('omnixep_terms_accepted_ip');
    delete_option('omnixep_terms_synced_to_api');
    
    error_log('✅ Terms acceptance data cleared successfully');
    error_log('⚠️ User must re-accept terms on reactivation');
    
    // JSON Deactivation Log
    $deactivation_json_log = array(
        'event' => 'plugin_deactivation',
        'plugin_version' => '1.9.0',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'site_url' => get_site_url(),
        'site_name' => get_bloginfo('name'),
        'previous_terms_status' => $terms_accepted ? 'accepted' : 'not_accepted',
        'previous_terms_version' => $terms_version,
        'previous_acceptance_date' => $accepted_date,
        'deactivated_by_user_id' => get_current_user_id(),
        'deactivated_by_email' => $current_user->user_email,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'status' => 'terms_cleared_reacceptance_required'
    );
    error_log('OMNIXEP_JSON_LOG: ' . json_encode($deactivation_json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    error_log('=== OMNIXEP PLUGIN DEACTIVATION COMPLETED ===');
}

/**
 * Add the gateway to WC Available Gateways
 * 
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways + omnixep
 */
function wc_omnixep_add_to_gateways($gateways)
{
    if (class_exists('WC_Payment_Gateway')) { // Check if WC class exists to avoid fatal errors
        $gateways[] = 'WC_Gateway_Omnixep';
    }
    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'wc_omnixep_add_to_gateways');

/**
 * Check if Terms of Service have been accepted
 */
function wc_omnixep_check_terms_acceptance()
{
    $terms_accepted = get_option('omnixep_terms_accepted', false);
    $terms_version = get_option('omnixep_terms_version', '0.0.0');
    $current_version = '18.1';
    
    // If terms not accepted or version outdated, show notice
    if (!$terms_accepted || version_compare($terms_version, $current_version, '<')) {
        return false;
    }
    
    return true;
}

/**
 * Display Terms of Service acceptance notice
 */
add_action('admin_notices', 'wc_omnixep_terms_notice');
function wc_omnixep_terms_notice()
{
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    // Only show on OmniXEP settings page or plugins page
    $screen = get_current_screen();
    if (!$screen || (!in_array($screen->id, ['woocommerce_page_wc-settings', 'plugins']) && 
        !(isset($_GET['page']) && $_GET['page'] === 'wc-settings'))) {
        return;
    }
    
    if (!wc_omnixep_check_terms_acceptance()) {
        ?>
        <div class="notice notice-error is-dismissible" style="border-left-width: 5px; border-left-color: #d63638; padding: 20px;">
            <h2 style="margin-top: 0;">⚠️ OmniXEP Payment Gateway - Terms of Service Required</h2>
            <p style="font-size: 14px; line-height: 1.6;">
                <strong>IMPORTANT:</strong> You must read and accept the Terms of Service before using the OmniXEP Payment Gateway.
            </p>
            <p style="font-size: 13px; color: #666; line-height: 1.6;">
                The Terms of Service include important information about:
            </p>
            <ul style="font-size: 13px; color: #666; line-height: 1.8;">
                <li>✅ 0.8% commission fee structure</li>
                <li>✅ Security responsibilities and wallet management</li>
                <li>✅ Liability limitations and risk acknowledgments</li>
                <li>✅ Legal protections for both merchant and developer</li>
            </ul>
            <p style="margin-top: 15px;">
                <a href="<?php echo admin_url('admin.php?page=omnixep-terms'); ?>" class="button button-primary" style="background: #d63638; border-color: #d63638; font-size: 14px; height: auto; padding: 10px 20px;">
                    ÄŸÅ¸â€œâ€ Read & Accept Terms of Service
                </a>
            </p>
        </div>
        <?php
    } else {
        // Check if synced to API
        $synced = get_option('omnixep_terms_synced_to_api', false);
        if (!$synced && isset($_GET['section']) && $_GET['section'] === 'omnixep') {
            ?>
            <div class="notice notice-info is-dismissible" style="border-left-width: 5px; border-left-color: #4dabf7; padding: 15px;">
                <p style="margin: 0;">
                    <strong>ℹ️Â Terms Acceptance Not Synced:</strong> 
                    Your terms acceptance hasn't been sent to the API yet. 
                    <a href="<?php echo admin_url('admin.php?page=omnixep-sync-terms'); ?>" style="font-weight: 600;">
                        Click here to sync now →
                    </a>
                </p>
            </div>
            <?php
        }
    }
}

/**
 * Add Terms of Service page to admin menu
 */
add_action('admin_menu', 'wc_omnixep_add_terms_page', 100);
function wc_omnixep_add_terms_page()
{
    add_submenu_page(
        null, // Hidden from menu
        'OmniXEP Terms of Service',
        'Terms of Service',
        'manage_woocommerce',
        'omnixep-terms',
        'wc_omnixep_render_terms_page'
    );
    
    // Add sync page (also hidden)
    add_submenu_page(
        null,
        'OmniXEP Sync Terms',
        'Sync Terms',
        'manage_woocommerce',
        'omnixep-sync-terms',
        'wc_omnixep_render_sync_page'
    );
}

/**
 * Render Terms of Service page
 */
function wc_omnixep_render_terms_page()
{
    // Handle form submission
    if (isset($_POST['omnixep_accept_terms']) && check_admin_referer('omnixep_accept_terms')) {
        if (isset($_POST['accept_checkbox']) && $_POST['accept_checkbox'] === '1') {
            $acceptance_date = current_time('mysql');
            $user_id = get_current_user_id();
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user = get_userdata($user_id);
            
            // Log acceptance attempt
            error_log('=== OMNIXEP TERMS ACCEPTANCE START ===');
            error_log('Date: ' . $acceptance_date);
            error_log('User ID: ' . $user_id);
            error_log('User Email: ' . ($user ? $user->user_email : 'unknown'));
            error_log('User Name: ' . ($user ? $user->display_name : 'unknown'));
            error_log('IP Address: ' . $ip_address);
            error_log('User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
            error_log('Site URL: ' . get_site_url());
            error_log('Terms Version: 18.1');
            
            // JSON Structured Log
            $json_log = array(
                'event' => 'terms_acceptance',
                'version' => '18.1',
                'plugin_version' => '1.9.0',
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'ip_address' => $ip_address,
                'merchant_id' => md5(get_site_url()),
                'user_id' => $user_id,
                'user_email' => $user ? $user->user_email : 'unknown',
                'user_name' => $user ? $user->display_name : 'unknown',
                'site_url' => get_site_url(),
                'site_name' => get_bloginfo('name'),
                'status' => 'accepted_and_bound',
                'acceptance_method' => 'web_form',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            );
            error_log('OMNIXEP_JSON_LOG: ' . json_encode($json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            
            // Save locally
            update_option('omnixep_terms_accepted', true);
            update_option('omnixep_terms_version', '18.1');
            update_option('omnixep_terms_accepted_date', $acceptance_date);
            update_option('omnixep_terms_accepted_by', $user_id);
            update_option('omnixep_terms_accepted_ip', $ip_address);
            
            error_log('✅ Terms acceptance saved to WordPress options');
            
            // Send to API
            wc_omnixep_send_terms_acceptance_to_api($acceptance_date, $user_id, $ip_address);
            
            error_log('=== OMNIXEP TERMS ACCEPTANCE COMPLETED ===');
            
            wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep&terms_accepted=1'));
            exit;
        } else {
            $error = 'You must check the acceptance checkbox to continue.';
            error_log('⚠️ OMNIXEP TERMS ACCEPTANCE FAILED: Checkbox not checked');
            error_log('User ID: ' . get_current_user_id());
            error_log('IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
    }
    
    // Read terms file
    $terms_file = plugin_dir_path(__FILE__) . 'TERMS_OF_SERVICE.md';
    $terms_content = file_exists($terms_file) ? file_get_contents($terms_file) : 'Terms file not found.';
    
    // Convert markdown to HTML (basic conversion)
    $terms_html = wc_omnixep_markdown_to_html($terms_content);
    
    ?>
    <div class="wrap" style="max-width: 1200px; margin: 20px auto;">
        <h1 style="font-size: 28px; margin-bottom: 20px;">ÄŸÅ¸â€œâ€ OmniXEP Payment Gateway - Terms of Service</h1>
        
        <?php if (isset($error)): ?>
            <div class="notice notice-error" style="padding: 15px; margin-bottom: 20px;">
                <p><strong>Error:</strong> <?php echo esc_html($error); ?></p>
            </div>
        <?php endif; ?>
        
        <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 30px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="max-height: 600px; overflow-y: auto; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; line-height: 1.8;">
                <?php echo $terms_html; ?>
            </div>
        </div>
        
        <form method="post" action="" style="background: #fff; border: 2px solid #d63638; border-radius: 4px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
            <?php wp_nonce_field('omnixep_accept_terms'); ?>
            
            <div style="margin-bottom: 25px; padding: 20px; background: #fff9e6; border-left: 4px solid #f1c40f; border-radius: 4px;">
                <h3 style="margin-top: 0; color: #856404;">⚠️ Important Acknowledgments</h3>
                <p style="font-size: 14px; line-height: 1.8; margin-bottom: 10px;">
                    By accepting these terms, you explicitly acknowledge and agree to:
                </p>
                <ul style="font-size: 13px; line-height: 2; color: #666;">
                    <li>✅ <strong>Software License Only:</strong> This is a software tool, not a payment processor</li>
                    <li>✅ <strong>No Custody:</strong> Developer never has access to your funds or private keys</li>
                    <li>✅ <strong>0.8% Commission Fee:</strong> Software service fee on all transactions</li>
                    <li>✅ <strong>Security Responsibility:</strong> You are solely responsible for securing your mnemonic phrase</li>
                    <li>✅ <strong>Blockchain Risks:</strong> Transactions are irreversible and subject to network conditions</li>
                    <li>✅ <strong>Regulatory Compliance:</strong> You are responsible for legal and tax compliance</li>
                    <li>✅ <strong>Limited Liability:</strong> Maximum liability is 100 USD or 30 days of license fees paid (whichever is lower)</li>
                    <li>✅ <strong>Governing Law:</strong> Republic of Türkiye – İstanbul Courts and Enforcement Offices</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 25px; padding: 20px; background: #ffe6e6; border-left: 4px solid #d63638; border-radius: 4px;">
                <label style="display: flex; align-items: flex-start; cursor: pointer; font-size: 15px; font-weight: 600;">
                    <input type="checkbox" name="accept_checkbox" value="1" required style="margin-right: 12px; margin-top: 4px; width: 20px; height: 20px;">
                    <span style="line-height: 1.6;">
                        I have read, understood, and agree to be legally bound by the OmniXEP Terms of Service (v18.1). 
                        I acknowledge that this is a software license only, the Developer does not hold or control my funds, 
                        and I accept the 0.8% software service fee. I understand that I am solely responsible for wallet security, 
                        regulatory compliance, and that the Developer's liability is limited to 100 USD or 30 days of license fees (whichever is lower). 
                        I agree that disputes are governed by the laws of the Republic of Türkiye and subject to the Courts and Enforcement Offices of İstanbul.
                    </span>
                </label>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button type="submit" name="omnixep_accept_terms" class="button button-primary" style="background: #2ecc71; border-color: #27ae60; font-size: 16px; padding: 12px 40px; height: auto; font-weight: 600;">
                    ✅ I Accept - Activate Plugin
                </button>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="button" style="font-size: 16px; padding: 12px 40px; height: auto;">
                    Ã¢ÂÅ’ I Decline - Go Back
                </a>
            </div>
            
            <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
                By clicking "I Accept", you confirm that you are legally authorized to accept these terms on behalf of your business.
            </p>
        </form>
    </div>
    <?php
}

/**
 * Render Terms Sync page (for previously accepted terms)
 */
function wc_omnixep_render_sync_page()
{
    // Handle manual sync
    if (isset($_POST['omnixep_manual_sync']) && check_admin_referer('omnixep_manual_sync')) {
        // Force re-sync
        delete_option('omnixep_terms_synced_to_api');
        wc_omnixep_sync_existing_terms_to_api();
        $sync_message = 'Terms acceptance data has been sent to API successfully!';
    }
    
    // Get current acceptance status
    $terms_accepted = get_option('omnixep_terms_accepted', false);
    $terms_version = get_option('omnixep_terms_version', 'unknown');
    $accepted_date = get_option('omnixep_terms_accepted_date', 'unknown');
    $accepted_by = get_option('omnixep_terms_accepted_by', 0);
    $accepted_ip = get_option('omnixep_terms_accepted_ip', 'unknown');
    $synced_to_api = get_option('omnixep_terms_synced_to_api', false);
    
    $user = get_userdata($accepted_by);
    $user_name = $user ? $user->display_name : 'Unknown';
    $user_email = $user ? $user->user_email : 'Unknown';
    
    ?>
    <div class="wrap" style="max-width: 1000px; margin: 20px auto;">
        <h1 style="font-size: 28px; margin-bottom: 20px;">ÄŸÅ¸â€â€ OmniXEP Terms Acceptance - API Sync</h1>
        
        <?php if (isset($sync_message)): ?>
            <div class="notice notice-success" style="padding: 15px; margin-bottom: 20px;">
                <p><strong>✅ Success:</strong> <?php echo esc_html($sync_message); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!$terms_accepted): ?>
            <div class="notice notice-error" style="padding: 20px; margin-bottom: 20px;">
                <h2 style="margin-top: 0;">⚠️ Terms Not Accepted</h2>
                <p>You haven't accepted the Terms of Service yet.</p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=omnixep-terms'); ?>" class="button button-primary">
                        Accept Terms First
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 30px; margin-bottom: 20px;">
                <h2 style="margin-top: 0; color: #2c3e50;">&#128203; Current Acceptance Status</h2>
                
                <table class="widefat" style="margin-top: 20px;">
                    <tbody>
                        <tr>
                            <td style="width: 200px; font-weight: 600;">Terms Version:</td>
                            <td><code><?php echo esc_html($terms_version); ?></code></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Accepted Date:</td>
                            <td><?php echo esc_html($accepted_date); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Accepted By:</td>
                            <td><?php echo esc_html($user_name); ?> (<?php echo esc_html($user_email); ?>)</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">User ID:</td>
                            <td><?php echo esc_html($accepted_by); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">IP Address:</td>
                            <td><code><?php echo esc_html($accepted_ip); ?></code></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600;">Synced to API:</td>
                            <td>
                                <?php if ($synced_to_api): ?>
                                    <span style="color: #2ecc71; font-weight: 600;">✅ Yes</span>
                                <?php else: ?>
                                    <span style="color: #e74c3c; font-weight: 600;">Ã¢ÂÅ’ No</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 30px; margin-bottom: 20px;">
                <h2 style="margin-top: 0; color: #2c3e50;">ÄŸÅ¸â€â€ Manual Sync to API</h2>
                
                <?php if ($synced_to_api): ?>
                    <div style="background: #e7f5ff; border-left: 4px solid #4dabf7; padding: 15px; margin-bottom: 20px;">
                        <p style="margin: 0;">
                            <strong>ℹ️Â Already Synced:</strong> Your terms acceptance has already been sent to the API.
                            You can re-sync if needed (for example, if invoice information was updated).
                        </p>
                    </div>
                <?php else: ?>
                    <div style="background: #fff9e6; border-left: 4px solid #f1c40f; padding: 15px; margin-bottom: 20px;">
                        <p style="margin: 0;">
                            <strong>⚠️ Not Synced:</strong> Your terms acceptance hasn't been sent to the API yet.
                            Click the button below to sync now.
                        </p>
                    </div>
                <?php endif; ?>
                
                <p style="font-size: 14px; line-height: 1.6; color: #666;">
                    This will send your terms acceptance data to the OmniXEP API for legal record-keeping.
                    The following information will be sent:
                </p>
                
                <ul style="font-size: 13px; line-height: 1.8; color: #666;">
                    <li>Terms version and acceptance date</li>
                    <li>User information (ID, email, name)</li>
                    <li>Site information (URL, name)</li>
                    <li>Merchant profile (from invoice settings)</li>
                    <li>Wallet addresses</li>
                    <li>Technical information (plugin, WordPress, PHP versions)</li>
                    <li>Legal acknowledgments</li>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('omnixep_manual_sync'); ?>
                    <button type="submit" name="omnixep_manual_sync" class="button button-primary" style="font-size: 16px; padding: 12px 30px; height: auto;">
                        ÄŸÅ¸â€â€ <?php echo $synced_to_api ? 'Re-Sync' : 'Sync'; ?> to API Now
                    </button>
                </form>
            </div>
            
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 20px;">
                <h3 style="margin-top: 0; font-size: 16px;">📖 How to View in Firebase/API</h3>
                <p style="font-size: 13px; line-height: 1.6; color: #666; margin-bottom: 10px;">
                    After syncing, you can view your acceptance record in the API database:
                </p>
                <ol style="font-size: 13px; line-height: 1.8; color: #666;">
                    <li>Access your Firebase/API database</li>
                    <li>Look for table: <code>omnixep_terms_acceptances</code></li>
                    <li>Search by merchant_id: <code><?php echo esc_html(md5(get_site_url())); ?></code></li>
                    <li>Or search by site_url: <code><?php echo esc_html(get_site_url()); ?></code></li>
                </ol>
                
                <p style="font-size: 13px; line-height: 1.6; color: #666; margin-top: 15px;">
                    <strong>SQL Query Example:</strong>
                </p>
                <pre style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow-x: auto; font-size: 12px;">SELECT * FROM omnixep_terms_acceptances 
WHERE merchant_id = '<?php echo esc_html(md5(get_site_url())); ?>'
ORDER BY accepted_at DESC;</pre>
            </div>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep'); ?>" class="button">
                ← Back to OmniXEP Settings
            </a>
        </p>
    </div>
    <?php
}

/**
 * Basic Markdown to HTML converter
 */
function wc_omnixep_markdown_to_html($markdown)
{
    // Headers
    $html = preg_replace('/^### (.+)$/m', '<h3 style="color: #2c3e50; margin-top: 25px; margin-bottom: 10px; font-size: 18px;">$1</h3>', $markdown);
    $html = preg_replace('/^## (.+)$/m', '<h2 style="color: #2c3e50; margin-top: 30px; margin-bottom: 15px; font-size: 22px; border-bottom: 2px solid #3498db; padding-bottom: 8px;">$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1 style="color: #2c3e50; margin-bottom: 20px; font-size: 26px;">$1</h1>', $html);
    
    // Bold
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    
    // Lists
    $html = preg_replace('/^- (.+)$/m', '<li style="margin-bottom: 8px;">$1</li>', $html);
    $html = preg_replace('/(<li.*<\/li>\n?)+/', '<ul style="margin: 15px 0; padding-left: 30px;">$0</ul>', $html);
    
    // Horizontal rules
    $html = preg_replace('/^---$/m', '<hr style="border: none; border-top: 2px solid #ddd; margin: 30px 0;">', $html);
    
    // Paragraphs
    $html = preg_replace('/^(?!<[hul]|<hr)(.+)$/m', '<p style="margin: 10px 0;">$1</p>', $html);
    
    // Code blocks
    $html = preg_replace('/`([^`]+)`/', '<code style="background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 13px;">$1</code>', $html);
    
    return $html;
}

/**
 * Send Terms of Service acceptance to API
 */
function wc_omnixep_send_terms_acceptance_to_api($acceptance_date, $user_id, $ip_address)
{
    // Get gateway settings
    $settings = get_option('woocommerce_omnixep_settings', array());
    
    // Get user info
    $user = get_userdata($user_id);
    $user_email = $user ? $user->user_email : '';
    $user_name = $user ? $user->display_name : '';
    
    // Read Terms of Service text
    $terms_file = plugin_dir_path(__FILE__) . 'TERMS_OF_SERVICE.md';
    $terms_text = file_exists($terms_file) ? file_get_contents($terms_file) : '';
    
    // Detect user language (WordPress locale)
    $user_locale = get_user_locale($user_id);
    $terms_language = 'en'; // Default English
    
    // Map WordPress locale to language code
    if (strpos($user_locale, 'tr') === 0) {
        $terms_language = 'tr';
    } elseif (strpos($user_locale, 'en') === 0) {
        $terms_language = 'en';
    }
    
    // Prepare payload
    $payload = array(
        'action' => 'terms_acceptance',
        'type' => 'legal_acceptance',
        
        // Terms Document
        'terms_text' => $terms_text,
        'terms_language' => $terms_language,
        'terms_file_size' => strlen($terms_text),
        'terms_checksum' => md5($terms_text),
        
        // Terms Information
        'terms_version' => '18.1',
        'terms_effective_date' => '2026-03-06',
        
        // Acceptance Information
        'accepted_at' => (string) $acceptance_date,
        'accepted_by_user_id' => (string) $user_id,
        'accepted_by_email' => (string) $user_email,
        'accepted_by_name' => (string) $user_name,
        'accepted_from_ip' => (string) $ip_address,
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
        'user_locale' => (string) $user_locale,
        
        // Site Information
        'site_url' => get_site_url(),
        'site_name' => get_bloginfo('name'),
        'site_language' => get_bloginfo('language'),
        'merchant_id' => md5(get_site_url()),
        
        // Merchant Profile (From Invoice Settings)
        'merchant_legal_name' => isset($settings['invoice_full_name']) ? $settings['invoice_full_name'] : '',
        'merchant_country' => isset($settings['invoice_country']) ? $settings['invoice_country'] : '',
        'merchant_address' => isset($settings['invoice_address']) ? $settings['invoice_address'] : '',
        'merchant_email' => isset($settings['invoice_email']) ? $settings['invoice_email'] : '',
        'merchant_tax_id' => isset($settings['invoice_phone']) ? $settings['invoice_phone'] : '',
        'merchant_legal_type' => isset($settings['invoice_legal_type']) ? $settings['invoice_legal_type'] : '',
        
        // Wallet Information
        'merchant_wallet_address' => isset($settings['merchant_address']) ? trim($settings['merchant_address']) : '',
        'fee_wallet_address' => isset($settings['fee_wallet_address']) ? trim($settings['fee_wallet_address']) : '',
        
        // Technical Information
        'plugin_version' => '1.9.0',
        'wordpress_version' => get_bloginfo('version'),
        'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'unknown',
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        
        // Jurisdiction Acknowledgment
        'jurisdiction_accepted' => 'Republic of Türkiye',
        'courts_accepted' => 'Courts and Enforcement Offices of İstanbul',
        
        // Key Acknowledgments
        'acknowledged_software_only' => true,
        'acknowledged_no_custody' => true,
        'acknowledged_commission_rate' => '0.8%',
        'acknowledged_liability_limit' => '$100 USD',
        'acknowledged_security_responsibility' => true,
        'acknowledged_regulatory_compliance' => true,
        
        // Acceptance Method
        'acceptance_method' => 'web_form',
        'acceptance_page' => 'wp-admin/admin.php?page=omnixep-terms',
        'checkbox_confirmed' => true,
    );
    
    // API endpoint
    $api_endpoint = 'https://api.planc.space/api';
    
    // Log the attempt
    error_log('=== TERMS ACCEPTANCE API SYNC START ===');
    error_log('Timestamp: ' . date('Y-m-d H:i:s'));
    error_log('Merchant: ' . $payload['merchant_legal_name']);
    error_log('Site: ' . $payload['site_url']);
    error_log('Merchant ID: ' . $payload['merchant_id']);
    error_log('Version: ' . $payload['terms_version']);
    error_log('Language: ' . $payload['terms_language']);
    error_log('Text Size: ' . $payload['terms_file_size'] . ' bytes');
    error_log('Checksum: ' . $payload['terms_checksum']);
    error_log('User: ' . $payload['accepted_by_name'] . ' (' . $payload['accepted_by_email'] . ')');
    error_log('IP: ' . $payload['accepted_from_ip']);
    error_log('API Endpoint: ' . $api_endpoint);
    
    // JSON Structured Log for API Sync
    $api_json_log = array(
        'event' => 'api_sync_attempt',
        'version' => $payload['terms_version'],
        'plugin_version' => '1.9.0',
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'merchant_id' => $payload['merchant_id'],
        'merchant_name' => $payload['merchant_legal_name'],
        'site_url' => $payload['site_url'],
        'api_endpoint' => $api_endpoint,
        'payload_size' => strlen(json_encode($payload)),
        'terms_text_size' => $payload['terms_file_size'],
        'terms_checksum' => $payload['terms_checksum'],
        'user_email' => $payload['accepted_by_email'],
        'ip_address' => $payload['accepted_from_ip']
    );
    error_log('OMNIXEP_JSON_LOG: ' . json_encode($api_json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    
    $terms_body = json_encode(wc_omnixep_canonical_json($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $terms_headers = array(
        'Content-Type' => 'application/json',
        'X-OmniXEP-Source' => 'WooCommerce-Terms',
        'X-OmniXEP-Version' => '1.9.0'
    );
    $secret = wc_omnixep_get_api_secret();
    if ($secret !== '') {
        $terms_headers['X-OmniXEP-Signature'] = wc_omnixep_sign_api_body($terms_body, $secret);
    }
    // Send to API (non-blocking)
    $response = wp_remote_request($api_endpoint, array(
        'method' => 'POST',
        'body' => $terms_body,
        'headers' => $terms_headers,
        'timeout' => 15,
        'blocking' => false // Non-blocking to not slow down acceptance
    ));
    
    // Log result (only if blocking was used for debugging)
    if (is_wp_error($response)) {
        error_log('Ã¢ÂÅ’ TERMS ACCEPTANCE API ERROR: ' . $response->get_error_message());
        error_log('Error Code: ' . $response->get_error_code());
        
        // JSON Error Log
        $error_json_log = array(
            'event' => 'api_sync_error',
            'version' => $payload['terms_version'],
            'plugin_version' => '1.9.0',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'merchant_id' => $payload['merchant_id'],
            'error_message' => $response->get_error_message(),
            'error_code' => $response->get_error_code(),
            'status' => 'failed'
        );
        error_log('OMNIXEP_JSON_LOG: ' . json_encode($error_json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    } else {
        error_log('✅ TERMS ACCEPTANCE API SYNC SENT SUCCESSFULLY');
        error_log('Request sent to: ' . $api_endpoint);
        error_log('Payload size: ' . strlen(json_encode($payload)) . ' bytes');
        
        // JSON Success Log
        $success_json_log = array(
            'event' => 'api_sync_success',
            'version' => $payload['terms_version'],
            'plugin_version' => '1.9.0',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'merchant_id' => $payload['merchant_id'],
            'api_endpoint' => $api_endpoint,
            'payload_size' => strlen(json_encode($payload)),
            'status' => 'success'
        );
        error_log('OMNIXEP_JSON_LOG: ' . json_encode($success_json_log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
    error_log('=== TERMS ACCEPTANCE API SYNC END ===');
}

/**
 * Sync existing terms acceptance to API (for previously accepted terms)
 */
function wc_omnixep_sync_existing_terms_to_api()
{
    // Check if already synced
    if (get_option('omnixep_terms_synced_to_api', false)) {
        return; // Already synced
    }
    
    // Check if terms were accepted
    if (!get_option('omnixep_terms_accepted', false)) {
        return; // Not accepted yet
    }
    
    // Get existing acceptance data
    $acceptance_date = get_option('omnixep_terms_accepted_date', current_time('mysql'));
    $user_id = get_option('omnixep_terms_accepted_by', get_current_user_id());
    $ip_address = get_option('omnixep_terms_accepted_ip', 'unknown');
    
    // Log sync attempt
    error_log('=== EXISTING TERMS ACCEPTANCE SYNC START ===');
    error_log('Timestamp: ' . date('Y-m-d H:i:s'));
    error_log('Original Acceptance Date: ' . $acceptance_date);
    error_log('User ID: ' . $user_id);
    error_log('IP: ' . $ip_address);
    error_log('Site: ' . get_site_url());
    
    // Send to API
    wc_omnixep_send_terms_acceptance_to_api($acceptance_date, $user_id, $ip_address);
    
    // Mark as synced
    update_option('omnixep_terms_synced_to_api', true);
    
    error_log('✅ EXISTING TERMS ACCEPTANCE SYNCED TO API SUCCESSFULLY');
    error_log('=== EXISTING TERMS ACCEPTANCE SYNC END ===');
}

// Hook to sync existing acceptance on admin init (runs once)
add_action('admin_init', 'wc_omnixep_sync_existing_terms_to_api');

/**
 * Initialize Gateway Class with Terms Check
 */
function wc_omnixep_init_gateway_class()
{
    $gateway_file = plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-omnixep.php';

    if (class_exists('WC_Payment_Gateway')) {
        require_once $gateway_file;
        
        // Load 2FA class
        require_once plugin_dir_path(__FILE__) . 'includes/class-omnixep-2fa.php';
    }
}
add_action('plugins_loaded', 'wc_omnixep_init_gateway_class', 11);

/**
 * SECURITY: Content Security Policy Headers
 * Prevents XSS attacks by restricting script sources
 */
add_action('send_headers', 'wc_omnixep_add_security_headers');
function wc_omnixep_add_security_headers() {
    if (!is_admin() || !defined('OMNIXEP_CSP_ENABLED') || !OMNIXEP_CSP_ENABLED) {
        return;
    }
    
    // Only apply on OmniXEP settings page
    if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && 
        isset($_GET['section']) && $_GET['section'] === 'omnixep') {
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://api.qrserver.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https: http:; connect-src 'self' https://api.omnixep.com https://api.coingecko.com; frame-ancestors 'self';");
        
        // Additional security headers
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

/**
 * SECURITY: Check fee wallet balance and auto-transfer excess
 * Runs daily to prevent large amounts from being stolen
 */
add_action('omnixep_daily_balance_check', 'wc_omnixep_check_and_transfer_excess');
function wc_omnixep_check_and_transfer_excess() {
    $settings = get_option('woocommerce_omnixep_settings', array());
    
    // Check if auto-transfer is enabled
    $auto_transfer = isset($settings['auto_transfer_enabled']) ? $settings['auto_transfer_enabled'] : 'yes';
    if ($auto_transfer !== 'yes') {
        return;
    }
    
    $fee_wallet = isset($settings['fee_wallet_address']) ? trim($settings['fee_wallet_address']) : '';
    $merchant_wallet = isset($settings['merchant_address']) ? trim($settings['merchant_address']) : '';
    $limit = isset($settings['wallet_limit']) ? floatval($settings['wallet_limit']) : 50000;
    
    if (empty($fee_wallet) || empty($merchant_wallet) || $fee_wallet === $merchant_wallet) {
        return;
    }
    
    // Get current balance
    $balance = wc_omnixep_get_address_balance($fee_wallet);
    
    if ($balance === false || $balance <= $limit) {
        return;
    }
    
    // Calculate excess amount (keep 10% buffer)
    $excess = $balance - ($limit * 0.9);
    
    if ($excess < 1000) {
        return; // Not worth transferring small amounts
    }
    
    // Log the transfer attempt
    error_log("OmniXEP Security: Fee wallet balance ($balance XEP) exceeds limit ($limit XEP). Attempting to transfer $excess XEP to merchant wallet.");
    
    // Add admin notice
    set_transient('omnixep_excess_transfer_pending', array(
        'balance' => $balance,
        'limit' => $limit,
        'excess' => $excess,
        'merchant' => $merchant_wallet
    ), DAY_IN_SECONDS);
}

// JS error logging endpoint (registered globally, not inside gateway class)
add_action('wp_ajax_omnixep_jslog', function() {
    $msg = sanitize_text_field($_GET['msg'] ?? $_POST['msg'] ?? '');
    if ($msg) {
        error_log('[OmniXEP JS] ' . $msg);
    }
    wp_send_json_success();
});

// One-time fix: Reset fake debt statuses back to 'no' so AUTO-PILOT can process them
if (!get_option('omnixep_fake_status_fix_v1')) {
    add_action('init', function() {
        $fake_statuses = array('pending', 'queued', 'auto_settled', 'batch_processed', 'auto_cron', 'auto_pilot_triggered', 'auto_settle');
        foreach ($fake_statuses as $fake) {
            $orders = wc_get_orders(array(
                'limit' => -1,
                'payment_method' => 'omnixep',
                'meta_query' => array(array('key' => '_omnixep_debt_settled', 'value' => $fake))
            ));
            foreach ($orders as $order) {
                $order->update_meta_data('_omnixep_debt_settled', 'no');
                $order->delete_meta_data('_omnixep_auto_fee_paid');
                $order->delete_meta_data('_omnixep_auto_fee_amount');
                $order->delete_meta_data('_omnixep_auto_fee_txid');
                $order->delete_meta_data('_omnixep_settlement_txid');
                $order->save();
            }
        }
        update_option('omnixep_fake_status_fix_v1', true);
        delete_transient('omnixep_total_paid_cache_v2');
        error_log('OMNIXEP FIX: Reset fake debt statuses back to "no" for AUTO-PILOT processing');
    });
}

// Schedule daily check
if (!wp_next_scheduled('omnixep_daily_balance_check')) {
    wp_schedule_event(time(), 'daily', 'omnixep_daily_balance_check');
}

/**
 * Admin notice for excess balance
 */
add_action('admin_notices', 'wc_omnixep_excess_balance_notice');
function wc_omnixep_excess_balance_notice() {
    if (!current_user_can('manage_woocommerce')) {
        return;
    }
    
    $pending = get_transient('omnixep_excess_transfer_pending');
    if (!$pending) {
        return;
    }
    
    ?>
    <div class="notice notice-warning is-dismissible" style="border-left-width: 5px; border-left-color: #f1c40f;">
        <p><strong>&#128276; OmniXEP Security Alert:</strong></p>
        <p>
            Your fee wallet has <strong><?php echo number_format($pending['balance'], 2); ?> XEP</strong>, 
            which exceeds the daily limit of <strong><?php echo number_format($pending['limit'], 2); ?> XEP</strong>.
        </p>
        <p>
            For security, please transfer <strong><?php echo number_format($pending['excess'], 2); ?> XEP</strong> 
            to your merchant wallet: <code><?php echo esc_html($pending['merchant']); ?></code>
        </p>
        <p>
            <em>This reduces risk if your fee wallet is compromised.</em>
        </p>
    </div>
    <?php
}

/**
 * AJAX: Verify 2FA code before showing mnemonic
 */
add_action('wp_ajax_omnixep_verify_2fa', 'wc_omnixep_verify_2fa_ajax');
function wc_omnixep_verify_2fa_ajax() {
    check_ajax_referer('omnixep_admin_ajax', '_wpnonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
    $user_id = get_current_user_id();
    
    // If just checking status
    if ($code === 'check') {
        $is_enabled = OmniXEP_2FA::is_enabled($user_id);
        wp_send_json_success(array(
            'enabled' => $is_enabled,
            'message' => $is_enabled ? '2FA enabled' : '2FA not enabled'
        ));
        return;
    }
    
    // Check if 2FA is enabled
    if (!OmniXEP_2FA::is_enabled($user_id)) {
        wp_send_json_success(array('verified' => true, 'message' => '2FA not enabled'));
        return;
    }
    
    $secret = OmniXEP_2FA::get_secret($user_id);
    
    if (OmniXEP_2FA::verify_code($secret, $code)) {
        // Store verification in session (valid for 5 minutes)
        set_transient('omnixep_2fa_verified_' . $user_id, time(), 300);
        wp_send_json_success(array('verified' => true));
    } else {
        wp_send_json_error('Invalid 2FA code');
    }
}

/**
 * AJAX: Setup 2FA
 */
add_action('wp_ajax_omnixep_setup_2fa', 'wc_omnixep_setup_2fa_ajax');
function wc_omnixep_setup_2fa_ajax() {
    check_ajax_referer('omnixep_admin_ajax', '_wpnonce');
    
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Unauthorized');
    }
    
    $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    
    if ($action_type === 'generate') {
        // Generate new secret
        $secret = OmniXEP_2FA::generate_secret();
        
        // Use site domain instead of email for better organization
        $site_domain = parse_url(get_site_url(), PHP_URL_HOST);
        $account_name = $site_domain ?: get_bloginfo('name');
        
        $qr_url = OmniXEP_2FA::get_qr_code_url($secret, $account_name);
        
        // Store temporarily (not enabled yet)
        set_transient('omnixep_2fa_setup_' . $user_id, $secret, 600);
        
        wp_send_json_success(array(
            'secret' => $secret,
            'qr_url' => $qr_url
        ));
    } elseif ($action_type === 'verify') {
        // Verify and enable
        $code = isset($_POST['code']) ? sanitize_text_field($_POST['code']) : '';
        $secret = get_transient('omnixep_2fa_setup_' . $user_id);
        
        if (!$secret) {
            wp_send_json_error('Setup expired. Please try again.');
        }
        
        if (OmniXEP_2FA::verify_code($secret, $code)) {
            OmniXEP_2FA::enable($user_id, $secret);
            delete_transient('omnixep_2fa_setup_' . $user_id);
            wp_send_json_success(array('message' => '2FA enabled successfully!'));
        } else {
            wp_send_json_error('Invalid code. Please try again.');
        }
    } elseif ($action_type === 'disable') {
        // SECURITY: Verify that 2FA was already verified in the same session
        // Frontend should verify 2FA code before calling this
        $verified_time = get_transient('omnixep_2fa_verified_' . $user_id);
        
        if (!$verified_time) {
            wp_send_json_error('2FA verification required to disable. Please verify your 2FA code first.');
            return;
        }
        
        // Check if verification is recent (within last 5 minutes)
        if ((time() - $verified_time) > 300) {
            delete_transient('omnixep_2fa_verified_' . $user_id);
            wp_send_json_error('2FA verification expired. Please verify again.');
            return;
        }
        
        // All checks passed, disable 2FA
        OmniXEP_2FA::disable($user_id);
        delete_transient('omnixep_2fa_verified_' . $user_id);
        
        // Log the action for security audit
        error_log('OmniXEP: 2FA disabled for user ' . $user_id . ' at ' . current_time('mysql'));
        
        wp_send_json_success(array('message' => '2FA disabled successfully'));
    } elseif ($action_type === 'recovery_disable') {
        // RECOVERY MODE: Disable 2FA without code verification
        // This is for users who lost access to their authenticator app
        // They will need to re-enter their mnemonic to reactivate the module
        
        // Log the recovery action for security audit
        error_log('OmniXEP: 2FA RECOVERY MODE - Module reset for user ' . $user_id . ' at ' . current_time('mysql'));
        error_log('OmniXEP: User will need to re-enter mnemonic to reactivate module');
        
        // Disable 2FA
        OmniXEP_2FA::disable($user_id);
        
        // Clear any pending verifications
        delete_transient('omnixep_2fa_verified_' . $user_id);
        delete_transient('omnixep_2fa_setup_' . $user_id);
        
        wp_send_json_success(array(
            'message' => 'Module reset successfully. Please re-enter your mnemonic to reactivate.',
            'recovery_mode' => true
        ));
    }
}

/**
 * Handle AJAX for Module Balance & Debt (Ensures hooks work even if gateway isn't instantiated)
 */
add_action('wp_ajax_omnixep_fetch_balance', 'wc_omnixep_ajax_fetch_balance_handler');
add_action('wp_ajax_omnixep_fetch_utxos', 'wc_omnixep_ajax_fetch_utxos_handler');
add_action('wp_ajax_omnixep_get_pending_debt', 'wc_omnixep_ajax_get_pending_debt_handler');
add_action('wp_ajax_omnixep_settle_debt', 'wc_omnixep_ajax_settle_debt_handler');
add_action('wp_ajax_omnixep_api_proxy', 'wc_omnixep_ajax_api_proxy_handler');
add_action('wp_ajax_omnixep_broadcast_tx', 'wc_omnixep_ajax_broadcast_tx_handler');
add_action('wp_ajax_omnixep_store_mnemonic', 'wc_omnixep_ajax_store_mnemonic_handler');
add_action('wp_ajax_omnixep_get_mnemonic_for_tx', 'wc_omnixep_ajax_get_mnemonic_for_tx_handler');

// Mobile Payment AJAX Handlers
add_action('wp_ajax_omnixep_check_mobile_payment', 'wc_omnixep_check_mobile_payment');
add_action('wp_ajax_nopriv_omnixep_check_mobile_payment', 'wc_omnixep_check_mobile_payment');
add_action('wp_ajax_omnixep_save_mobile_txid', 'wc_omnixep_save_mobile_txid');
add_action('wp_ajax_nopriv_omnixep_save_mobile_txid', 'wc_omnixep_save_mobile_txid');

// Mobile Callback Handler (wallet app redirects here after payment)
add_action('template_redirect', 'wc_omnixep_mobile_callback_handler');

function wc_omnixep_mobile_callback_handler()
{
    if (!isset($_GET['omnixep_mobile_callback']))
        return;

    // SECURITY: Enhanced validation
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $txid = isset($_GET['omnixep_txid']) ? sanitize_text_field($_GET['omnixep_txid']) : '';

    if (!$order_id || !$order_key || !$txid) {
        error_log('OmniXEP Security: Invalid mobile callback parameters');
        wp_die('Invalid mobile callback parameters.');
    }

    // SECURITY: Validate TXID format
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
        error_log('OmniXEP Security: Invalid TXID format in mobile callback');
        wp_die('Invalid transaction ID format.');
    }
    
    // SECURITY: Rate limiting per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $rate_key = 'omnixep_callback_' . md5($ip . $order_id);
    $callback_count = get_transient($rate_key);
    
    if ($callback_count && $callback_count > 10) {
        error_log('OmniXEP Security: Rate limit exceeded for mobile callback. IP: ' . $ip);
        wp_die('Too many requests.');
    }
    
    set_transient($rate_key, ($callback_count ? $callback_count + 1 : 1), 300);

    // SECURITY: Verify order exists and key matches
    $order = wc_get_order($order_id);
    if (!$order || $order->get_order_key() !== $order_key) {
        error_log('OmniXEP Security: Order key mismatch in mobile callback. Order: ' . $order_id);
        wp_die('Invalid order.');
    }

    $existing_txid = $order->get_meta('_omnixep_txid');
    if (!empty($existing_txid)) {
        wp_redirect($order->get_checkout_order_received_url());
        exit;
    }

    $platform = isset($_GET['omnixep_platform']) ? sanitize_text_field($_GET['omnixep_platform']) : 'Mobile';

    $order->update_meta_data('_omnixep_txid', $txid);
    $order->update_meta_data('_omnixep_mobile_pending', '');
    $order->update_meta_data('_omnixep_platform', $platform);
    $order->update_status('pending-crypto', 'Mobile wallet payment successful (' . esc_html($platform) . '). Transaction ID: ' . esc_html($txid));
    $order->save();

    if (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(time() + 15, 'omnixep_verify_single_order', array($order_id));
    }

    error_log('OmniXEP Mobile Callback: TXID saved for Order #' . $order_id . ' - ' . $txid . ' (Platform: ' . $platform . ')');
    wp_redirect($order->get_checkout_order_received_url());
    exit;
}

function wc_omnixep_check_mobile_payment()
{
    // SECURITY: Enhanced validation
    $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
    $order_key = isset($_REQUEST['key']) ? sanitize_text_field($_REQUEST['key']) : '';
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';

    if (!$order_id || !$order_key) {
        wp_send_json_error('Invalid request parameters');
    }
    
    if (!wp_verify_nonce($nonce, 'omnixep_mobile_nonce_' . $order_id)) {
        error_log('OmniXEP Security: Invalid nonce for mobile payment check. Order: ' . $order_id);
        wp_send_json_error('Security check failed');
    }
    
    // SECURITY: Verify order key matches
    $order = wc_get_order($order_id);
    if (!$order || $order->get_order_key() !== $order_key) {
        error_log('OmniXEP Security: Order key mismatch. Order: ' . $order_id);
        wp_send_json_error('Invalid order');
    }
    
    // SECURITY: Rate limiting per IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $rate_key = 'omnixep_mobile_check_' . md5($ip . $order_id);
    $check_count = get_transient($rate_key);
    
    if ($check_count && $check_count > 30) {
        wp_send_json_error('Too many requests');
    }
    
    set_transient($rate_key, ($check_count ? $check_count + 1 : 1), 300);

    // Rate limiting
    $rl_key = 'omnixep_rl_mobile_' . $order_id;
    $attempts = (int) get_transient($rl_key);
    if ($attempts > 10) {
        wp_send_json_error('Too many requests');
    }
    set_transient($rl_key, $attempts + 1, 60);

    if (!$order_id || !$order_key) {
        wp_send_json_error('Missing parameters');
    }

    $order = wc_get_order($order_id);
    if (!$order || $order->get_order_key() !== $order_key) {
        wp_send_json_error('Invalid order');
    }

    $txid = $order->get_meta('_omnixep_txid');
    if (!empty($txid)) {
        wp_send_json_success(array('has_txid' => true, 'status' => $order->get_status(), 'redirect' => $order->get_checkout_order_received_url()));
    }
    wp_send_json_success(array('has_txid' => false, 'status' => $order->get_status()));
}

function wc_omnixep_save_mobile_txid()
{
    // SECURITY: Enhanced validation
    $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
    $order_key = isset($_REQUEST['key']) ? sanitize_text_field($_REQUEST['key']) : '';
    $txid = isset($_REQUEST['txid']) ? sanitize_text_field($_REQUEST['txid']) : '';
    $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';

    if (!$order_id || !$order_key || !$txid) {
        wp_send_json_error('Missing parameters');
    }
    
    if (!wp_verify_nonce($nonce, 'omnixep_mobile_nonce_' . $order_id)) {
        error_log('OmniXEP Security: Invalid nonce for save mobile TXID. Order: ' . $order_id);
        wp_send_json_error('Security check failed');
    }

    // SECURITY: Rate limiting
    $rl_key = 'omnixep_rl_mobile_save_' . $order_id;
    $attempts = (int) get_transient($rl_key);
    if ($attempts > 5) {
        error_log('OmniXEP Security: Rate limit exceeded for save mobile TXID. Order: ' . $order_id);
        wp_send_json_error('Too many requests');
    }
    set_transient($rl_key, $attempts + 1, 60);
    
    // SECURITY: Validate TXID format
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
        error_log('OmniXEP Security: Invalid TXID format. Order: ' . $order_id);
        wp_send_json_error('Invalid transaction ID format');
    }
    
    // SECURITY: Verify order exists and key matches
    $order = wc_get_order($order_id);
    if (!$order || $order->get_order_key() !== $order_key) {
        error_log('OmniXEP Security: Order key mismatch for save mobile TXID. Order: ' . $order_id);
        wp_send_json_error('Invalid order');
    }
    
    // SECURITY: Check if TXID already used by another order
    global $wpdb;
    $existing_order = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
         WHERE meta_key = '_omnixep_txid' AND meta_value = %s AND post_id != %d LIMIT 1",
        $txid,
        $order_id
    ));
    
    if ($existing_order) {
        error_log('OmniXEP Security: TXID replay attempt in mobile save. TXID: ' . $txid . ' already used by Order #' . $existing_order);
        wp_send_json_error('Transaction ID already used');
    }

    // Check if TXID already saved
    $existing = $order->get_meta('_omnixep_txid');
    if (!empty($existing)) {
        wp_send_json_success(array('redirect' => $order->get_checkout_order_received_url()));
        return;
    }

    $platform = isset($_GET['platform']) ? sanitize_text_field($_GET['platform']) : 'Mobile';

    $order->update_meta_data('_omnixep_txid', $txid);
    $order->update_meta_data('_omnixep_mobile_pending', '');
    $order->update_meta_data('_omnixep_platform', $platform);
    $order->add_order_note('In-app browser payment received (' . esc_html($platform) . '). TXID: ' . esc_html($txid));
    $order->save();

    if (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(time() + 15, 'omnixep_verify_single_order', array($order_id));
    }

    wp_send_json_success(array('redirect' => $order->get_checkout_order_received_url()));
}

function wc_omnixep_ajax_fetch_balance_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_fetch_balance();
    }
    wp_die();
}

function wc_omnixep_ajax_fetch_utxos_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_fetch_utxos();
    }
    wp_die();
}

function wc_omnixep_ajax_get_pending_debt_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_get_pending_debt();
    }
    wp_die();
}

function wc_omnixep_ajax_settle_debt_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_settle_debt();
    }
    wp_die();
}

function wc_omnixep_ajax_broadcast_tx_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_broadcast_tx();
    }
    wp_die();
}

function wc_omnixep_ajax_api_proxy_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_api_proxy();
    }
    wp_die();
}

function wc_omnixep_ajax_store_mnemonic_handler()
{
    error_log('[OmniXEP] ajax_store_mnemonic_handler triggered');
    if (!class_exists('WC_Gateway_Omnixep')) {
        error_log('[OmniXEP Error] WC_Gateway_Omnixep class not found in handler. Forcing require.');
        $gateway_file = plugin_dir_path(__FILE__) . 'includes/class-wc-gateway-omnixep.php';
        if (file_exists($gateway_file)) {
            require_once $gateway_file;
        }
    }

    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_store_mnemonic();
    } else {
        error_log('[OmniXEP Error] WC_Gateway_Omnixep STILL not found after force require.');
        echo 'Error: Gateway class missing.';
    }
    wp_die();
}

function wc_omnixep_ajax_get_mnemonic_for_tx_handler()
{
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $gateway->ajax_get_mnemonic_for_tx();
    }
    wp_die();
}

/**
 * Add OmniXEP Payment Settings to WordPress Admin Menu
 */
function wc_omnixep_add_admin_menu()
{
    add_menu_page(
        'OmniXEP Payment Settings',           // Page title
        'OmniXEP Payment',                    // Menu title
        'manage_woocommerce',                 // Capability required
        'omnixep-payment-settings',           // Menu slug
        'wc_omnixep_admin_page_redirect',     // Callback function
        'dashicons-money-alt',                // Icon (WordPress dashicon)
        56                                    // Position (after WooCommerce)
    );
}
add_action('admin_menu', 'wc_omnixep_add_admin_menu');

/**
 * Redirect to WooCommerce OmniXEP settings page
 */
function wc_omnixep_admin_page_redirect()
{
    $settings_url = admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep');
    wp_redirect($settings_url);
    exit;
}

/**
 * Also handle direct menu click redirect (for cases where callback doesn't trigger)
 */
function wc_omnixep_admin_menu_redirect()
{
    if (isset($_GET['page']) && $_GET['page'] === 'omnixep-payment-settings') {
        wp_redirect(admin_url('admin.php?page=wc-settings&tab=checkout&section=omnixep'));
        exit;
    }
}
add_action('admin_init', 'wc_omnixep_admin_menu_redirect');

/**
 * Helper to fetch address balance from multiple sources
 */
function wc_omnixep_get_address_balance($address)
{
    if (empty($address))
        return 0;

    // Check cache first
    $cache_key = 'omnixep_addr_balance_' . md5($address);
    $cached = get_transient($cache_key);
    if ($cached !== false)
        return floatval($cached);

    $balance = 0;
    $found = false;

    // 1. Try OmniXEP API (Property ID 0)
    $api_url = "https://api.omnixep.com/api/v2/address/{$address}/balances?_t=" . time();
    $response = wp_remote_get($api_url, array('timeout' => 3));

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if ($data && isset($data['data']['balances']) && is_array($data['data']['balances'])) {
            foreach ($data['data']['balances'] as $b) {
                $pid = $b['property_id'] ?? $b['propertyid'] ?? $b['id'] ?? null;
                if ($pid !== null && (int) $pid === 0) {
                    $raw = (float) ($b['balance'] ?? $b['total'] ?? $b['value'] ?? 0);
                    $decimals = (isset($b['decimals']) && ($b['decimals'] === true || $b['decimals'] === 'true' || (int) $b['decimals'] === 1 || (int) $b['decimals'] === 8));
                    $balance = $decimals ? ($raw / 100000000) : $raw;
                    $found = true;
                    // error_log('[OmniXEP] Balance from API (0): ' . $balance);
                    break;
                }
            }
        }
    } else {
        error_log('[OmniXEP] API Error (OmniXEP API): ' . $response->get_error_message());
    }

    // 2. Fallback to ElectrumX
    if (!$found) {
        $alt_url = "https://electrumx.xep.ai/api/address/{$address}/balance";
        $response = wp_remote_get($alt_url, array('timeout' => 3));
        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if ($data && (isset($data['confirmed']) || isset($data['balance']))) {
                if (isset($data['confirmed'])) {
                    $balance = (floatval($data['confirmed']) + floatval($data['unconfirmed'] ?? 0)) / 100000000;
                } else {
                    $balance = $data['balance'] > 100000 ? floatval($data['balance']) / 100000000 : floatval($data['balance']);
                }
                $found = true;
                // error_log('[OmniXEP] Balance from ElectrumX: ' . $balance);
            }
        } else {
            error_log('[OmniXEP] API Error (ElectrumX): ' . $response->get_error_message());
        }
    }

    if ($found) {
        set_transient($cache_key, $balance, 30 * MINUTE_IN_SECONDS);
        // Also update the fee wallet balance for other checks (compatibility)
        set_transient('omnixep_fee_wallet_balance_' . md5($address), $balance, 30 * MINUTE_IN_SECONDS);
        return $balance;
    } else {
        error_log('[OmniXEP] Failed to find balance for address: ' . $address);
        return false;
    }
}

/**
 * Check fee wallet balance and show warning if low
 */
function wc_omnixep_check_commission_wallet_balance()
{
    // Only run on admin pages
    if (!is_admin() || !current_user_can('manage_woocommerce')) {
        return;
    }

    // Get the fee wallet address from settings
    $settings = get_option('woocommerce_omnixep_settings', array());
    $fee_wallet_address = isset($settings['fee_wallet_address']) ? trim($settings['fee_wallet_address']) : (isset($settings['merchant_address']) ? trim($settings['merchant_address']) : '');

    if (empty($fee_wallet_address)) {
        return;
    }

    // Auto-clear cache when on the OmniXEP settings page to show live balance
    $is_settings_page = (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['section']) && $_GET['section'] === 'omnixep');
    if ($is_settings_page || isset($_GET['omnixep_clear_cache'])) {
        delete_transient('omnixep_addr_balance_' . md5($fee_wallet_address));
        delete_transient('omnixep_fee_wallet_balance_' . md5($fee_wallet_address));
    }

    $balance = wc_omnixep_get_address_balance($fee_wallet_address);

    // Only set warning if we actually got a balance and it's less than 10000
    if ($balance !== false && $balance < 10000) {
        $GLOBALS['omnixep_low_balance_warning'] = array(
            'balance' => $balance,
            'address' => $fee_wallet_address
        );
    } elseif ($balance !== false) {
        unset($GLOBALS['omnixep_low_balance_warning']);
    }
}
add_action('admin_init', 'wc_omnixep_check_commission_wallet_balance');

/**
 * Display low balance admin notice
 */
function wc_omnixep_low_balance_admin_notice()
{
    // Check if we should force show (useful for debugging)
    $force = isset($_GET['omnixep_debug_notice']);

    if (empty($GLOBALS['omnixep_low_balance_warning']) && !$force) {
        wc_omnixep_check_commission_wallet_balance();
    }

    if (empty($GLOBALS['omnixep_low_balance_warning']) && !$force) {
        return;
    }

    $warning = $GLOBALS['omnixep_low_balance_warning'] ?? [
        'balance' => 0,
        'address' => 'Debug Mode Active'
    ];

    $balance = number_format($warning['balance'], 2);
    $address = $warning['address'];
    $short_address = substr($address, 0, 10) . '...' . substr($address, -8);

    ?>
    <div class="notice notice-warning is-dismissible omnixep-low-balance-notice"
        style="border-left-color: #ff6b35; background: linear-gradient(135deg, #fff8f0 0%, #fff3e6 100%);">
        <div style="display: flex; align-items: center; padding: 10px 0;">
            <span style="font-size: 32px; margin-right: 15px;">&#9888;&#65039;</span>
            <div>
                <p style="margin: 0 0 5px 0; font-size: 14px; font-weight: bold; color: #d35400;">
                    &#128276; OmniXEP Fee Wallet Low Balance Warning!
                </p>
                <p style="margin: 0; color: #7f5000;">
                    Your fee wallet (<code
                        style="background: #fff; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($short_address); ?></code>)
                    currently has <strong style="color: #c0392b;"><?php echo esc_html($balance); ?> XEP</strong>.
                </p>
                <p style="margin: 5px 0 0 0; color: #7f5000;">
                    &#8505;&#65039; <strong>Recommendation:</strong> To ensure uninterrupted payment processing, your OmniXEP module
                    fee wallet should have at least <strong style="color: #27ae60;">10,000 XEP</strong>.
                </p>
            </div>
        </div>
    </div>
    <style>
        .omnixep-low-balance-notice .notice-dismiss:before {
            color: #d35400;
        }

        .omnixep-low-balance-notice .notice-dismiss:hover:before {
            color: #c0392b;
        }
    </style>
    <?php
}

add_action('admin_notices', 'wc_omnixep_low_balance_admin_notice');

/**
 * Check if OmniXEP payment module is properly configured for frontend
 * Returns array with 'configured' boolean and 'message' if not configured
 * 
 * PERFORMANCE: This function is called on every cart/checkout page load.
 * It ONLY checks cached balance - never makes external API calls on frontend.
 */
function wc_omnixep_check_module_configuration()
{
    // Static cache to prevent multiple checks in same request
    static $cached_result = null;
    if ($cached_result !== null) {
        return $cached_result;
    }

    $settings = get_option('woocommerce_omnixep_settings', array());

    // Check if gateway is enabled
    $enabled = isset($settings['enabled']) ? $settings['enabled'] : 'no';
    if ($enabled !== 'yes') {
        $cached_result = array('configured' => true, 'message' => '');
        return $cached_result;
    }

    // Get wallet addresses
    $fee_wallet_address = isset($settings['fee_wallet_address']) ? trim($settings['fee_wallet_address']) : '';
    $merchant_address = isset($settings['merchant_address']) ? trim($settings['merchant_address']) : '';

    // Check if fee wallet or merchant address is configured
    $wallet_address = !empty($fee_wallet_address) ? $fee_wallet_address : $merchant_address;

    if (empty($wallet_address)) {
        $cached_result = array(
            'configured' => false,
            'message' => 'OmniXEP payment module is not fully configured. Please contact the store administrator. Payment wallet address is not set.'
        );
        return $cached_result;
    }

    // PERFORMANCE FIX: Only check CACHED balance on frontend - NEVER make API calls here
    // The balance is refreshed in admin_init hook (wc_omnixep_check_commission_wallet_balance)
    $cache_key = 'omnixep_addr_balance_' . md5($wallet_address);
    $cached_balance = get_transient($cache_key);

    // If no cached balance exists, assume configured (admin will refresh it)
    // This prevents API calls from blocking page loads
    if ($cached_balance === false) {
        $cached_result = array('configured' => true, 'message' => '');
        return $cached_result;
    }

    $balance = floatval($cached_balance);

    if ($balance < 2000) {
        $cached_result = array(
            'configured' => false,
            'message' => 'OmniXEP payment module is currently unavailable due to insufficient balance. Please contact the store administrator. (Transaction wallet requires at least 2,000 XEP).'
        );
        return $cached_result;
    }

    $cached_result = array('configured' => true, 'message' => '');
    return $cached_result;
}

/**
 * Display frontend warning if OmniXEP module is not properly configured
 * Shows on cart and checkout pages
 */
function wc_omnixep_frontend_configuration_warning()
{
    // Only show on cart and checkout pages
    if (!is_cart() && !is_checkout()) {
        return;
    }

    $config_check = wc_omnixep_check_module_configuration();

    if ($config_check['configured']) {
        return;
    }

    ?>
    <div id="omnixep-extension-warning" class="woocommerce-notice woocommerce-notice--error woocommerce-error" role="alert"
        style="display: none; background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%); border: 1px solid #fc8181; border-left: 4px solid #e53e3e; border-radius: 8px; padding: 16px 20px; margin: 20px 0; z-index: 9999; position: relative;">
        <div style="display: flex; align-items: flex-start; gap: 12px;">
            <span style="font-size: 24px; line-height: 1;">⚠️</span>
            <div>
                <strong style="color: #c53030; font-size: 15px; display: block; margin-bottom: 6px;">WARNING: OmniXEP Wallet
                    Not Found</strong>
                <p style="margin: 0; color: #742a2a; font-size: 14px; line-height: 1.5;">
                    The OmniXEP Wallet browser extension is required to complete payment. If you are using
                    <strong>Incognito/Private mode</strong>, you must enable "Allow in incognito" in the extension settings
                    or use a normal window.
                </p>
            </div>
        </div>
    </div>

    <script>     (function () {
            function checkOmnixepExtension() {
                const warning = document.getElementById('omnixep-extension-warning'); if (!warning) return;
                const isExtensionPresent = (typeof window.omnixep !== 'undefined' && window.omnixep !== null);
                if (!isExtensionPresent) { warning.style.display = 'block'; } else { warning.style.display = 'none'; }
            }
            checkOmnixepExtension(); setTimeout(checkOmnixepExtension, 1000); setTimeout(checkOmnixepExtension, 3000); setTimeout(checkOmnixepExtension, 5000); window.addEventListener('load', checkOmnixepExtension);
        })();
    </script>

    <?php if (!$config_check['configured']): ?>
        <div class="woocommerce-notice woocommerce-notice--error woocommerce-error omnixep-config-warning" role="alert" style="
        background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
        border: 1px solid #fc8181;
        border-left: 4px solid #e53e3e;
        border-radius: 8px;
        padding: 16px 20px;
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(229, 62, 62, 0.15);
    ">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <span style="font-size: 24px; line-height: 1;">⚠️</span>
                <div>
                    <strong style="color: #c53030; font-size: 15px; display: block; margin-bottom: 6px;">
                        Payment Module Configuration Issue
                    </strong>
                    <p style="margin: 0; color: #742a2a; font-size: 14px; line-height: 1.5;">
                        <?php echo esc_html($config_check['message']); ?>
                    </p>
                    <p style="margin: 8px 0 0 0; color: #9b2c2c; font-size: 13px;">
                        <em>Cryptocurrency payment option may not be available until this is resolved.</em>
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php
}
add_action('woocommerce_before_cart', 'wc_omnixep_frontend_configuration_warning');
add_action('woocommerce_before_checkout_form', 'wc_omnixep_frontend_configuration_warning');

/**
 * Register Custom Order Status: Crypto Payment
 */
function wc_omnixep_register_crypto_status()
{
    // Confirmed crypto payment
    register_post_status('wc-crypto', array(
        'label' => 'Crypto Payment',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Crypto Payment <span class="count">(%s)</span>', 'Crypto Payment <span class="count">(%s)</span>')
    ));

    // Pending crypto payment (in mempool, awaiting confirmation)
    register_post_status('wc-pending-crypto', array(
        'label' => 'Pending Crypto Payment',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Pending Crypto Payment <span class="count">(%s)</span>', 'Pending Crypto Payment <span class="count">(%s)</span>')
    ));
}
add_action('init', 'wc_omnixep_register_crypto_status');

/**
 * Add Custom Status to WC Order Statuses
 */
function wc_omnixep_add_crypto_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-on-hold' === $key) { // Add after on-hold
            $new_order_statuses['wc-pending-crypto'] = 'Pending Crypto Payment';
            $new_order_statuses['wc-crypto'] = 'Crypto Payment';
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'wc_omnixep_add_crypto_to_order_statuses');

/**
 * Allow payment for pending-crypto orders (needed for mobile deep link flow)
 */
function wc_omnixep_valid_order_statuses_for_payment($statuses, $order)
{
    $statuses[] = 'pending-crypto';
    return $statuses;
}
add_filter('woocommerce_valid_order_statuses_for_payment', 'wc_omnixep_valid_order_statuses_for_payment', 10, 2);

/**
 * Allow payment_complete() to work from pending-crypto status
 * WITHOUT THIS, payment_complete() silently fails because WooCommerce
 * only allows it from 'pending', 'on-hold', 'failed' by default.
 */
function wc_omnixep_valid_order_statuses_for_payment_complete($statuses, $order)
{
    $statuses[] = 'pending-crypto';
    return $statuses;
}
add_filter('woocommerce_valid_order_statuses_for_payment_complete', 'wc_omnixep_valid_order_statuses_for_payment_complete', 10, 2);

/**
 * Schedule recurring Action Scheduler job for checking pending confirmations
 */
function wc_omnixep_schedule_confirmation_check()
{
    if (!function_exists('as_next_scheduled_action')) {
        return; // Action Scheduler not available
    }

    // Schedule a recurring action to check pending confirmations (Failsafe)
    if (false === as_next_scheduled_action('omnixep_check_pending_confirmations')) {
        as_schedule_recurring_action(time(), 60, 'omnixep_check_pending_confirmations'); // Run every 1 minute as failsafe
    }
}
add_action('init', 'wc_omnixep_schedule_confirmation_check');

// MANUAL CHECK BUTTON & HANDLER
add_action('admin_init', function () {
    if (isset($_GET['omnixep_check_now']) && $_GET['omnixep_check_now'] == '1' && current_user_can('manage_woocommerce')) {

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'omnixep_manual_check')) {
            wp_die('Security check failed');
        }

        $debug_log = [];
        wc_omnixep_process_pending_confirmations($debug_log);

        set_transient('omnixep_manual_check_result', $debug_log, 60);

        wp_redirect(remove_query_arg(array('omnixep_check_now', '_wpnonce')));
        exit;
    }
});

// 2. Show Button & Results
add_action('admin_notices', function () {
    $screen = get_current_screen();

    $is_order_page = false;
    if ($screen && ($screen->id === 'edit-shop_order' || $screen->id === 'woocommerce_page_wc-orders')) {
        $is_order_page = true;
    }

    if ($is_order_page) {
        $pending_count = 0;
        $temp_orders = wc_get_orders(['status' => ['wc-pending-crypto', 'pending-crypto'], 'limit' => -1, 'return' => 'ids']);
        $pending_count = count($temp_orders);

        if ($pending_count > 0) {
            $url = add_query_arg(array(
                'omnixep_check_now' => '1',
                '_wpnonce' => wp_create_nonce('omnixep_manual_check')
            ));
            echo '<div class="notice notice-warning">';
            echo '<p>';
            echo '<strong>OmniXEP:</strong> Found ' . esc_html($pending_count) . ' pending crypto payment(s). ';
            echo '<a href="' . esc_url($url) . '" class="button button-primary" style="margin-left: 10px;">Check Status Now</a>';
            echo '</p>';
            echo '</div>';
        }
    }

    $results = get_transient('omnixep_manual_check_result');
    if ($results) {
        delete_transient('omnixep_manual_check_result');

        echo '<div class="notice notice-info is-dismissible" style="border-left-color: #2ecc71; border-left-width: 5px;">';
        echo '<p><strong>OmniXEP MANUAL CHECK - Results:</strong></p>';
        echo '<ul style="max-height: 250px; overflow-y: auto;">';
        if (empty($results)) {
            echo '<li>No pending crypto orders found in "wc-pending-crypto" status.</li>';
        } else {
            foreach ($results as $log) {
                echo '<li>' . esc_html($log) . '</li>';
            }
        }
        echo '</ul>';
        echo '</div>';
    }
});

// Hook for single order verification (Smart Polling)
add_action('omnixep_verify_single_order', 'wc_omnixep_verify_single_order', 10, 1);

/**
 * Smart Polling: Verify a SINGLE order
 * Checks confirmation, updates status, or reschedules itself with backoff.
 */
function wc_omnixep_verify_single_order($order_id)
{
    $lock_key = 'omnixep_verify_lock_' . $order_id;

    if (get_transient($lock_key)) {
        error_log('OmniXEP Smart Polling: Order #' . $order_id . ' is already being verified. Skipping.');
        return;
    }

    set_transient($lock_key, 1, 30);

    $order = wc_get_order($order_id);
    if (!$order) {
        delete_transient($lock_key);
        return;
    }

    if (!in_array($order->get_status(), array('pending-crypto', 'wc-pending-crypto'))) {
        delete_transient($lock_key);
        return;
    }

    $txid = $order->get_meta('_omnixep_txid');
    if (!$txid) {
        delete_transient($lock_key);
        return;
    }

    $order_created = $order->get_date_created()->getTimestamp();
    if (time() - $order_created > 86400) {
        $order->update_status('failed', 'Crypto payment not confirmed within 24 hours.');
        delete_transient($lock_key);
        return;
    }

    if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
        error_log('OmniXEP Smart Polling: Invalid TXID format for #' . $order_id);
        delete_transient($lock_key);
        return;
    }

    // Use the robust gateway verification (checks recipient, amount, token PID, and confirmations)
    if (class_exists('WC_Gateway_Omnixep')) {
        $gateway = new WC_Gateway_Omnixep();
        $verified = $gateway->verify_transaction_on_chain($txid, $order, true);

        if ($verified) {
            // Respect configured status
            $target_status = $gateway->get_option('order_status', 'processing');
            if (strpos($target_status, 'wc-') === 0) {
                $target_status = substr($target_status, 3);
            }
            if (empty($target_status) || $target_status === 'pending-crypto' || $target_status === 'pending') {
                $target_status = 'processing';
            }

            // Direct status update (payment_complete has whitelist issues with custom statuses)
            $order->set_transaction_id(esc_html($txid));
            $order->update_status($target_status, 'Crypto payment confirmed via background verification. TXID: ' . esc_html($txid));
            $order->save();

            wc_reduce_stock_levels($order_id);
            do_action('woocommerce_payment_complete', $order_id);

            error_log('OmniXEP Smart Polling: Order #' . $order_id . ' completed. Status: ' . $target_status);

            delete_transient($lock_key);
            return;
        }
    }

    $current_interval = (int) $order->get_meta('_omnixep_next_check_interval');

    if ($current_interval <= 0)
        $current_interval = 120;

    $next_interval = floor($current_interval * 1.5);
    if ($next_interval > 3600)
        $next_interval = 3600;

    $order->update_meta_data('_omnixep_next_check_interval', $next_interval);
    $order->save();

    as_schedule_single_action(time() + $current_interval, 'omnixep_verify_single_order', array($order_id));
    delete_transient($lock_key);
}


/**
 * Process pending crypto payments - check for blockchain confirmations
 * @param array $debug_output Reference to array to store debug strings
 */
function wc_omnixep_process_pending_confirmations(&$debug_output = null)
{
    if ($debug_output === null)
        $debug_output = []; // Initialize if not provided

    // Verify function is running
    error_log('OmniXEP Cron: Starting execution at ' . date('Y-m-d H:i:s'));
    $debug_output[] = 'Cron started at ' . date('H:i:s');

    // Query all orders with pending-crypto status
    $args = array(
        'status' => array('wc-pending-crypto', 'pending-crypto'), // Handle both slug formats
        'limit' => 50, // Process up to 50 orders per run
        'return' => 'ids'
    );

    $order_ids = wc_get_orders($args);
    error_log('OmniXEP Cron: Found ' . count($order_ids) . ' pending orders.');
    $debug_output[] = 'Found ' . count($order_ids) . ' matching orders.';

    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order)
            continue;

        $txid = $order->get_meta('_omnixep_txid');
        error_log('OmniXEP Cron: Checking Order #' . $order_id . ' (TXID: ' . ($txid ? $txid : 'NONE') . ')');

        if (!$txid) {
            // Don't fail mobile pending orders that are waiting for wallet callback
            $mobile_pending = $order->get_meta('_omnixep_mobile_pending');
            if ($mobile_pending === '1') {
                $debug_output[] = 'Order #' . $order_id . ' skipped (Mobile payment pending)';
                continue;
            }
            $order->update_status('failed', 'No transaction ID found for pending crypto payment.');
            error_log('OmniXEP Cron: Order #' . $order_id . ' failed - No TXID');
            $debug_output[] = 'Order #' . $order_id . ' skipped (No TXID)';
            continue;
        }

        // Check if order is too old (24 hours timeout)
        $order_created = $order->get_date_created()->getTimestamp();
        if (time() - $order_created > 86400) {
            $order->update_status('failed', 'Crypto payment not confirmed within 24 hours.');
            error_log('OmniXEP Cron: Order #' . $order_id . ' failed - Timeout');
            $debug_output[] = 'Order #' . $order_id . ' Failed (Timeout)';
            continue;
        }

        // Try to verify with ALL security checks (Recipient, Amount, Token, and Commission if applicable)
        $gateway = new WC_Gateway_Omnixep();
        $verified = $gateway->verify_transaction_on_chain($txid, $order, true);

        if (!$verified) {
            $debug_output[] = 'Order #' . $order_id . ': Verification failed (Recipient/Amount mismatch or unconfirmed)';
            continue;
        }

        // SUCCESS! If $verified is true, verify_transaction_on_chain already confirmed everything
        $debug_output[] = 'Order #' . $order_id . ': SUCCESS! Verification passed.';

        $target_status = $gateway->get_option('order_status', 'processing');
        if (strpos($target_status, 'wc-') === 0) {
            $target_status = substr($target_status, 3);
        }
        // pending-crypto is the WAITING status, not a valid completion status
        if (empty($target_status) || $target_status === 'pending-crypto' || $target_status === 'pending') {
            $target_status = 'processing';
        }

        $old_status = $order->get_status();
        $debug_output[] = 'Order #' . $order_id . ': Old status=' . $old_status . ', Target=' . $target_status;

        // Use direct update_status instead of payment_complete (which has whitelist issues)
        $order->set_transaction_id(esc_html($txid));
        $order->update_status($target_status, 'Crypto payment confirmed via blockchain verification. TXID: ' . esc_html($txid));
        $order->save();

        $new_status = $order->get_status();
        $debug_output[] = 'Order #' . $order_id . ': New status=' . $new_status;
        error_log('OmniXEP Cron: Order #' . $order_id . ' status changed from ' . $old_status . ' to ' . $new_status);

        // Reduce stock and clear carts
        wc_reduce_stock_levels($order_id);
        do_action('woocommerce_payment_complete', $order_id);
    }
}
add_action('omnixep_check_pending_confirmations', 'wc_omnixep_process_pending_confirmations');


/**
 * MEXC Price Fetcher
 */
function wc_omnixep_fetch_mexc_price($symbol)
{
    if (empty($symbol))
        return 0;

    $cache_key = 'omnixep_mexc_' . strtoupper($symbol);
    $cached = get_transient($cache_key);
    if ($cached !== false)
        return (float) $cached;

    $response = wp_remote_get("https://api.mexc.com/api/v3/ticker/price?symbol=" . urlencode(strtoupper($symbol)), array(
        'timeout' => 5,
        'user-agent' => 'OmniXEP-WooCommerce/1.0'
    ));

    if (is_wp_error($response))
        return 0;

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $price = isset($data['price']) ? (float) $data['price'] : 0;

    if ($price > 0) {
        set_transient($cache_key, $price, 300);
    }

    return $price;
}

/**
 * Dex-Trade Price Fetcher
 */
function wc_omnixep_fetch_dextrade_price($pair)
{
    if (empty($pair))
        return 0;

    $cache_key = 'omnixep_dextrade_' . strtoupper($pair);
    $cached = get_transient($cache_key);
    if ($cached !== false)
        return (float) $cached;

    $response = wp_remote_get("https://api.dex-trade.com/v1/public/ticker?pair=" . urlencode(strtoupper($pair)), array(
        'timeout' => 5,
        'user-agent' => 'OmniXEP-WooCommerce/1.0'
    ));

    if (is_wp_error($response))
        return 0;

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Dex-Trade API response structure: { "status": true, "data": { "last": "0.000123", ... } }
    if (!isset($data['status']) || $data['status'] !== true || !isset($data['data']['last'])) {
        return 0;
    }

    $price = (float) $data['data']['last'];

    if ($price > 0) {
        set_transient($cache_key, $price, 300);
    }

    return $price;
}


/**
 * Custom function to fetch prices with Caching and Source support
 */
function wc_omnixep_get_prices($cg_ids_str, $tokens = [])
{
    $prices = [];
    $cg_ids = [];

    // 1. Session-based Lock Check (Per-user price persistence)
    $last_fetch = 0;
    $session_prices = [];
    if (!is_admin() && function_exists('WC') && WC()->session) {
        $last_fetch = (int) WC()->session->get('omnixep_last_fetch_time');
        $session_prices = (array) WC()->session->get('omnixep_price_cache');

        // If we have a valid cache within the 30s window
        if ($last_fetch && (time() - $last_fetch < 30) && !empty($session_prices)) {
            // Check if all requested tokens are present
            $all_present = true;
            foreach ($tokens as $token) {
                $p_id = isset($token['price_id']) ? $token['price_id'] : (isset($token['cg_id']) ? $token['cg_id'] : '');
                if ($p_id && !isset($session_prices[$p_id])) {
                    $all_present = false;
                    break;
                }
            }
            if ($all_present) {
                return $session_prices;
            }
        }
    }

    // 2. Fetch logic (if session empty, expired, or partial)
    if (!empty($cg_ids_str)) {
        $cg_ids = explode(',', $cg_ids_str);
    }

    foreach ($tokens as $token) {
        $source = isset($token['source']) ? $token['source'] : 'coingecko';
        $p_id = isset($token['price_id']) ? $token['price_id'] : (isset($token['cg_id']) ? $token['cg_id'] : '');

        if ($source === 'mexc') {
            $price = wc_omnixep_fetch_mexc_price($p_id);
            if ($price > 0)
                $prices[$p_id]['usd'] = $price;
        } else if ($source === 'dextrade') {
            $price = wc_omnixep_fetch_dextrade_price($p_id);
            if ($price > 0)
                $prices[$p_id]['usd'] = $price;
        } else if ($source === 'coingecko') {
            if (!empty($p_id) && !in_array($p_id, $cg_ids)) {
                $cg_ids[] = $p_id;
            }
        }
    }

    if (!empty($cg_ids)) {
        $cg_ids_query = implode(',', array_filter($cg_ids));
        $cache_key = 'omnixep_gecko_' . md5($cg_ids_query);
        $cached_gecko = get_transient($cache_key);

        if ($cached_gecko !== false) {
            $prices = array_merge($prices, $cached_gecko);
        } else {
            $url = "https://api.coingecko.com/api/v3/simple/price?ids=" . urlencode($cg_ids_query) . "&vs_currencies=usd";
            $settings = get_option('woocommerce_omnixep_settings');
            $cg_api_key = isset($settings['coingecko_api_key']) ? $settings['coingecko_api_key'] : '';

            $args = array('timeout' => 10, 'user-agent' => 'OmniXEP-WooCommerce/1.0');
            if (!empty($cg_api_key)) {
                $url = "https://pro-api.coingecko.com/api/v3/simple/price?ids=" . urlencode($cg_ids_query) . "&vs_currencies=usd";
                $args['headers'] = array('x-cg-pro-api-key' => $cg_api_key);
            }

            $response = wp_remote_get($url, $args);
            if (!is_wp_error($response)) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if ($data) {
                    $prices = array_merge($prices, $data);
                    set_transient($cache_key, $data, 30);
                }
            } else {
                error_log('OmniXEP Price Fetch Error (CoinGecko): ' . $response->get_error_message());
            }
        }
    }

    // 3. Final Storage & Return
    $final_prices = array_merge($session_prices, $prices);
    if (!is_admin() && function_exists('WC') && WC()->session) {
        WC()->session->set('omnixep_price_cache', $final_prices);
        if (!$last_fetch || (time() - $last_fetch >= 30)) {
            WC()->session->set('omnixep_last_fetch_time', time());
        }
    }

    return $final_prices;
}

/**
 * Filter to fix Mixed Content for Favicon and other internal assets
 * Aggressive fix: Hook into clean_url to catch URLs passed via esc_url()
 */
function wc_omnixep_force_ssl_assets($url)
{
    if (is_ssl() && is_string($url) && strpos($url, 'http://') === 0) {
        $url = str_replace('http://', 'https://', $url);
    }
    return $url;
}
add_filter('get_site_icon_url', 'wc_omnixep_force_ssl_assets', 99);
add_filter('wp_get_attachment_url', 'wc_omnixep_force_ssl_assets', 99);
add_filter('theme_file_uri', 'wc_omnixep_force_ssl_assets', 99);
add_filter('clean_url', 'wc_omnixep_force_ssl_assets', 99);
add_filter('wp_calculate_image_srcset', function ($sources) {
    if (is_ssl()) {
        foreach ($sources as &$source) {
            $source['url'] = str_replace('http://', 'https://', $source['url']);
        }
    }
    return $sources;
}, 99);

/**
 * AJAX Handler for Test Price Fetch
 */
add_action('wp_ajax_omnixep_test_price', 'wc_omnixep_test_price_ajax');
function wc_omnixep_test_price_ajax()
{
    check_ajax_referer('omnixep_test_price_nonce', 'nonce');
    if (!current_user_can('manage_woocommerce'))
        wp_send_json_error('Unauthorized');

    $source = sanitize_text_field($_POST['source']);
    $price_id = sanitize_text_field($_POST['price_id']);

    if (empty($price_id))
        wp_send_json_error('Price ID is required');

    $price = 0;
    if ($source === 'mexc') {
        $price = wc_omnixep_fetch_mexc_price($price_id);
    } else if ($source === 'dextrade') {
        $price = wc_omnixep_fetch_dextrade_price($price_id);
    } else {
        // Gecko Test with global key
        $settings = get_option('woocommerce_omnixep_settings');
        $api_key = isset($settings['coingecko_api_key']) ? $settings['coingecko_api_key'] : '';

        $url = "https://api.coingecko.com/api/v3/simple/price?ids=" . urlencode($price_id) . "&vs_currencies=usd";
        $args = array('timeout' => 10);
        if (!empty($api_key)) {
            $url = "https://pro-api.coingecko.com/api/v3/simple/price?ids=" . urlencode($price_id) . "&vs_currencies=usd";
            $args['headers'] = array('x-cg-pro-api-key' => $api_key);
        }
        $response = wp_remote_get($url, $args);
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data[$price_id]['usd'])) {
                $price = $data[$price_id]['usd'];
            }
        }
    }

    if ($price > 0) {
        wp_send_json_success(['price' => $price]);
    } else {
        wp_send_json_error('Fetch failed. Check ID/Pair and Global API Key.');
    }
}

/**
 * Get live price for a specific token name (cached)
 */
function wc_omnixep_get_live_price($token_name)
{
    if (empty($token_name))
        return 0;

    $token_upper = strtoupper($token_name);

    // 1. Static cache for current request
    static $request_cache = [];
    if (isset($request_cache[$token_upper])) {
        return $request_cache[$token_upper];
    }

    $cache_key = 'omnixep_price_single_' . $token_upper;
    $cached_price = get_transient($cache_key);

    if ($cached_price !== false) {
        $request_cache[$token_upper] = (float) $cached_price;
        return $request_cache[$token_upper];
    }

    $price = 0;

    // Fetch token config from settings
    $settings = get_option('woocommerce_omnixep_settings', []);
    $token_config_str = isset($settings['token_config']) ? $settings['token_config'] : '';
    $tokens = wc_omnixep_parse_token_config($token_config_str);

    foreach ($tokens as $t) {
        if (strtoupper($t['name']) === $token_upper) {
            $source = isset($t['source']) ? $t['source'] : 'coingecko';
            $p_id = isset($t['price_id']) ? $t['price_id'] : (isset($t['cg_id']) ? $t['cg_id'] : '');

            if ($source === 'mexc') {
                $price = (float) wc_omnixep_fetch_mexc_price($p_id);
            } else if ($source === 'dextrade') {
                $price = (float) wc_omnixep_fetch_dextrade_price($p_id);
            } else {
                $prices = wc_omnixep_get_prices('', [$t]);
                $price = isset($prices[$p_id]['usd']) ? (float) $prices[$p_id]['usd'] : 0;
            }
            break;
        }
    }

    // MEMEX Fallback with caching
    if ($price <= 0 && $token_upper === 'MEMEX') {
        $memex_cache_key = 'omnixep_memex_geckoterminal';
        $cached_memex = get_transient($memex_cache_key);

        if ($cached_memex !== false) {
            $price = (float) $cached_memex;
        } else {
            $response = wp_remote_get('https://api.geckoterminal.com/api/v2/networks/omax-chain/pools/0xc84edbf1e3fef5e4583aaa0f818cdfebfcae095b', array('timeout' => 5));
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($body['data']['attributes']['base_token_price_usd'])) {
                    $price = (float) $body['data']['attributes']['base_token_price_usd'];
                    set_transient($memex_cache_key, $price, 300); // 5 minute cache
                }
            }
        }
    }

    if ($price > 0) {
        set_transient($cache_key, $price, 30); // Cache for 30 seconds to match frontend timer
    }

    $request_cache[$token_upper] = $price;
    return $price;
}

/**
 * Add custom styling for Crypto Payment status in admin
 */
function wc_omnixep_admin_order_status_styles()
{
    ?>
    <style>
        /* Crypto Payment status badge - ONLY the status label, not the row */

        /* Target only the mark/span element with status */
        mark.order-status.status-crypto,
        mark.status-crypto,
        span.order-status.status-crypto,
        .column-order_status mark.status-crypto,
        .column-wc_actions mark.status-crypto,
        td.order_status mark.status-crypto {
            background: #28a745 !important;
            color: #fff !important;
            border-radius: 3px;
            padding: 2px 8px;
        }

        /* Pending Crypto Payment status badge - ORANGE */
        mark.order-status.status-pending-crypto,
        mark.status-pending-crypto,
        span.order-status.status-pending-crypto,
        .column-order_status mark.status-pending-crypto,
        .column-wc_actions mark.status-pending-crypto,
        td.order_status mark.status-pending-crypto {
            background: #ff9800 !important;
            color: #fff !important;
            border-radius: 3px;
            padding: 2px 8px;
        }

        /* HPOS tables - only status badge */
        .wc-orders-list-table .order-status.status-crypto,
        .woocommerce-page mark.status-crypto {
            background: #28a745 !important;
            color: #fff !important;
        }

        .wc-orders-list-table .order-status.status-pending-crypto,
        .woocommerce-page mark.status-pending-crypto {
            background: #ff9800 !important;
            color: #fff !important;
        }

        /* Crypto payment info below status */
        .omnixep-payment-info {
            display: block;
            font-size: 11px;
            color: #28a745;
            font-weight: bold;
            margin-top: 4px;
            line-height: 1.3;
        }

        .omnixep-payment-info .token-name {
            color: #0d6efd;
            font-weight: 600;
        }

        .omnixep-payment-info .usd-value {
            color: #666;
            font-weight: normal;
        }

        /* TXID link styling */
        .omnixep-txid-link {
            color: #0073aa !important;
            text-decoration: none;
            word-break: break-all;
        }

        .omnixep-txid-link:hover {
            text-decoration: underline;
        }

        .omnixep-verified {
            color: #28a745;
            font-weight: bold;
            margin-right: 5px;
        }
    </style>
    <script>
        jQuery(document).ready(function ($) {
            // Convert OmniXEP hashes to clickable links
            function convertOmniXEPHashes() {
                // Find elements that might contain the payment text and hash
                // Targeting by text content and common WooCommerce containers
                $('body').find('.wc-order-preview-payment-method, .payment-method, .method, :contains("Pay with OmniXEP")').each(function () {
                    var el = $(this);
                    // Check if it's a text node or contains only text/br
                    if (el.children(':not(br, span.omnixep-verified, a.omnixep-txid-link)').length === 0) {
                        var html = el.html();
                        // Match hash pattern: 64 character hex string, potentially surrounded by whitespace/newlines
                        var hashPattern = /\(\s*([a-fA-F0-9]{64})\s*\)/g;
                        if (hashPattern.test(html)) {
                            var newHtml = html.replace(/\(\s*([a-fA-F0-9]{64})\s*\)/g, function (match, hash) {
                                var shortHash = hash.substring(0, 16) + '...';
                                var link = '<span class="omnixep-verified">✓</span><a href="https://electraprotocol.network/transaction/' + hash + '" target="_blank" class="omnixep-txid-link">(' + shortHash + ')</a>';
                                return link;
                            });
                            if (html !== newHtml) {
                                el.html(newHtml);
                            }
                        }
                    }
                });
            }
            // Initial run
            setTimeout(convertOmniXEPHashes, 200);
            // Higher frequency for dynamic/AJAX content
            $(document).on('wc_backbone_modal_loaded', function () {
                setTimeout(convertOmniXEPHashes, 100);
                setTimeout(convertOmniXEPHashes, 500);
                setTimeout(convertOmniXEPHashes, 1000);
            });
            $(document).ajaxComplete(function () {
                setTimeout(convertOmniXEPHashes, 200);
                setTimeout(convertOmniXEPHashes, 600);
            });
            // Loop for safety against late-loading elements
            setInterval(convertOmniXEPHashes, 2000);
        });
    </script>
    <?php
}
add_action('admin_head', 'wc_omnixep_admin_order_status_styles');

/**
 * Enhanced filter for Order Preview Modal
 */
function wc_omnixep_admin_order_preview_details($data, $order)
{
    if (!$order || !is_a($order, 'WC_Order'))
        return $data;

    if ($order->get_payment_method() === 'omnixep') {
        $txid = $order->get_meta('_omnixep_txid');
        $token_name = $order->get_meta('_omnixep_token_name');
        $amount = $order->get_meta('_omnixep_amount');

        if ($txid) {
            $explorer_url = 'https://electraprotocol.network/transaction/';
            $check_icon = '<span style="color:#28a745;">✓</span>';
            $link = '<a href="' . esc_url($explorer_url . $txid) . '" target="_blank" style="color:#0073aa;">(' . esc_html(substr($txid, 0, 16)) . '...)</a>';

            $payment_via = 'Paid via OmniXEP ' . $check_icon;
            if ($token_name && $amount) {
                $payment_via .= ' <strong>' . esc_html($token_name) . ':</strong> ' . esc_html($amount);
            }
            $payment_via .= ' ' . $link;

            $data['payment_via'] = $payment_via;
        }
    }
    return $data;
}
add_filter('woocommerce_admin_order_preview_get_order_details', 'wc_omnixep_admin_order_preview_details', 20, 2);

/**
 * Display crypto payment details after order status in admin list
 */
function wc_omnixep_display_crypto_info_in_status($column, $post_id)
{
    if ($column === 'order_status') {
        $order = wc_get_order($post_id);
        if (!$order)
            return;

        // Check if this is a crypto payment order
        if ($order->get_status() === 'crypto' || $order->get_payment_method() === 'omnixep') {
            $token_name = $order->get_meta('_omnixep_token_name');
            $amount = $order->get_meta('_omnixep_amount');
            $usd_value = $order->get_meta('_omnixep_usd_value');

            if ($token_name && $amount) {
                echo '<div class="omnixep-payment-info">';
                echo '<span class="token-name">' . esc_html($token_name) . '</span>: ';
                echo esc_html($amount);

                // Show current value
                $current_price = wc_omnixep_get_live_price($token_name);
                if ($current_price > 0) {
                    $current_val = (float) $amount * $current_price;
                    echo ' <span class="usd-value">($' . esc_html(number_format($current_val, 2)) . ')</span>';
                } elseif ($usd_value) {
                    // Fallback to saved value if live fetch fails
                    echo ' <span class="usd-value">($' . esc_html(number_format((float) $usd_value, 2)) . ')</span>';
                }
                echo '</div>';
            }
        }
    }
}
add_action('manage_shop_order_posts_custom_column', 'wc_omnixep_display_crypto_info_in_status', 20, 2);

/**
 * HPOS compatibility - Display crypto payment details for new order tables
 */
function wc_omnixep_display_crypto_info_hpos($column, $order)
{
    if ($column === 'order_status') {
        if (!$order || !is_a($order, 'WC_Order'))
            return;

        // Check if this is a crypto payment order
        if ($order->get_status() === 'crypto' || $order->get_payment_method() === 'omnixep') {
            $token_name = $order->get_meta('_omnixep_token_name');
            $amount = $order->get_meta('_omnixep_amount');
            $usd_value = $order->get_meta('_omnixep_usd_value');

            if ($token_name && $amount) {
                echo '<div class="omnixep-payment-info">';
                echo '<span class="token-name">' . esc_html($token_name) . '</span>: ';
                echo esc_html($amount);

                // Show current value
                $current_price = wc_omnixep_get_live_price($token_name);
                if ($current_price > 0) {
                    $current_val = (float) $amount * $current_price;
                    echo ' <span class="usd-value">($' . esc_html(number_format($current_val, 2)) . ')</span>';
                } elseif ($usd_value) {
                    // Fallback to saved value if live fetch fails
                    echo ' <span class="usd-value">($' . esc_html(number_format((float) $usd_value, 2)) . ')</span>';
                }
                echo '</div>';
            }
        }
    }
}
add_action('manage_woocommerce_page_wc-orders_custom_column', 'wc_omnixep_display_crypto_info_hpos', 20, 2);

/**
 * Display OmniXEP payment details in order admin page (meta box)
 */
function wc_omnixep_add_payment_meta_box()
{
    $screen = function_exists('wc_get_container') ? wc_get_page_screen_id('shop-order') : 'shop_order';
    add_meta_box(
        'omnixep_payment_details',
        '💰 OmniXEP Payment Details',
        'wc_omnixep_payment_meta_box_content',
        $screen,
        'side',
        'high'
    );
    // Also add for legacy
    add_meta_box(
        'omnixep_payment_details',
        '💰 OmniXEP Payment Details',
        'wc_omnixep_payment_meta_box_content',
        'shop_order',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'wc_omnixep_add_payment_meta_box');

/**
 * Payment meta box content
 */
function wc_omnixep_payment_meta_box_content($post_or_order)
{
    // HPOS compatibility
    if ($post_or_order instanceof WC_Order) {
        $order = $post_or_order;
    } else {
        $order = wc_get_order($post_or_order->ID);
    }

    if (!$order || $order->get_payment_method() !== 'omnixep') {
        echo '<p>This order was not paid with OmniXEP.</p>';
        return;
    }

    $txid = $order->get_meta('_omnixep_txid');
    $commission_txid = $order->get_meta('_omnixep_commission_txid');
    $token_name = $order->get_meta('_omnixep_token_name');
    $amount = $order->get_meta('_omnixep_amount');
    $merchant_amount = $order->get_meta('_omnixep_merchant_amount');
    $commission_amount = $order->get_meta('_omnixep_commission_amount');
    $commission_address = $order->get_meta('_omnixep_commission_address');
    $usd_value = $order->get_meta('_omnixep_usd_value');
    $explorer_url = 'https://electraprotocol.network/transaction/';

    echo '<div style="padding: 10px 0;">';

    if ($token_name && $amount) {
        echo '<p style="margin: 5px 0;"><strong>Token:</strong> ' . esc_html($token_name) . '</p>';
        echo '<p style="margin: 5px 0;"><strong>Total Amount:</strong> ' . esc_html($amount);
        if ($usd_value && floatval($usd_value) > 0) {
            echo ' <span style="color: #666;">($' . esc_html(number_format((float) $usd_value, 2)) . ')</span>';
        }
        echo '</p>';

        if ($commission_amount && floatval($commission_amount) > 0) {
            echo '<div style="background: #fff3cd; padding: 12px; border-left: 4px solid #ffc107; margin: 10px 0;">';
            echo '<p style="margin: 3px 0; font-size: 0.95em;"><strong>⚠️ Two-Step Payment:</strong></p>';
            echo '<p style="margin: 5px 0 5px 20px;"><strong>STEP 1 (First):</strong> Commission to XEP System</p>';
            echo '<p style="margin: 3px 0 3px 30px;">→ Amount: <strong>' . esc_html($commission_amount) . ' ' . esc_html($token_name) . '</strong></p>';
            echo '<p style="margin: 5px 0 5px 20px;"><strong>STEP 2 (Second):</strong> Order Payment to Merchant</p>';
            echo '<p style="margin: 3px 0 3px 30px;">→ Amount: <strong>' . esc_html($merchant_amount) . ' ' . esc_html($token_name) . '</strong></p>';
            if ($commission_address) {
                echo '<p style="margin: 8px 0 3px 0; font-size: 0.85em; color: #856404;">Commission Wallet: ' . esc_html(substr($commission_address, 0, 25)) . '...</p>';
            }
            echo '</div>';
        }
    }

    if ($commission_txid) {
        $comm_link = $explorer_url . $commission_txid;
        echo '<p style="margin: 10px 0 5px 0;"><strong>STEP 1 - Commission TX (FIRST):</strong></p>';
        echo '<div style="background: #d4edda; padding: 8px; border-radius: 4px; border-left: 3px solid #28a745; word-break: break-all; font-size: 11px;">';
        echo '<span style="color: #28a745; font-size: 16px; margin-right: 5px;">✓</span>';
        echo '<a href="' . esc_url($comm_link) . '" target="_blank" style="color: #155724;">';
        echo esc_html(substr($commission_txid, 0, 20)) . '...';
        echo '</a>';
        echo '</div>';
        echo '<p style="margin-top: 8px;"><a href="' . esc_url($comm_link) . '" target="_blank" class="button button-small">🔍 View Commission TX</a></p>';
    } elseif ($commission_amount && floatval($commission_amount) > 0) {
        echo '<p style="color: #dc3545; margin: 10px 0;"><strong>⚠️ STEP 1 - Commission TX:</strong> Not received yet (required first!)</p>';
    }

    if ($txid) {
        $tx_link = $explorer_url . $txid;
        echo '<p style="margin: 10px 0 5px 0;"><strong>STEP 2 - Order TX (SECOND):</strong></p>';
        echo '<div style="background: #d4edda; padding: 8px; border-radius: 4px; border-left: 3px solid #28a745; word-break: break-all; font-size: 11px;">';
        echo '<span style="color: #28a745; font-size: 16px; margin-right: 5px;">✓</span>';
        echo '<a href="' . esc_url($tx_link) . '" target="_blank" style="color: #155724;">';
        echo esc_html(substr($txid, 0, 20)) . '...';
        echo '</a>';
        echo '</div>';
        echo '<p style="margin-top: 8px;"><a href="' . esc_url($tx_link) . '" target="_blank" class="button button-small">🔍 View Order TX</a></p>';
    } else {
        echo '<p style="color: #dc3545; margin: 10px 0;"><strong>⚠️ STEP 2 - Order TX:</strong> Not received</p>';
    }


    echo '</div>';

    $system_fee_debt = (float) $order->get_meta('_omnixep_system_fee_debt');
    $commission_fee_debt = (float) $order->get_meta('_omnixep_commission_fee_debt');
    $total_debt = $system_fee_debt + $commission_fee_debt;
    $debt_settled = $order->get_meta('_omnixep_debt_settled');

    if ($total_debt > 0) {
        $status_color = ($debt_settled === 'yes') ? '#28a745' : '#dc3545';
        $status_text = ($debt_settled === 'yes') ? 'Paid' : 'Pending (Merchant Debt)';
        echo '<div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 6px; border-left: 4px solid ' . $status_color . ';">';
        echo '<p style="margin: 0; font-size: 0.9em; color: #555;"><strong>📡 0.8% Sales Commission:</strong></p>';
        echo '<p style="margin: 5px 0 0 0; font-weight: bold; color: ' . $status_color . ';">' . number_format($total_debt, 8, '.', '') . ' XEP (' . $status_text . ')</p>';
        echo '</div>';
    }
}

/**
 * Also display in order notes/payment method section
 */
function wc_omnixep_display_payment_details_in_order($order)
{
    if ($order->get_payment_method() !== 'omnixep')
        return;

    $txid = $order->get_meta('_omnixep_txid');
    $token_name = $order->get_meta('_omnixep_token_name');
    $amount = $order->get_meta('_omnixep_amount');
    $explorer_url = 'https://electraprotocol.network/transaction/';

    if ($txid) {
        echo '<p><strong>OmniXEP Transaction:</strong><br>';
        echo '<span style="color: #28a745;">✓</span> ';
        echo '<a href="' . esc_url($explorer_url . $txid) . '" target="_blank">';
        echo esc_html(substr($txid, 0, 30)) . '...';
        echo '</a></p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'wc_omnixep_display_payment_details_in_order');

/**
 * Filter payment method title to include clickable TXID link
 * This makes the hash in "Pay with OmniXEP (hash)" clickable
 */
function wc_omnixep_format_payment_method_title($title, $order)
{
    if (!$order || !is_a($order, 'WC_Order'))
        return $title;
    if ($order->get_payment_method() !== 'omnixep')
        return $title;

    $txid = $order->get_meta('_omnixep_txid');
    $token_name = $order->get_meta('_omnixep_token_name');
    $amount = $order->get_meta('_omnixep_amount');
    $explorer_url = 'https://electraprotocol.network/transaction/';

    // Admin check: Return plain text to avoid escaped HTML in WooCommerce admin meta boxes
    if (is_admin() && !wp_doing_ajax()) {
        $admin_title = 'Pay with OmniXEP';
        if ($txid) {
            $admin_title .= ' (' . substr($txid, 0, 10) . '...)';
        }
        return $admin_title;
    }

    // Rich version for frontend (Account page, Checkout success, etc.)
    $result = 'Pay with OmniXEP';

    if ($txid) {
        // Verify transaction exists
        $verified = wc_omnixep_verify_transaction($txid);
        $check_icon = $verified ? '<span style="color:#28a745;">✓</span>' : '<span style="color:#ffc107;">⏳</span>';
        $result .= ' ' . $check_icon;
    }

    // Add token name and amount
    if ($token_name && $amount) {
        $result .= ' <strong>' . esc_html($token_name) . ':</strong> ' . esc_html($amount);
    }

    return $result;
}
add_filter('woocommerce_order_get_payment_method_title', 'wc_omnixep_format_payment_method_title', 20, 2);

/**
 * Verify transaction on ElectraProtocol network
 */
function wc_omnixep_verify_transaction($txid)
{
    // Cache the result for 5 minutes
    $cache_key = 'omnixep_tx_' . $txid;
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cached === 'verified';
    }

    // Sanitize and validate TXID to prevent SSRF
    if (!preg_match('/^[a-fA-F0-9]{64}$/', $txid)) {
        return false;
    }

    // Call ElectraProtocol API to verify transaction
    $api_url = 'https://api.electraprotocol.com/api/v2/tx/' . $txid;
    $response = wp_remote_get($api_url, array('timeout' => 5));

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Check if transaction exists and has confirmations
    $verified = false;
    if (isset($body['data']['confirmations']) && intval($body['data']['confirmations']) > 0) {
        $verified = true;
    } elseif (isset($body['confirmations']) && intval($body['confirmations']) > 0) {
        $verified = true;
    } elseif (isset($body['data']['txid']) || isset($body['txid'])) {
        // Transaction exists even if 0 confirmations
        $verified = true;
    }

    // Cache for 5 minutes
    set_transient($cache_key, $verified ? 'verified' : 'pending', 300);

    return $verified;
}

/**
 * Helper to parse token configuration string into array
 * Format: id,name,source,price_id,api_key,decimals
 */
function wc_omnixep_parse_token_config($config_str)
{
    if (empty($config_str)) {
        return [
            [
                'id' => '0',
                'name' => 'XEP',
                'source' => 'mexc',
                'price_id' => 'XEPUSDT',
                'decimals' => 8
            ]
        ];
    }

    $tokens = [];
    $lines = explode("\n", $config_str);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line))
            continue;

        $parts = explode(',', $line);
        $count = count($parts);

        // Standard 5-part: id,name,source,price_id,decimals
        if ($count === 5 && in_array(strtolower(trim($parts[2])), ['mexc', 'coingecko', 'dextrade'])) {
            $tokens[] = [
                'id' => trim($parts[0]),
                'name' => trim($parts[1]),
                'source' => strtolower(trim($parts[2])),
                'price_id' => trim($parts[3]),
                'decimals' => intval(trim($parts[4]))
            ];
        }
        // Legacy 6-part (with api_key): id,name,source,price_id,api_key,decimals
        else if ($count >= 6) {
            $tokens[] = [
                'id' => trim($parts[0]),
                'name' => trim($parts[1]),
                'source' => strtolower(trim($parts[2])),
                'price_id' => trim($parts[3]),
                'decimals' => intval(trim($parts[5]))
            ];
        }
        // Legacy 4-part: id,name,cg_id,decimals
        else if ($count === 4) {
            $tokens[] = [
                'id' => trim($parts[0]),
                'name' => trim($parts[1]),
                'source' => 'coingecko',
                'price_id' => trim($parts[2]),
                'decimals' => intval(trim($parts[3]))
            ];
        }
        // Legacy 3-part: id,name,cg_id
        else if ($count === 3) {
            $tokens[] = [
                'id' => trim($parts[0]),
                'name' => trim($parts[1]),
                'source' => 'coingecko',
                'price_id' => trim($parts[2]),
                'decimals' => 8
            ];
        }
    }

    if (empty($tokens)) {
        return [
            [
                'id' => '0',
                'name' => 'XEP',
                'source' => 'coingecko',
                'price_id' => 'electra-protocol',
                'api_key' => '',
                'decimals' => 8
            ]
        ];
    }

    return $tokens;
}

/**
 * Render custom 'token_table' field in WooCommerce settings
 */
function wc_omnixep_render_token_table_field($value)
{
    $field_id = $value['id'];
    $field_value = get_option($field_id, $value['default']);

    // Use the robust parser
    $tokens = wc_omnixep_parse_token_config($field_value);

    // If empty, add a default row for XEP
    if (empty($tokens)) {
        $tokens[] = ['id' => '0', 'name' => 'XEP', 'cg_id' => 'electra-protocol', 'decimals' => '8', 'mexc_symbol' => 'XEPUSDT'];
    }

    ?>
    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($value['title']); ?></label>
            <?php echo isset($value['description']) ? wc_help_tip($value['description']) : ''; ?>
        </th>
        <td class="forminp">
            <style>
                .omnixep-token-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                    border: 1px solid #ccd0d4;
                    max-width: 800px;
                }

                .omnixep-token-table th,
                .omnixep-token-table td {
                    padding: 10px;
                    border: 1px solid #ccd0d4;
                    text-align: left;
                }

                .omnixep-token-table th {
                    background: #f8f9fa;
                    font-weight: 600;
                }

                .omnixep-token-table input {
                    width: 100%;
                    box-sizing: border-box;
                    padding: 5px;
                    border: 1px solid #ddd;
                }

                .omnixep-remove-token {
                    color: #d63638;
                    cursor: pointer;
                    font-size: 20px;
                    line-height: 1;
                    font-weight: bold;
                }

                .omnixep-remove-token:hover {
                    color: #b32d2e;
                }

                #omnixep-add-token {
                    margin-top: 5px;
                }
            </style>

            <table class="omnixep-token-table" id="omnixep-token-config-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Token ID</th>
                        <th>Token Name</th>
                        <th>CoinGecko ID</th>
                        <th style="width: 80px;">Decimals</th>
                        <th>Price Source (Ex: BINANCE:SYMBOL)</th>
                        <th style="width: 40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $token): ?>
                        <tr class="token-row">
                            <td><input type="text" class="token-id" value="<?php echo esc_attr($token['id']); ?>"
                                    placeholder="e.g. 0"></td>
                            <td><input type="text" class="token-name" value="<?php echo esc_attr($token['name']); ?>"
                                    placeholder="e.g. XEP"></td>
                            <td><input type="text" class="token-cgid"
                                    value="<?php echo esc_attr(isset($token['price_id']) ? $token['price_id'] : (isset($token['cg_id']) ? $token['cg_id'] : '')); ?>"
                                    placeholder="e.g. electra-protocol"></td>
                            <td><input type="number" class="token-decimals" value="<?php echo esc_attr($token['decimals']); ?>"
                                    placeholder="8"></td>
                            <td><input type="text" class="token-mexc"
                                    value="<?php echo esc_attr(isset($token['mexc_symbol']) ? $token['mexc_symbol'] : ''); ?>"
                                    placeholder="Ex: BINANCE:XEPUSDT or MEXC:XEPUSDT"></td>
                            <td><span class="omnixep-remove-token" title="Remove">&times;</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" class="button" id="omnixep-add-token">+ Add New Token</button>
            <p class="description"><?php echo isset($value['description']) ? esc_html($value['description']) : ''; ?></p>

            <input type="hidden" name="<?php echo esc_attr($field_id); ?>" id="<?php echo esc_attr($field_id); ?>"
                value="<?php echo esc_attr($field_value); ?>">

            <script>
                jQuery(document).ready(function ($) {
                    var $table = $('#omnixep-token-config-table tbody');
                    var $hiddenInput = $('#<?php echo esc_js($field_id); ?>');

                    function updateHiddenInput() {
                        var rows = [];
                        $table.find('.token-row').each(function () {
                            var id = $(this).find('.token-id').val().trim();
                            var name = $(this).find('.token-name').val().trim();
                            var cgid = $(this).find('.token-cgid').val().trim();
                            var decimals = $(this).find('.token-decimals').val().trim();
                            var mexc = $(this).find('.token-mexc').val().trim();
                            if (id !== '' && name !== '') {
                                rows.push(id + ',' + name + ',' + cgid + ',' + (decimals !== '' ? decimals : '8') + ',' + mexc);
                            }
                        });
                        $hiddenInput.val(rows.join('\n'));
                    }

                    $('#omnixep-add-token').on('click', function () {
                        var $newRow = $('<tr class="token-row">' +
                            '<td><input type="text" class="token-id" placeholder="ID"></td>' +
                            '<td><input type="text" class="token-name" placeholder="Name"></td>' +
                            '<td><input type="text" class="token-cgid" placeholder="CoinGecko ID"></td>' +
                            '<td><input type="number" class="token-decimals" value="8" placeholder="8"></td>' +
                            '<td><input type="text" class="token-mexc" placeholder="Ex: BINANCE:XEPUSDT"></td>' +
                            '<td><span class="omnixep-remove-token" title="Remove">&times;</span></td>' +
                            '</tr>');
                        $table.append($newRow);
                        updateHiddenInput();
                    });

                    $(document).on('click', '.omnixep-remove-token', function () {
                        if ($table.find('.token-row').length > 1) {
                            $(this).closest('tr').remove();
                            updateHiddenInput();
                        } else {
                            alert('At least one token is required.');
                        }
                    });

                    $(document).on('change keyup', '#omnixep-token-config-table input', function () {
                        updateHiddenInput();
                    });

                    // Form submission check
                    $('form').on('submit', function () {
                        updateHiddenInput();
                    });

                    // Initial update to ensure hidden input reflects current table state
                    updateHiddenInput();
                });
            </script>
        </td>
    </tr>
    <?php
}
add_action('woocommerce_admin_field_token_table', 'wc_omnixep_render_token_table_field');

/**
 * Display ElectraPay logo in footer if module is active
 */
function wc_omnixep_display_footer_logo()
{
    // Don't show in admin
    if (is_admin()) {
        return;
    }

    $settings = get_option('woocommerce_omnixep_settings', array());
    $enabled = isset($settings['enabled']) ? $settings['enabled'] : 'no';

    if ($enabled === 'yes') {
        $logo_url = plugins_url('electrapay.png', __FILE__);
        ?>
        <div class="omnixep-footer-logo" style="text-align: center; padding: 30px 0; width: 100%; clear: both;">
            <a href="https://shops.electraprotocol.com" target="_blank" rel="noopener">
                <img src="<?php echo esc_url($logo_url); ?>" alt="ElectraPay"
                    style="max-width: 125px; height: auto; display: inline-block; vertical-align: middle; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1)); transition: transform 0.3s ease;">
            </a>
            <style>
                .omnixep-footer-logo img:hover {
                    transform: scale(1.05);
                }
            </style>
        </div>
        <?php
    }
}
add_action('wp_footer', 'wc_omnixep_display_footer_logo', 20);

/**
 * Add "Platform" column to WooCommerce Orders List
 */
add_filter('manage_edit-shop_order_columns', 'wc_omnixep_add_platform_column'); // Legacy
add_filter('manage_woocommerce_page_wc-orders_columns', 'wc_omnixep_add_platform_column'); // HPOS
function wc_omnixep_add_platform_column($columns)
{
    $new_columns = array();
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        if ($key === 'order_total' || $key === 'total') {
            $new_columns['omnixep_platform'] = 'Platform';
        }
    }
    return $new_columns;
}

/**
 * Populate "Platform" column in WooCommerce Orders List
 */
add_action('manage_shop_order_posts_custom_column', 'wc_omnixep_populate_platform_column', 10, 2); // Legacy
add_action('manage_woocommerce_page_wc-orders_custom_column', 'wc_omnixep_populate_platform_column_hpos', 10, 2); // HPOS

function wc_omnixep_populate_platform_column($column, $post_id)
{
    if ($column === 'omnixep_platform') {
        $order = wc_get_order($post_id);
        if ($order) {
            $platform = $order->get_meta('_omnixep_platform');
            if (empty($platform)) {
                // If not mobile, check if it's a web payment
                $txid = $order->get_meta('_omnixep_txid');
                if (!empty($txid)) {
                    echo '<span class="badge" style="background:#4dabf7;color:white;padding:3px 8px;border-radius:4px;font-size:11px;">Web / Extension</span>';
                } else {
                    echo '<span style="color:#adb5bd;">-</span>';
                }
            } else {
                $color = (strpos($platform, 'Android') !== false) ? '#a4c639' : ((strpos($platform, 'iOS') !== false) ? '#8e8e93' : '#4dabf7');
                echo '<span class="badge" style="background:' . $color . ';color:white;padding:3px 8px;border-radius:4px;font-size:11px;">' . esc_html($platform) . '</span>';
            }
        }
    }
}

function wc_omnixep_populate_platform_column_hpos($column, $order)
{
    if ($column === 'omnixep_platform') {
        $platform = $order->get_meta('_omnixep_platform');
        if (empty($platform)) {
            $txid = $order->get_meta('_omnixep_txid');
            if (!empty($txid)) {
                echo '<span class="badge" style="background:#4dabf7;color:white;padding:3px 8px;border-radius:4px;font-size:11px;">Web / Extension</span>';
            } else {
                echo '<span style="color:#adb5bd;">-</span>';
            }
        } else {
            $color = (strpos($platform, 'Android') !== false) ? '#a4c639' : ((strpos($platform, 'iOS') !== false) ? '#8e8e93' : '#4dabf7');
            echo '<span class="badge" style="background:' . $color . ';color:white;padding:3px 8px;border-radius:4px;font-size:11px;">' . esc_html($platform) . '</span>';
        }
    }
}

// Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â
// CUSTOMER FEEDBACK SYSTEM
// Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â

/**
 * Submit customer feedback to API
 * 
 * @param array $data Feedback data
 * @return array API response
 */
function wc_omnixep_submit_feedback($data)
{
    $api_url = 'https://api.planc.space/api';
    $merchant_id = get_option('omnixep_merchant_id');
    
    // If merchant_id not set, generate from site URL
    if (empty($merchant_id)) {
        $merchant_id = md5(get_site_url());
        update_option('omnixep_merchant_id', $merchant_id);
    }
    
    $payload = array(
        'action' => 'submit_feedback',
        'site_url' => get_site_url(),
        'merchant_id' => (string) $merchant_id,
        'order_id' => (string) sanitize_text_field($data['order_id'] ?? ''),
        'category' => (string) sanitize_text_field($data['category'] ?? ''),
        'description' => (string) sanitize_textarea_field($data['description'] ?? ''),
        'customer_email' => (string) sanitize_email($data['email'] ?? ''),
        'customer_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
        'submitted_at' => (string) current_time('c')
    );
    $feedback_body = json_encode(wc_omnixep_canonical_json($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $feedback_headers = array('Content-Type' => 'application/json');
    $secret = wc_omnixep_get_api_secret();
    // DEBUG: Log signature details (REMOVE AFTER DEBUGGING)
    error_log('=== OMNIXEP FEEDBACK DEBUG ===');
    error_log('SECRET (first 10): ' . substr($secret, 0, 10) . '...');
    error_log('SECRET LENGTH: ' . strlen($secret));
    error_log('BODY: ' . $feedback_body);
    error_log('BODY LENGTH: ' . strlen($feedback_body));
    if ($secret !== '') {
        $sig = wc_omnixep_sign_api_body($feedback_body, $secret);
        error_log('SIGNATURE: ' . $sig);
        $feedback_headers['X-OmniXEP-Signature'] = $sig;
    } else {
        error_log('SECRET IS EMPTY - NO SIGNATURE WILL BE SENT');
    }
    error_log('HEADERS: ' . print_r($feedback_headers, true));
    error_log('=== END OMNIXEP FEEDBACK DEBUG ===');
    $response = wp_remote_post($api_url, array(
        'headers' => $feedback_headers,
        'body' => $feedback_body,
        'timeout' => 15
    ));
    
    if (is_wp_error($response)) {
        error_log('OmniXEP Feedback Error: ' . $response->get_error_message());
        return array(
            'success' => false,
            'message' => 'Connection error. Please try again later.',
            'error' => $response->get_error_message()
        );
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Handle non-200 responses
    if ($status_code !== 200) {
        error_log('OmniXEP Feedback API Error (HTTP ' . $status_code . '): ' . $body);
        
        // Return error message from API if available
        if (!empty($result['error'])) {
            return array(
                'success' => false,
                'message' => $result['error'],
                'error' => $result['error']
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Server error. Please try again later.',
            'error' => 'HTTP ' . $status_code
        );
    }
    
    // Log successful submission
    if (!empty($result['success'])) {
        error_log('✅ Customer Feedback Submitted to API: ' . ($result['reference_number'] ?? 'N/A'));
    } else {
        error_log('Ã¢ÂÅ’ Customer Feedback Failed: ' . ($result['error'] ?? 'Unknown error'));
    }
    
    return $result;
}

/**
 * AJAX Handler for feedback submission (logged-in users)
 */
function wc_omnixep_ajax_submit_feedback()
{
    check_ajax_referer('omnixep_feedback_nonce', 'nonce');
    
    // Bot detection: Check honeypot field
    if (!empty($_POST['website'])) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid submission detected.'
        ));
        return;
    }
    
    // Bot detection: Check form submission time (must be at least 3 seconds)
    if (!empty($_POST['form_loaded_at'])) {
        $formLoadedAt = intval($_POST['form_loaded_at']);
        $timeDiff = (microtime(true) * 1000) - $formLoadedAt;
        if ($timeDiff < 3000) {
            wp_send_json(array(
                'success' => false,
                'message' => 'Please take a moment to review your submission.'
            ));
            return;
        }
    }
    
    $result = wc_omnixep_submit_feedback($_POST);
    wp_send_json($result);
}
add_action('wp_ajax_omnixep_submit_feedback', 'wc_omnixep_ajax_submit_feedback');

/**
 * AJAX Handler for feedback submission (non-logged-in users)
 */
function wc_omnixep_ajax_submit_feedback_nopriv()
{
    check_ajax_referer('omnixep_feedback_nonce', 'nonce');
    
    // Bot detection: Check honeypot field
    if (!empty($_POST['website'])) {
        wp_send_json(array(
            'success' => false,
            'message' => 'Invalid submission detected.'
        ));
        return;
    }
    
    // Bot detection: Check form submission time (must be at least 3 seconds)
    if (!empty($_POST['form_loaded_at'])) {
        $formLoadedAt = intval($_POST['form_loaded_at']);
        $timeDiff = (microtime(true) * 1000) - $formLoadedAt;
        if ($timeDiff < 3000) {
            wp_send_json(array(
                'success' => false,
                'message' => 'Please take a moment to review your submission.'
            ));
            return;
        }
    }
    
    $result = wc_omnixep_submit_feedback($_POST);
    wp_send_json($result);
}
add_action('wp_ajax_nopriv_omnixep_submit_feedback', 'wc_omnixep_ajax_submit_feedback_nopriv');

/**
 * Enqueue feedback form scripts and styles
 */
function wc_omnixep_enqueue_feedback_scripts()
{
    // Only load on frontend
    if (is_admin()) {
        return;
    }
    
    // Inline script for feedback form
    wp_add_inline_script('jquery', "
        var omnixep_feedback = {
            ajax_url: '" . admin_url('admin-ajax.php') . "',
            nonce: '" . wp_create_nonce('omnixep_feedback_nonce') . "'
        };
    ");
}
add_action('wp_enqueue_scripts', 'wc_omnixep_enqueue_feedback_scripts');

/**
 * Background sync feedback to API
 */
function omnixep_sync_feedback_to_api_handler($feedback_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnixep_feedback';
    
    // Get feedback from database
    $feedback = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE feedback_id = %s",
        $feedback_id
    ), ARRAY_A);
    
    if (!$feedback) {
        return;
    }
    
    // Try to send to API
    $api_url = 'https://api.planc.space/api';
    $payload = array(
        'action' => 'submit_feedback',
        'site_url' => (string) $feedback['site_url'],
        'merchant_id' => (string) $feedback['merchant_id'],
        'order_id' => (string) $feedback['order_id'],
        'category' => (string) $feedback['category'],
        'description' => (string) $feedback['description'],
        'customer_email' => (string) $feedback['customer_email'],
        'customer_ip' => (string) $feedback['customer_ip'],
        'user_agent' => (string) $feedback['user_agent'],
        'submitted_at' => (string) $feedback['submitted_at']
    );
    $sync_body = json_encode(wc_omnixep_canonical_json($payload), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $sync_headers = array('Content-Type' => 'application/json');
    $secret = wc_omnixep_get_api_secret();
    if ($secret !== '') {
        $sync_headers['X-OmniXEP-Signature'] = wc_omnixep_sign_api_body($sync_body, $secret);
    }
    $response = wp_remote_post($api_url, array(
        'headers' => $sync_headers,
        'body' => $sync_body,
        'timeout' => 15,
        'blocking' => false // Non-blocking request
    ));
    
    if (!is_wp_error($response)) {
        error_log('✅ Feedback synced to API: ' . $feedback['reference_number']);
    }
}
add_action('omnixep_sync_feedback_to_api', 'omnixep_sync_feedback_to_api_handler');

/**
 * REST API endpoint for feedback list (for admin panel)
 */
function omnixep_register_feedback_rest_routes()
{
    register_rest_route('omnixep/v1', '/feedback', array(
        'methods' => 'GET',
        'callback' => 'omnixep_rest_get_feedback',
        'permission_callback' => '__return_true' // Public access (add auth later if needed)
    ));
    
    register_rest_route('omnixep/v1', '/feedback/stats', array(
        'methods' => 'GET',
        'callback' => 'omnixep_rest_get_feedback_stats',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'omnixep_register_feedback_rest_routes');

/**
 * Get feedback list
 */
function omnixep_rest_get_feedback($request)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnixep_feedback';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return new WP_REST_Response(array(
            'success' => true,
            'data' => array(),
            'total' => 0
        ), 200);
    }
    
    // Get parameters
    $limit = $request->get_param('limit') ?: 100;
    $status = $request->get_param('status');
    $severity = $request->get_param('severity');
    $category = $request->get_param('category');
    
    // Build query
    $where = array('1=1');
    if ($status) {
        $where[] = $wpdb->prepare('status = %s', $status);
    }
    if ($severity) {
        $where[] = $wpdb->prepare('severity = %s', $severity);
    }
    if ($category) {
        $where[] = $wpdb->prepare('category = %s', $category);
    }
    
    $where_clause = implode(' AND ', $where);
    
    $feedbacks = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE $where_clause ORDER BY created_at DESC LIMIT " . intval($limit),
        ARRAY_A
    );
    
    // Format dates for JavaScript
    foreach ($feedbacks as &$feedback) {
        $feedback['submitted_at'] = date('c', strtotime($feedback['submitted_at']));
        $feedback['created_at'] = date('c', strtotime($feedback['created_at']));
    }
    
    return new WP_REST_Response(array(
        'success' => true,
        'data' => $feedbacks,
        'total' => count($feedbacks)
    ), 200);
}

/**
 * Get feedback statistics
 */
function omnixep_rest_get_feedback_stats($request)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'omnixep_feedback';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return new WP_REST_Response(array(
            'success' => true,
            'total' => 0,
            'new' => 0,
            'reviewed' => 0,
            'resolved' => 0,
            'dismissed' => 0,
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0
        ), 200);
    }
    
    $stats = array(
        'success' => true,
        'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
        'new' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'new'"),
        'reviewed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'reviewed'"),
        'resolved' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'resolved'"),
        'dismissed' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'dismissed'"),
        'critical' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity = 'critical'"),
        'high' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity = 'high'"),
        'medium' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity = 'medium'"),
        'low' => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE severity = 'low'")
    );
    
    return new WP_REST_Response($stats, 200);
}







