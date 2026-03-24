<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$color_separator = [
        '1'=>__( 'Basic horizontal', 'product-variations-swatches-for-woocommerce' ),
        '2'=>__( 'Basic vertical', 'product-variations-swatches-for-woocommerce' ),
        '3'=>__( 'Basic diagonal left', 'product-variations-swatches-for-woocommerce' ),
        '4'=>__( 'Basic diagonal right', 'product-variations-swatches-for-woocommerce' ),
        '5'=>__( 'Hard lines horizontal', 'product-variations-swatches-for-woocommerce' ),
        '6'=>__( 'Hard lines vertical', 'product-variations-swatches-for-woocommerce' ),
        '7'=>__( 'Hard lines diagonal left', 'product-variations-swatches-for-woocommerce' ),
        '8'=>__( 'Hard lines diagonal right', 'product-variations-swatches-for-woocommerce' ),
];
$can_edit                          = in_array( $vi_attribute_type, [ 'image', 'color' ] );
$img_src                      = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail', true ) : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
?>
<div class="<?php echo esc_attr( $class ); ?>"
     data-attribute_number="<?php echo esc_attr( $i ); ?>"
     data-term_id="<?php echo esc_attr( $term_id ?: '' ); ?>">
    <div class="vi-wpvs-attribute-value-title-wrap vi-wpvs-attribute-value-title-toggle">
        <span class="vi-wpvs-attribute-value-name">
            <?php echo wp_kses_post( $item_name ); ?>
        </span>
        <div class="vi-wpvs-attribute-value-action-wrap">
            <span class="vi-wpvs-attribute-value-action-icon vi-wpvs-attribute-value-action-icon-down dashicons dashicons-arrow-down"></span>
            <span class="vi-wpvs-attribute-value-action-icon vi-wpvs-attribute-value-action-icon-up dashicons dashicons-arrow-up vi-wpvs-hidden"></span>
        </div>
    </div>
    <div class="vi-wpvs-attribute-value-content-wrap vi-wpvs-attribute-value-content-close">
        <table>
            <tbody>
            <tr class="vi-wpvs-attribute-value-content-image-wrap vi-wpvs-attribute-value-content-item-wrap">
                <td>
					<?php
					esc_html_e( 'Image', 'product-variations-swatches-for-woocommerce' );
					echo wc_help_tip( esc_html__( 'Image can also be used for "Change product image" option if attribute type is not Image', 'product-variations-swatches-for-woocommerce' ) );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
                </td>
                <td>
                    <input type="hidden" name="<?php echo esc_attr( $attr_item_image_name ) ; ?>"
                           data-name="<?php echo esc_attr( $attr_item_image_name ); ?>" class="vi_attribute_image"
                           value="<?php echo esc_attr( $img_id?:0 ); ?>">
                    <div class="vi-attribute-image-wrap vi-attribute-edit-image-wrap vi-wpvs-term-image-upload-img">
                            <span class="vi-attribute-edit-image-preview vi-attribute-image-preview">
                                 <img src="<?php echo esc_attr( esc_url( $img_src ) ); ?>"
                                      data-src_placeholder="<?php echo esc_attr( wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ) ); ?>">
                            </span>
                        <span class="vi-attribute-image-remove dashicons dashicons-dismiss<?php echo $img_id ? '' : esc_attr( ' vi-wpvs-hidden' ); ?>"></span>
                        <div class="vi-attribute-image-add-new"><?php esc_html_e( 'Upload/Add an image', 'product-variations-swatches-for-woocommerce' ); ?></div>
                    </div>
                    <p class="description">
						<?php esc_html_e( 'Choose an image', 'product-variations-swatches-for-woocommerce' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="vi-wpvs-attribute-value-content-color-wrap vi-wpvs-attribute-value-content-item-wrap">
                <td>
					<?php esc_html_e( 'Color', 'product-variations-swatches-for-woocommerce' ); ?>
                </td>
                <td>
                    <p><?php esc_html_e( 'Color separator', 'product-variations-swatches-for-woocommerce' ); ?>
                        <select name="<?php echo esc_attr( $attr_item_color_separator_name ); ?>"
                                data-name="<?php echo esc_attr( $attr_item_color_separator_name ); ?>"
                                class="vi_attribute_color_separator">
                            <?php
                            foreach ($color_separator as $color_separator_i => $color_separator_v){
                                ?>
                                <option value="<?php echo esc_attr($color_separator_i)?>" <?php selected( $attr_item_color_separator, $color_separator_i ) ?>>
		                            <?php echo wp_kses_post($color_separator_v)?>
                                </option>
                                <?php
                            }
                            ?>
                        </select>
                    </p>
                    <table class="vi-wpvs-attribute-value-content-color-table">
                        <tr>
                            <th><?php esc_html_e( 'Color', 'product-variations-swatches-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'product-variations-swatches-for-woocommerce' ); ?></th>
                        </tr>
						<?php
                        if (!is_array($colors) || empty($colors)){
                            $colors =[$vi_default_colors[ strtolower( $item_name ) ] ?? '#fff'];
                        }
						foreach ( $colors as $color ) {
							?>
                            <tr>
                                <td>
                                    <input type="text" class="vi-wpvs-color vi_attribute_colors"
                                           name="<?php echo esc_attr( $attr_item_color_name ); ?>"
                                           data-name="<?php echo esc_attr( $attr_item_color_name ); ?>"
                                           value="<?php echo esc_attr( $color ) ?>">
                                </td>
                                <td>
                                    <span class="vi-wpvs-attribute-colors-action-clone button button-primary button-small"">
									<?php esc_html_e( 'Clone', 'product-variations-swatches-for-woocommerce' ) ?>
                                    </span>
                                    <span class="vi-wpvs-attribute-colors-action-remove button button-secondary delete button-small">
                                               <?php esc_html_e( 'Remove', 'product-variations-swatches-for-woocommerce' ) ?>
                                           </span>
                                </td>
                            </tr>
							<?php
						}
						?>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
