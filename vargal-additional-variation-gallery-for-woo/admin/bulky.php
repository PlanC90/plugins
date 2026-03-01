<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VARGALPRO_Compatible_Bulky {
	protected static $settings;
	public static $cache=[];

	public function __construct() {
		self::$settings = VARGALPRO_DATA::get_instance();
		if ( ! self::$settings->get_params( 'enable' ) ) {
			return;
		}
		add_filter( 'bulky_get_product_data', array( $this, 'get_product_data' ), 10, 3 );
		add_filter( 'vi_wbe_set_cell_depend', array( $this, 'set_cell_depend' ), 10, 1 );
		add_action( 'bulky_parse_product_data_to_save', [ $this, 'save_vargal_data' ], 10, 3 );
		add_filter( 'bulky_image_storage',  [$this,'bulky_image_storage'], 15,1 );
	}
	public function bulky_image_storage($img_storage){
		$image_ids =[];
		if (is_array($img_storage)){
			$image_ids += array_keys($img_storage);
		}
		if (!empty($image_ids)) {
			foreach ( $image_ids as $image_id) {
				if (in_array($image_id, self::$cache['img_storage'])){
					continue;
				}
				if ( wp_attachment_is_image( $image_id ) ) {
					$img_storage[ $image_id ] = wp_get_attachment_image_url( $image_id );
				} else {
					$img_storage[ $image_id ] = get_post_meta( $image_id, '_exmage_external_oembed_src', true ) ?:
						( get_post_meta( $image_id, '_exmage_external_url', true ) ?: wp_get_attachment_url( $image_id ) );
				}
			}
		}
		return $img_storage;
	}
	public function save_vargal_data( \WC_Product $product, $type, $value ) {
		if ( ! $product || !$product->is_type('variation') || $type !== 'gallery' ) {
			return;
		}
		$product->set_gallery_image_ids( '' );
		if ( is_array( $value ) && ! empty( $value ) ) {
			$product->update_meta_data( 'vargal_params', $value );
		} else {
			$product->delete_meta_data( 'vargal_params' );
		}
		$product->save();
	}

	public function set_cell_depend( $result ) {
		if ( ! wp_script_is( 'vargal-bulky-edit' ) ) {
			self::$settings::enqueue_style(
				array( 'vargal-admin-product', 'villatheme-show-message' ),
				array( 'admin-product', 'villatheme-show-message' )
			);
			self::$settings::enqueue_script( [ 'vargal-bulky-edit', 'villatheme-show-message' ], [ 'bulky-edit', 'villatheme-show-message' ] );
			$arg = [
				'duplicate_img_mes' => esc_html__( 'This file is selected before.', 'vargal-additional-variation-gallery-for-woo' ),
				'invalid_type_mes'  => esc_html__( 'Please select only images or videos to set the gallery.', 'vargal-additional-variation-gallery-for-woo' ),
				'browser_not_support' => esc_html__( 'This format is not supported, Please use another file.', 'vargal-additional-variation-gallery-for-woo' ),
				'invalid_video_mes' => esc_html__( 'Please select only videos in MP4 or WebM format.', 'vargal-additional-variation-gallery-for-woo' ),
			];
			wp_localize_script( 'vargal-bulky-edit', 'vargal_params', $arg );
		}
		if ( ! isset( $result['variation'] ) || ! is_array( $result['variation'] ) ) {
			return $result;
		}
		$index = array_search( 'gallery', $result['variation'] );
		if ( $index !== false ) {
			unset( $result['variation'][ $index ] );
			$result['variation'] = array_values( $result['variation'] );
		}

		return $result;
	}

	public function get_product_data( $result, \WC_Product $product, $fields ) {
		if ( ! $product  || !$product->is_type('variation')) {
			return $result;
		}
		if (in_array( 'gallery', $fields )) {
			$image_ids = $product->get_meta( 'vargal_params' );
			if ( ! empty( $image_ids ) ) {
				$index            = array_search( 'gallery', $fields );
				$result[ $index ] = $image_ids;
				if (!isset(self::$cache['img_storage'])){
					self::$cache['img_storage'] =[];
				}
				self::$cache['img_storage'] +=  $image_ids;
				add_filter( 'bulky_image_storage', function ( $img_storage ) use ( $image_ids ) {
					foreach ( $image_ids as $image_id ) {
						if ( wp_attachment_is_image( $image_id ) ) {
							$img_storage[ $image_id ] = wp_get_attachment_image_url( $image_id );
						} else {
							$img_storage[ $image_id ] = get_post_meta( $image_id, '_exmage_external_oembed_src', true ) ?:
								( get_post_meta( $image_id, '_exmage_external_url', true ) ?: wp_get_attachment_url( $image_id ) );
						}
					}
					return $img_storage;
				} );
			}
		}
		return $result;
	}
}