<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Goya {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_action( 'vargal_goya_quickview_enqueue_scripts', array( $this, 'quickview_enqueue_scripts' ),10,1 );
		add_filter( 'goya_product_quickview_link', array( $this, 'quickview_register_scripts' ),10,1 );
		add_filter( 'woocommerce_post_class', array( $this, 'add_vargal_class' ),10,1 );
		if (self::$settings->get_params('override_template')){
			add_action( 'vargal-register_scripts', array( $this, 'add_inline_style' ) );
			return;
		}
		add_action( 'vargal-register_scripts', array( $this, 'register_scripts' ) );
		add_action( 'goya_single_product_showcase_top', array( $this, 'enqueue_scripts' ) );
	}
	public function quickview_enqueue_scripts($result ){
		VARGAL_Frontend_Frontend::enqueue_scripts();
		self::$settings::enqueue_script(['vargal-goya-quickview'],['goya-quickview']);
	}
	public function quickview_register_scripts($result ){
		if (wp_script_is('vargal-goya-quickview')){
			return $result;
		}
		do_action('vargal_goya_quickview_enqueue_scripts');
		return $result;
	}
	public function add_vargal_class($classes ){
		if (!self::theme_active()){
			return $classes;
		}
		if (!in_array('et-product-detail',$classes)){
			return $classes;
		}
		$classes[] = 'vargal-goya-product-gallery';
		if (!self::$settings->get_params('override_template')){
			return $classes;
		}
		$change_carousel = false;
		if (in_array('et-product-gallery-grid',$classes)){
			unset($classes[array_search('et-product-gallery-grid',$classes)]);
			$change_carousel = true;
		}
		if (in_array('et-product-gallery-column',$classes)){
			unset($classes[array_search('et-product-gallery-column',$classes)]);
			$change_carousel = true;
		}
		if ($change_carousel){
			$classes[] = 'et-product-gallery-carousel';
		}
		return $classes;
	}
	public function register_scripts(){
		if (!self::theme_active()){
			return;
		}
		self::$settings::enqueue_script(['vargal-goya-frontend'],['goya-frontend'],[],['vargal-frontend'],'register');
	}
	public function add_inline_style(){
		if (!self::theme_active()){
			return;
		}
		if (self::$settings->get_params('override_template')) {
			$css = '.et-product-detail.et-product-layout-no-padding.thumbnails-vertical .has-additional-thumbnails .vargal-product-gallery.vargal-product-gallery-wrap-bottom .woocommerce-product-gallery__wrapper img,';
			$css .= '.et-product-detail.et-product-layout-no-padding.thumbnails-vertical .has-additional-thumbnails .vargal-product-gallery.vargal-product-gallery-wrap-bottom .woocommerce-product-gallery__wrapper video,';
			$css .= '.et-product-detail.et-product-layout-no-padding.thumbnails-vertical .has-additional-thumbnails .vargal-product-gallery.vargal-product-gallery-wrap-bottom .vargal-product-gallery__wrapper{';
			$css .= 'max-height: calc( 100% - '.(70 + floatval(self::$settings->get_params('thumbnail_gap_with_main_img') )).'px );';
			$css .= '}';
			$css .= '.et-product-detail.et-product-layout-no-padding.thumbnails-vertical .has-additional-thumbnails .vargal-product-gallery.vargal-product-gallery-wrap-bottom .woocommerce-product-gallery__wrapper,';
			$css .= '.et-product-detail.et-product-layout-no-padding.thumbnails-vertical .has-additional-thumbnails .vargal-product-gallery.vargal-product-gallery-wrap-bottom .vargal-product-gallery-viewport{';
			$css .= 'max-height: 100% ;';
			$css .= '}';
			$css .= '.vargal-pswp button.pswp__button{';
			$css .= 'background-image: none !important;';
			$css .= '}';
			wp_add_inline_style('vargal-frontend',$css);
		}
	}
	public function enqueue_scripts(){
		VARGAL_Frontend_Frontend::enqueue_scripts();
		wp_enqueue_script('vargal-goya-frontend');
	}
	public static function theme_active(){
		return defined('GOYA_THEME_VERSION');
	}
}