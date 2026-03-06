<?php
/**
 * Admin page to fix encoding issues in old orders
 */

defined('ABSPATH') || exit;

// Add admin menu
add_action('admin_menu', 'omnixep_add_fix_encoding_menu', 99);

function omnixep_add_fix_encoding_menu() {
    add_submenu_page(
        'woocommerce',
        'Fix OmniXEP Encoding',
        'Fix OmniXEP Encoding',
        'manage_woocommerce',
        'omnixep-fix-encoding',
        'omnixep_fix_encoding_page'
    );
}

function omnixep_fix_encoding_page() {
    ?>
    <div class="wrap">
        <h1>Fix OmniXEP Order Encoding</h1>
        <p>This tool will fix broken emoji characters in old OmniXEP orders.</p>
        
        <?php
        if (isset($_POST['fix_encoding']) && check_admin_referer('omnixep_fix_encoding')) {
            omnixep_run_encoding_fix();
        } else {
            ?>
            <div class="notice notice-warning">
                <p><strong>Warning:</strong> This will update all existing OmniXEP orders in your database.</p>
                <p>It is recommended to backup your database before proceeding.</p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('omnixep_fix_encoding'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Orders to Fix</th>
                        <td>
                            <?php
                            $count = count(wc_get_orders(array(
                                'limit' => -1,
                                'payment_method' => 'omnixep',
                                'return' => 'ids',
                            )));
                            echo "<strong>{$count}</strong> OmniXEP orders found";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">What will be fixed?</th>
                        <td>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <li>Payment method titles</li>
                                <li>Broken emoji characters</li>
                                <li>Turkish characters (Turkiye, Istanbul)</li>
                            </ul>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="fix_encoding" class="button button-primary" 
                            onclick="return confirm('Are you sure you want to fix all orders? This cannot be undone.');">
                        Fix All Orders Now
                    </button>
                </p>
            </form>
            <?php
        }
        ?>
    </div>
    <?php
}

function omnixep_run_encoding_fix() {
    echo '<div class="notice notice-info"><p>Processing orders...</p></div>';
    
    // Character replacements - using hex codes to avoid encoding issues
    $replacements = array(
        "\xC4\x9F\xC5\xB8\xE2\x80\x99\xC2\xB0" => '&#128176;',  // Money bag
        "\xC4\x9F\xC5\xB8\xE2\x80\x9C\xC5\xA1\xC3\xAF\xC2\xB8\xC2\x8F" => '&#128176;',
        "\xC3\xA2\xC5\xA1\xC2\xA0\xC3\xAF\xC2\xB8\xC2\x8F" => '&#9888;&#65039;',  // Warning
        "\xC3\xA2\xC5\x93\xE2\x80\xA6" => '&#9989;',  // Check mark
        "\xC3\xA2\xC2\x80\xC5\x93" => '&#10060;',  // Cross mark
        "\xC3\xA2\xE2\x80\x9A\xC2\xAC\xE2\x80\x99" => '&#8594;',  // Right arrow
        "\xC3\xA2\xE2\x80\x9A\xC2\xAC\xC2\xA0" => '&#8592;',  // Left arrow
        "\xC4\x9F\xC5\xB8\xC5\xA1\xC2\xAB" => '&#128274;',  // Lock
        "\xC4\x9F\xC5\xB8\xE2\x80\x9C\xE2\x80\x9D" => '&#128196;',  // Document
        "\xC4\x9F\xC5\xB8\xE2\x80\x9D\xC2\xA1" => '&#128276;',  // Bell
        "\xC4\x9F\xC5\xB8\xE2\x80\x9C\xC2\xAB" => '&#128274;',  // Lock
        "\xC4\x9F\xC5\xB8\xE2\x80\x9A\xC2\xAC" => '&#128065;',  // Eye
        "T\xC3\x83\xC2\xBC" . "rkiye" => 'Turkiye',
        "\xC3\x84\xC2\xB0stanbul" => 'Istanbul',
        "g\xC3\x83\xC2\xBC" . "nde" => 'gunde',
    );
    
    $args = array(
        'limit' => -1,
        'payment_method' => 'omnixep',
        'return' => 'ids',
    );
    
    $order_ids = wc_get_orders($args);
    $fixed_count = 0;
    $total_count = count($order_ids);
    
    echo '<div style="background: white; padding: 20px; border: 1px solid #ccc; margin: 20px 0;">';
    echo "<p><strong>Total Orders:</strong> {$total_count}</p>";
    echo '<hr>';
    
    foreach ($order_ids as $order_id) {
        $order = wc_get_order($order_id);
        if (!$order) continue;
        
        $updated = false;
        
        // Fix payment method title
        $payment_title = $order->get_payment_method_title();
        if ($payment_title) {
            $new_title = $payment_title;
            foreach ($replacements as $broken => $fixed) {
                if (strpos($new_title, $broken) !== false) {
                    $new_title = str_replace($broken, $fixed, $new_title);
                    $updated = true;
                }
            }
            if ($updated) {
                $order->set_payment_method_title($new_title);
            }
        }
        
        // Fix meta data
        $meta_keys = array(
            '_omnixep_token_name',
            '_omnixep_amount',
            '_omnixep_merchant_amount',
            '_omnixep_commission_amount',
        );
        
        foreach ($meta_keys as $meta_key) {
            $value = $order->get_meta($meta_key);
            if ($value) {
                $new_value = $value;
                foreach ($replacements as $broken => $fixed) {
                    if (is_string($new_value) && strpos($new_value, $broken) !== false) {
                        $new_value = str_replace($broken, $fixed, $new_value);
                        $updated = true;
                    }
                }
                if ($updated && $new_value !== $value) {
                    $order->update_meta_data($meta_key, $new_value);
                }
            }
        }
        
        if ($updated) {
            $order->save();
            $fixed_count++;
            echo "<p style='color: green;'>Fixed Order #{$order_id}</p>";
            flush();
        }
    }
    
    echo '</div>';
    echo '<div class="notice notice-success"><p><strong>Complete!</strong></p></div>';
    echo "<p><strong>Orders Fixed:</strong> {$fixed_count} / {$total_count}</p>";
    echo "<p><a href='" . admin_url('edit.php?post_type=shop_order') . "' class='button button-primary'>View Orders</a></p>";
}
