<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $product;

$attachment_ids = $product->get_gallery_image_ids();

if ( $attachment_ids && $product->get_image_id() ) {
	foreach ( $attachment_ids as $key => $attachment_id ) {
		/**
		 * Filter product image thumbnail HTML string.
		 *
		 * @since 1.6.4
		 *
		 * @param string $html          Product image thumbnail HTML string.
		 * @param int    $attachment_id Attachment ID.
		 */
		echo wp_kses(apply_filters( 'woocommerce_single_product_image_thumbnail_html', wc_get_gallery_image_html( $attachment_id, false, $key ), $attachment_id ), VARGAL_DATA::filter_allowed_html());
	}
}