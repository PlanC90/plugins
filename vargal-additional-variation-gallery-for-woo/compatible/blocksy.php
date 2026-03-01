<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Compatible_Blocksy {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_filter( 'blocksy:woocommerce:product-view:use-default', '__return_true' );
		add_filter( 'vargal_migrate_get_old_gallery_data', [$this,'vargal_migrate_get_old_gallery_data'], 10,3);
		add_filter( 'vargal_get_thumbnail_width', [$this,'vargal_get_thumbnail_width'],10,1);
	}
	public function vargal_get_thumbnail_width($result) {
		if (in_array( wp_get_theme()->get_stylesheet(), [ 'woodmart', 'woodmart-child' ] ) ) {
//		if (in_array( wp_get_theme()->get_stylesheet(), [ 'blocksy', 'blocksy-child' ] ) ) {
			$result = 100;
		}
		return $result;
	}
	/**
	 * @param $product WC_Product
	 *
	 * @return array
	 */
	public function vargal_migrate_get_old_gallery_data($old_gallery, $meta_key, $product) {
		if (!$product || $meta_key !== 'blocksy_post_meta_options'){
			return $old_gallery;
		}
		$tmp = $old_gallery;
		$old_gallery = [];
		if (!empty($tmp['images']) && is_array($tmp['images'])){
			foreach ($tmp['images'] as $image) {
				if (!empty($image['attachment_id'])){
					$old_gallery[] = $image['attachment_id'];
				}
			}
		}
		return $old_gallery;
	}
}