<?php
/**
 * Plugin Name: XEP Market Telegram Notification Bot
 * Plugin URI: https://xepmarket.com
 * Description: A modern plugin to send WooCommerce order notifications directly to a Telegram bot.
 * Version: 1.0.0
 * Author: XEP Market
 * Author URI: https://xepmarket.com
 * License: GPLv2 or later
 * Text Domain: xep-telegram-bot
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add Settings Link on Plugins Page
$plugin_file = plugin_basename(__FILE__);
add_filter("plugin_action_links_{$plugin_file}", 'xep_tg_bot_add_settings_link');
function xep_tg_bot_add_settings_link($links) {
    $settings_link = '<a href="admin.php?page=xep-telegram-bot">' . __('Settings', 'xep-telegram-bot') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// 1. Add Settings Menu & Page
add_action('admin_menu', 'xep_tg_bot_add_admin_menu');
function xep_tg_bot_add_admin_menu()
{
    add_menu_page(
        'Telegram Bot',
        'Telegram Bot',
        'manage_options',
        'xep-telegram-bot',
        'xep_tg_bot_options_page',
        'dashicons-format-chat',
        56
    );
}

// 2. Register Settings
add_action('admin_init', 'xep_tg_bot_settings_init');
function xep_tg_bot_settings_init()
{
    register_setting('xep_tg_bot_settings', 'xep_tg_bot_enabled');
    register_setting('xep_tg_bot_settings', 'xep_tg_bot_token');
    register_setting('xep_tg_bot_settings', 'xep_tg_bot_chat_id');
    register_setting('xep_tg_bot_settings', 'xep_tg_bot_msg_new_order');
    register_setting('xep_tg_bot_settings', 'xep_tg_bot_msg_status_changed');

    // Defaults
    if (get_option('xep_tg_bot_msg_new_order') === false) {
        $default_new = "🛒 <b>NEW ORDER RECEIVED!</b>\n\n<b>Order ID:</b> #{order_id}\n<b>Customer:</b> {customer_name}\n<b>Total:</b> {total}\n<b>Status:</b> {status}\n\n<b>Items:</b>\n{items}";
        update_option('xep_tg_bot_msg_new_order', $default_new);
    }
    if (get_option('xep_tg_bot_msg_status_changed') === false) {
        $default_status = "🔄 <b>ORDER STATUS UPDATED</b>\n\n<b>Order ID:</b> #{order_id}\n<b>New Status:</b> {status}\n<b>Customer:</b> {customer_name}\n<b>Total:</b> {total}";
        update_option('xep_tg_bot_msg_status_changed', $default_status);
    }
}

// 3. Admin Panel UI (Modern Dark Theme)
function xep_tg_bot_options_page()
{
    ?>
    <style>
        .xep-wrap {
            max-width: 1000px;
            margin: 20px auto;
            font-family: ' Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            color: #e2e8f0;
        }

        .xep-header {
            background: linear-gradient(135deg, #05060a 0%, #11131c 100%);
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin-bottom: 25px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .xep-header h1 {
            color: #fff;
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .xep-header h1 .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: #00f2ff;
        }

        .xep-card {
            background: #11131c;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-bottom: 25px;
        }

        .xep-card h2 {
            color: #fff;
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .xep-form-group {
            margin-bottom: 20px;
        }

        .xep-form-group label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .xep-form-control {
            width: 100%;
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            padding: 12px 15px !important;
            border-radius: 8px !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            box-shadow: none !important;
        }

        .xep-form-control:focus {
            border-color: #00f2ff !important;
            box-shadow: 0 0 0 3px rgba(0, 242, 255, 0.1) !important;
        }

        .xep-form-textarea {
            height: 150px;
            resize: vertical;
            font-family: monospace;
        }

        .xep-save-btn {
            background: linear-gradient(135deg, #00f2ff 0%, #0088ff 100%) !important;
            color: #000 !important;
            border: none !important;
            padding: 12px 30px !important;
            font-weight: 700 !important;
            font-size: 16px !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .xep-save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 242, 255, 0.3);
        }

        .xep-switch-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .xep-switch-wrap .info h3 {
            margin: 0 0 5px 0;
            color: #fff;
            font-size: 16px;
        }

        .xep-switch-wrap .info p {
            margin: 0;
            color: #94a3b8;
            font-size: 13px;
        }

        /* Switch CSS */
        .xep-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .xep-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .xep-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.1);
            transition: .4s;
            border-radius: 34px;
        }

        .xep-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: #fff;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.xep-slider {
            background-color: #00f2ff;
        }

        input:focus+.xep-slider {
            box-shadow: 0 0 1px #00f2ff;
        }

        input:checked+.xep-slider:before {
            transform: translateX(24px);
            background-color: #000;
        }

        .var-tags span {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin: 0 5px 5px 0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .var-tags span:hover {
            background: #00f2ff;
            color: #000;
        }
    </style>

    <div class="xep-wrap">
        <div class="xep-header">
            <h1><i class="dashicons dashicons-format-chat"></i> Telegram Order Bot</h1>
            <button form="xep-tg-settings-form" type="submit" class="xep-save-btn">Save Settings</button>
        </div>

        <form id="xep-tg-settings-form" method="post" action="options.php">
            <?php settings_fields('xep_tg_bot_settings'); ?>

            <div class="xep-card">
                <h2>API Settings</h2>

                <div class="xep-switch-wrap">
                    <div class="info">
                        <h3>Enable Telegram Bot</h3>
                        <p>Turn on or off Telegram notifications for your store.</p>
                    </div>
                    <label class="xep-switch">
                        <input type="checkbox" name="xep_tg_bot_enabled" value="yes" <?php checked(get_option('xep_tg_bot_enabled'), 'yes'); ?>>
                        <span class="xep-slider"></span>
                    </label>
                </div>

                <div class="xep-form-group">
                    <label>Telegram Bot Token</label>
                    <input type="text" name="xep_tg_bot_token" class="xep-form-control"
                        value="<?php echo esc_attr(get_option('xep_tg_bot_token')); ?>"
                        placeholder="e.g. 1234567890:ABCdefGhIJKlmNoPQRsTUVWxyz">
                    <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">Get this from <a
                            href="https://t.me/BotFather" target="_blank" style="color:#00f2ff;">@BotFather</a> on Telegram.
                    </p>
                </div>

                <div class="xep-form-group">
                    <label>Target Chat ID</label>
                    <input type="text" name="xep_tg_bot_chat_id" class="xep-form-control"
                        value="<?php echo esc_attr(get_option('xep_tg_bot_chat_id')); ?>"
                        placeholder="e.g. -1001234567890 or your personal ID">
                    <p style="font-size: 12px; color: #94a3b8; margin-top: 5px;">The Chat ID of the group, channel, or user
                        to send messages to.</p>
                </div>
            </div>

            <div class="xep-card">
                <h2>Message Templates</h2>
                <p style="font-size: 13px; color: #94a3b8; margin-bottom: 15px;">Use the following variables to customize
                    your messages. HTML tags like &lt;b&gt;, &lt;i&gt;, &lt;code&gt; are supported by Telegram.</p>
                <div class="var-tags" style="margin-bottom: 20px;">
                    <span>{order_id}</span>
                    <span>{status}</span>
                    <span>{total}</span>
                    <span>{customer_name}</span>
                    <span>{telegram_username}</span>
                    <span>{items}</span>
                </div>

                <div class="xep-form-group">
                    <label>New Order Message</label>
                    <textarea name="xep_tg_bot_msg_new_order"
                        class="xep-form-control xep-form-textarea"><?php echo esc_textarea(get_option('xep_tg_bot_msg_new_order')); ?></textarea>
                </div>

                <div class="xep-form-group">
                    <label>Status Changed Message</label>
                    <textarea name="xep_tg_bot_msg_status_changed"
                        class="xep-form-control xep-form-textarea"><?php echo esc_textarea(get_option('xep_tg_bot_msg_status_changed')); ?></textarea>
                </div>
            </div>

            <?php submit_button('Save Settings', 'xep-save-btn', 'submit', false); ?>
        </form>
    </div>
    <script>
        // Quick copy feature for variables
        document.querySelectorAll('.var-tags span').forEach(el => {
            el.addEventListener('click', function () {
                navigator.clipboard.writeText(this.innerText);
                let orig = this.innerText;
                this.innerText = 'Copied!';
                this.style.background = '#00f2ff';
                this.style.color = '#000';
                setTimeout(() => {
                    this.innerText = orig;
                    this.style.background = 'rgba(255,255,255,0.1)';
                    this.style.color = '#fff';
                }, 1000);
            });
        });
    </script>
    <?php
}

