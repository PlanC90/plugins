<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Flatsome {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_action( 'flatsome_product_box_actions',[$this,'quickview_enqueue_scripts'],51);
		if (self::$settings->get_params('override_template')){
			return;
		}
		add_action( 'flatsome_single_product_thumbnails_render_without_attachments','__return_true');
		add_action( 'flatsome_single_product_thumbnails_classes',[$this,'detect_gallery_thumbnail'],10,1);
		add_action( 'flatsome_before_product_images', array( $this, 'enqueue_scripts' ) );
		add_action( 'vargal-register_scripts', array( $this, 'register_scripts' ) );
	}
	public function quickview_enqueue_scripts( ){
		if (get_theme_mod( 'disable_quick_view', 0 ) || wp_script_is('vargal-flatsome-quickview')){
			return;
		}
		VARGAL_Frontend_Frontend::enqueue_scripts();
		self::$settings::enqueue_script(['vargal-flatsome-quickview'],['flatsome-quickview']);
	}
	public function detect_gallery_thumbnail( $result ){
		if (!is_array($result)){
			return $result;
		}
		$result[] = 'vargal-flatsome-product-gallery-thumb';
		return $result;
	}
	public function register_scripts(){
		self::$settings::enqueue_script(['vargal-flatsome-frontend'],['flatsome-frontend'],[],['vargal-frontend'],'register');
	}
	public function enqueue_scripts(){
		VARGAL_Frontend_Frontend::enqueue_scripts();
		wp_enqueue_script('vargal-flatsome-frontend');
	}
}