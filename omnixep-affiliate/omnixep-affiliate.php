<?php
/**
 * Plugin Name: XEPMARKET-Affiliate
 * Plugin URI: https://xepmarket.com
 * Description: Affiliate system for WooCommerce. Users can get a unique referral link from their My Account page and earn a commission on completed sales.
 * Version: 1.0.0
 * Author: XEPMARKET
 * Author URI: https://xepmarket.com
 * Text Domain: omnixep-affiliate
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class OMNIXEPAffiliate
{

    private static $instance = null;

    /**
     * Get instance of the class
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // Activation & Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin Settings
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Affiliate Tracking
        add_action('init', array($this, 'track_affiliate_link'));

        // Checkout & Order
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_affiliate_to_order'));
        add_action('woocommerce_order_status_completed', array($this, 'process_commission'));
        add_action('woocommerce_order_status_refunded', array($this, 'revert_commission'));
        add_action('woocommerce_order_status_cancelled', array($this, 'revert_commission'));

        // My Account Integration
        add_action('init', array($this, 'add_endpoints'));
        add_filter('query_vars', array($this, 'add_query_vars'), 0);
        add_filter('woocommerce_account_menu_items', array($this, 'add_affiliate_menu_item'));
        add_action('woocommerce_account_affiliate_endpoint', array($this, 'affiliate_endpoint_content'));

        // Plugin Action Links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));
    }

    /**
     * Add Settings link to plugin actions
     */
    public function add_plugin_action_links($links)
    {
        $settings_link = '<a href="admin.php?page=omnixep-affiliate">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Activate plugin
     */
    public function activate()
    {
        $this->add_endpoints();
        flush_rewrite_rules();
    }

    /**
     * Deactivate plugin
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Admin Menu
     */
    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            'Affiliate Settings',
            'Affiliates',
            'manage_options',
            'omnixep-affiliate',
            array($this, 'admin_settings_page')
        );
    }

    /**
     * Register Settings
     */
    public function register_settings()
    {
        register_setting('omnixep_affiliate_group', 'omnixep_affiliate_rate');
        register_setting('omnixep_affiliate_group', 'omnixep_affiliate_cookie_days');
    }

    /**
     * Admin Settings Page Content
     */
    public function admin_settings_page()
    {
        // Handle payouts manually if marked
        if (isset($_POST['mark_paid']) && isset($_POST['user_id']) && current_user_can('manage_options')) {
            $u_id = intval($_POST['user_id']);
            $current_unpaid = get_user_meta($u_id, 'omnixep_affiliate_balance', true);
            $current_unpaid = $current_unpaid ? floatval($current_unpaid) : 0;

            if ($current_unpaid > 0) {
                // Add to paid balance
                $current_paid = get_user_meta($u_id, 'omnixep_affiliate_paid_balance', true);
                $current_paid = $current_paid ? floatval($current_paid) : 0;
                update_user_meta($u_id, 'omnixep_affiliate_paid_balance', $current_paid + $current_unpaid);

                // Reset unpaid
                update_user_meta($u_id, 'omnixep_affiliate_balance', 0);
                echo '<div class="notice notice-success is-dismissible"><p>Balance for User ID ' . $u_id . ' has been marked as paid and moved to their paid history.</p></div>';
            }
        }

        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'total_earned';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 100;

        // Fetch users who have affiliate data
        $args = array(
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => 'omnixep_affiliate_balance', 'compare' => 'EXISTS'),
                array('key' => 'omnixep_affiliate_history', 'compare' => 'EXISTS'),
                array('key' => 'omnixep_aff_wallet', 'compare' => 'EXISTS')
            )
        );
        $all_users = get_users($args);

        // Stats and processing
        $stat_total_affiliates = 0;
        $stat_total_sales = 0;
        $stat_total_paid = 0;
        $stat_total_unpaid = 0;

        $processed_users = array();

        foreach ($all_users as $u) {
            $balance = get_user_meta($u->ID, 'omnixep_affiliate_balance', true);
            $balance = $balance ? floatval($balance) : 0;

            $paid_balance = get_user_meta($u->ID, 'omnixep_affiliate_paid_balance', true);
            $paid_balance = $paid_balance ? floatval($paid_balance) : 0;

            $wallet_address = get_user_meta($u->ID, 'omnixep_aff_wallet', true);

            $history = get_user_meta($u->ID, 'omnixep_affiliate_history', true);
            $deals_count = is_array($history) ? count($history) : 0;

            $total_earned = $balance + $paid_balance;

            $stat_total_affiliates++;
            $stat_total_sales += $deals_count;
            $stat_total_paid += $paid_balance;
            $stat_total_unpaid += $balance;

            // Search filter
            if (!empty($search_query)) {
                $search_lower = strtolower($search_query);
                if (
                    strpos(strtolower($u->display_name), $search_lower) === false &&
                    strpos(strtolower($u->user_email), $search_lower) === false &&
                    strpos(strtolower($wallet_address), $search_lower) === false
                ) {
                    continue; // Skip if it doesn't match
                }
            }

            $processed_users[] = array(
                'user' => $u,
                'balance' => $balance,
                'paid_balance' => $paid_balance,
                'total_earned' => $total_earned,
                'wallet_address' => $wallet_address,
                'deals_count' => $deals_count,
                'history' => $history
            );
        }

        // Sorting
        usort($processed_users, function ($a, $b) use ($orderby, $order) {
            $val_a = 0;
            $val_b = 0;

            if ($orderby === 'unpaid_balance') {
                $val_a = $a['balance'];
                $val_b = $b['balance'];
            } elseif ($orderby === 'paid_commissions') {
                $val_a = $a['paid_balance'];
                $val_b = $b['paid_balance'];
            } elseif ($orderby === 'deals') {
                $val_a = $a['deals_count'];
                $val_b = $b['deals_count'];
            } else {
                $val_a = $a['total_earned'];
                $val_b = $b['total_earned']; // default
            }

            if ($val_a == $val_b)
                return 0;
            $cmp = ($val_a < $val_b) ? -1 : 1;
            return ($order === 'asc') ? $cmp : -$cmp;
        });

        // Pagination
        $total_items = count($processed_users);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($paged - 1) * $per_page;
        $affiliate_users_page = array_slice($processed_users, $offset, $per_page);

        // Sort URL helper
        $base_url = admin_url('admin.php?page=omnixep-affiliate');
        if (!empty($search_query)) {
            $base_url = add_query_arg('s', urlencode($search_query), $base_url);
        }

        ?>
        <style>
            .xep-admin-wrap {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                margin-top: 20px;
                margin-right: 20px;
            }

            .xep-aff-header {
                background: linear-gradient(135deg, #1e1e2d 0%, #151521 100%);
                border-radius: 12px;
                padding: 25px 30px;
                color: #fff;
                display: flex;
                align-items: center;
                gap: 20px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                margin-bottom: 30px;
                border: 1px solid rgba(255, 255, 255, 0.05);
            }

            .xep-aff-header h1 {
                margin: 0;
                color: #fff;
                font-size: 24px;
                font-weight: 600;
                letter-spacing: -0.5px;
            }

            .xep-aff-settings-card {
                background: #fff;
                border-radius: 12px;
                padding: 25px 30px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e4e7;
                margin-bottom: 30px;
            }

            .xep-aff-table-container {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                border: 1px solid #e2e4e7;
                overflow: hidden;
            }

            .xep-aff-table {
                width: 100%;
                border-collapse: collapse;
            }

            .xep-aff-table th,
            .xep-aff-table td {
                padding: 15px 20px;
                text-align: left;
            }

            .xep-aff-table th {
                background: #f8f9fa;
                font-weight: 600;
                color: #1d2327;
                border-bottom: 2px solid #e2e4e7;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }

            .xep-aff-table td {
                border-bottom: 1px solid #f0f0f1;
                color: #50575e;
            }

            .xep-aff-table tr:hover td {
                background: #fcfcfc;
            }

            .xep-badge {
                background: rgba(0, 242, 255, 0.1);
                color: #008a91;
                padding: 4px 10px;
                border-radius: 50px;
                font-weight: 600;
                font-size: 12px;
            }

            .xep-badge-high {
                background: rgba(0, 163, 42, 0.1);
                color: #00a32a;
            }
        </style>

        <div class="xep-admin-wrap">
            <div class="xep-aff-header">
                <div
                    style="background: rgba(0, 242, 255, 0.1); border: 1px solid rgba(0, 242, 255, 0.2); width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #00f2ff; font-size: 24px;">
                    <span class="dashicons dashicons-networking"></span>
                </div>
                <div>
                    <h1>Affiliate Management System</h1>
                    <p style="margin: 5px 0 0; color: rgba(255,255,255,0.6); font-size: 14px;">Manage global commission rates
                        and track your active affiliates' earnings.</p>
                </div>
            </div>

            <div class="xep-aff-settings-card">
                <h2 style="margin-top:0; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">Global
                    Configuration</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('omnixep_affiliate_group'); ?>
                    <?php do_settings_sections('omnixep_affiliate_group'); ?>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <div>
                            <label style="font-weight:600; display:block; margin-bottom: 8px;">Commission Rate (%)</label>
                            <input type="number" step="0.01" name="omnixep_affiliate_rate"
                                value="<?php echo esc_attr(get_option('omnixep_affiliate_rate', 10)); ?>"
                                style="width: 100%; max-width: 250px; padding: 6px 10px; border-radius: 4px;" />
                            <p class="description">Default percentage granted to affiliates upon completed order.</p>
                        </div>
                        <div>
                            <label style="font-weight:600; display:block; margin-bottom: 8px;">Cookie Duration (Days)</label>
                            <input type="number" name="omnixep_affiliate_cookie_days"
                                value="<?php echo esc_attr(get_option('omnixep_affiliate_cookie_days', 30)); ?>"
                                style="width: 100%; max-width: 250px; padding: 6px 10px; border-radius: 4px;" />
                            <p class="description">Number of days the referral tracking cookie remains active.</p>
                        </div>
                    </div>
                    <div style="margin-top: 25px;">
                        <?php submit_button('Save Configuration', 'primary', 'submit', false, array('style' => 'border-radius: 6px; padding: 4px 20px;')); ?>
                    </div>
                </form>
            </div>

            <!-- Summary Cards -->
            <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
                <div
                    style="flex:1; min-width: 200px; background:#fff; border-radius:12px; padding:20px; border:1px solid #e2e4e7; box-shadow:0 4px 15px rgba(0,0,0,0.05); text-align:center;">
                    <div
                        style="font-size:12px; color:#646970; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-bottom:10px;">
                        Total Affiliates</div>
                    <div style="font-size:28px; font-weight:700; color:#1d2327;">
                        <?php echo number_format($stat_total_affiliates); ?></div>
                </div>
                <div
                    style="flex:1; min-width: 200px; background:#fff; border-radius:12px; padding:20px; border:1px solid #e2e4e7; box-shadow:0 4px 15px rgba(0,0,0,0.05); text-align:center;">
                    <div
                        style="font-size:12px; color:#646970; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-bottom:10px;">
                        Total Sales (Deals)</div>
                    <div style="font-size:28px; font-weight:700; color:#1d2327;"><?php echo number_format($stat_total_sales); ?>
                    </div>
                </div>
                <div
                    style="flex:1; min-width: 200px; background:#fff; border-radius:12px; padding:20px; border:1px solid #e2e4e7; box-shadow:0 4px 15px rgba(0,0,0,0.05); text-align:center;">
                    <div
                        style="font-size:12px; color:#646970; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-bottom:10px;">
                        Total Paid</div>
                    <div style="font-size:28px; font-weight:700; color:#00a32a;">
                        <?php echo number_format($stat_total_paid, 2); ?> <span style="font-size:14px;">XEP</span></div>
                </div>
                <div
                    style="flex:1; min-width: 200px; background:#fff; border-radius:12px; padding:20px; border:1px solid #e2e4e7; box-shadow:0 4px 15px rgba(0,0,0,0.05); text-align:center;">
                    <div
                        style="font-size:12px; color:#646970; text-transform:uppercase; font-weight:600; letter-spacing:0.5px; margin-bottom:10px;">
                        Total Unpaid</div>
                    <div style="font-size:28px; font-weight:700; color:#d63638;">
                        <?php echo number_format($stat_total_unpaid, 2); ?> <span style="font-size:14px;">XEP</span></div>
                </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Active Affiliates Overview</h2>
                <form method="get" style="display:flex; gap:10px; align-items:center;">
                    <input type="hidden" name="page" value="omnixep-affiliate">
                    <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr($order); ?>">
                    <input type="text" name="s" value="<?php echo esc_attr($search_query); ?>"
                        placeholder="Search Name, Email or Wallet..."
                        style="padding: 5px 10px; border-radius: 4px; min-width: 250px;">
                    <button type="submit" class="button">Search</button>
                    <?php if (!empty($search_query)): ?>
                        <a href="<?php echo esc_url($base_url); ?>" class="button">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="xep-aff-table-container">
                <table class="xep-aff-table">
                    <?php
                    $get_sort_url = function ($col) use ($base_url, $orderby, $order) {
                        $new_order = ($orderby === $col && $order === 'asc') ? 'desc' : 'asc';
                        return add_query_arg(array('orderby' => $col, 'order' => $new_order), $base_url);
                    };
                    ?>
                    <thead>
                        <tr>
                            <th>User Info &amp; Wallet</th>
                            <th>Referral Link</th>
                            <th><a href="<?php echo esc_url($get_sort_url('total_earned')); ?>"
                                    style="text-decoration:none; color:inherit;">Total Earned
                                    <?php if ($orderby === 'total_earned')
                                        echo $order === 'asc' ? '&uarr;' : '&darr;'; ?></a></th>
                            <th><a href="<?php echo esc_url($get_sort_url('paid_commissions')); ?>"
                                    style="text-decoration:none; color:inherit;">Paid Commissions
                                    <?php if ($orderby === 'paid_commissions')
                                        echo $order === 'asc' ? '&uarr;' : '&darr;'; ?></a></th>
                            <th><a href="<?php echo esc_url($get_sort_url('unpaid_balance')); ?>"
                                    style="text-decoration:none; color:inherit;">Unpaid Balance
                                    <?php if ($orderby === 'unpaid_balance')
                                        echo $order === 'asc' ? '&uarr;' : '&darr;'; ?></a></th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($affiliate_users_page)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #888;">No active affiliates found
                                    matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($affiliate_users_page as $row):
                                $user = $row['user'];
                                $balance = $row['balance'];
                                $paid_balance = $row['paid_balance'];
                                $wallet_address = $row['wallet_address'];
                                $history = $row['history'];
                                $deals_count = $row['deals_count'];
                                $total_earned = $row['total_earned'];

                                $ref_link = add_query_arg('ref', $user->ID, site_url('/'));
                                ?>
                                <tr>
                                    <td style="display:flex; align-items:center; gap:30px; border-bottom: none;">
                                        <div style="min-width: 150px;">
                                            <strong><?php echo esc_html($user->display_name); ?></strong> <small
                                                style="color:#aaa;">(ID: <?php echo $user->ID; ?>)</small><br>
                                            <a href="mailto:<?php echo esc_attr($user->user_email); ?>"
                                                style="font-size:12px;"><?php echo esc_html($user->user_email); ?></a>
                                        </div>
                                        <?php if ($wallet_address):
                                            // Construct the data string. E.g: xep:WalletAddress?amount=123.45 (Only add amount if > 0)
                                            $qr_data = $wallet_address;
                                            if ($balance > 0) {
                                                // Using a standard cryptocurrency URI format
                                                $qr_data = "electraprotocol:" . $wallet_address . "?amount=" . number_format($balance, 2, '.', '');
                                            }
                                            $qr_url_small = "https://api.qrserver.com/v1/create-qr-code/?size=40x40&data=" . urlencode($qr_data);
                                            $qr_url_large = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qr_data);
                                            ?>
                                            <div
                                                style="display:flex; align-items:center; gap: 12px; background:#f4f5f7; padding:8px 12px; border-radius:8px; border:1px solid #e1e1e1;">

                                                <!-- Small QR Code indicating clickability -->
                                                <img src="<?php echo esc_url($qr_url_small); ?>" width="40" height="40" alt="QR"
                                                    style="border-radius:4px; border: 1px solid #ddd; cursor: zoom-in;"
                                                    onclick="document.getElementById('qr-modal-<?php echo $user->ID; ?>').style.display='flex'">

                                                <div>
                                                    <span
                                                        style="font-size:10px; color:#888; text-transform:uppercase; font-weight:600; display:block; margin-bottom: 2px;">XEP
                                                        Wallet</span>
                                                    <code
                                                        style="font-family: monospace; font-size: 11px; color:#1d2327; background:transparent; padding:0;"><?php echo esc_html($wallet_address); ?></code>
                                                </div>
                                            </div>

                                            <!-- Modal for Large QR Code -->
                                            <div id="qr-modal-<?php echo $user->ID; ?>"
                                                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:99999; justify-content:center; align-items:center;"
                                                onclick="this.style.display='none'">
                                                <div style="background:#fff; padding:30px; border-radius:12px; text-align:center; box-shadow: 0 10px 40px rgba(0,0,0,0.3);"
                                                    onclick="event.stopPropagation();">
                                                    <h3 style="margin-top:0; color:#1d2327;">Scan to Pay</h3>
                                                    <img src="<?php echo esc_url($qr_url_large); ?>" width="200" height="200" alt="Large QR"
                                                        style="border: 1px solid #eee; padding: 10px; border-radius: 8px;">
                                                    <p
                                                        style="margin-top:20px; font-family:monospace; font-size:13px; color:#555; word-wrap: break-word; max-width: 250px;">
                                                        <?php echo esc_html($wallet_address); ?>
                                                    </p>
                                                    <?php if ($balance > 0): ?>
                                                        <div
                                                            style="margin-top:15px; background: rgba(0,163,42,0.1); color: #00a32a; padding: 8px 15px; border-radius: 5px; font-weight: bold; font-size: 16px;">
                                                            Amount: <?php echo number_format($balance, 2); ?> XEP
                                                        </div>
                                                    <?php endif; ?>
                                                    <button type="button" class="button" style="margin-top:20px; width: 100%;"
                                                        onclick="document.getElementById('qr-modal-<?php echo $user->ID; ?>').style.display='none'">Close</button>
                                                </div>
                                            </div>

                                        <?php else: ?>
                                            <div
                                                style="font-size:11px; color:#999; padding:8px 15px; border-radius:8px; border:1px dashed #ddd; background:#fafafa;">
                                                <em>No wallet provided</em>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" value="<?php echo esc_url($ref_link); ?>" readonly
                                            style="width: 100%; max-width: 150px; font-size: 12px; background: #f9f9f9;"
                                            onclick="this.select();">
                                    </td>
                                    <td>
                                        <strong><?php echo number_format($total_earned, 2); ?> XEP</strong>
                                        <br><small style="color:#aaa;"><?php echo $deals_count . ' deals'; ?></small>
                                    </td>
                                    <td style="color:#00a32a; font-weight:600;">
                                        <?php echo number_format($paid_balance, 2); ?> XEP
                                    </td>
                                    <td>
                                        <?php if ($balance > 0): ?>
                                            <span class="xep-badge xep-badge-high"><?php echo number_format($balance, 2); ?> XEP</span>
                                        <?php else: ?>
                                            <span class="xep-badge"><?php echo number_format($balance, 2); ?> XEP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($balance > 0): ?>
                                            <form method="post" style="margin:0;"
                                                onsubmit="return confirm('Are you sure you want to mark this balance as paid? This will reset their unpaid balance and move it to paid commissions.');">
                                                <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                                <button type="submit" name="mark_paid" class="button button-small"
                                                    style="border-color: #00a32a; color: #00a32a;">Mark as Paid</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color:#aaa; font-size: 12px;">Settled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div style="margin-top: 20px; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                    <span style="color:#646970;">Page <?php echo $paged; ?> of <?php echo $total_pages; ?></span>
                    <?php if ($paged > 1): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $paged - 1, $base_url)); ?>" class="button">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php if ($paged < $total_pages): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $paged + 1, $base_url)); ?>" class="button">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }

    /**
     * Add Endpoint
     */
    public function add_endpoints()
    {
        add_rewrite_endpoint('affiliate', EP_ROOT | EP_PAGES);
    }

    /**
     * Add Query Vars
     */
    public function add_query_vars($vars)
    {
        $vars[] = 'affiliate';
        return $vars;
    }

    /**
     * Add My Account Menu Item
     */
    public function add_affiliate_menu_item($items)
    {
        $new_items = array();
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'orders') {
                $new_items['affiliate'] = 'Affiliate Dashboard';
            }
        }
        return $new_items;
    }

    /**
     * Affiliate Endpoint Content
     */
    public function affiliate_endpoint_content()
    {
        $user_id = get_current_user_id();

        // Handle wallet address save
        if (isset($_POST['save_xep_wallet']) && isset($_POST['omnixep_aff_wallet']) && wp_verify_nonce($_POST['xep_wallet_nonce'], 'save_wallet')) {
            $new_wallet = sanitize_text_field($_POST['omnixep_aff_wallet']);
            update_user_meta($user_id, 'omnixep_aff_wallet', $new_wallet);
            echo '<div class="woocommerce-message" role="alert">Your XEP wallet address has been updated successfully!</div>';
        }

        $rate = get_option('omnixep_affiliate_rate', 10);
        $affiliate_link = add_query_arg('ref', $user_id, site_url('/'));

        $balance = get_user_meta($user_id, 'omnixep_affiliate_balance', true);
        if (!$balance)
            $balance = 0;

        $paid_balance = get_user_meta($user_id, 'omnixep_affiliate_paid_balance', true);
        if (!$paid_balance)
            $paid_balance = 0;

        $wallet_address = get_user_meta($user_id, 'omnixep_aff_wallet', true);

        $history = get_user_meta($user_id, 'omnixep_affiliate_history', true);
        if (!is_array($history))
            $history = array();

        // Sort history descending by date
        usort($history, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        ?>
        <h3>My Affiliate Dashboard</h3>
        <p>Share your unique referral link to earn <strong>
                <?php echo esc_html($rate); ?>% commission
            </strong> on every successful sale! Commissions are paid in <strong>XEP</strong>.</p>

        <div
            style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.08); padding: 15px 20px; border-radius: 12px; margin-bottom: 30px;">
            <form method="post" action="">
                <?php wp_nonce_field('save_wallet', 'xep_wallet_nonce'); ?>
                <label style="display:block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #ddd;">Your XEP
                    Payout Wallet Address:</label>
                <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                    <input type="text" name="omnixep_aff_wallet" value="<?php echo esc_attr($wallet_address); ?>"
                        placeholder="Enter your XEP wallet address starting with X..."
                        style="flex: 1; min-width: 250px; border-radius: 8px; padding: 10px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); color: #fff; font-family: monospace;">
                    <button type="submit" name="save_xep_wallet" class="button"
                        style="border-radius: 8px; white-space:nowrap;">Save Wallet</button>
                </div>
                <p style="font-size: 12px; color: #999; margin-top: 8px; margin-bottom: 0;">We will send your earned commissions
                    to this address.</p>
            </form>
        </div>

        <div
            style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; margin-bottom: 30px;">
            <label style="display:block; margin-bottom: 8px; font-weight: 600;">Your Unique Affiliate Link:</label>
            <div style="display:flex; gap: 10px;">
                <input type="text" id="xep-affiliate-link" value="<?php echo esc_url($affiliate_link); ?>" readonly
                    style="width: 100%; border-radius: 8px; padding: 10px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                <button type="button" class="button" onclick="copyAffiliateLink()"
                    style="border-radius: 8px; white-space:nowrap;">Copy Link</button>
            </div>
        </div>

        <div
            style="display:flex; flex-wrap: wrap; gap: 20px; justify-content: space-between; align-items: center; background: linear-gradient(135deg, rgba(0,242,255,0.1), rgba(112,0,255,0.1)); border: 1px solid rgba(0,242,255,0.2); padding: 25px; border-radius: 12px; margin-bottom: 30px;">
            <div style="flex: 1; min-width: 120px;">
                <h4
                    style="margin: 0; color: rgba(255,255,255,0.7); text-transform:uppercase; font-size: 13px; letter-spacing:1px;">
                    Unpaid Balance</h4>
                <div style="font-size: 32px; font-weight: 700; color: #00f2ff;">
                    <?php echo number_format($balance, 2); ?> <span style="font-size: 16px; opacity: 0.8;">XEP</span>
                </div>
            </div>
            <div style="flex: 1; min-width: 120px; border-left: 1px solid rgba(255,255,255,0.1); padding-left: 20px;">
                <h4
                    style="margin: 0; color: rgba(255,255,255,0.7); text-transform:uppercase; font-size: 13px; letter-spacing:1px;">
                    Total Paid</h4>
                <div style="font-size: 32px; font-weight: 700; color: #00a32a;">
                    <?php echo number_format($paid_balance, 2); ?> <span style="font-size: 16px; opacity: 0.8;">XEP</span>
                </div>
            </div>
            <div>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>?subject=Payout Request - Affiliate ID: <?php echo $user_id; ?>&body=Hello, I would like to request a payout for my affiliate earnings. My XEP Wallet Address is: <?php echo esc_attr($wallet_address); ?>"
                    class="button"
                    style="background: #00f2ff !important; background-image: none !important; color: #000 !important; font-weight: bold !important; border:none !important; box-shadow: 0 4px 15px rgba(0,242,255,0.3) !important; padding: 10px 20px !important; border-radius: 8px !important;">Request
                    Payout</a>
            </div>
        </div>

        <h4>Commission History</h4>
        <?php if (!empty($history)): ?>
            <table
                class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table"
                style="background: rgba(0,0,0,0.2); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                <thead>
                    <tr>
                        <th class="woocommerce-orders-table__header"
                            style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1);"><span
                                class="nobr">Order ID</span></th>
                        <th class="woocommerce-orders-table__header"
                            style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1);"><span
                                class="nobr">Date</span></th>
                        <th class="woocommerce-orders-table__header"
                            style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1);"><span
                                class="nobr">Commission</span></th>
                        <th class="woocommerce-orders-table__header"
                            style="background: rgba(255,255,255,0.05); border-bottom: 1px solid rgba(255,255,255,0.1);"><span
                                class="nobr">Status</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $log): ?>
                        <tr class="woocommerce-orders-table__row">
                            <td class="woocommerce-orders-table__cell" style="border-bottom: 1px solid rgba(255,255,255,0.05);"><a
                                    href="<?php echo esc_url(wc_get_endpoint_url('view-order', $log['order_id'])); ?>"
                                    style="color: #00f2ff;">#
                                    <?php echo esc_html($log['order_id']); ?>
                                </a></td>
                            <td class="woocommerce-orders-table__cell" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <?php echo esc_html(wp_date(get_option('date_format'), strtotime($log['date']))); ?>
                            </td>
                            <td class="woocommerce-orders-table__cell" style="border-bottom: 1px solid rgba(255,255,255,0.05);"><strong
                                    style="color: #00f2ff;">
                                    <?php echo number_format($log['amount'], 2); ?> XEP
                                </strong></td>
                            <td class="woocommerce-orders-table__cell" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <?php if (isset($log['type']) && $log['type'] === 'reverted'): ?>
                                    <span
                                        style="color:#ff4b4b; background: rgba(255,75,75,0.1); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Reverted</span>
                                <?php else: ?>
                                    <span
                                        style="color:#00a32a; background: rgba(0,163,42,0.1); padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Earned</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div
                style="background: rgba(255,255,255,0.02); padding: 20px; border-radius: 8px; text-align: center; border: 1px dashed rgba(255,255,255,0.1);">
                <span class="dashicons dashicons-money-alt"
                    style="font-size: 32px; color: rgba(255,255,255,0.2); width:32px; height:32px; margin-bottom:10px;"></span>
                <p style="margin:0; color: #aaa;">No commissions earned yet. Start sharing your link to earn!</p>
            </div>
        <?php endif; ?>

        <script>
            function copyAffiliateLink() {
                var copyText = document.getElementById("xep-affiliate-link");
                copyText.select();
                copyText.setSelectionRange(0, 99999); /* For mobile devices */
                navigator.clipboard.writeText(copyText.value).then(function () {
                    alert("Affiliate link copied!");
                });
            }
        </script>
        <?php
    }

    /**
     * Track Affiliate Link Clicks
     */
    public function track_affiliate_link()
    {
        if (!is_admin() && isset($_GET['ref'])) {
            $ref_id = intval($_GET['ref']);
            if ($ref_id > 0) {
                $days = intval(get_option('omnixep_affiliate_cookie_days', 30));
                setcookie('omnixep_affiliate_ref', $ref_id, time() + ($days * DAY_IN_SECONDS), '/');
            }
        }
    }

    /**
     * Save Affiliate ID to Order
     */
    public function save_affiliate_to_order($order_id)
    {
        if (isset($_COOKIE['omnixep_affiliate_ref'])) {
            $ref_id = intval($_COOKIE['omnixep_affiliate_ref']);

            // Prevent user from becoming their own affiliate
            if (is_user_logged_in() && get_current_user_id() === $ref_id) {
                return;
            }

            if ($ref_id > 0) {
                update_post_meta($order_id, '_omnixep_affiliate_id', $ref_id);
            }
        }
    }

    /**
     * Process Commission when order is completed
     */
    public function process_commission($order_id)
    {
        $ref_user_id = get_post_meta($order_id, '_omnixep_affiliate_id', true);

        // Check if referral exists and hasn't been paid yet
        if ($ref_user_id && !get_post_meta($order_id, '_omnixep_commission_paid', true)) {
            $order = wc_get_order($order_id);
            if (!$order)
                return;

            // Calculate commission based on subtotal (excluding shipping/taxes)
            $total = $order->get_subtotal();
            $rate = floatval(get_option('omnixep_affiliate_rate', 10));
            $commission = ($total * $rate) / 100;

            if ($commission > 0) {
                // Update Balance
                $current_balance = get_user_meta($ref_user_id, 'omnixep_affiliate_balance', true);
                $current_balance = floatval($current_balance) + $commission;
                update_user_meta($ref_user_id, 'omnixep_affiliate_balance', $current_balance);

                // Mark Paid in order
                update_post_meta($order_id, '_omnixep_commission_paid', 'yes');
                update_post_meta($order_id, '_omnixep_commission_amount', $commission);

                // Log History
                $history = get_user_meta($ref_user_id, 'omnixep_affiliate_history', true);
                if (!is_array($history))
                    $history = array();
                $history[] = array(
                    'order_id' => $order_id,
                    'amount' => $commission,
                    'date' => current_time('mysql'),
                    'type' => 'earned'
                );
                update_user_meta($ref_user_id, 'omnixep_affiliate_history', $history);
            }
        }
    }

    /**
     * Revert Commission when order is refunded/cancelled
     */
    public function revert_commission($order_id)
    {
        $ref_user_id = get_post_meta($order_id, '_omnixep_affiliate_id', true);

        if ($ref_user_id && get_post_meta($order_id, '_omnixep_commission_paid', true) && !get_post_meta($order_id, '_omnixep_commission_reverted', true)) {
            $commission = floatval(get_post_meta($order_id, '_omnixep_commission_amount', true));
            if ($commission > 0) {
                // Deduct Balance
                $current_balance = get_user_meta($ref_user_id, 'omnixep_affiliate_balance', true);
                $current_balance = max(0, floatval($current_balance) - $commission);
                update_user_meta($ref_user_id, 'omnixep_affiliate_balance', $current_balance);

                // Mark reverted
                update_post_meta($order_id, '_omnixep_commission_reverted', 'yes');

                // Log History
                $history = get_user_meta($ref_user_id, 'omnixep_affiliate_history', true);
                if (!is_array($history))
                    $history = array();
                $history[] = array(
                    'order_id' => $order_id,
                    'amount' => -$commission,
                    'date' => current_time('mysql'),
                    'type' => 'reverted'
                );
                update_user_meta($ref_user_id, 'omnixep_affiliate_history', $history);
            }
        }
    }
}

// Initialize
OMNIXEPAffiliate::get_instance();
