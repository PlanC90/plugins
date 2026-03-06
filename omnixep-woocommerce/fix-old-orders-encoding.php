<?php
/**
 * One-time script to fix encoding issues in old order meta data
 * 
 * USAGE: 
 * 1. Upload this file to wp-content/plugins/omnixep-woocommerce/
 * 2. Visit: https://yoursite.com/wp-content/plugins/omnixep-woocommerce/fix-old-orders-encoding.php
 * 3. Delete this file after running
 */

// Load WordPress
require_once('../../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator.');
}

echo '<h1>OmniXEP Order Encoding Fix</h1>';
echo '<p>Fixing broken emoji characters in old orders...</p>';

// Get all OmniXEP orders
$args = array(
    'limit' => -1,
    'payment_method' => 'omnixep',
    'return' => 'ids',
);

$order_ids = wc_get_orders($args);
$fixed_count = 0;
$total_count = count($order_ids);

echo "<p>Found {$total_count} OmniXEP orders to check.</p>";
echo "<hr>";

// Character replacements
$replacements = array(
    'ÄŸÅ¸â€™Â°' => '&#128176;',  // Money bag
    'ÄŸÅ¸â€Â' => '&#128065;',    // Eye
    'ÄŸÅ¸â€œÅ¡Ã¯Â¸Â' => '&#128176;',  // Money bag
    'Ã¢Å¡Â Ã¯Â¸Â' => '&#9888;&#65039;',  // Warning
    'Ã¢Å"â€¦' => '&#9989;',  // Check mark
    'Ã¢ÂÅ'' => '&#10060;',  // Cross mark
    'Ã¢â€ â€™' => '&#8594;',  // Right arrow
    'Ã¢â€ Â' => '&#8592;',  // Left arrow
    'ÄŸÅ¸Å¡Â«' => '&#128274;',  // Lock
    'ÄŸÅ¸â€œâ€' => '&#128196;',  // Document
);

foreach ($order_ids as $order_id) {
    $order = wc_get_order($order_id);
    if (!$order) continue;
    
    $updated = false;
    
    // Check and fix payment method title
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
    
    // Check and fix order notes
    $notes = wc_get_order_notes(array('order_id' => $order_id));
    foreach ($notes as $note) {
        $note_content = $note->content;
        $new_content = $note_content;
        foreach ($replacements as $broken => $fixed) {
            if (strpos($new_content, $broken) !== false) {
                $new_content = str_replace($broken, $fixed, $new_content);
                $updated = true;
            }
        }
        // Note: Order notes are harder to update, we'll skip for now
    }
    
    if ($updated) {
        $order->save();
        $fixed_count++;
        echo "<p style='color: green;'>✓ Fixed Order #{$order_id}</p>";
    }
}

echo "<hr>";
echo "<h2>Summary:</h2>";
echo "<p><strong>Total Orders Checked:</strong> {$total_count}</p>";
echo "<p><strong>Orders Fixed:</strong> {$fixed_count}</p>";
echo "<p style='color: green; font-weight: bold;'>✓ Done! You can now delete this file.</p>";
echo "<p><a href='" . admin_url('edit.php?post_type=shop_order') . "'>View Orders</a></p>";