// 4. Send Message Function
function xep_tg_bot_send_message($message)
{
    if (get_option('xep_tg_bot_enabled') !== 'yes')
        return;

    $token = get_option('xep_tg_bot_token');
    $chat_id = get_option('xep_tg_bot_chat_id');

    if (empty($token) || empty($chat_id))
        return;

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = array(
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    );

    wp_remote_post($url, array(
        'timeout' => 15,
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => json_encode($data)
    ));
}

// 5. Replace Variables Helper
function xep_tg_bot_replace_vars($template, $order)
{
    if (!$order)
        return $template;

    // Build items string
    $items_str = "";
    foreach ($order->get_items() as $item_id => $item) {
        $qty = $item->get_quantity();
        $name = $item->get_name();
        $total = html_entity_decode(wp_strip_all_tags(wc_price($item->get_total(), array('currency' => $order->get_currency()))));
        $items_str .= "- {$qty}x {$name} ({$total})\n";
    }

    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    if (empty(trim($customer_name))) {
        $customer_name = 'Guest';
    }

    // Get telegram username
    $telegram_username = get_post_meta($order->get_id(), '_billing_telegram', true);
    if (empty($telegram_username)) {
        $telegram_username = get_post_meta($order->get_id(), 'billing_telegram', true);
    }

    // Format the telegram string
    $telegram_str = "Not provided";
    if (!empty($telegram_username)) {
        // Strip out the @ if it exists and make it a link
        $clean_username = ltrim($telegram_username, '@');
        $telegram_str = "<a href='https://t.me/" . esc_attr($clean_username) . "'>@" . esc_html($clean_username) . "</a>";
    }

    $vars = array(
        '{order_id}' => $order->get_order_number(),
        '{status}' => wc_get_order_status_name($order->get_status()),
        '{total}' => html_entity_decode(wp_strip_all_tags(wc_price($order->get_total(), array('currency' => $order->get_currency())))),
        '{customer_name}' => $customer_name,
        '{telegram_username}' => $telegram_str,
        '{items}' => $items_str
    );

    return strtr($template, $vars);
}

// 6. Hook: New Order
add_action('woocommerce_checkout_order_processed', 'xep_tg_bot_on_new_order', 10, 3);
function xep_tg_bot_on_new_order($order_id, $posted_data, $order)
{
    $template = get_option('xep_tg_bot_msg_new_order');
    if (empty($template))
        return;

    $message = xep_tg_bot_replace_vars($template, $order);
    xep_tg_bot_send_message($message);
}

// 7. Hook: Order Status Changed
add_action('woocommerce_order_status_changed', 'xep_tg_bot_on_status_changed', 10, 4);
function xep_tg_bot_on_status_changed($order_id, $old_status, $new_status, $order)
{
    // Don't spam when it drops into pending initially
    if ($new_status === 'pending' || $old_status === 'pending')
        return;

    $template = get_option('xep_tg_bot_msg_status_changed');
    if (empty($template))
        return;

    $message = xep_tg_bot_replace_vars($template, $order);
    xep_tg_bot_send_message($message);
}
