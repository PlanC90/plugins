<?php
/**
 * Plugin Name: VARGAL - Additional Variation Gallery for Woo
 * Plugin URI: https://villatheme.com/extensions/vargal
 * Description: Easily set unlimited images or MP4/WebM videos for each WC product variation and display them when the customer selects
 * Version: 1.0.8
 * Author: VillaTheme
 * Author URI: https://villatheme.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vargal-additional-variation-gallery-for-woo
 * Domain Path: /languages
 * Copyright 2025 - 2026 VillaTheme.com. All rights reserved.
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

if ( ! defined( 'VARGAL_VERSION' ) ) {
	define( 'VARGAL_VERSION', '1.0.8' );
	define( 'VARGAL_NAME', 'VARGAL-Additional Variation Gallery for Woo' );
	define( 'VARGAL_BASENAME', plugin_basename( __FILE__ ) );
	define( 'VARGAL_DIR', plugin_dir_path( __FILE__ ) );
	define( 'VARGAL_LANGUAGES', VARGAL_DIR . "languages" . DIRECTORY_SEPARATOR );
	define( 'VARGAL_INCLUDES', VARGAL_DIR . "includes" . DIRECTORY_SEPARATOR );
	define( 'VARGAL_ADMIN', VARGAL_DIR . "admin" . DIRECTORY_SEPARATOR );
	define( 'VARGAL_FRONTEND', VARGAL_DIR . "frontend" . DIRECTORY_SEPARATOR );
	define( 'VARGAL_TEMPLATES', VARGAL_DIR . "templates" . DIRECTORY_SEPARATOR );
	define( 'VARGAL_COMPATIBLE', VARGAL_DIR . "compatible" . DIRECTORY_SEPARATOR );
	$plugin_url = plugins_url( 'assets/', __FILE__ );
	define( 'VARGAL_CSS', $plugin_url . "css/" );
	define( 'VARGAL_JS', $plugin_url . "js/" );
	define( 'VARGAL_IMAGES', $plugin_url . "images/" );
	define( 'VARGAL_Admin_Class_Prefix', "VARGAL_Admin_" );
	define( 'VARGAL_Frontend_Class_Prefix', "VARGAL_Frontend_" );
	define( 'VARGAL_Compatible_Class_Prefix', "VARGAL_Compatible_" );
}

if ( ! class_exists( 'VARGAL_INIT' ) ) {
	/**
	 * Class VARGAL_INIT
	 */
	class VARGAL_INIT {
		public function __construct() {
			add_action( 'before_woocommerce_init', [ $this, 'before_woocommerce_init' ] );
			add_action( 'plugins_loaded', [ $this, 'check_environment' ] );
		}

		public function check_environment( ) {
			if ( defined( 'VARGALPRO_VERSION' ) ) {
				return;
			}
			if ( ! class_exists( 'VillaTheme_Require_Environment' ) ) {
				require_once VARGAL_INCLUDES . 'support.php';
			}
			$environment = new \VillaTheme_Require_Environment( [
					'plugin_name'     => VARGAL_NAME,
					'php_version'     => '7.0',
					'wp_version'      => '5.0',
					'require_plugins' => [
						[
							'slug'            => 'woocommerce',
							'name'            => 'WooCommerce',
							'defined_version' => 'WC_VERSION',
							'version'         => '7.0',
						],
					]
				]
			);
			if ( $environment->has_error() ) {
				return;
			}
			$this->includes();
			add_action( 'init', array( $this, 'init' ) );
			add_filter( 'plugin_action_links_' . VARGAL_BASENAME, array( $this, 'settings_link' ) );
		}

		protected function includes() {
			$files = array(
				VARGAL_INCLUDES   => [
					'file_name' => [
						'support.php',
						'data.php',
					]
				],
				VARGAL_ADMIN      => [
					'class_prefix' => VARGAL_Admin_Class_Prefix,
					'file_name'    => [
						'settings.php',
						'product.php',
						'migrate.php',
						'recommend.php',
					]
				],
				VARGAL_FRONTEND   => [
					'class_prefix' => VARGAL_Frontend_Class_Prefix,
					'file_name'    => [
						'frontend.php',
					]
				],
				VARGAL_COMPATIBLE => [
					'class_prefix' => VARGAL_Compatible_Class_Prefix,
					'file_name'    => [
						'flatsome.php',
						'goya.php',
						'woodmart.php',
						'bulky.php',
						'dokan.php',
						'blocksy.php',
						'bricks.php',
					]
				],
			);
			foreach ( $files as $path => $items ) {
				if ( empty( $items['file_name'] ) || ! is_array( $items['file_name'] ) ) {
					continue;
				}
				$class_prefix = $items['class_prefix'] ?? '';
				foreach ( $items['file_name'] as $file_name ) {
					$file = $path . '/' . $file_name;
					if ( ! file_exists( $file ) ) {
						continue;
					}
					require_once $file;
					$ext_file   = pathinfo( $file );
					$class_name = $ext_file['filename'] ?? '';
					if ( $class_prefix ) {
						$class_name = preg_replace( '/\W/i', '_', $class_prefix . ucfirst( $class_name ) );
					}
					if ( $class_name && class_exists( $class_name ) ) {
						new $class_name;
					}
				}
			}
		}

		public function init() {
			$this->load_plugin_textdomain();
			if ( class_exists( 'VillaTheme_Support' ) ) {
				new VillaTheme_Support(
					array(
						'support'    => 'https://wordpress.org/support/plugin/vargal-additional-variation-gallery-for-woo/',
						'docs'       => 'http://docs.villatheme.com/?item=vargal',
						'review'     => 'https://wordpress.org/support/plugin/vargal-additional-variation-gallery-for-woo/reviews/?rate=5#rate-response',
						'pro_url'    => 'https://1.envato.market/RGXJKa',
						'css'        => VARGAL_CSS,
						'image'      => VARGAL_IMAGES,
						'slug'       => 'vargal-additional-variation-gallery-for-woo',
						'menu_slug'  => 'vargal',
						'version'    => VARGAL_VERSION,
						'survey_url' => 'https://script.google.com/macros/s/AKfycbwtkipT2GkvzhyRDYGmtXagVEbL6BLmhcEwVd0UZZQpxoOqXch2MdC-CoE9kLJ4DgzyrQ/exec',
					)
				);
			}
		}

		public function load_plugin_textdomain() {
			/**
			 * load Language translate
			 */
			$locale = apply_filters( 'plugin_locale', get_locale(), 'vargal-additional-variation-gallery-for-woo' );
			load_textdomain( 'vargal-additional-variation-gallery-for-woo', VARGAL_LANGUAGES . "vargal-additional-variation-gallery-for-woo-$locale.mo" );
		}

		public function settings_link( $links ) {
			$settings_link = sprintf( '<a href="%s" title="%s">%s</a>', esc_attr( admin_url( 'admin.php?page=vargal' ) ),
				esc_attr__( 'Settings', 'vargal-additional-variation-gallery-for-woo' ),
				esc_html__( 'Settings', 'vargal-additional-variation-gallery-for-woo' )
			);
			array_unshift( $links, $settings_link );

			return $links;
		}

		public function before_woocommerce_init( $links ) {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

	}

	new VARGAL_INIT();
}