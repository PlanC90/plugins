<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class VARGAL_Frontend_Frontend {
	protected static $settings;
	public function __construct() {
		self::$settings = VARGAL_DATA::get_instance();
		if (!self::$settings->get_params('enable')){
			return;
		}
		if (self::$settings->get_params('override_template')){
			add_filter( 'wc_get_template', array( $this, 'replace_template_path' ), PHP_INT_MAX, 2 );
			add_filter( 'wc_get_template_part', array( $this, 'replace_template_path' ), PHP_INT_MAX, 2 );
		}else{
			add_filter( 'woocommerce_single_product_image_gallery_classes', array( $this, 'detect_gallery' ),10,1 );
		}
		add_filter( 'woocommerce_available_variation', array( $this, 'get_gallery_info' ),10,3 );
		add_action( 'woocommerce_product_thumbnails', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}
	public function replace_template_path( $located, $template_name) {
		$args = [
			'single-product/product-image.php' => 'product-image.php',
			'single-product/product-thumbnails.php' => 'product-thumbnails.php',
		];
		if (isset($args[$template_name])){
			$located = VARGAL_TEMPLATES.$args[$template_name];
		}

		return apply_filters('vargal_get_template_part',$located,$template_name);
	}
	public function detect_gallery( $result ){
		if (!is_array($result)){
			return $result;
		}
		global $product;
		$product_t = wc_get_product($product);
		if (!$product_t){
			return $result;
		}
		$result[] = 'vargal-product-gallery';
		$result[] = 'vargal-product-gallery-product-'.$product_t->get_id();
		return $result;
	}
	public function get_gallery_info( $result, $parent, $variation ){
		if (!is_array($result)){
			return $result;
		}
		$variation = wc_get_product($variation);
		if (!$variation){
			return $result;
		}
		$galley = $variation->get_meta('vargal_params');
		$tmp =[];
		if (is_array($galley) && !empty($galley)){
			foreach ($galley as $attachment_id){
				$attachment_props = $this->get_attachment_props($attachment_id);
				if ($attachment_props){
					$tmp[] = $attachment_props;
				}
			}
		}
		$result['vargal'] = $tmp;
		return $result;
	}
	public static function get_attachment_props($attachment_id){
		if (!$attachment_id || (!wp_attachment_is_image($attachment_id) && !wp_attachment_is( 'video', $attachment_id ))){
			return false;
		}
		$props      = array(
			'title'   =>'',
			'caption' => wp_get_attachment_caption($attachment_id),
			'id'     => $attachment_id,
			'url'     => wp_get_attachment_url($attachment_id),
			'alt'     => '',
			'src'     => '',
			'srcset'  => false,
			'sizes'   => false,
		);
		// Alt text.
		$alt_text = array( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ), $props['caption'], $props['title']);
		$alt_text     = array_filter( $alt_text );
		$props['alt'] = $alt_text ? reset( $alt_text ) : '';
		if (wp_attachment_is_image($attachment_id)){
			// Large version.
			$full_size           = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
			$src                 = wp_get_attachment_image_src( $attachment_id, $full_size );
			$props['full_src']   = $src[0];
			$props['full_src_w'] = $src[1];
			$props['full_src_h'] = $src[2];

			// Gallery thumbnail.
			$gallery_thumbnail                = wc_get_image_size( 'gallery_thumbnail' );
			$gallery_thumbnail_size           = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
			$src                              = wp_get_attachment_image_src( $attachment_id, $gallery_thumbnail_size );
			$props['gallery_thumbnail_src']   = $src[0];
			$props['gallery_thumbnail_src_w'] = $src[1];
			$props['gallery_thumbnail_src_h'] = $src[2];
			$props['gallery_thumbnail_srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, $gallery_thumbnail_size ) : false;
			$props['gallery_thumbnail_sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, $gallery_thumbnail_size ) : false;

			// Thumbnail version.
			$thumbnail_size       = apply_filters( 'woocommerce_thumbnail_size', 'woocommerce_thumbnail' );
			$src                  = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
			$props['thumb_src']   = $src[0];
			$props['thumb_src_w'] = $src[1];
			$props['thumb_src_h'] = $src[2];
			$image_size      = apply_filters( 'woocommerce_gallery_image_size', 'woocommerce_single' );
			$src             = wp_get_attachment_image_src( $attachment_id, $image_size );
			$props['src']    = $src[0];
			$props['src_w']  = $src[1];
			$props['src_h']  = $src[2];
			$props['srcset'] = function_exists( 'wp_get_attachment_image_srcset' ) ? wp_get_attachment_image_srcset( $attachment_id, $image_size ) : false;
			$props['sizes']  = function_exists( 'wp_get_attachment_image_sizes' ) ? wp_get_attachment_image_sizes( $attachment_id, $image_size ) : false;
			$props['mime_type']  = get_post_mime_type($attachment_id);
		}else{
			$metadata = wp_get_attachment_metadata($attachment_id);
			if (($metadata['fileformat'] ??'') === 'mp4' && ($metadata['mime_type']??'') !== 'video/mp4'){
				$metadata['mime_type'] = 'video/mp4';
			}
			$props['metadata'] = $metadata;
			$props['is_video']    = 1;
			$props['full_src']  = $props['thumb_src'] = $props['src']    = $props['url'];
			$props['full_src_w'] = $props['thumb_src_w'] = $props['src_w']  = $metadata['width']??'';
			$props['full_src_h'] = $props['thumb_src_h'] = $props['src_h']  = $metadata['height']??'';
			$props['sizes']  = $props['src_w'] ?  sprintf( '(max-width: %1$dpx) 100vw, %1$dpx', $props['src_w'] ): false;
			$props['thumb_srcset'] = false;
			$props['thumb_sizes']  = $props['sizes'] ;

			// Gallery thumbnail.
			$gallery_thumbnail                = wc_get_image_size( 'gallery_thumbnail' );
			$gallery_thumbnail_size           = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
			$thumb_id = get_post_thumbnail_id( $attachment_id );
			if ( ! empty( $thumb_id ) ) {
				$props['poster'] = wp_get_attachment_url( $thumb_id );
				$src                              = wp_get_attachment_image_src( $thumb_id, $gallery_thumbnail_size );
				$props['gallery_thumbnail_src']   = $src[0];
				$props['gallery_thumbnail_src_w'] = $src[1];
				$props['gallery_thumbnail_src_h'] = $src[2];
			}else {
				$props['gallery_thumbnail_src']   = $props['url'];
				$props['gallery_thumbnail_src_w'] = $gallery_thumbnail_size[0];
				$props['gallery_thumbnail_src_h'] = $gallery_thumbnail_size[1];
			}
		}
		return apply_filters('vargal_get_attachment_props',$props,$attachment_id);
	}
	public function register_scripts(){
		self::$settings::enqueue_style(['vargal-frontend','vargal-loading'],['frontend','loading'],[],['photoswipe-default-skin'],'register');
		self::$settings::enqueue_script(['vargal-frontend','vargal-loading'],['frontend','loading'],[],['zoom','flexslider','photoswipe-ui-default','wc-single-product'],'register');
		$loading_icon_color = self::$settings->get_params('loading_icon_color') ?: '#fff';
		$css ='.vargal-loading-icon-default div, .vargal-loading-icon-animation_face_1 div, .vargal-loading-icon-animation_face_2 div, .vargal-loading-icon-roller div:after, .vargal-loading-icon-loader_balls_1 div, .vargal-loading-icon-loader_balls_2 div, .vargal-loading-icon-loader_balls_3 div, .vargal-loading-icon-spinner div:after{background:'.$loading_icon_color.'}';
		$css .='.vargal-loading-icon-ripple div{border: 4px solid '.esc_html($loading_icon_color).'}';
		$css .='.vargal-loading-icon-ring div{border-color: '.esc_html($loading_icon_color).' transparent transparent transparent}';
		$css .='.vargal-loading-icon-dual_ring:after{border-color:'.esc_html($loading_icon_color).' transparent '.esc_html($loading_icon_color).' transparent}';
		$args=[
			'loading_icon_type'=>self::$settings->get_params('loading_icon_type'),
		];
		if (self::$settings->get_params('override_template')){
			$args +=[
				'override_template'=>1,
				'lightbox'=>self::$settings->get_params('lightbox'),
				'zoom'=>self::$settings->get_params('zoom'),
				'transition'=>self::$settings->get_params('transition'),
				'thumbnail_main_img'=>self::$settings->get_params('thumbnail_main_img'),
				'thumbnail_default_enable'=>self::$settings->get_params('thumbnail_default_enable'),
				'navigation_pos'=>self::$settings->get_params('navigation_pos')?:'',
				'navigation_mobile_pos'           => self::$settings->get_params( 'navigation_mobile_pos' ) ?: '',
			];
			if (self::$settings->get_params('thumbnail')){
				$args +=[
					'thumbnail_hover_change'=>self::$settings->get_params('thumbnail_hover_change')?: '',
					'thumbnail_pos'=>self::$settings->get_params('thumbnail_pos')?: '',
					'thumbnail_mobile_pos'=>self::$settings->get_params('thumbnail_mobile_pos')?:'',
					'thumbnail_gap_with_main_img'=>self::$settings->get_params('thumbnail_gap_with_main_img'),
					'thumbnail_gap'=>self::$settings->get_params('thumbnail_gap'),
					'thumbnail_slide'=>self::$settings->get_params('thumbnail_slide'),
					'thumbnail_width'            => apply_filters('vargal_get_thumbnail_width', 70),
				];
				$css .='.vargal-product-gallery.vargal-product-gallery-wrap{gap:'.esc_html($args['thumbnail_gap_with_main_img']).'px}';
				$css .='.vargal-product-gallery.vargal-product-gallery-wrap .vargal-control-nav{gap:'.esc_html($args['thumbnail_gap']).'px}';
			}
			if ($args['lightbox']){
				$args +=[
					'lightbox_icon'=>self::$settings->get_params('lightbox_icon'),
					'photoswipe_wrap'=>wc_get_template_html('single-product/photoswipe.php'),
				];
			}
			if (self::$settings->get_params('auto_play')){
				$args +=[
					'auto_play_speed'=>self::$settings->get_params('auto_play_speed')?:'',
				];
			}
		}
		wp_add_inline_style('vargal-frontend',$css);
		wp_localize_script('vargal-frontend','vargal_params',apply_filters('vargal-frontend-params',$args));
		do_action('vargal-register_scripts');
		if (apply_filters('vargal-frontend-enqueue', false)){
			$this->enqueue_scripts();
		}
	}
	public static function enqueue_scripts(){
		wp_enqueue_style('vargal-frontend');
		wp_enqueue_script('vargal-frontend');
		if (self::$settings->get_params('override_template')) {
			wp_enqueue_style( 'photoswipe-default-skin' );
			wp_enqueue_script( 'zoom' );
			wp_enqueue_script( 'flexslider' );
			wp_enqueue_script( 'photoswipe-ui-default' );
		}
		wp_enqueue_style('vargal-loading');
		wp_enqueue_script('vargal-loading');
		wp_enqueue_script( 'wc-single-product' );
		do_action('vargal-enqueue_scripts');
	}
}