=== Orders Tracking for WooCommerce ===
Contributors: villatheme, mrt3vn
Donate link: https://www.villatheme.com/donate
Tags: advanced shipment tracking for woocommerce, orders tracking for woocommerce,  woocommerce order tracking plugin, woocommerce shipment tracking, woocommerce tracking number
Requires at least: 5.0.0
Tested up to: 6.9
Requires PHP: 7.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: trunk

Easily import/manage your tracking numbers, add tracking numbers to PayPal and send email notifications to customers.

== Description ==

Orders Tracking for WooCommerce is an essential plugin for tracking orders of WooCommerce. This plugin allows shop owners to add, edit, import, export the orders tracking numbers and tracking URLs of every item of an order. Then the plugin can send the orders tracking emails, SMS, add the tracking information to PayPal transactions with orders tracking information and tracking URLs.  This plugin also integrates the order tracking information with other tracking services. With the advantages of tracking orders, the shop owners can manage tracking orders easily and inform their customers about the orders journey. Likewise, customers will feel secure and comfortable in tracking their orders.

[Try the Demo](http://new2new.com/?item=woo-orders-tracking "Demo Orders Tracking for WooCommerce") | [Documents](http://docs.villatheme.com/woo-orders-tracking "Documents") | [Pro Version](https://1.envato.market/6ZPBE "Premium Version") | [Facebook group](https://www.facebook.com/groups/villatheme "VillaTheme")

[youtube https://youtu.be/D7iRLBB3hpM]

### IMPORTANT NOTICE:

- The plugin works based on WooCommerce plugin.

- It is released on WordPress.org and you can use the plugin as free to build themes for sale.

### FEATURES:

&#9658; Shipping Carriers in Orders Tracking for WooCommerce
The plugin integrates more than 80 common shipping carriers all over the world. And you still can add your custom shipping carrier if it is necessary.

 - **More than 80 carriers integrated**: The plugin allows you to select the carriers you want to use.

 - **Add custom shipping carriers**: If your desired carrier is not support by default, you can easily add a custom carrier yourself and you can add as many custom carriers as you want.

 - **Automatically generate tracking URL**: Then when adding a tracking number, you just need to select the carrier and fill in the tracking number. Orders Tracking for WooCommerce will auto-generate a tracking URL.

 - **Enable/disable any carriers**: You can choose to only enable carriers that you use

&#9658; The orders tracking information emails.

 - **Send tracking emails**: you can select to send a tracking email to the customer when adding a tracking number to their orders.

 - **Edit the tracking email**: You are able to change the tracking email subject, heading, and content. The plugin provides shortcodes that display customer name, order ID and shipping information in emails.

 - **Tracking of all items**: Able to include tracking of all order items instead of only changed item in email

&#9658; WooCommerce Emails in Orders Tracking for WooCommerce

 - Including the tracking information in the WooCommerce orders emails when the orders have changed the status.

 - Besides default order statuses of WooCommerce, custom order statuses created by other plugins(such as WooCommerce Order Status Manager plugin and WooCommerce Order Status & Actions Manager plugin) are also supported

 - Customizing the positions of the tracking information in the WooCommerce emails.

&#9658; PayPal feature in Orders Tracking for WooCommerce

 - **Automatically add shipping information**: Shipping information includes carrier names and tracking number will be added to your PayPal transaction automatically.

 - **PayPal Sandbox supported**: The plugin provides a sandbox option that allows you to test the feature with a PayPal Sandbox account before using it with your live account.

&#9658; Tracking service

 - Orders Tracking for WooCommerce supports TrackingMore API. Track info of your tracking numbers will be synced to your TrackingMore account and display on your tracking page when your customers track their orders.

&#9658; Tracking Page in Orders Tracking for WooCommerce
The tracking page option allows your customer to check the shipping information on your site (instead of going to the shipping carrier site). This option works with Tracking Service only.

 - **Create a tracking page**: when the plugin is installed. A tracking page will automatically be added to your page (check it at Dashboard/ Pages/ Track Order).

 - **Design the tracking page**: The page has two templates and multiple design options for you to change its front-end. You can easily customize the tracking page using WordPress customize.

 - **Shortcode**: When you set a page as the tracking page, tracking form and track info will be appended to the end of the page content. If you need to change that, just use [vi_wot_form_track_order] shortcode when customizing the page content.

&#9658; Adding Tracking numbers to WooCommerce Orders
Orders Tracking for WooCommerce plugin allows you to add tracking numbers to each product. The tracking number can be added manually to the WooCommerce/Orders page, or you can bulk-adding using a CSV file.

&#9658;Export orders and Import tracking number using CSV file:
The plugin provides an option to bulk import tracking number using CSV files. You can select orders and export them as a CSV file, add tracking numbers to the exported file. Then import the CSV file in the plugin backend. Tracking numbers will be added to your WooCommerce orders and PayPal transactions.

 - **Order filters**: filters help you to select the orders you want to export by created date, paid date, completed date, status, billing address, shipping address, payment method, and the shipping method.

 - **Export fields**: select the order fields you want to export. Included Order ID, Order Item ID, Product ID, Variation ID, Product Name, Product Price, Quantity and Product SKU…

 - **Import tracking number using CSV files**: after editing the exported order file and add tracking numbers. You can import it in the plugin backend. Tracking numbers will be automatically added to your WooCommerce orders.

&#9658; Manually add the Tracking numbers to WooCommerce Orders

 - **Add tracking information for each line item**: Tracking number, Tracking carrier, Shipping carrier, Change order status, sending email to the customer(if tracking info changes) and Adding tracking number to PayPal option.

 - **Bulk add tracking number**: You can add bulk tracking numbers for orders that have many different items.

&#9658; Order details
Tracking numbers are displayed in orders list/order details on customers' My account page which makes it easier for customers to track their orders while logging in to their accounts

###PRO VERSION

- **All features from the free version**

- **Manage Tracking By**(NEW): If your orders only have 1 tracking number each at a time, you can choose to manage tracking by order only. This way, tracking number and carrier are saved to order post metas so that you can use these post meta keys to add integration with other plugins such as an email designer plugin, an SMS plugin...

- **Edit Tracking in Order List**(NEW): This makes it easier, quicker and more convenient to edit tracking numbers of different orders on a single screen. A lot of time can be saved.

- **Track Per Item Quantity**(NEW): There will be a tracking number field for each purchased quantity of an order item. Helpful when the same item in an order can have different tracking numbers at a time.

- **REST API**(NEW): The pro version supports REST API to set and get tracking numbers which makes it easy to integrate with an ERP service

- **Manage User Roles**(NEW): It's possible to grant Shop manager access to specific settings of the plugin

- **Support More PayPal plugins**: While the free version only supports PayPal standard and PayPal Express checkout, the pro version supports the new WooCommerce PayPal Payments plugin and a lot of other PayPal plugins from other authors

- **Default track info**: No more "tracking info not available" message by adding default track info when ever a new order is placed. This helps reduce a lot of support emails from your customers asking about their packets.

- **Input fields**: You can track with email and/or order ID and/or tracking number

- **Webhooks**: Support webhooks to automatically update new tracking data as well as send email to customers when shipment status changes

- **Schedule**: Schedule a time to sync tracking data of existing orders

- **Change order status**: Automatically change order status when shipment statuses of all tracking numbers of an order change to delivered

- **Manage tracking in email**:

- Able to use display name of carriers instead of real name, good for dropshipping

- Able to customize tracking in email with multilingual supported

&#9658; SMS

 - The SMS option allows you to inform your customers by SMS with a tracking URL when the orders tracking information has changed.

 - There are 3 SMS Provider options for you to choose including Twilio, Nexmo and Plivo.

&#9658; Tracking service

 - **More tracking services supported**: Besides TrackingMore the premium version supports 17Track, EasyPost, Aftership and TrackTry. The plugin will get the shipping information from these tracking services and transfer it to your customers.

 - **TrackingMore, EasyPost, Aftership, 17Track, TrackTry**: These options require a valid API to work. Orders Tracking for WooCommerce will automatically get tracking information from these tracking services.

 - **Translate timeline**: Using Google Cloud Translation API to translate timeline to a specific language

 - **Google reCAPTCHA**: This option helps you protect your website from spam and abuse.

 - **Map order status when importing tracking from CSV**

 - **Compatible with our ALD - AliExpress Dropshipping and Fulfillment for WooCommerce plugin**: When you sync AliExpress orders, if tracking numbers are available, they can be automatically added to PayPal, synced with your currently used tracking service and email/SMS notification will be sent to your customers

 - **Compatible with our WooCommerce Email Template Customizer plugin**: You can easily customize tracking emails sent to your customers to bring them the most satisfaction while waiting for their packages to be delivered

 - **Integrate with Dianxiaomi ERP**: Tracking numbers managed by Dianxiaomi can be synced with our plugin so that your customers can still track their orders and receive updated tracking information via our plugin

- **6 or 12 months of Premium support depending on the type of license you purchase**

- **Lifetime update**

[GET PRO VERSION](https://1.envato.market/6ZPBE) or [https://codecanyon.net/item/woocommerce-orders-tracking/26062993](https://1.envato.market/6ZPBE)

### MAY BE YOU NEED

[9MAIL - WordPress Email Templates Designer](https://wordpress.org/plugins/9mail-wp-email-templates-designer/)

[9Map - Map Multi Locations](https://wordpress.org/plugins/9map-map-multi-locations/)

[Abandoned Cart Recovery for WooCommerce](https://wordpress.org/plugins/woo-abandoned-cart-recovery/)

[Advanced Product Information for WooCommerce](https://wordpress.org/plugins/woo-advanced-product-information/)

[AFFI - Affiliate Marketing for WooCommerce](https://wordpress.org/plugins/affi-affiliate-marketing-for-woo/)

[ALD - Dropshipping and Fulfillment for AliExpress and WooCommerce](https://wordpress.org/plugins/woo-alidropship/)

[Boost Sales for WooCommerce - Set up Up-Sells & Cross-Sells Popups & Auto Apply Coupon](https://wordpress.org/plugins/woo-boost-sales/)

[Bopo - WooCommerce Product Bundle Builder](https://wordpress.org/plugins/bopo-woo-product-bundle-builder/)

[Bulky - Bulk Edit Products for WooCommerce](https://wordpress.org/plugins/bulky-bulk-edit-products-for-woo/)

[Cart All In One For WooCommerce](https://wordpress.org/plugins/woo-cart-all-in-one/)

[Catna - Woo Name Your Price and Offers](https://wordpress.org/plugins/catna-woo-name-your-price-and-offers/)

[Checkout Upsell Funnel for WooCommerce](https://wordpress.org/plugins/checkout-upsell-funnel-for-woo/)

[ChinaDS – Tmall-Taobao Dropshipping for WooCommerce](https://wordpress.org/plugins/chinads-dropshipping-taobao-woocommerce/)

[Clear Autoptimize Cache Automatically](https://wordpress.org/plugins/clear-autoptimize-cache-automatically/)

[COMPE - WooCommerce Compare Products](https://wordpress.org/plugins/compe-woo-compare-products/)

[Coreem - Coupon Reminder for WooCommerce](https://wordpress.org/plugins/woo-coupon-reminder/)

[Coupon Box for WooCommerce](https://wordpress.org/plugins/woo-coupon-box/)

[CURCY - Multi Currency for WooCommerce - Smoothly on WooCommerce 9.x](https://wordpress.org/plugins/woo-multi-currency/)

[Customer Coupons for WooCommerce](https://wordpress.org/plugins/woo-customer-coupons/)

[DEPART - Deposit and Part payment for Woo](https://wordpress.org/plugins/depart-deposit-and-part-payment-for-woo/)

[Email Template Customizer for WooCommerce](https://wordpress.org/plugins/email-template-customizer-for-woo/)

[EPOI - WP Points and Rewards](https://wordpress.org/plugins/epoi-wp-points-and-rewards/)

[EPOW - Custom Product Options for WooCommerce](https://wordpress.org/plugins/epow-custom-product-options-for-woocommerce/)

[EU Cookies Bar for WordPress](https://wordpress.org/plugins/eu-cookies-bar/)

[EXMAGE - WordPress Image Links](https://wordpress.org/plugins/exmage-wp-image-links/)

[Faview - Virtual Reviews for WooCommerce](https://wordpress.org/plugins/woo-virtual-reviews/)

[FEWC - Extra Checkout Fields For WooCommerce](https://wordpress.org/plugins/fewc-extra-checkout-fields-for-woocommerce/)

[Free Shipping Bar for WooCommerce](https://wordpress.org/plugins/woo-free-shipping-bar/)

[GIFT4U - Gift Cards All in One for Woo](https://wordpress.org/plugins/gift4u-gift-cards-all-in-one-for-woo/)

[HANDMADE - Dropshipping for Etsy and WooCommerce](https://wordpress.org/plugins/handmade-dropshipping-for-etsy-and-woo/)

[HAPPY - Helpdesk Support Ticket System](https://wordpress.org/plugins/happy-helpdesk-support-ticket-system/)

[Jagif - WooCommerce Free Gift](https://wordpress.org/plugins/jagif-woo-free-gift/)

[LookBook for WooCommerce - Shoppable with Product Tags](https://wordpress.org/plugins/woo-lookbook/)

[Lucky Wheel for WooCommerce - Spin a Sale](https://wordpress.org/plugins/woo-lucky-wheel/)

[Lucky Wheel Giveaway](https://wordpress.org/plugins/wp-lucky-wheel/)

[Notification for WooCommerce | Boost Your Sales - Recent Sales Popup - Live Feed Sales - Upsells](https://wordpress.org/plugins/woo-notification/)

[Orders Tracking for WooCommerce](https://wordpress.org/plugins/woo-orders-tracking/)

[Photo Reviews for WooCommerce](https://wordpress.org/plugins/woo-photo-reviews/)

[Pofily - WooCommerce Product Filters](https://wordpress.org/plugins/pofily-woo-product-filters/)

[PRENA - Product Pre-Orders for WooCommerce](https://wordpress.org/plugins/product-pre-orders-for-woo/)

[Product Builder for WooCommerce - Custom PC Builder](https://wordpress.org/plugins/woo-product-builder/)

[Product Pre-Orders for WooCommerce](https://wordpress.org/plugins/product-pre-orders-for-woo/)

[Product Size Chart For WooCommerce](https://wordpress.org/plugins/product-size-chart-for-woo/)

[Product Variations Swatches for WooCommerce](https://wordpress.org/plugins/product-variations-swatches-for-woocommerce/)

[REDIS - WooCommerce Dynamic Pricing and Discounts](https://wordpress.org/plugins/redis-woo-dynamic-pricing-and-discounts/)

[REES - Real Estate for Woo](https://wordpress.org/plugins/rees-real-estate-for-woo/)

[S2W - Import Shopify to WooCommerce](https://wordpress.org/plugins/import-shopify-to-woocommerce/)

[Sales Countdown Timer](https://wordpress.org/plugins/sales-countdown-timer/)

[SUBRE – Product Subscription for WooCommerce - Recurring Payments](https://wordpress.org/plugins/subre-product-subscription-for-woo/)

[Suggestion Engine for WooCommerce](https://wordpress.org/plugins/woo-suggestion-engine/)

[Thank You Page Customizer for WooCommerce - Increase Your Sales](https://wordpress.org/plugins/woo-thank-you-page-customizer/)

[TMDS - Dropshipping for TEMU and Woo](https://wordpress.org/plugins/tmds-dropshipping-for-temu-and-woo/)

[VARGAL - Additional Variation Gallery for WooCommerce](https://wordpress.org/plugins/vargal-additional-variation-gallery-for-woo/)

[VIMA - Multi Customer Addresses for Woo](https://wordpress.org/plugins/vima-multi-customer-addresses-for-woo/)

[VISeek - Easy Custom Search](https://wordpress.org/plugins/viseek-easy-custom-search/)

[W2S - Migrate WooCommerce to Shopify](https://wordpress.org/plugins/w2s-migrate-woo-to-shopify/)

[WebPOS – Point of Sale for WooCommerce](https://wordpress.org/plugins/webpos-point-of-sale-for-woocommerce/)

[WordPress Lucky Wheel - Spin a Sale](https://wordpress.org/plugins/wp-lucky-wheel/)

[WPBulky - WordPress Bulk Edit Post Types](https://wordpress.org/plugins/wpbulky-wp-bulk-edit-post-types/)

[Bulky - Bulk Edit Products for WooCommerce](https://wordpress.org/plugins/bulky-bulk-edit-products-for-woo/)

### Plugin Links
- [Project Page](https://villatheme.com)
- [Documentation](http://docs.villatheme.com/woo-orders-tracking)
- [Report Bugs/Issues](https://villatheme.com/knowledge-base/security-is-our-priority/)

== Upgrade Notice ==

== Installation ==
1. Unzip the download package
1. Upload `woo-orders-tracking` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

== Screenshots ==
1. Custom design tracking page
2. Order tracking page
3. Tracking carrier

== Changelog ==
/**1.2.17 - 2026.01.20/**/
- Updated: Villatheme support

/**1.2.16 - 2025.11.19/**/
- Fixed: Can not export order tracking number based on order status filter

/**1.2.15 - 2025.**/
- Updated: Minify some source code

/**1.2.14 - 2025.02.28**/
- Updated: Update support file

/**1.2.13 - 2024.12.28**/
- Updated: Compatible with WP 6.7 and  WC 9.5
– Updated: Update support class

/**1.2.12 - 2024.09.27**/
- Added: The beta version of ViTracking for 'Tracking Service'
- Updated: Convert shipping status from Cainao

/**1.2.11 - 2024.05.09**/
– Updated: Use WC log to save the plugin log
– Updated: Including the custom JS/CSS via wp_enqueue
– Fixed: Validating and sanitizing the input of tracking number for shortcode

/**1.2.10 - 2024.05.02**/
– Updated: Validating and sanitizing the input of tracking number for shortcode
– Updated: Update support class

/**1.2.9 - 2024.04.16**/
– Fixed: Can not choose “Before Order Email” on track info position

/**1.2.8 - 2024.04.13**/
– Updated: Compatible with WP 6.5 and  WC 8.7
– Updated: Update support class

/**1.2.7 - 2023.10.06**/
– Fixed: Miss data field in export orders

/**1.2.6 - 2023.08.12**/
– Updated: Do not show the information uploaded on the URL while importing the tracking number.

/**1.2.5 - 2023.08.04**/
- Updated: Compatibility check  WC 7.9
- Updated: VillaTheme_Support

/**1.2.4 - 2023.05.15**/
– Fixed: Miss data field in export orders

/**1.2.3 - 2023.04.12**/
– Updated: Compatible with 'High-Performance order storage (COT)'

/**1.2.1 - 2022.10.06**/
- Fixed: Edit carriers
- Fixed: Link to settings from other pages
- Updated: Show tracking info inside [woocommerce_order_tracking] shortcode of WooCommerce
- Added: Vietnamese + Italian translations

/**1.2.0 - 2022.07.22**/
- Optimized: Tracking service
- Added: (Export orders)Ability to save filter settings
- Added: (Customize)Track button color, background color
- Added: (Carriers)Ability to enable/disable a carrier, set slug when creating a custom carrier
- Added: (Email)Option to send tracking of whole order
- Added: (WooCommerce Email)Option to put tracking info before order table; compatible with custom order status plugin
- Added: [vi_wot_form_track_order] shortcode
- Added: (WooCommerce Orders)Ability to refresh track info
- Added: (My account)Tracking column
- Added: (Import csv)Map order status

/**1.1.16 - 2022.05.13**/
- Fixed: PHP error below settings page in some cases

/**1.1.15 - 2022.04.22**/
- Fixed: Sanitize, escape data
- Fixed: Save filter settings
- Updated: VillaTheme_Support

/**1.1.14 - 2022.03.29**/
- Updated: VillaTheme_Support

/**1.1.13 - 2022.03.21**/
- Updated: VillaTheme_Support

/**1.1.12 - 2022.01.15**/
- Fixed: Deprecated is_ajax in WC6.1

/**1.1.11 - 2022.01.12**/
- Updated: VillaTheme_Support

/**1.1.10 - 2021.12.14**/
- Fixed: Patched XSS vulnerability

/**1.1.9 - 2021.11.17**/
- Fixed: Tracking URL of Fedex and Portugal Post - CTT

/**1.1.8.7 - 2021.09.09**/
- Fixed: Move the call of VI_WOO_ORDERS_TRACKING_DATA::shipping_carriers() to hook instead of the construct function of class VI_WOO_ORDERS_TRACKING_ADMIN_ORDERS_EDIT_TRACKING

/**1.1.8.6 - 2021.08.10**/
- Updated: Compatible with WP5.8 and WC5.5
- Updated: Class VillaTheme_Support

/**1.1.8.5 - 2020.12.10**/
- Fixed: Can not select shipping carrier since WP5.6
- Updated: Compatible with WP5.6 and WC4.8

/**1.1.8.4 - 2020.08.15**/
- Fixed: Conditional tag for my account page
- Fixed: Exported fields not saved
- Updated: Compatible with WP5.5 and WC4.3

/**1.1.8.3 - 2020.05.09**/
- Fixed: Error on admin orders page
- Updated: Compatible with WC4.1

/**1.1.8.2 - 2020.04.23**/
- Fixed: Date filter for exporting orders when site language is not English
- Fixed: Add tracking to PayPal after importing via CSV
- Updated: Class support

/**1.1.8.1 - 2020.03.25**/
- Updated: Introduce premium version

/**1.1.8 - 2020.03.23**/
- Changed: Admin menu icon
- Updated: Compatible with WP5.4 and WC4.0
- Updated: Class support

/**1.1.7 - 2020.03.05**/
- Fixed: Can not save tracking with custom carrier in the 1.1.6 update

/**1.1.6 - 2020.02.21**/
- Fixed: Tracking included in WooCommerce emails when order status changes
- Fixed: Error when placing new order
- Added: Placeholder for {postal_code} in tracking url
- Added: Support "Digital delivery" carrier
- Updated: Select which order status to set to instead of just changing to completed when editing tracking

/**1.1.5 - 2019.12.16**/
- Fixed: PayPal access token not renew

/**1.1.4 - 2019.12.13**/
- Fixed: TrackingMore form shortcode

/**1.1.3 - 2019.12.11**/
- Fixed: Wrong TrackingMore carrier slug

/**1.1.2 - 2019.12.07**/
- Updated: Adding tracking numbers to TrackingMore when importing trackings
- Added: TrackingMore tracking form shortcode
- Added: Compatibility with Dropshipping and Fulfillment for AliExpress and WooCommerce by VillaTheme

/**1.1.1 - 2019.11.16**/
- Updated: Compatible with WP 5.3 and WooCommerce 3.8

/**1.1.0 - 2019.11.09**/
- Fixed: Import CSV
- Fixed: Save tracking
- Fixed: Edit tracking
- Fixed: Query to export orders
- Optimized: UI/UX and performance

/**1.0.14 - 2019.10.18**/
- Fixed: error when importing

/**1.0.13 - 2019.10.18**/
- Fixed: error when importing

/**1.0.12 - 2019.10.18**/
- Fixed: error time out when importing
- added: include tracking information to WooCommecre order email

/**1.0.9 - 2019.10.11**/
- Fixed: error when importing

/**1.0.8 - 2019.10.11**/
- Fixed: error when add tracking number to PayPal

/**1.0.6 - 2019.10.05**/
- Fixed: error when add tracking in order detail

/**1.0.5 - 2019.09.25**/
- Fixed: error when add tracking number to PayPal

/**1.0.4 - 2019.09.24**/
- Fixed: Can't save PayPal client id & secret

/**1.0.3 - 2019.09.24**/
- Fixed: Slow down admin orders page

/**1.0.2 - 2019.09.12**/
- Fixed: mapping
- added: add list define shipping carriers
- added: export orders tracking number
- added: track shipment with the 3rd party service
- added: add tracking number to PayPal

/**1.0.1 - 2019.05.17**/
- Fixed: Translate
- Updated: Skip if tracking does not change when importing