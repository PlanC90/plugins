<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VARGAL_Admin_Product {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		add_action( 'woocommerce_save_product_variation', array( $this, 'variation_gallery_save' ), 10, 2 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_gallery_settings' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}
    public function variation_gallery_save( $variation_id, $i ) {
	    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['vargal_params-'.$i.'-nonce'] ??'')),'vargal_params-'.$i)){
		    return;
	    }
        $product = wc_get_product($variation_id);
        if (!$product){
            return;
        }
	    $gallery = isset( $_POST['vargal_params'][ $i ] ) ? wc_clean( wp_unslash( $_POST['vargal_params'][ $i ] ) ) : '';// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if (is_array($gallery) && !empty($gallery)){
            $product->update_meta_data('vargal_params', $gallery);
        }else{
            $product->delete_meta_data('vargal_params');
        }
        $product->save();
    }
	public static function variation_gallery_settings($loop, $variation_data, $variation){
		if (!isset($variation->ID)){
			return;
		}
		$product = wc_get_product($variation->ID);
		if (!$product){
			return;
		}
        $gallery_input_name = "vargal_params[{$loop}][]";
		$gallery = $product->get_meta('vargal_params');
		?>
		<div class="vargal-settings" data-gallery_input_name="<?php echo esc_attr($gallery_input_name) ?>">
            <?php
            wp_nonce_field('vargal_params-'.$loop, 'vargal_params-'.$loop.'-nonce', false);
            self::gallery_settings_html($gallery,$gallery_input_name);
            ?>
		</div>
		<?php
	}
    public static function gallery_item_html($gallery,$gallery_input_name){
	    if (is_array($gallery) && !empty($gallery)){
		    foreach ($gallery as $media_id){
			    $class = ['vargal-img vargal-img-selected'];
			    $class[] = 'vargal-img-selected-'.$media_id;
			    $media_html='';
			    if ( wp_attachment_is_image( $media_id ) ) {
				    $media_html = wp_get_attachment_image( $media_id );
			    } elseif ( wp_attachment_is( 'video', $media_id ) ) {
				    $class[]='vargal-thumb-video';
				    $media_html = '<video preload="metadata" src="'.wp_get_attachment_url($media_id). '"></video>';
			    }
			    $media_html= apply_filters('vargal_product_get_media_html', $media_html, $media_id);
			    if (!$media_html){
				    continue;
			    }
			    ?>
                <div class="<?php echo esc_attr(implode(' ',$class))?>">
                    <input type="hidden" name="<?php echo esc_attr($gallery_input_name)?>" value="<?php echo esc_attr($media_id)?>">
				    <?php
				    echo wp_kses( $media_html, self::$settings::filter_allowed_html() );
				    ?>
                    <span class="vargal-img-delete"><i class="dashicons dashicons-dismiss"></i></span>
                </div>
			    <?php
		    }
	    }
    }
    public static function gallery_settings_html($gallery,$gallery_input_name){
        ?>
        <div class="vargal-settings-header">
            <h2>
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.998 10C19.9993 10.47 20 10.97 20 11.5C20 15.978 20 18.218 18.609 19.609C17.218 21 14.979 21 10.5 21C6.022 21 3.782 21 2.391 19.609C1 18.218 1 15.979 1 11.5C1 7.022 1 4.782 2.391 3.391C3.782 2 6.021 2 10.5 2C11.03 2 11.53 2.00067 12 2.002" stroke="#FC6736" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3.5 20.5C7.872 15.275 12.774 8.384 19.998 13.042M17.5 1L17.758 1.697C18.096 2.611 18.265 3.068 18.598 3.401C18.932 3.735 19.389 3.904 20.303 4.242L21 4.5L20.303 4.758C19.389 5.096 18.932 5.265 18.599 5.598C18.265 5.932 18.096 6.389 17.758 7.303L17.5 8L17.242 7.303C16.904 6.389 16.735 5.932 16.402 5.599C16.068 5.265 15.611 5.096 14.697 4.758L14 4.5L14.697 4.242C15.611 3.904 16.068 3.735 16.401 3.402C16.735 3.068 16.904 2.611 17.242 1.697L17.5 1Z" stroke="#FC6736" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
			    <?php esc_html_e('Variation Gallery','vargal-additional-variation-gallery-for-woo'); ?>
            </h2>
            <div class="vargal-settings-header-actions">
                <span class="toggle-indicator" aria-hidden="true"></span>
            </div>
        </div>
        <div class="vargal-settings-content">
		    <?php self::gallery_item_html($gallery, $gallery_input_name);?>
            <div class="vargal-img vargal-btn-upload">
                <svg width="30" height="30" viewBox="0 0 42 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.5" d="M29.75 15.7534C33.5563 15.7744 35.6178 15.9442 36.9618 17.2882C38.5 18.8264 38.5 21.3009 38.5 26.2499V27.9999C38.5 32.9507 38.5 35.4252 36.9618 36.9634C35.4253 38.4999 32.949 38.4999 28 38.4999H14C9.051 38.4999 6.57475 38.4999 5.03825 36.9634C3.5 35.4234 3.5 32.9507 3.5 27.9999V26.2499C3.5 21.3009 3.5 18.8264 5.03825 17.2882C6.38225 15.9442 8.44375 15.7744 12.25 15.7534" stroke="#FC6736" stroke-width="3" stroke-linecap="round"/>
                    <path d="M21 26.25V3.5M21 3.5L26.25 9.625M21 3.5L15.75 9.625" stroke="#FC6736" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="vargal-btn-upload-text">
                        <span class="vargal-btn-upload-text1">
                            <?php esc_html_e('Click here', 'vargal-additional-variation-gallery-for-woo'); ?>
                        </span>
                        <span class="vargal-btn-upload-text2">
                            <?php esc_html_e(' to upload your file', 'vargal-additional-variation-gallery-for-woo'); ?>
                        </span>
                    </span>
            </div>
        </div>
        <?php
    }
	public function admin_enqueue_scripts() {
		$screen = get_current_screen()->id;
		if ($screen !=='product'){
			return;
		}
        self::enqueue_scripts();
	}
    public static function enqueue_scripts(){
	    $prefix = self::$settings::$prefix;
	    self::$settings::enqueue_style(
		    array( $prefix . '-admin-product', 'villatheme-show-message' ),
		    array( 'admin-product', 'villatheme-show-message' ),
		    array( 0, 0 )
	    );
	    self::$settings::enqueue_script(
		    array( $prefix . '-admin-product', 'villatheme-show-message' ),
		    array( 'admin-product', 'villatheme-show-message' ),
		    array( 0, 0 )
	    );
	    do_action('vargal-product-admin-scripts');
	    $arg = [
		    'upload_frame_title'=> __('Choose or upload media that suits your preference. Just make sure it\'s an image or an MP4/WebM video.', 'vargal-additional-variation-gallery-for-woo'),
		    'upload_frame_btn'=> __('Use this media', 'vargal-additional-variation-gallery-for-woo'),
		    'duplicate_img_mes'=> __('This file is selected before.', 'vargal-additional-variation-gallery-for-woo'),
		    'invalid_type_mes'=> __('Please select only images or videos to set the gallery.', 'vargal-additional-variation-gallery-for-woo'),
		    'invalid_video_mes'=> __('Please select only videos in MP4 or WebM format.', 'vargal-additional-variation-gallery-for-woo'),
	    ];
	    wp_localize_script($prefix . '-admin-product','vargal_params', apply_filters('vargal-product-admin-params', $arg));
    }
}