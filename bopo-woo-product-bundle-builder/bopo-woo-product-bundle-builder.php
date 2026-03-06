<?php
/**
 * Plugin Name: Bopo - WooCommerce Product Bundle Builder
 * Plugin URI: https://villatheme.com/extensions/bopo-woocommerce-product-bundle-builder/
 * Description: Create irresistible bundle products for WooCommerce, offering flexible pricing and great deals for your customers
 * Version: 1.1.6
 * Author: VillaTheme
 * Author URI: http://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-bopo-bundle
 * Domain Path: /languages
 * Copyright 2022-2025 VillaTheme.com. All rights reserved.
 * Requires Plugins: woocommerce
 * Requires at least: 5.0
 * Tested up to: 6.8.2
 * WC requires at least: 7.0
 * WC tested up to: 10.0.3
 * Requires PHP: 7.0
 */

defined('ABSPATH') || exit;
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

//Compatible with High-Performance order storage (COT)
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
} );
if (is_plugin_active('bopo-woocommerce-product-bundle-builder/bopo-woocommerce-product-bundle-builder.php')) {
	return;
}
if (!defined('VI_WOO_BOPO_BUNDLE_VERSION')) {
    define('VI_WOO_BOPO_BUNDLE_VERSION', '1.1.6');
}

if (!class_exists('VI_WOO_BOPO_BUNDLE')) {
    class VI_WOO_BOPO_BUNDLE {
        public $plugin_name = 'Bopo - WooCommerce Product Bundle Builder';

        public function __construct() {
//			register_activation_hook( __FILE__, array( $this, 'install' ) );
//			register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

            add_action('plugins_loaded', array($this, 'init'));

        }


        public function init() {
            if (!function_exists('is_plugin_active')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }


            if (!class_exists('VillaTheme_Require_Environment')) {
                require_once WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "bopo-woo-product-bundle-builder/includes/support.php";
            }

            $environment = new VillaTheme_Require_Environment([
                    'plugin_name' => $this->plugin_name,
                    'php_version' => '7.0',
                    'wp_version' => '5.0',
                    'require_plugins' => [
                        [
                            'slug' => 'woocommerce',
                            'name' => 'WooCommerce',
							'defined_version' => 'WC_VERSION',
                            'version' => '7.0',
                        ],
                    ]
                ]
            );

            if ($environment->has_error()) {
                return;
            }

            $init_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "bopo-woo-product-bundle-builder" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "includes.php";
            require_once $init_file;
        }

        /**
         * When active plugin Function will be call
         */
        public function install() {
            global $wp_version;
            if (version_compare($wp_version, "5.1", "<")) {
                deactivate_plugins(basename(__FILE__)); // Deactivate our plugin
                wp_die("This plugin requires WordPress version 5.1 or higher.");
            }
        }

        /**
         * When deActive function will be call
         */
        public function uninstall() {

        }
    }
}
new VI_WOO_BOPO_BUNDLE();