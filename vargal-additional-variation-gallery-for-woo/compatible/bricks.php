<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Bricks {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if ( ! self::$settings->get_params( 'enable' ) ) {
			return;
		}
		if ( self::$settings->get_params( 'override_template' ) ) {
			add_action( 'vargal-enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}
	}
	public function enqueue_scripts(){
		if (!class_exists('Bricks\Database')){
			return;
		}
		if (! Bricks\Database::get_setting( 'disableBricksCascadeLayer' ) && !wp_style_is('vargal-photoswipe')){
			$register_styles = array(
				'photoswipe'                  => array(
					'src'     => self::get_asset_url( 'assets/css/photoswipe/photoswipe.min.css' ),
					'deps'    => array(),
					'version' => VARGAL_VERSION,
				),
				'photoswipe-default-skin'     => array(
					'src'     => self::get_asset_url( 'assets/css/photoswipe/default-skin/default-skin.min.css' ),
					'deps'    => array( 'photoswipe' ),
					'version' => VARGAL_VERSION,
				),
			);
			foreach ( $register_styles as $name => $props ) {
				self::register_style( 'vargal-'.$name, $props['src'], $props['deps'], $props['version'], 'all' );
			}
		}
	}
	private static function register_style( $handle, $path, $deps = array(), $version = VARGAL_VERSION, $media = 'all' ) {
		wp_register_style( $handle, $path, $deps, $version, $media );
		wp_enqueue_style($handle);
	}
	private static function get_asset_url( $path ) {
		return apply_filters( 'woocommerce_get_asset_url', plugins_url( $path, WC_PLUGIN_FILE ), $path );
	}
}