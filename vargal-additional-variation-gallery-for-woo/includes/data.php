<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VARGAL_DATA {
	public static $prefix = 'vargal';
	private $params, $default, $current_params;
	protected static $instance = null;
	public static $allow_html;

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	public function __construct() {
		$this->define($this->default, $this->current_params);
		$this->params         = wp_parse_args( $this->current_params, $this->default ) ;
	}
	public function define(&$default, &$current_params){
		global $vargal_params;
		$default        = array(
			'enable'                      => 1,
			'loading_icon_type'                      => 'default',
			'loading_icon_color'                      => '#706f6f',
			'override_template'                      => 1,
			'lightbox'                      => 1,
			'lightbox_icon'                      => '',
			'zoom'                      => '',
			'auto_play'                      => '',
			'auto_play_speed'                      => 5,
			'transition'                      => 'slide',
			'navigation_pos'                      => 'center',
			'navigation_mobile_pos'            => 0,
			'thumbnail'                      => 1,
			'thumbnail_pos'                      => 'bottom',
			'thumbnail_mobile_pos'                      => 'bottom',
			'thumbnail_main_img'                      => '',
			'thumbnail_default_enable'                      => '',
			'thumbnail_hover_change'                      => 1,
			'thumbnail_gap'                      => 13,
			'thumbnail_gap_with_main_img'                      => 15,
			'thumbnail_slide'                      => 1,
		);
		if ( ! $vargal_params ) {
			$vargal_params = get_option( 'vargal_params', array() );
		}
		$current_params = $vargal_params;
	}
	public function get_params( $name = "") {
		if ( ! $name ) {
			return apply_filters( 'villatheme_'.self::$prefix.'_params', $this->params);
		}
		$name_t = $name ;
		return apply_filters( 'villatheme_'.self::$prefix.'_params_' . $name_t, $this->params[ $name_t ] ?? $this->params[ $name ] ?? false );
	}
	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 'villatheme_'.self::$prefix.'_params_default-' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}
	public function get_img_transiton_options(){
		return [
			'slide'=>__('Slide', 'vargal-additional-variation-gallery-for-woo'),
			'fade'=>__('Fade', 'vargal-additional-variation-gallery-for-woo'),
			'1'=>__('Zoom', 'vargal-additional-variation-gallery-for-woo'),
		];
	}
	public function get_img_transiton_options_pro(){
		return [
			'2'     => esc_html__( 'Zoomout', 'vargal-additional-variation-gallery-for-woo' ),
			'3'     => esc_html__( 'Bubble', 'vargal-additional-variation-gallery-for-woo' ),
			'4'     => esc_html__( 'Blur', 'vargal-additional-variation-gallery-for-woo' ),
			'5'     => esc_html__( 'Circle', 'vargal-additional-variation-gallery-for-woo' ),
			'6'     => esc_html__( 'Mask', 'vargal-additional-variation-gallery-for-woo' ),
			'7'     => esc_html__( 'Rotateup', 'vargal-additional-variation-gallery-for-woo' ),
			'8'     => esc_html__( 'Rotatedown', 'vargal-additional-variation-gallery-for-woo' ),
			'9'     => esc_html__( 'Split', 'vargal-additional-variation-gallery-for-woo' ),
			'10'    => esc_html__( 'Split 2', 'vargal-additional-variation-gallery-for-woo' ),
			'11'    => esc_html__( 'Split 3', 'vargal-additional-variation-gallery-for-woo' ),
		];
	}
	public function get_navigation_pos_options(){
		return [
			'0'=>__('None', 'vargal-additional-variation-gallery-for-woo'),
			'center'=>__('Center', 'vargal-additional-variation-gallery-for-woo'),
			'top'=>__('Top', 'vargal-additional-variation-gallery-for-woo'),
			'bottom'=>__('Bottom', 'vargal-additional-variation-gallery-for-woo'),
		];
	}
	public function get_navigation_pos_options_pro(){
		return [
			'center_left'  => esc_html__( 'Center left', 'vargal-additional-variation-gallery-for-woo' ),
			'center_right' => esc_html__( 'Center right', 'vargal-additional-variation-gallery-for-woo' ),
			'top_left'     => esc_html__( 'Top left', 'vargal-additional-variation-gallery-for-woo' ),
			'top_right'    => esc_html__( 'Top right', 'vargal-additional-variation-gallery-for-woo' ),
			'bottom_left'  => esc_html__( 'Bottom left', 'vargal-additional-variation-gallery-for-woo' ),
			'bottom_right' => esc_html__( 'Bottom right', 'vargal-additional-variation-gallery-for-woo' ),
		];
	}
	/**
	 * @param $tags
	 *
	 * @return array
	 */
	public static function filter_allowed_html( $tags = [] ) {
		if ( self::$allow_html && empty( $tags ) ) {
			return self::$allow_html;
		}
		$tags = array_merge_recursive( $tags, wp_kses_allowed_html( 'post' ), array(
			'input'  => array(
				'type'         => 1,
				'name'         => 1,
				'placeholder'  => 1,
				'autocomplete' => 1,
				'step'         => 1,
				'min'          => 1,
				'max'          => 1,
				'value'        => 1,
				'size'         => 1,
				'checked'      => 1,
				'disabled'     => 1,
				'readonly'     => 1,
			),
			'form'   => array(
				'method' => 1,
				'action' => 1,
			),
			'select' => array(
				'name'     => 1,
				'multiple' => 1,
			),
			'option' => array(
				'value'    => 1,
				'selected' => 1,
				'disabled' => 1,
			),
			'style'  => array(
				'id'   => 1,
				'type' => 1,
			),
			'source' => array(
				'type' => 1,
				'src'  => 1
			),
			'video'  => array(
				'width'  => 1,
				'height' => 1,
				'src'    => 1
			),
			'iframe' => array(
				'width'           => 1,
				'height'          => 1,
				'allowfullscreen' => 1,
				'allow'           => 1,
				'src'             => 1
			),
		) );
		$tmp  = $tags;
		foreach ( $tmp as $key => $value ) {
			$tags[ $key ] = wp_parse_args( [
				'width'         => 1,
				'height'        => 1,
				'class'         => 1,
				'id'            => 1,
				'type'          => 1,
				'style'         => 1,
				'data-*'        => 1,
				'fetchpriority' => 1,
				'loading'       => 1,
			], $value );
		}
		self::$allow_html = apply_filters( 'vargal_filter_allowed_html', $tags );

		return self::$allow_html;
	}
	public static function implode_html_attributes( $raw_attributes ) {
		$attributes = array();
		foreach ( $raw_attributes as $name => $value ) {
			$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
		return implode( ' ', $attributes );
	}
	public static function villatheme_render_field( $name, $field ) {
		if ( ! $name ) {
			return;
		}
		if ( ! empty( $field['html'] ) ) {
			echo wp_kses($field['html'], self::filter_allowed_html());
			return;
		}
		$type  = $field['type'] ?? '';
		$value = $field['value'] ?? '';
		if ( ! empty( $field['prefix'] ) ) {
			$id = "vargal-{$field['prefix']}-{$name}";
		} else {
			$id = "vargal-{$name}";
		}

		$class             = $field['class'] ?? $id;
		$custom_attributes = array_merge( [
			'type'  => $type,
			'name'  => $name,
			'id'    => $id,
			'value' => $value,
			'class' => $class,
		], (array) ( $field['custom_attributes'] ?? [] ) );
		if ( ! empty( $field['input_label'] ) ) {
			$input_label_type = $field['input_label']['type'] ?? 'left';
			echo wp_kses( sprintf( '<div class="vi-ui %s labeled input">', ( ! empty( $field['input_label']['fluid'] ) ? 'fluid ' : '' ) . $input_label_type ), self::filter_allowed_html() );
			if ( $input_label_type === 'left' ) {
				echo wp_kses( sprintf( '<div class="%s">%s</div>', $field['input_label']['label_class'] ?? 'vi-ui label', $field['input_label']['label'] ?? '' ), self::filter_allowed_html() );
			}
		}
		if (!empty($field['empty_name_field'])){
			unset($custom_attributes['name']);
		}
		switch ( $type ) {
			case 'premium_option':
				printf('<a class="vi-ui button vargal-basic-label" href="https://1.envato.market/RGXJKa"
                                       target="_blank">%s</a>', esc_html__( 'Unlock This Feature', 'vargal-additional-variation-gallery-for-woo' ));
				break;
			case 'radio':
				unset( $custom_attributes['type'] );
				echo wp_kses( sprintf( '
					<div class="vi-ui radio checkbox%s">
						<input type="radio" id="%s-radio" %s ><label>%s</label>
					</div>', !empty($field['disabled']) ? ' disabled' : '',$id,self::implode_html_attributes( $custom_attributes ), $field['label']??''
				), self::filter_allowed_html() );
				break;
			case 'checkbox':
				unset( $custom_attributes['type'] );
				echo wp_kses( sprintf( '
					<div class="vi-ui toggle checkbox%s">
						<input type="hidden" %s>
						<input type="checkbox" id="%s-checkbox" %s ><label></label>
					</div>', !empty($field['disabled']) ? ' disabled' : '',self::implode_html_attributes( $custom_attributes ),  $id,  $value ? 'checked' : ''
				), self::filter_allowed_html() );
				break;
			case 'select':
				$select_options = $field['options'] ?? '';
				$multiple       = $field['multiple'] ?? '';
				unset( $custom_attributes['type'] );
				unset( $custom_attributes['value'] );
				$custom_attributes['class'] = "vi-ui fluid dropdown {$class}";
				if ( $multiple ) {
					$value                         = (array) $value;
					$custom_attributes['name']     = $name . '[]';
					$custom_attributes['multiple'] = "multiple";
				}
				if (!empty($field['is_search'])){
					$custom_attributes['class'] .=' search';
				}
				echo wp_kses( sprintf( '<select %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
				if ( is_array( $select_options ) && !empty( $select_options ) ) {
					foreach ( $select_options as $k => $v ) {
						$selected = $multiple ? in_array( $k, $value ) : ( $k == $value );
						echo wp_kses( sprintf( '<option value="%s" %s>%s</option>',
							$k, $selected ? 'selected' : '', $v ), self::filter_allowed_html() );
					}
				}
				$pro_options = $field['pro_options'] ??'';
				if ( is_array( $pro_options ) && !empty( $select_options ) ) {
					foreach ( $pro_options as $k => $v ) {
						echo wp_kses( sprintf( '<option disabled>%s</option>',
							$v.__(' - Premium version only', 'vargal-additional-variation-gallery-for-woo') ), self::filter_allowed_html() );
					}
				}
				printf( '</select>' );
				break;
			case 'select2':
				$select_options = [];
				if ( ! empty( $value ) && is_array( $value ) ) {
					switch ($custom_attributes['data-type_select2'] ?? ''){
						case 'category':
						case 'tag':
							foreach ( $value as $item ) {
								$category = get_term( $item );
								if ( $category ) {
									$select_options[$item] = $category->name;
								}
							}
							break;
					}
				}
				$multiple = $field['multiple'] ?? '';
				unset($custom_attributes['type']);
				unset($custom_attributes['value']);
				if ($multiple){
					if (isset($custom_attributes['name'])) {
						$custom_attributes['name'] = $name . '[]';
					}
					$custom_attributes['multiple']= "multiple";
				}
				$custom_attributes['class'] .= ' vargal-search-select2';
				echo wp_kses(sprintf('<select %s>', wc_implode_html_attributes( $custom_attributes)),self::filter_allowed_html());
				if (is_array($select_options) && count($select_options)){
					foreach ($select_options as $k => $v){
						printf( '<option value="%s" selected>%s</option>', esc_attr( $k ), wp_kses_post( $v ) );
					}
				}
				printf('</select>');
				break;
			case 'textarea':
				unset( $custom_attributes['type'] );
				unset( $custom_attributes['value'] );
				echo wp_kses( sprintf( '<textarea %s>%s</textarea>', self::implode_html_attributes( $custom_attributes ), $value ), self::filter_allowed_html() );
				break;
			default:
				if ( $type ) {
					echo wp_kses( sprintf( '<input %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
				}
		}
		if ( ! empty( $field['input_label'] ) ) {
			if ( ! empty( $input_label_type ) && $input_label_type === 'right' ) {
				printf( '<div class="%s">%s</div>', esc_attr( $field['input_label']['label_class'] ?? 'vi-ui label vargal-basic-label' ), wp_kses_post( $field['input_label']['label'] ?? '' ) );
			}
			printf( '</div>' );
		}
	}
	public static function villatheme_render_table_field( $options ) {
		if ( ! is_array( $options ) || empty( $options ) ) {
			return;
		}
		if ( ! empty( $options['html'] ) ) {
			echo wp_kses( $options['html'], self::filter_allowed_html() );
			return;
		}
		if ( isset( $options['section_start'] ) ) {
			if ( ! empty( $options['section_start']['accordion'] ) ) {
				echo wp_kses( sprintf( '<div class="vi-ui styled fluid accordion%s">
                                            <div class="title%s">
                                                <i class="dropdown icon"> </i>
                                                %s
                                            </div>
                                        <div class="content%s">',
					! empty( $options['section_start']['class'] ) ? " {$options['section_start']['class']}" : '',
					! empty( $options['section_start']['active'] ) ? " active" : '',
					$options['section_start']['title'] ?? '',
					! empty( $options['section_start']['active'] ) ? " active" : ''
				),
					self::filter_allowed_html() );
			}
			if ( empty( $options['fields_html'] ) ) {
				echo wp_kses_post( '<table class="form-table">' );
			}
		}
		if ( ! empty( $options['fields_html'] ) ) {
			echo wp_kses( $options['fields_html'], self::filter_allowed_html() );
		} else {
			$fields = $options['fields'] ?? '';
			if ( is_array( $fields ) && count( $fields ) ) {
				foreach ( $fields as $key => $param ) {
					$type = $param['type'] ?? '';
					$name = $param['name'] ?? $key;
					if ( ! $name ) {
						continue;
					}
					if ( ! empty( $param['prefix'] ) ) {
						$id = "vargal-{$param['prefix']}-{$name}";
					} else {
						$id = "vargal-{$name}";
					}
					if ( empty( $param['not_wrap_html'] ) ) {
						if ( ! empty( $param['wrap_class'] ) ) {
							printf( '<tr class="%s"><th><label for="%s">%s</label></th><td>',
								esc_attr( $param['wrap_class'] ), esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
						} else {
							printf( '<tr><th><label for="%s">%s</label></th><td>', esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
						}
					}
					do_action( 'vargal_before_option_field', $name, $param );
					self::villatheme_render_field( $name, $param );
					if ( ! empty( $param['custom_desc'] ) ) {
						echo wp_kses_post( $param['custom_desc'] );
					}
					if ( ! empty( $param['desc'] ) ) {
						printf( '<p class="description">%s</p>', wp_kses_post( $param['desc'] ) );
					}
					if (!empty($param['after_desc'])){
						echo wp_kses( $param['after_desc'],self::filter_allowed_html());
					}
					do_action( 'vargal_after_option_field', $name, $param );
					if ( empty( $param['not_wrap_html'] ) ) {
						echo wp_kses_post( '</td></tr>' );
					}
				}
			}
		}
		if ( isset( $options['section_end'] ) ) {
			if ( empty( $options['fields_html'] ) ) {
				echo wp_kses_post( '</table>' );
			}
			if ( ! empty( $options['section_end']['accordion'] ) ) {
				echo wp_kses_post( '</div></div>' );
			}
		}
	}
	public static function villatheme_set_time_limit() {
		ActionScheduler_Compatibility::raise_memory_limit();
		wc_set_time_limit();
	}
	public static function enqueue_style( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue',$css_dir=VARGAL_CSS,$version=VARGAL_VERSION ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, $css_dir . $srcs[ $i ] . $suffix_t . '.css', ! empty( $des[ $i ] ) ? $des[ $i ] : array(), $version );
		}
	}

	public static function enqueue_script( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue', $in_footer = false,$js_dir=VARGAL_JS,$version=VARGAL_VERSION ) {
		if ( empty( $handles ) || empty( $srcs ) ) {
			return;
		}
		$action = $type === 'register' ? 'wp_register_script' : 'wp_enqueue_script';
		$suffix = WP_DEBUG ? '' : '.min';
		foreach ( $handles as $i => $handle ) {
			if ( ! $handle || empty( $srcs[ $i ] ) ) {
				continue;
			}
			$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
			$action( $handle, $js_dir . $srcs[ $i ] . $suffix_t . '.js', ! empty( $des[ $i ] ) ? $des[ $i ] : array( 'jquery' ), $version, $in_footer );
		}
	}
}

