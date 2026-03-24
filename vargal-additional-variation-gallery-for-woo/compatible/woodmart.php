<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Woodmart {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_action( 'vargal-register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'woodmart_before_single_product_summary_wrap', array( $this, 'enqueue_scripts' ) );
		add_filter( 'woodmart_single_product_gallery_image_class', array( $this, 'add_vargal_class' ),10,1 );
		add_filter( 'woodmart_option', array( $this, 'variation_gallery_settings' ),99999,2 );
		if (self::$settings->get_params('override_template')){
			add_filter( 'woocommerce_single_product_image_gallery_classes', array( $this, 'add_vargal_class_wrap' ),10,1 );
		}
		add_filter( 'vargal_get_thumbnail_width', [$this,'vargal_get_thumbnail_width'],10,1);
	}
	public function vargal_get_thumbnail_width($result) {
		if (self::theme_active() ) {
			$result = 100;
		}
		return $result;
	}
	public function variation_gallery_settings($opt, $slug){
//		if (!is_admin() && $slug === 'variation_gallery'){
		if ($slug === 'variation_gallery'){
			$opt = false;
		}
		return $opt;
	}
	public function add_vargal_class_wrap($class){
		if (self::theme_active()){
			$class[]= 'vargal-woodmart-product-gallery';
			if (!is_admin() && !wp_doing_ajax()){
				$this->enqueue_scripts();
			}
		}
		return $class;
	}
	public function add_vargal_class($class){
		$class .= ' vargal-woodmart-product-gallery-image';
		if (!is_admin() && !wp_doing_ajax()){
			$this->enqueue_scripts();
		}
		return $class;
	}
	public function register_scripts(){
		if (!self::theme_active()){
			return;
		}
		self::$settings::enqueue_script(['vargal-woodmart-frontend'],['woodmart-frontend'],[],['vargal-frontend'],'register');
	}
	public function enqueue_scripts(){
		VARGAL_Frontend_Frontend::enqueue_scripts();
		wp_enqueue_script('vargal-woodmart-frontend');
	}
	public static function theme_active(){
		return defined('WOODMART_THEME_DIR')||in_array( wp_get_theme()->get_stylesheet(), [ 'woodmart', 'woodmart-child' ] );
	}
}