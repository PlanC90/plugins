<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$attribute_name     = $attribute->get_name();
$attribute_position = $attribute->get_position();
$is_taxonomy = $attribute->is_taxonomy();
if ( $is_taxonomy ) {
	$global_settings_url = admin_url( 'admin.php?page=woocommerce-product-variations-swatches-global-attrs&viwvps_attr='.$attribute->get_id() );
	$vi_attribute_profile         = $vi_attribute_settings['attribute_profile'][ $attribute_name ] ?? null;
	$vi_attribute_type            = $vi_attribute_settings['attribute_type'][ $attribute_name ] ?? null;
	$vi_attribute_colors          = $vi_attribute_settings['attribute_colors'][ $attribute_name ] ?? array();
	$vi_attribute_color_separator = $vi_attribute_settings['attribute_color_separator'][ $attribute_name ] ?? array();
	$vi_attribute_img_ids         = $vi_attribute_settings['attribute_img_ids'][ $attribute_name ] ?? array();
	$vi_attribute_display_type    = $vi_attribute_settings['attribute_display_type'][ $attribute_name ] ?? null;
}else{
	$global_settings_url = admin_url( 'admin.php?page=woocommerce-product-variations-swatches#custom_attrs' );
	$attribute_name_              = html_entity_decode( $attribute_name, ENT_NOQUOTES, 'UTF-8' );
	$vi_attribute_profile         = $vi_attribute_settings['attribute_profile'][ $attribute_name_ ] ?? null;
	$vi_attribute_type            = $vi_attribute_settings['attribute_type'][ $attribute_name_ ] ?? null;
	$vi_attribute_colors          = $vi_attribute_settings['attribute_colors'][ $attribute_name_ ] ?? array();
	$vi_attribute_color_separator = $vi_attribute_settings['attribute_color_separator'][ $attribute_name_ ] ?? array();
	$vi_attribute_img_ids         = $vi_attribute_settings['attribute_img_ids'][ $attribute_name_ ] ?? array();
	$vi_attribute_display_type    = $vi_attribute_settings['attribute_display_type'][ $attribute_name_ ] ?? null;
}
$need_edit = ($vi_attribute_type || $vi_attribute_profile )? 1:0;
?>
<tr>
    <td colspan="4">
        <p>
		    <?php esc_html_e('You can customize the general display settings for ', 'product-variations-swatches-for-woocommerce' ); ?>
            <a href="<?php echo esc_url($global_settings_url)?>" target="_blank"><?php esc_html_e(' attribute ', 'product-variations-swatches-for-woocommerce');echo wp_kses_post(wc_attribute_label($attribute_name)); ?></a>
		    <?php esc_html_e(' of all products or click ', 'product-variations-swatches-for-woocommerce' ); ?>
            <a href="#" class="vi-wpvs-attribute-info-custom-open"><?php esc_html_e('here', 'product-variations-swatches-for-woocommerce' ); ?></a>
		    <?php esc_html_e(' to configure settings specifically for this product.', 'product-variations-swatches-for-woocommerce' ); ?>
        </p>
        <div class="vi-wpvs-attribute-wrap<?php echo esc_attr($need_edit?'' :' vi-wpvs-hidden')?>">
            <div class="vi-wpvs-attribute-info-wrap vi-wpvs-attribute-info-custom-wrap">
                <div class="vi-wpvs-attribute-loop-enable">
                    <a class="button button-primary" disabled target="_blank"
                       href="https://1.envato.market/bd0ek">
		                <?php esc_html_e( 'Show in product list - Premium version only', 'product-variations-swatches-for-woocommerce' ); ?>
                    </a>
                </div>
                <div class="vi-wpvs-attribute-display-type">
                    <select name="vi_attribute_display_type[<?php echo esc_attr( $i ); ?>]"
                            title="<?php esc_attr_e( 'Choose display style', 'product-variations-swatches-for-woocommerce' ); ?>">
                        <option value="0" <?php selected( $vi_attribute_display_type, '0' ) ?>>
						    <?php esc_html_e( 'Global Style', 'product-variations-swatches-for-woocommerce' ); ?>
                        </option>
                        <option value="vertical" <?php selected( $vi_attribute_display_type, 'vertical' ) ?>>
						    <?php esc_html_e( 'Vertical', 'product-variations-swatches-for-woocommerce' ); ?>
                        </option>
                        <option value="horizontal" <?php selected( $vi_attribute_display_type, 'horizontal' ) ?>>
						    <?php esc_html_e( 'Horizontal', 'product-variations-swatches-for-woocommerce' ); ?>
                        </option>
                    </select>
                </div>
                <div class="vi-wpvs-attribute-type">
                    <select name="vi_attribute_type[<?php echo esc_attr( $i ); ?>]"
                            title="<?php esc_attr_e( 'Choose display type', 'product-variations-swatches-for-woocommerce' ); ?>">
                        <option value="0" <?php selected( $vi_attribute_type, '0' ) ?>>
						    <?php esc_html_e( 'Global Type', 'product-variations-swatches-for-woocommerce' ); ?>
                        </option>
					    <?php
					    foreach ( $attribute_types as $k => $v ) {
						    ?>
                            <option value="<?php echo esc_attr( $k ); ?>" <?php selected( $vi_attribute_type, $k ) ?>><?php echo esc_html( $v ); ?></option>
						    <?php
					    }
					    ?>
                    </select>
                </div>
                <div class="vi-wpvs-attribute-profile">
                    <select name="vi_attribute_profile[<?php echo esc_attr( $i ); ?>]"
                            title="<?php esc_attr_e( 'Choose swatches profile', 'product-variations-swatches-for-woocommerce' ); ?>">
                        <option value="0" <?php selected( $vi_attribute_profile, '0' ) ?>>
						    <?php esc_html_e( 'Global Profile', 'product-variations-swatches-for-woocommerce' ); ?>
                        </option>
					    <?php
					    foreach ( $vi_wpvs_ids as $k => $id ) {
						    ?>
                            <option value="<?php echo esc_attr( $id ) ?>" <?php selected( $vi_attribute_profile, $id ) ?>><?php echo esc_html( $vi_wpvs_name[ $k ] ); ?></option>
						    <?php
					    }
					    ?>
                    </select>
                </div>
                <div class="vi-wpvs-change-product-image"
                     title="<?php echo esc_html__( 'When selecting an attribute value, change product image according to attribute/variation image(Only use for Image/Variation image type)', 'product-variations-swatches-for-woocommerce' ) ?>">
                    <a class="button button-primary" disabled target="_blank"
                       href="https://1.envato.market/bd0ek">
		                <?php esc_html_e( 'Show in product list - Premium version only', 'product-variations-swatches-for-woocommerce' ); ?>
                    </a>
                </div>
            </div>
            <div class="vi-wpvs-attribute-value-wrap">
                <input type="hidden" class="viwpvs_save_attribute" name="viwpvs_save_attribute[<?php echo esc_attr( $i ); ?>]"
                       value="<?php echo esc_attr( $need_edit ); ?>">
                <p><strong><?php esc_html_e('Please click \'Save attributes\' before setting the new attribute item', 'product-variations-swatches-for-woocommerce' ); ?></strong></p>
			    <?php
			    if ( $is_taxonomy) {
				    foreach ( $attribute_options as $option ) {
					    $term = get_term( $option );
					    if ( ! $term ) {
						    continue;
					    }
					    $vi_wpvs_terms_settings = ! empty( get_term_meta( $option, 'vi_wpvs_terms_params', true ) ) ? get_term_meta( $option, 'vi_wpvs_terms_params', true ) : array();
					    $attr_item_name = $term->name;
					    $attr_item_color_separator = $vi_attribute_color_separator[ $option ] ?? $vi_wpvs_terms_settings['color_separator'] ?? '1';
					    $attr_item_colors          = $vi_attribute_colors[ $option ] ?? $vi_wpvs_terms_settings['color'] ?? '';
					    $attr_item_img_id          = $vi_attribute_img_ids[ $option ] ?? $vi_wpvs_terms_settings['img_id'] ?? '';
					    $attr_item_color_separator_name = 'vi_attribute_color_separator[' . $i . '][' . $term->term_id . ']';
					    $attr_item_color_name                  = 'vi_attribute_colors[' . $i . '][' . $term->term_id . '][]';
					    $attr_item_image_name              = 'vi_attribute_images[' . $i . '][' . $term->term_id . ']';
					    wc_get_template( 'html-product-attribute-item.php',
						    array(
							    'class'              => 'vi-wpvs-attribute-value vi-wpvs-attribute-taxonomy-value vi-wpvs-attribute-taxonomy-value-'.$term->term_id,
							    'i'                     => $i,
							    'attr_item'                  => $term,
							    'term_id'                  => $term->term_id,
							    'item_name'                  => $attr_item_name,
							    'img_id'          => $attr_item_img_id,
							    'colors'          => $attr_item_colors,
							    'attr_item_color_separator' => $attr_item_color_separator,
							    'vi_attribute_type'     => $vi_attribute_type,
							    'vi_default_colors'     => $vi_default_colors,
							    'attr_item_image_name'          => $attr_item_image_name,
							    'attr_item_color_name'          => $attr_item_color_name,
							    'attr_item_color_separator_name'          => $attr_item_color_separator_name,
						    ),
						    '',
						    VI_WOO_PRODUCT_VARIATIONS_SWATCHES_TEMPLATES );
				    }
			    }else{
				    if ( is_array( $attribute_options ) && !empty( $attribute_options ) ) {
					    foreach ( $attribute_options as $k => $attribute_option ) {
						    $attr_item_name = $attribute_option;
						    $attr_item_color_separator = $vi_attribute_color_separator[ $k ] ?? '1';
						    $attr_item_colors          = $vi_attribute_colors[ $k ] ?? array();
						    $attr_item_img_id          = $vi_attribute_img_ids[ $k ] ?? '';
						    $attr_item_colors_id       = current_time( 'timestamp' ) . '-' . $k;
						    $attr_item_color_separator_name = 'vi_attribute_color_separator[' . $i . '][]';
						    $attr_item_color_name                  = 'vi_attribute_colors[' . $i . '][' .$attr_item_colors_id. '][]';
						    $attr_item_image_name              = 'vi_attribute_images[' . $i . '][]';
						    wc_get_template( 'html-product-attribute-item.php',
							    array(
								    'class'              => 'vi-wpvs-attribute-value',
								    'i'                     => $i,
								    'attr_item'                  => $attribute_option,
								    'term_id'                  => 0,
								    'item_name'                  => $attr_item_name,
								    'attr_item_image_name'          => $attr_item_image_name,
								    'attr_item_color_name'          => $attr_item_color_name,
								    'attr_item_color_separator_name'          => $attr_item_color_separator_name,
								    'img_id'          => $attr_item_img_id,
								    'colors'          => $attr_item_colors,
								    'attr_item_color_separator' => $attr_item_color_separator,
								    'vi_attribute_type'     => $vi_attribute_type,
								    'vi_default_colors'     => $vi_default_colors,
							    ),
							    '',
							    VI_WOO_PRODUCT_VARIATIONS_SWATCHES_TEMPLATES );
					    }
				    }
			    }
			    ?>
            </div>
        </div>
    </td>
</tr>
