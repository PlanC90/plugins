<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Dokan {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_action( 'dokan_product_edit_after_title', array( 'VARGAL_Admin_Product', 'enqueue_scripts' ) );
		add_action( 'dokan_product_after_variable_attributes', array( 'VARGAL_Admin_Product', 'variation_gallery_settings' ), 10, 3 );
	}
}