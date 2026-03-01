<?php

defined( 'ABSPATH' ) || exit;

class VI_WOO_PRODUCT_VARIATIONS_SWATCHES_Admin_Recommend {
	protected $dismiss;
	protected static $settings;
    public static $plugins=[];

	public function __construct() {
        $this->dismiss  = 'villatheme_swatches_install_recommended_plugins_dismiss';
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}


	public function admin_enqueue_scripts() {
		$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		if (strpos( $page , 'woocommerce-product-variations-swatches' ) !== 0 ){
			return;
		}
		$prefix = 'swatches';
		$dismiss_nonce = isset( $_REQUEST[$prefix.'_dismiss_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST[$prefix.'_dismiss_nonce'] ) ) : '';
		if ( wp_verify_nonce( $dismiss_nonce,  $prefix.'_dismiss_nonce' ) && ! get_option( $this->dismiss ) ) {
			update_option( $this->dismiss , time() , 'no');
		}
		if (! get_option( $this->dismiss ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	public function admin_notices() {
		global $pagenow;
        if ( $pagenow === 'update.php' || get_option( $this->dismiss )){
            return;
        }
		$installed_plugins = get_plugins();
        $active_plugins = self::get_active_plugins();
		$recommended_plugins = self::recommended_plugins();
        $notices =[];
        $prefix = 'swatches';
		foreach ( $recommended_plugins as $recommended_plugin ) {
            $plugin_slug = $recommended_plugin['slug'];
            if (empty( $recommended_plugin['message_not_install'] ) && empty( $recommended_plugin['message_not_active'] )){
                continue;
            }
            $button = '';
            $pro_file =  ! empty( $recommended_plugin['pro'] )? "{$recommended_plugin['pro']}/{$recommended_plugin['pro']}.php":'';
            $plugin_file = "{$plugin_slug}/{$plugin_slug}.php";
            if ((!$pro_file || !isset($installed_plugins[$pro_file])) && !isset($installed_plugins[$plugin_file]) && ! empty( $recommended_plugin['message_not_install'] ) ){
                if ( current_user_can( 'install_plugins' )  ) {
                    $button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
                            esc_url( wp_nonce_url( network_admin_url( "update.php?action=install-plugin&plugin={$plugin_slug}" ), "install-plugin_{$plugin_slug}" ) ),
                            esc_html__( 'Install', 'product-variations-swatches-for-woocommerce' ),
                            $recommended_plugin['name']);
                }
                $notices[] = $recommended_plugin['message_not_install']. $button;
            }elseif ( ! empty( $recommended_plugin['message_not_active'] ) && ! isset($active_plugins[$plugin_slug] ) ) {
                if ( isset( $installed_plugins[ $pro_file ] ) ) {
                    if (! isset( $active_plugins[ $recommended_plugin['pro'] ] )) {
                        if ( current_user_can( 'activate_plugin', $pro_file ) ) {
                            $button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
                                    esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => $pro_file ), self_admin_url( 'plugins.php' ) ), "activate-plugin_{$pro_file}" ) ),
                                    esc_html__( 'Activate', 'product-variations-swatches-for-woocommerce' ),
                                    $recommended_plugin['name'] );
                        }
                        $notices[] = $recommended_plugin['message_not_active'] . $button;
                    }
                } else {
                    if ( current_user_can( 'activate_plugin', $plugin_file ) ) {
                        $button = sprintf( '<br><br> <a href="%s" target="_blank" class="button button-primary">%s %s</a>',
                                esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'activate', 'plugin' => $plugin_file ), self_admin_url( 'plugins.php' ) ), "activate-plugin_{$plugin_file}" ) ),
                                esc_html__( 'Activate', 'product-variations-swatches-for-woocommerce' ),
                                $recommended_plugin['name'] );
                    }
                    $notices[] = $recommended_plugin['message_not_active'] . $button;
                }
            }
		}
        if (!empty($notices)){
            ?>
            <div class="notice notice-info is-dismissible">
                <?php
                if (count($notices) > 1){
	                echo wp_kses_post(__('<p>WooCommerce Product Variations Swatches will work better with:</p>','product-variations-swatches-for-woocommerce'));
                    ?>
                    <ol>
                        <?php
                        foreach ( $notices as $notice ) {
	                        printf( "<li>%s</li>", wp_kses_post( $notice ) );
                        }
                        ?>
                    </ol>
                    <?php
                }else{
                    printf('<p>WooCommerce Product Variations Swatches will work better with: %s</p>', wp_kses_post( current( $notices ) ));
                }
                ?>
                <a href="<?php echo esc_url( add_query_arg( array( $prefix.'_dismiss_nonce' => wp_create_nonce( $prefix.'_dismiss_nonce' ) ) ) ) ?>"
                   target="_self">
                    <button type="button" class="notice-dismiss"></button>
                </a>
            </div>
            <?php
        }
	}
	public static function get_active_plugins(){
		if (empty(self::$plugins['active'])){
			$active_plugins = [];
			$tmp = get_option( 'active_plugins' ,[]);
			if (is_multisite()){
				$tmp += array_keys(get_site_option( 'active_sitewide_plugins', [] ));
			}
			if (!empty($tmp)){
				foreach ($tmp as $v){
					$info = explode('/',$v);
					if (empty($info[1])){
						$info= explode(DIRECTORY_SEPARATOR, $v);
					}
					if (empty($info[1])){
						continue;
					}
					$active_plugins[$info[0]] = $v;
				}
			}
			self::$plugins['active'] = $active_plugins;
		}
		return self::$plugins['active'];
	}

	public static function recommended_plugins() {
		if (empty(self::$plugins['recommend'])){
			self::$plugins['recommend'] = [
				'exmage-wp-image-links' => [
					'slug' => 'exmage-wp-image-links',
					'name' => 'EXMAGE – WordPress Image Links',
					'desc' => esc_html__( 'Save storage by using external image URLs', 'product-variations-swatches-for-woocommerce' ),
					'message_not_install' => sprintf( "%s <strong>EXMAGE – WordPress Image Links</strong> %s </br>",
						esc_html__( 'Need to save your server storage?', 'product-variations-swatches-for-woocommerce' ),
						esc_html__( 'will help you solve the problem by using external image URLs.', 'product-variations-swatches-for-woocommerce' )
					),
					'message_not_active'  => sprintf( "<strong>EXMAGE – WordPress Image Links</strong> %s",
						esc_html__( 'is currently inactive, external images added by this plugin(Post/product featured image, product gallery images...) will no longer work properly.', 'product-variations-swatches-for-woocommerce' ) ),
				],
				'vargal-additional-variation-gallery-for-woo'=>[
					'slug' => 'vargal-additional-variation-gallery-for-woo',
					'name' => 'VARGAL – Additional Variation Gallery for Woo',
					'desc' => esc_html__( 'Easily set unlimited images or videos for each WC product variation and display them when the customer selects',
						'product-variations-swatches-for-woocommerce' ),
					'message_not_install' => sprintf( "%s <strong>VARGAL – Additional Variation Gallery for Woo</strong> %s",
						esc_html__( 'Looking for a plugin that lets you add unlimited images or videos to each WooCommerce product variation?', 'product-variations-swatches-for-woocommerce' ),
						esc_html__( 'is what you need.', 'product-variations-swatches-for-woocommerce' ) ),
					'message_not_active'  => sprintf( "<strong>VARGAL</strong> %s",
						esc_html__( 'Set unlimited images or videos for every WooCommerce product variation.', 'product-variations-swatches-for-woocommerce' ) ),
				],
			];
		}
		return self::$plugins['recommend'];
	}
}
