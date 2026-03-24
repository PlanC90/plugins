<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WOO_PRODUCT_VARIATIONS_SWATCHES_Admin_Custom_Attribute {
	protected static $settings;

	function __construct() {
		self::$settings = VI_WOO_PRODUCT_VARIATIONS_SWATCHES_DATA::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 99 );
		add_action( 'woocommerce_product_option_terms', array( $this, 'woocommerce_product_option_terms' ),10,3 );
		add_action( 'woocommerce_after_product_attribute_settings', array( $this, 'after_product_attribute_settings' ),99,2 );
		add_filter( 'woocommerce_admin_meta_boxes_prepare_attribute', array( $this, 'save_attribute_swatches' ), 99999, 3 );
	}
	/**
	 * @param array|null $attribute_taxonomy Attribute taxonomy object.
	 * @param number $i Attribute index.
	 * @param WC_Product_Attribute $attribute Attribute object.
	 */
	public function woocommerce_product_option_terms( $attribute_taxonomy, $i, $attribute){
		if ($attribute_taxonomy->attribute_type !== 'select'){
			$attribute_orderby = ! empty( $attribute_taxonomy->attribute_orderby ) ? $attribute_taxonomy->attribute_orderby : 'name';
			/**
			 * @param int $term_limit The maximum number of terms to display in the list.
			 */
			$term_limit = absint( apply_filters( 'woocommerce_admin_terms_metabox_datalimit', 50 ) );
			?>
			<select multiple="multiple"
			        data-minimum_input_length="0"
			        data-limit="<?php echo esc_attr( $term_limit ); ?>" data-return_id="id"
			        data-placeholder="<?php esc_attr_e( 'Select values', 'woocommerce' ); ?>"
			        data-orderby="<?php echo esc_attr( $attribute_orderby ); ?>"
			        class="multiselect attribute_values wc-taxonomy-term-search"
			        name="attribute_values[<?php echo esc_attr( $i ); ?>][]"
			        data-taxonomy="<?php echo esc_attr( $attribute->get_taxonomy() ); ?>">
				<?php
				$selected_terms = $attribute->get_terms();
				if ( $selected_terms ) {
					foreach ( $selected_terms as $selected_term ) {
						/**
						 * Filter the selected attribute term name.
						 *
						 * @since 3.4.0
						 * @param string  $name Name of selected term.
						 * @param array   $term The selected term object.
						 */
						echo '<option value="' . esc_attr( $selected_term->term_id ) . '" selected="selected">' . esc_html( apply_filters( 'woocommerce_product_attribute_term_name', $selected_term->name, $selected_term ) ) . '</option>';
					}
				}
				?>
			</select>
			<button class="button plus select_all_attributes"><?php esc_html_e( 'Select all', 'woocommerce' ); ?></button>
			<button class="button minus select_no_attributes"><?php esc_html_e( 'Select none', 'woocommerce' ); ?></button>
			<button class="button fr plus add_new_attribute"><?php esc_html_e( 'Create value', 'woocommerce' ); ?></button>
			<?php
		}
	}
	public function save_attribute_swatches( $attribute, $data, $i){
		$post_id = isset( $_POST['post_id'] ) ? (int) sanitize_text_field(wp_unslash($_POST['post_id'])) :0;//phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( !$post_id || empty($data['viwpvs_save_attribute'][$i])) {
			return $attribute;
		}
		if ( empty( $data['attribute_names'] ) || empty( $data['attribute_values'] ) ) {
			delete_post_meta($post_id,'_vi_woo_product_variation_swatches_product_attribute');
			return $attribute;
		}
		if (empty($data['viwpvs_save_attribute'][$i])) {
			return $attribute;
		}
		$attribute_name               = wc_clean( esc_html( $data['attribute_names'][ $i ] ) );
		if (!empty($data['viwpvs_save_attribute'][$i])) {
			$vi_attribute_type            = isset( $data['vi_attribute_type'][ $i ] ) ? wc_clean( $data['vi_attribute_type'][ $i ] ) : '';
			$vi_attribute_profile         = isset( $data['vi_attribute_profile'][ $i ] ) ? wc_clean( $data['vi_attribute_profile'][ $i ] ) : '';
			$vi_attribute_color_separator = isset( $data['vi_attribute_color_separator'][ $i ] ) ? wc_clean( $data['vi_attribute_color_separator'][ $i ] ) : '';
			$vi_attribute_colors          = isset( $data['vi_attribute_colors'][ $i ] ) ? wc_clean( $data['vi_attribute_colors'][ $i ] ) : '';
			$vi_attribute_images          = isset( $data['vi_attribute_images'][ $i ] ) ? wc_clean( $data['vi_attribute_images'][ $i ] ) : '';
			$vi_attribute_display_type    = isset( $data['vi_attribute_display_type'][ $i ] ) ? wc_clean( $data['vi_attribute_display_type'][ $i ] ) : '';
		}
		if ( 'pa_' !== substr( $attribute_name, 0, 3 ) ) {
			$attribute_name = html_entity_decode( $attribute_name, ENT_NOQUOTES, 'UTF-8' );
			if (!empty($vi_attribute_colors) && is_array($vi_attribute_colors)){
				$vi_attribute_colors = array_values($vi_attribute_colors);
			}
		}
		$vi_attribute_settings                                                 = get_post_meta( $post_id, '_vi_woo_product_variation_swatches_product_attribute', true );
		$vi_attribute_settings                                                 = $vi_attribute_settings ? json_decode( $vi_attribute_settings, true ) : array();
		$vi_attribute_settings['attribute_type'][ $attribute_name ]            = $vi_attribute_type ??'';
		$vi_attribute_settings['attribute_profile'][ $attribute_name ]         = $vi_attribute_profile??'';
		$vi_attribute_settings['attribute_color_separator'][ $attribute_name ] = $vi_attribute_color_separator??'';
		$vi_attribute_settings['attribute_colors'][ $attribute_name ]          = $vi_attribute_colors??'';
		$vi_attribute_settings['attribute_img_ids'][ $attribute_name ]         = $vi_attribute_images??'';
		$vi_attribute_settings['attribute_display_type'][ $attribute_name ]    = $vi_attribute_display_type??'';
        global $viwvps_f_save_attribute_settings;
        if (!$viwvps_f_save_attribute_settings){
            $viwvps_f_save_attribute_settings = [];
        }
        if (!isset($viwvps_f_save_attribute_settings[$post_id])){
            $viwvps_f_save_attribute_settings[$post_id] = [];
        }
        $viwvps_f_save_attribute_settings[$post_id][$i] = $vi_attribute_settings;
		$vi_attribute_settings                                                 = wp_json_encode( $vi_attribute_settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		update_post_meta( $post_id, '_vi_woo_product_variation_swatches_product_attribute', $vi_attribute_settings );
		return $attribute;
	}
	public static function after_product_attribute_settings($attribute, $i){
		global $thepostid;
		if (!$attribute || !is_a($attribute,'WC_Product_Attribute')){
			return;
		}
		$attribute_options  = $attribute->get_options();
		$vi_attribute_settings=[];
        $post_id = $thepostid;
        if (!$post_id){
            $post_id = isset( $_POST['post_id'] ) ? sanitize_text_field(wp_unslash($_POST['post_id'])) :0;//phpcs:ignore WordPress.Security.NonceVerification.Missing
        }
        global $viwvps_f_save_attribute_settings;
        if (isset($viwvps_f_save_attribute_settings[$post_id][$i])){
			$vi_attribute_settings = $viwvps_f_save_attribute_settings[$post_id][$i];
		}elseif ($post_id) {
			$vi_attribute_settings = get_post_meta( $thepostid, '_vi_woo_product_variation_swatches_product_attribute', true );
			$vi_attribute_settings    = $vi_attribute_settings ? json_decode( $vi_attribute_settings, true ) : array();
		}
		if (!is_array($vi_attribute_settings)){
			$vi_attribute_settings = [];
		}
		$vi_wpvs_ids              = self::$settings->get_params( 'ids' );
		$vi_wpvs_name             = self::$settings->get_params( 'names' );
		$vi_default_colors        = self::$settings->get_default_color();
		$attribute_types          = wc_get_attribute_types();
		include VI_WOO_PRODUCT_VARIATIONS_SWATCHES_TEMPLATES . 'html-product-attribute.php';
	}
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( $screen->id == 'product' ) {
            self::enqueue_scripts();
        }
	}
    public static function enqueue_scripts(){
	    wp_enqueue_style( 'product-variations-swatches-for-woocommerce-admin-minicolors', VI_WOO_PRODUCT_VARIATIONS_SWATCHES_CSS . 'minicolors.css', array(), VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION );
	    wp_enqueue_style( 'product-variations-swatches-for-woocommerce-admin-custom-attribute', VI_WOO_PRODUCT_VARIATIONS_SWATCHES_CSS . 'admin-custom-attribute.css', array(), VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION );
	    wp_enqueue_script( 'viwvps-select2', VI_WOO_PRODUCT_VARIATIONS_SWATCHES_JS . 'select2.js', array( 'jquery' ), VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION, true );
	    wp_enqueue_script( 'product-variations-swatches-for-woocommerce-admin-custom-attribute', VI_WOO_PRODUCT_VARIATIONS_SWATCHES_JS . 'admin-custom-attribute.js', array( 'jquery' ), VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION, true );
	    wp_enqueue_script( 'product-variations-swatches-for-woocommerce-admin-minicolors', VI_WOO_PRODUCT_VARIATIONS_SWATCHES_JS . 'minicolors.min.js', array( 'jquery' ), VI_WOO_PRODUCT_VARIATIONS_SWATCHES_VERSION, true );
    }

}