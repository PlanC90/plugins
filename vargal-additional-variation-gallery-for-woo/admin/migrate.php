<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VARGAL_Admin_Migrate {
	protected static $settings, $prefix;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		self::$prefix = self::$settings::$prefix;
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 25 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_vargal_migrate_gallery', [ $this, 'vargal_migrate_gallery' ] );
	}
	public function admin_menu() {
		$menu_slug = self::$settings::$prefix;
		add_submenu_page(
			$menu_slug,
			esc_html__( 'Migrate Gallery', 'vargal-additional-variation-gallery-for-woo' ),
			esc_html__( 'Migrate Gallery', 'vargal-additional-variation-gallery-for-woo' ),
			apply_filters( "villatheme_{$menu_slug}_admin_sub_menu_capability", 'manage_woocommerce', "{$menu_slug}-migrate" ),
			"{$menu_slug}-migrate",
			array( $this, 'page_callback' )
		);
	}
	public function page_callback() {
		$log_page = admin_url( 'admin.php?page=wc-status&tab=logs' );
        $selected ='';
        $options= [
                '_wc_additional_variation_images'=>'WooCommerce Additional Variation Images',
                'wavi_value'=>'Product Gallery Slider, Additional Variation Images for WooCommerce - by Niloy - Codeixer',
                'woo_variation_gallery_images'=>'Additional Variation Images Gallery for WooCommerce - by Emran Ahmed',
                '_product_image_gallery'=>'Smart Variations Images & Swatches for WooCommerce - by David Rosendo',
                'variation_image_gallery'=>'WooThumbs for WooCommerce - by Iconic',
                'rtwpvg_images'=>'Variation Images Gallery for WooCommerce - by RadiusTheme',
        ];
		$themes = wp_get_themes();
		foreach ($themes as $theme) {
            $slug = $theme->get_stylesheet();
			if (!isset($options['wd_additional_variation_images_data']) && in_array($slug,['woodmart','woodmart-child'])){
				$options['wd_additional_variation_images_data'] = 'WoodMart theme';
                continue;
			}
			if (!isset($options['blocksy_post_meta_options']) && in_array($slug,['blocksy','blocksy-child'])){
				$options['blocksy_post_meta_options'] = 'Blocksy theme';
			}
		}
		?>
		<div class="wrap vargal-migrate-wrap">
			<h2><?php esc_html_e( 'Migrate Variation Gallery', 'vargal-additional-variation-gallery-for-woo' ) ?></h2>
			<div class="vi-ui message positive">
				<ul class="list">
					<li><?php esc_html_e( 'Convert the variation gallery data by theme or other plugins to Vargal data without resetting per variation ', 'vargal-additional-variation-gallery-for-woo' ); ?></li>
					<li><?php printf( esc_html__( 'For details of migration, please go to %s', 'vargal-additional-variation-gallery-for-woo' ), '<a target="_blank" href="' . esc_url( $log_page ) . '">' . esc_html( $log_page ) . '</a>' ); //phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment ?></li>
				</ul>
			</div>
			<div class="vi-ui segment">
				<div class="vi-ui steps fluid">
					<div class="step active vargal-migrate-step-1">
						<i class="settings icon"></i>
						<div class="content">
							<div class="title"><?php esc_html_e( 'Select options', 'vargal-additional-variation-gallery-for-woo' ); ?></div>
						</div>
					</div>
					<div class="step disabled vargal-migrate-step-2">
						<i class="refresh icon"></i>
						<div class="content">
							<div class="title"><?php esc_html_e( 'Migrate', 'vargal-additional-variation-gallery-for-woo' ); ?></div>
						</div>
					</div>
				</div>
				<div class="vi-ui form">
					<div class="field vargal-migrate-step-content vargal-migrate-step-content-1">
						<table class="form-table">
							<tbody>
							<tr>
								<th>
									<label for="vargal-source"><?php esc_html_e( 'Variation Gallery Source', 'vargal-additional-variation-gallery-for-woo' ) ?></label>
								</th>
								<td>
									<select name="source" id="vargal-source" class="vargal-source vi-ui dropdown search fluid">
										<?php
                                        foreach ($options as $k => $v){
                                            ?>
                                            <option value="<?php echo esc_attr($k) ?>" <?php selected($k, $selected) ?>><?php echo wp_kses_post($v) ?></option>
                                            <?php
                                        }
                                        ?>
									</select>
								</td>
							</tr>
							</tbody>
						</table>
						<p class="vargal-button-scan-container">
                            <span class="vi-ui button primary vargal-button-migrate">
                                <?php esc_html_e( 'Migrate', 'vargal-additional-variation-gallery-for-woo' ) ?>
                            </span>
						</p>
					</div>
					<div class="field vargal-migrate-step-content vargal-migrate-step-content-2 vargal-hidden">
						<div class="vi-ui indicating progress standard active vargal-migrate-progress">
							<div class="label"></div>
							<div class="bar">
								<div class="progress"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	public function admin_enqueue_scripts() {
		$menu_slug = self::$settings::$prefix;
		$page      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $page !== ($menu_slug . '-migrate' ) ) {
			return;
		}
		self::$settings::enqueue_style(
			array(
				'semantic-ui-button',
				'semantic-ui-dropdown',
				'semantic-ui-segment',
				'semantic-ui-form',
				'semantic-ui-label',
				'semantic-ui-input',
				'semantic-ui-icon',
				'semantic-ui-step',
				'semantic-ui-progress',
				'semantic-ui-message',
				'transition',
			),
			array(
				'button',
				'dropdown',
				'segment',
				'form',
				'label',
				'input',
				'icon',
				'step',
				'progress',
				'message',
				'transition',
			),
			array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
		);
		self::$settings::enqueue_style(
			array( 'vargal-admin-settings', 'villatheme-show-message' ),
			array( 'admin-settings', 'villatheme-show-message' ),
			array( )
		);
		self::$settings::enqueue_script(
			array( 'vargal-address',  'semantic-ui-progress', 'semantic-ui-dropdown', 'transition' ),
			array( 'address','progress','dropdown','transition' ),
			array( 1, 1,1, 1)
		);
		self::$settings::enqueue_script(
			array( 'vargal-admin-migrate', 'villatheme-show-message' ),
			array( 'admin-migrate','villatheme-show-message' ),
			array(),
			array()
		);
		$params          = array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'vargal-migrate-gallery' ),
			'i18n_error'                => esc_html__( 'An error occurs, please try again later', 'vargal-additional-variation-gallery-for-woo' ),
		);
		$localize_script = $menu_slug . '-admin-migrate';
		wp_localize_script( $localize_script, $menu_slug . '_params', $params );
	}
    public function vargal_migrate_gallery(){
	    $response = array(
		    'status'         => 'error',
		    'message' => '',
		    'stop'    => '',
	    );
	    if ( !check_ajax_referer( 'vargal-migrate-gallery','_vargal_nonce', false )) {
		    $result['message']='Invalid nonce';
		    wp_send_json($result);
	    }
	    $meta_key = isset( $_POST['product_source_meta'] ) ? sanitize_text_field( wp_unslash($_POST['product_source_meta'] )) : '';
	    if ( ! $meta_key ) {
		    $response['message'] = __( 'Can not find the meta key that one can use to store the variation gallery.', 'vargal-additional-variation-gallery-for-woo' );
		    wp_send_json( $response );
	    }
	    VARGAL_DATA::villatheme_set_time_limit();
	    $page             = isset( $_POST['page'] ) ? absint( sanitize_text_field( wp_unslash( $_POST['page'] )) ) : 1;
	    $max_page         = isset( $_POST['max_page'] ) ? absint( sanitize_text_field(wp_unslash( $_POST['max_page'] )) ) : 1;
	    $per_page = 100;
	    $args     = array(
		    'type'       => array( 'variation'),
		    'limit' => $per_page,
		    'paged'          => $page,
		    'vargal_meta_query'          => array(
			    'relation' => 'AND',
			    array(
				    'key'     => $meta_key,
				    'compare' => 'exists',
			    ),
			    array(
				    'key'     => $meta_key,
				    'value'   => '',
				    'compare' => '!=',
			    )
		    ),
		    'return'     => 'ids',
		    'post_status'    => [ 'publish', 'pending' ],
	    );
        if ($page=== 1){
            $args['paginate'] = true;
        }
	    add_filter( 'woocommerce_product_data_store_cpt_get_products_query', [ $this, 'product_data_store_cpt_get_products_query' ], 10, 2 );
	    $woo_ids = wc_get_products($args);
        remove_filter('woocommerce_product_data_store_cpt_get_products_query', [ $this, 'product_data_store_cpt_get_products_query' ]);
	    if ($page=== 1){
		    $max_page = $woo_ids->max_num_pages ?? 1;
		    $woo_ids = $woo_ids->products;
            ob_start();
		    printf( wp_kses_post('Migration of the variation gallery data from: %s ' ) , esc_attr($meta_key) );
		    $this->wc_log(ob_get_clean() ,'migrate-gallery');
	    }
	    if (is_array($woo_ids) && !empty($woo_ids)){
		    foreach ( $woo_ids as $woo_id ) {
                $product = wc_get_product($woo_id);
			    $old_gallery = apply_filters('vargal_migrate_get_old_gallery_data',$product->get_meta($meta_key), $meta_key, $product);
                if (!empty($old_gallery)){
                    if (!is_array($old_gallery)){
	                    $old_gallery = explode( ',', $old_gallery ) ;
                    }
                    if (is_array($old_gallery)){
	                    ob_start();
	                    printf( wp_kses_post('Variation: (# '.$woo_id.' ) '.$product->get_name()) );
	                    $this->wc_log(ob_get_clean() ,'migrate-gallery');
	                    $old_gallery = array_values( array_filter(  $old_gallery )  );
	                    $product->update_meta_data('vargal_params', $old_gallery);
	                    $product->save();
                    }
                }
		    }
	    }
	    $response['max_page'] = $max_page;
	    if ( $max_page > 0 ) {
		    $response['percent'] = intval( 100 * ( $page / $max_page ) );
		    if ( $page < $max_page ) {
			    $page ++;
		    } else {
			    $response['message'] = esc_html__( 'Complete.', 'vargal-additional-variation-gallery-for-woo');
		    }
	    } else {
		    $response['percent'] = 100;
	    }
	    $response['page']   = $page;
	    $response['status'] = 'success';
	    wp_send_json($response);
    }
    public function wc_log( $content, $source = 'debug', $level = 'info' ) {
	    if (!$content){
		    return;
	    }
	    $log     = wc_get_logger();
	    $log->log( $level,
		    $content,
		    array(
			    'source' => 'vargal-' . $source,
		    )
	    );
    }
    public function product_data_store_cpt_get_products_query($wp_query_args, $query_vars ){
        if (isset($wp_query_args['vargal_meta_query'])){
            $wp_query_args['meta_query'] = $wp_query_args['vargal_meta_query'];// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        }
	    return $wp_query_args;
    }
}