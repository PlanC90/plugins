<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VARGAL_Admin_Settings {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}
	public function admin_menu() {
		$menu_slug = self::$settings::$prefix;
		add_menu_page(
			VARGAL_NAME,
			esc_html__( 'VARGAL', 'vargal-additional-variation-gallery-for-woo' ),
			apply_filters( "villatheme_{$menu_slug}_admin_menu_capability", 'manage_woocommerce', $menu_slug ),
			$menu_slug,
			array( $this, 'page_callback' ),
			'dashicons-format-gallery',
			2
		);
		add_submenu_page(
			$menu_slug,
			esc_html__( 'Variation Gallery', 'vargal-additional-variation-gallery-for-woo' ),
			esc_html__( 'Variation Gallery', 'vargal-additional-variation-gallery-for-woo' ),
			apply_filters( "villatheme_{$menu_slug}_admin_sub_menu_capability", 'manage_woocommerce', "{$menu_slug}-settings" ),
			"{$menu_slug}",
			array( $this, 'page_callback' )
		);
	}
	public function page_callback(){
		$prefix = self::$settings::$prefix;
		$tabs       = apply_filters("villatheme_{$prefix}_settings_tabs",array(
			'general' => esc_html__( 'General', 'vargal-additional-variation-gallery-for-woo' ),
			'template' => esc_html__( 'Gallery Template', 'vargal-additional-variation-gallery-for-woo' ),
		));
		$tab_active = array_key_first( $tabs );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'VARGAL Settings', 'vargal-additional-variation-gallery-for-woo' ); ?></h2>
			<?php
			$messages = array();
			if ( ! empty( $messages ) ) {
				?>
				<div class="vi-ui message negative">
					<div class="header"><?php esc_html_e( 'VARGAL - Warning', 'vargal-additional-variation-gallery-for-woo' ) ?>
						:
					</div>
					<ul class="list">
						<?php
						foreach ( $messages as $message ) {
							?>
							<li><?php echo wp_kses( $message,self::$settings::filter_allowed_html() );  ?></li>
							<?php
						}
						?>
					</ul>
				</div>
				<?php
			}
			?>
			<form method="post" class="vi-ui small form">
				<?php wp_nonce_field( "villatheme_{$prefix}_settings", "_villatheme_{$prefix}_settings_nonce" , false); ?>
				<div class="vi-ui attached tabular main menu">
					<?php
					foreach ( $tabs as $slug => $text ) {
						$active = $tab_active === $slug ? ' active' : '';
						printf( ' <a class="item%s" data-tab="%s">%s</a>', esc_attr( $active ), esc_attr( $slug ), esc_html( $text ) );
					}
					?>
				</div>
				<?php
				foreach ( $tabs as $slug => $text ) {
					$active = $tab_active === $slug ? 'active' : '';
					$method = str_replace( '-', '_', $slug ) . '_options';
					$fields = [];
					printf( '<div class="vi-ui bottom attached %s tab segment" data-tab="%s">', esc_attr( $active ), esc_attr( $slug ) );
					if ( method_exists( $this, $method ) ) {
						$fields = $this->$method();
					}
					self::$settings::villatheme_render_table_field( apply_filters( "villatheme_{$prefix}_settings_fields", $fields, $slug ) );
					do_action( "villatheme_{$prefix}_settings_tab", $slug );
					printf( '</div>' );
				}
				?>
				<p class="tmds-save-settings-container">
					<button type="submit" class="vi-ui button labeled icon primary vargal-save-settings"
					        name="tmds_save_settings">
						<i class="save icon"> </i>
						<?php esc_html_e( 'Save Settings', 'vargal-additional-variation-gallery-for-woo' ); ?>
					</button>
				</p>
			</form>
			<?php do_action( 'villatheme_support_vargal-additional-variation-gallery-for-woo' ) ?>
		</div>
		<?php
	}
    public function general_options(){
        $loading_icon_options = array(
	        '0'                => esc_html__( 'Hidden', 'vargal-additional-variation-gallery-for-woo' ),
	        'default'          => esc_html__( 'Default', 'vargal-additional-variation-gallery-for-woo' ),
	        'dual_ring'        => esc_html__( 'Dual Ring', 'vargal-additional-variation-gallery-for-woo' ),
	        'animation_face_1' => esc_html__( 'Animation Facebook 1', 'vargal-additional-variation-gallery-for-woo' ),
	        'animation_face_2' => esc_html__( 'Animation Facebook 2', 'vargal-additional-variation-gallery-for-woo' ),
	        'ring'             => esc_html__( 'Ring', 'vargal-additional-variation-gallery-for-woo' ),
	        'roller'           => esc_html__( 'Roller', 'vargal-additional-variation-gallery-for-woo' ),
	        'loader_balls_1'   => esc_html__( 'Loader Balls 1', 'vargal-additional-variation-gallery-for-woo' ),
	        'loader_balls_2'   => esc_html__( 'Loader Balls 2', 'vargal-additional-variation-gallery-for-woo' ),
	        'loader_balls_3'   => esc_html__( 'Loader Balls 3', 'vargal-additional-variation-gallery-for-woo' ),
	        'ripple'           => esc_html__( 'Ripple', 'vargal-additional-variation-gallery-for-woo' ),
	        'spinner'          => esc_html__( 'Spinner', 'vargal-additional-variation-gallery-for-woo' ),
        );
        $loading_icon_type = self::$settings->get_params('loading_icon_type');
        ?>
        <div class="vi-ui equal width fields vargal-general-settings-wrap">
            <div class="field">
                <?php
                $fields = [
	                'enable'                    => [
		                'type'  => 'checkbox',
		                'value' => self::$settings->get_params( 'enable' ),
		                'title' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
	                ],
                ];
                ob_start();
                ?>
                <div class="vi-ui equal width fields">
                    <div class="vi-ui field">
                        <select name="loading_icon_type" class="vi-ui dropdown vargal-loading_icon_type" id="vargal-loading_icon_type">
				            <?php
				            foreach ($loading_icon_options as $k => $v){
					            ?>
                                <option value="<?php echo esc_attr($k)?>" <?php selected($k, $loading_icon_type) ?>><?php echo esc_html($v) ?></option>
					            <?php
				            }
				            ?>
                        </select>
                        <p class="description"><?php esc_html_e('Style', 'vargal-additional-variation-gallery-for-woo'); ?></p>
                    </div>
                    <div class="vi-ui field">
                        <input type="text"
                               class="vargal-color vargal-loading_icon_color"
                               name="loading_icon_color"
                               value="<?php echo esc_attr( self::$settings->get_params('loading_icon_color') ) ?>">
                        <p class="description"><?php esc_html_e('Color', 'vargal-additional-variation-gallery-for-woo'); ?></p>
                    </div>
                </div>
	            <?php
	            $tmp_html = ob_get_clean();
	            $fields += [
		            'loading_icon_type'   => [
			            'title' => esc_html__( 'Loading icon', 'vargal-additional-variation-gallery-for-woo' ),
			            'html'  => $tmp_html,
		            ],
		            'product_video'                => [
			            'type'   => 'premium_option',
			            'title'  => esc_html__( 'Product video', 'vargal-additional-variation-gallery-for-woo' ),
			            'desc'  => esc_html__( 'Set a video as the featured image or variation image for your product', 'vargal-additional-variation-gallery-for-woo' ),
		            ],
	            ];
	            $fields   = [
		            'section_start' => [],
		            'section_end'   => [],
		            'fields'        => $fields
	            ];
	            self::$settings::villatheme_render_table_field( $fields );
                ?>
            </div>
            <div class="vi-ui field">
                <div class="field vagal-loading-icon-preview"></div>
            </div>
        </div>
        <?php
        return '';
    }
    public function template_options(){
	    ?>
        <div class="vi-ui secondary pointing tabular attached top menu">
            <div class="active item" data-tab="override_general">
			    <?php esc_html_e( 'General', 'vargal-additional-variation-gallery-for-woo' ) ?>
            </div>
            <div class="item" data-tab="override_lightbox">
			    <?php esc_html_e( 'Lightbox', 'vargal-additional-variation-gallery-for-woo' ) ?>
            </div>
            <div class="item" data-tab="override_thumbnails">
			    <?php esc_html_e( 'Thumbnails', 'vargal-additional-variation-gallery-for-woo' ) ?>
            </div>
        </div>
	    <?php
	    $fields                 = [
		    'section_start' => [],
		    'section_end'   => [],
	    ];
        $args = [
	        'override_template'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'override_template' ),
		        'title' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Turn on to use our template for the product gallery', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'thumbnail_main_img'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'thumbnail_main_img' ),
		        'title' => esc_html__( 'Product Image', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Display the main product image in the variation gallery.', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'thumbnail_default_enable'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'thumbnail_default_enable' ),
		        'title' => esc_html__( 'Default Gallery', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Display the default gallery in the variation gallery.', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'default_video'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Set videos as product gallery', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'video_auto_play'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Auto play video', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc'  => esc_html__( 'Automatically play the video when switching the gallery.', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'zoom'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'zoom' ),
		        'title' => esc_html__( 'Image zoom', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
        ];
        ob_start();
        ?>
        <div class="vi-ui equal width fields">
            <div class="field">
                <?php
                self::$settings::villatheme_render_field('auto_play',[
	                'type'=>'checkbox',
	                'value'=>self::$settings->get_params( 'auto_play' ),
                ]);
                ?>
                <p class="description"><?php esc_html_e('Enable', 'vargal-additional-variation-gallery-for-woo'); ?></p>
            </div>
            <div class="field">
                <?php
                self::$settings::villatheme_render_field('auto_play_speed',[
	                'type'=>'number',
	                'input_label'=>[
                         'type'=>'right' ,
                         'label'=> __('Seconds', 'vargal-additional-variation-gallery-for-woo')
                    ],
	                'custom_attributes'=>[
                         'step'=> 0.01 ,
                         'min'=> 0.01 ,
                    ],
	                'value'=>self::$settings->get_params( 'auto_play_speed' ),
                ]);
                ?>
                <p class="description"><?php esc_html_e('Speed', 'vargal-additional-variation-gallery-for-woo'); ?></p>
            </div>
            <div class="field">
		        <?php
		        self::$settings::villatheme_render_field('auto_play_pause',[
			        'type'=>'premium_option',
		        ]);
		        ?>
                <p class="description"><?php esc_html_e('Pause on hover', 'vargal-additional-variation-gallery-for-woo'); ?></p>
            </div>
        </div>
        <?php
        $tmp_html = ob_get_clean();
        $args += [
	        'auto_play'                    => [
		        'title' => esc_html__( 'Auto play', 'vargal-additional-variation-gallery-for-woo' ),
		        'html'  => $tmp_html,
	        ],
	        'transition'                    => [
		        'type'  => 'select',
		        'value' => self::$settings->get_params( 'transition' ),
		        'title' => esc_html__( 'Image Transition', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Image transition for slide gallery', 'vargal-additional-variation-gallery-for-woo' ),
		        'options'  =>self::$settings->get_img_transiton_options() ,
		        'pro_options'  =>self::$settings->get_img_transiton_options_pro() ,
	        ],
	        'navigation_pos'                    => [
		        'type'  => 'select',
		        'value' => self::$settings->get_params( 'navigation_pos' ),
		        'title' => esc_html__( 'Navigation', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Show the image switch icon for the gallery', 'vargal-additional-variation-gallery-for-woo' ),
		        'options'  => self::$settings->get_navigation_pos_options(),
		        'pro_options'  =>self::$settings->get_navigation_pos_options_pro() ,
	        ],
            'navigation_mobile_pos' => [
                    'type'    => 'checkbox',
                    'value'   => self::$settings->get_params( 'navigation_mobile_pos' ),
                    'title'   => esc_html__( 'Show navigation on mobile', 'vargal-additional-variation-gallery-for-woo' ),
                    'desc'    => esc_html__( 'Show the image switch icon for the gallery on mobile', 'vargal-additional-variation-gallery-for-woo' ),
                    'wrap_class'    => 'vagarl-navigation-design-wrap',
            ],
	        'nav_design'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Navigation design', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'show_nav_dot'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Show navigation dot', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
        ];
        $fields['fields'] = $args;
        ?>
        <div class="vi-ui bottom attached active tab" data-tab="override_general">
		    <?php
		    self::$settings::villatheme_render_table_field(  apply_filters( 'villatheme_'. self::$settings::$prefix.'_settings_fields',$fields,'template_override_general' ));
		    ?>
        </div>
        <?php
        $args = [
	        'lightbox'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'lightbox' ),
		        'title' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'lightbox_icon'                    => [
		        'type'  => 'checkbox',
		        'value' => self::$settings->get_params( 'lightbox_icon' ),
		        'title' => esc_html__( 'Icon', 'vargal-additional-variation-gallery-for-woo' ),
		        'desc' => esc_html__( 'Show icon lightbox on the product gallery', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'icon_type'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Icon type', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'icon_pos'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Icon position', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'style'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Lightbox color', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'nav_img_name'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Show image name', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'nav_full_screen'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Allow show image full screen', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'nav_icon'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Navigation icon', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
	        'nav_style'                => [
		        'type'   => 'premium_option',
		        'title'  => esc_html__( 'Navigation Style', 'vargal-additional-variation-gallery-for-woo' ),
	        ],
        ];
        $fields['fields'] = $args;
        ?>
        <div class="vi-ui bottom attached tab" data-tab="override_lightbox">
		    <?php
		    self::$settings::villatheme_render_table_field(  apply_filters( 'villatheme_'. self::$settings::$prefix.'_settings_fields',$fields,'template_override_lightbox' ) );
		    ?>
        </div>
        <?php
	    $args = [
		    'thumbnail'                    => [
			    'type'  => 'checkbox',
			    'value' => self::$settings->get_params( 'thumbnail' ),
			    'title' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
			    'desc' => esc_html__( 'Enable', 'vargal-additional-variation-gallery-for-woo' ),
		    ],
		    'thumbnail_hover_change'                    => [
			    'type'  => 'checkbox',
			    'value' => self::$settings->get_params( 'thumbnail_hover_change' ),
			    'title' => esc_html__( 'Swap images on hover', 'vargal-additional-variation-gallery-for-woo' ),
			    'desc' => esc_html__( 'Show the corresponding gallery image when the mouse hovers over the thumbnail, without clicking.', 'vargal-additional-variation-gallery-for-woo' ),
		    ],
		    'thumbnail_slide'                    => [
			    'type'  => 'checkbox',
			    'value' => self::$settings->get_params( 'thumbnail_slide' ),
			    'title' => esc_html__( 'Display as slide', 'vargal-additional-variation-gallery-for-woo' ),
		    ],
		    'infinite_loop'                => [
			    'type'   => 'premium_option',
			    'title'  => esc_html__( 'Infinite loop', 'vargal-additional-variation-gallery-for-woo' ),
		    ],
	    ];
	    ob_start();
	    ?>
        <div class="vi-ui equal width fields">
            <div class="field">
			    <?php
			    self::$settings::villatheme_render_field('thumbnail_gap',[
				    'type'=>'number',
				    'input_label'=>[
					    'type'=>'right' ,
					    'label'=> __('px', 'vargal-additional-variation-gallery-for-woo')
				    ],
				    'custom_attributes'=>[
					    'step'=> 0.01 ,
				    ],
				    'value'=>self::$settings->get_params( 'thumbnail_gap' ),
			    ]);
			    ?>
                <p class="description"><?php esc_html_e('Between thumbnails', 'vargal-additional-variation-gallery-for-woo'); ?></p>
            </div>
            <div class="field">
			    <?php
			    self::$settings::villatheme_render_field('thumbnail_gap_with_main_img',[
				    'type'=>'number',
				    'input_label'=>[
					    'type'=>'right' ,
					    'label'=> __('px', 'vargal-additional-variation-gallery-for-woo')
				    ],
				    'custom_attributes'=>[
					    'step'=> 0.01 ,
				    ],
				    'value'=>self::$settings->get_params( 'thumbnail_gap_with_main_img' ),
			    ]);
			    ?>
                <p class="description"><?php esc_html_e('Between thumbnails and gallery image', 'vargal-additional-variation-gallery-for-woo'); ?></p>
            </div>
        </div>
	    <?php
	    $tmp_html = ob_get_clean();
	    $args += [
		    'thumbnail_gap'                    => [
			    'title' => esc_html__( 'Gap', 'vargal-additional-variation-gallery-for-woo' ),
			    'html'  => $tmp_html,
		    ],
		    'make_beauty'                => [
			    'type'   => 'premium_option',
			    'title'  => esc_html__( 'Make beauty', 'vargal-additional-variation-gallery-for-woo' ),
			    'desc'  => esc_html__( 'The number of thumbnails will be automatically adjusted so their total size aligns with the slide gallery size', 'vargal-additional-variation-gallery-for-woo' ),
		    ],
	    ];
	    ob_start();
        $thumbnail_pos_args=[
                '0' => __('None', 'vargal-additional-variation-gallery-for-woo'),
                'left' => __('Left', 'vargal-additional-variation-gallery-for-woo'),
                'right' => __('Right', 'vargal-additional-variation-gallery-for-woo'),
                'top' => __('Top', 'vargal-additional-variation-gallery-for-woo'),
                'bottom' => __('Bottom', 'vargal-additional-variation-gallery-for-woo'),
        ];
        $thumbnail_pos = self::$settings->get_params('thumbnail_pos');
        $thumbnail_mobile_pos = self::$settings->get_params('thumbnail_mobile_pos');
	    ?>
        <div class="vi-ui icon tabular attached top menu vargal-thumbnail_pos-menu">
            <div class="active item" data-tab="thumbnail_pos_desktop">
			    <i class="icon desktop"></i>
            </div>
            <div class="item" data-tab="thumbnail_pos_mobile">
                <i class="icon mobile alternate"></i>
            </div>
        </div>
        <div class="vi-ui bottom attached active tab segment vargal-thumbnail_pos-tab" data-tab="thumbnail_pos_desktop">
            <div class="equal width fields">
	            <?php
	            foreach ($thumbnail_pos_args as $k => $v){
		            $selected = $thumbnail_pos ? ($k === $thumbnail_pos) : $k==$thumbnail_pos;
		            $id = $k ? "vargal-{$k}-thumbnail_pos-radio": 'vargal-thumbnail_pos-radio';
		            ?>
                    <div class="field vargal-thumbnail_pos vargal-thumbnail_pos-<?php echo esc_attr($selected ? ($k.' vargal-thumbnail_pos-selected'): $k)?>" data-type="<?php echo esc_attr($k)?>">
                        <label for="<?php echo esc_attr($id)?>" class="vargal-thumbnail_pos-img">
                            <img src="<?php echo esc_url(VARGAL_IMAGES.'thumb-'.$k.'.png') // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" loading="lazy" alt="<?php echo esc_attr($v)?>">
                        </label>
			            <?php
			            $tmp_args = [
				            'type'=>'radio',
				            'prefix'=>$k,
				            'value'=>$k,
				            'label'=>$v,
			            ];
			            if ($selected){
				            $tmp_args['custom_attributes'] = ['checked'=>'checked'];
			            }
			            self::$settings::villatheme_render_field('thumbnail_pos',$tmp_args);
			            ?>
                    </div>
		            <?php
	            }
	            ?>
            </div>
        </div>
        <div class="vi-ui bottom attached tab segment vargal-thumbnail_pos-tab" data-tab="thumbnail_pos_mobile">
            <div class="equal width fields">
		        <?php
		        foreach ($thumbnail_pos_args as $k => $v){
			        $selected = $thumbnail_mobile_pos ? ($k === $thumbnail_mobile_pos) : $k==$thumbnail_mobile_pos;
                    $id = $k ? "vargal-{$k}-thumbnail_mobile_pos-radio": 'vargal-thumbnail_mobile_pos-radio';
			        ?>
                    <div class="field vargal-thumbnail_pos vargal-thumbnail_mobile_pos-<?php echo esc_attr($selected ? ($k.' vargal-thumbnail_pos-selected'): $k)?>" data-type="<?php echo esc_attr($k)?>">
                        <label for="<?php echo esc_attr($id)?>" class="vargal-thumbnail_pos-img">
                            <img src="<?php echo esc_url(VARGAL_IMAGES.'thumb-'.$k.'.png') // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>" loading="lazy" alt="<?php echo esc_attr($v)?>">
                        </label>
				        <?php
				        $tmp_args = [
					        'type'=>'radio',
					        'prefix'=>$k,
					        'value'=>$k,
					        'label'=>$v,
				        ];
				        if ($selected){
					        $tmp_args['custom_attributes'] = ['checked'=>'checked'];
				        }
				        self::$settings::villatheme_render_field('thumbnail_mobile_pos',$tmp_args);
				        ?>
                    </div>
			        <?php
		        }
		        ?>
            </div>
        </div>
	    <?php
	    $tmp_html = ob_get_clean();
	    $args += [
		    'thumbnail_pos'                    => [
			    'title' => esc_html__( 'Position', 'vargal-additional-variation-gallery-for-woo' ),
			    'html'  => $tmp_html,
		    ],
	    ];
        $fields['fields'] = $args;
        ?>
        <div class="vi-ui bottom attached tab" data-tab="override_thumbnails">
		    <?php
		    self::$settings::villatheme_render_table_field(  apply_filters( 'villatheme_'. self::$settings::$prefix.'_settings_fields',$fields,'template_override_thumbnails' ) );
		    ?>
        </div>
        <?php

    }

	public function save_settings(){
		global $vargal_params;
		$prefix = self::$settings::$prefix;
		if ( ! current_user_can( apply_filters( "villatheme_{$prefix}_admin_sub_menu_capability", 'manage_woocommerce', "{$prefix}-settings" ) ) ) {
			return;
		}
		if ( ! isset( $_POST["_villatheme_{$prefix}_settings_nonce"] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST["_villatheme_{$prefix}_settings_nonce"] ) ), "villatheme_{$prefix}_settings" ) ) {
			return;
		}
		$args = self::$settings->get_params();
		foreach ( $args as $key => $arg ) {
			if ( isset( $_POST[ $key ] ) ) {
				if ( is_array( $_POST[ $key ] ) ) {
					$args[ $key ] = array_map( 'sanitize_text_field',wp_unslash( $_POST[ $key ] ) );
				} else {
					$args[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			} else {
                unset($args[$key]);
			}
		}
		$args                    = apply_filters( "villatheme_{$prefix}_save_plugin_settings_params", $args );
		$vargal_params = $args;
		update_option( 'vargal_params', $args,'no' );
		self::$settings = VARGAL_DATA::get_instance( true );
	}
	public function admin_enqueue_scripts() {
		$menu_slug = self::$settings::$prefix;
		$enqueue   = false;
		$page      = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $page, [ $menu_slug, $menu_slug . '-settings', ] ) ) {
			$enqueue = true;
		}
		if ( ! $enqueue ) {
			return;
		}
		self::$settings::enqueue_style(
			array(
				'semantic-ui-button',
				'semantic-ui-checkbox',
				'semantic-ui-dropdown',
				'semantic-ui-segment',
				'semantic-ui-form',
				'semantic-ui-label',
				'semantic-ui-input',
				'semantic-ui-icon',
				'semantic-ui-table',
				'semantic-ui-message',
				'semantic-ui-menu',
				'semantic-ui-tab',
				'transition',
				'vargal-minicolors',
			),
			array(
				'button',
				'checkbox',
				'dropdown',
				'segment',
				'form',
				'label',
				'input',
				'icon',
				'table',
				'message', 'menu', 'tab',
				'transition',
				'minicolors',
			),
			array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
		);
		self::$settings::enqueue_style(
			array( 'vargal-admin-settings','vargal-loading', 'villatheme-show-message' ),
			array( 'admin-settings', 'loading', 'villatheme-show-message' ),
			array( )
		);
		self::$settings::enqueue_script(
			array( 'vargal-address', 'vargal-minicolors', 'semantic-ui-checkbox', 'semantic-ui-dropdown',  'semantic-ui-tab', 'transition' ),
			array( 'address', 'minicolors', 'checkbox', 'dropdown','tab', 'transition' ),
			array( 1, 1, 1,1, 1, 1 )
		);
		self::$settings::enqueue_script(
			array( 'vargal-loading','vargal-admin-settings', 'villatheme-show-message' ),
			array( 'loading','admin-settings','villatheme-show-message' ),
			array(),
			array()
		);
		$params          = array(
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
		);
		$localize_script = $menu_slug . '-admin-settings';
		wp_localize_script( $localize_script, $menu_slug . '_params', $params );
		do_action('vargal-admin-register_scripts');
	}
}