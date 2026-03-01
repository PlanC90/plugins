<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Automattic\WooCommerce\Enums\ProductType;
global $product;
$columns           = apply_filters( 'woocommerce_product_thumbnails_columns', 4 );
$post_thumbnail_id = $product->get_image_id();
$wrapper_classes   = apply_filters(
	'woocommerce_single_product_image_gallery_classes',
	array(
		'vargal-product-gallery',
		'vargal-product-gallery-wrap',
		'vargal-product-gallery-product-'.$product->get_id(),
		'woocommerce-product-gallery',
		'woocommerce-product-gallery--' . ( $post_thumbnail_id ? 'with-images' : 'without-images' ),
		'woocommerce-product-gallery--columns-' . absint( $columns ),
		'images',
	)
);
add_filter('woocommerce_single_product_flexslider_enabled','__return_true');
?>
<div class="<?php echo esc_attr( implode( ' ', array_map( 'sanitize_html_class', $wrapper_classes ) ) ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>" style="opacity: 0; transition: opacity .25s ease-in-out;">
	<div class="vargal-product-gallery__wrapper">
		<div class="vargal-woocommerce-product-gallery__wrapper woocommerce-product-gallery__wrapper">
			<?php
			if ( $post_thumbnail_id ) {
				$html = wc_get_gallery_image_html( $post_thumbnail_id, true );
			} else {
				$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_available_variations( 'image' ) ) ?
					'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder' :
					'woocommerce-product-gallery__image--placeholder';
				$html              = sprintf( '<div class="%s">', esc_attr( $wrapper_classname ) );
				$html             .= wc_placeholder_img('woocommerce_single',[
					'alt'=> esc_html__( 'Awaiting product image', 'vargal-additional-variation-gallery-for-woo' ) ,
					'class'=> 'wp-post-image'
				]);
				$html             .= '</div>';
			}

			echo wp_kses(apply_filters( 'woocommerce_single_product_image_thumbnail_html', $html, $post_thumbnail_id ), VARGAL_DATA::filter_allowed_html());

			do_action( 'woocommerce_product_thumbnails' );
			?>
		</div>
	</div>
</div>