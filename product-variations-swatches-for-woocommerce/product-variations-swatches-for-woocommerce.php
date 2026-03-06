<?php
/**
 * Plugin Name: Product Variations Swatches for WooCommerce
 * Plugin URI: https://villatheme.com/extensions/woocommerce-product-variations-swatches
 * Description: Showcase variations and impress your customers with beautiful swatches such as color, button, image, and more.
 * Version: 1.1.15
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-variations-swatches-for-woocommerce
 * Domain Path: /languages
 * Copyright 2020-2026 VillaTheme.com. All rights reserved.
 * Tested up to: 6.9
 * WC requires at least: 7.0
 * WC tested up to: 10.4
 * Requires PHP: 7.0
 * Requires at least: 5.0
 * Requires Plugins: woocommerce
 **/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION', '1.1.15' );
/**
 * Return if the premium version is active
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
//compatible with 'High-Performance order storage (COT)'
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
});
if ( is_plugin_active( 'woocommerce-product-variations-swatches/woocommerce-product-variations-swatches.php' ) ) {
	return;
}

/**
 * Class VI_WOO_PRODUCT_VARIATIONS_SWATCHES
 */
class VI_WOO_PRODUCT_VARIATIONS_SWATCHES {
	public function __construct() {

		add_action( 'plugins_loaded',[$this,'check_environment'] );
	}
	public function check_environment( $recent_activate = false ) {
		if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
			include_once  WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "product-variations-swatches-for-woocommerce" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . 'support.php';
		}
		$environment = new \VillaTheme_Require_Environment( [
				'plugin_name'     => 'Product Variations Swatches for WooCommerce',
				'php_version'     => '7.0',
				'wp_version'      => '5.0',
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
		if ( $environment->has_error() ) {
			return;
		}
		$init_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . "product-variations-swatches-for-woocommerce" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "define.php";
		require_once $init_file;
	}

}

new VI_WOO_PRODUCT_VARIATIONS_SWATCHES();