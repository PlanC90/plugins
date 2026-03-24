(function ($) {
    'use strict';
    if (typeof vargal_params === "undefined") {
        return;
    }
    window.vargal_mobile_check = function () {
        let check = false;
        (function (a) {
            if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) check = true;
        })(navigator.userAgent || navigator.vendor || window.opera);
        return check;
    };
    let is_mobile,thumnails_pos,navigation_pos;
    window.vargal_define_params = function () {
        if (vargal_params.override_template){
            is_mobile = vargal_mobile_check();
            thumnails_pos = is_mobile ? vargal_params?.thumbnail_mobile_pos : vargal_params?.thumbnail_pos;
            vargal_params.current_thumbnail_pos = thumnails_pos;
            navigation_pos = vargal_params?.navigation_pos;
            if (is_mobile && !vargal_params?.navigation_mobile_pos){
                navigation_pos = '';
            }
            vargal_params.current_navigation_pos = navigation_pos;
            if (vargal_params?.zoom){
                wc_single_product_params.zoom_enabled = true;
            }else {
                wc_single_product_params.zoom_enabled = false;
            }
            wc_single_product_params.flexslider_enabled = 1;
            wc_single_product_params.photoswipe_enabled = false ;
            wc_single_product_params.flexslider.namespace = 'vargal-product-gallery-';
            wc_single_product_params.flexslider.controlsContainer = '.vargal-product-gallery__wrapper';
            wc_single_product_params.flexslider.animation = vargal_params.transition;
            wc_single_product_params.flexslider.prevText = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-left"><polyline points="15 18 9 12 15 6"></polyline></svg>';
            wc_single_product_params.flexslider.nextText = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>';
            wc_single_product_params.flexslider.animationLoop = false;
            if (vargal_params?.auto_play_speed){
                wc_single_product_params.flexslider.slideshow = true;
                wc_single_product_params.flexslider.slideshowSpeed = 1000 * parseFloat(vargal_params.auto_play_speed);
            }else {
                wc_single_product_params.flexslider.slideshow = false;
            }
            if (vargal_params?.current_navigation_pos){
                wc_single_product_params.flexslider.directionNav = true;
            }else {
                wc_single_product_params.flexslider.directionNav = false;
            }
            if (!vargal_params.current_thumbnail_pos) {
                wc_single_product_params.flexslider.controlNav = false;
            } else {
                wc_single_product_params.flexslider.controlNav = true;
                wc_single_product_params.flexslider.manualControls = '.vargal-control-nav li a';
            }
            // if (wc_single_product_params.flexslider.directionNav && vargal_params.current_thumbnail_pos ) {
            //     // wc_single_product_params.flexslider.touch = false;
            // }
        }
    };
    vargal_params.current_thumbnail_pos ='';
    if (!vargal_params?.thumbnail_width || !parseFloat(vargal_params.thumbnail_width)) {
        vargal_params.thumbnail_width = 70;
    }
    vargal_params.default_product_main = {};
    vargal_params.default_product_gallery = {};
    vargal_params.gallery_is_changing = {};
    setTimeout(function () {
        if (typeof is_mobile === "undefined" && typeof wc_single_product_params !== 'undefined') {
            $(document).trigger('vargal_defined_params');
        }
    },20);
    vargal_params.get_default_product_main = function (gallery) {
        let $gallery = $(gallery), img;
        img = $gallery.find('.wp-post-image').closest('.woocommerce-product-gallery__image').clone();
        if (img.data('src')) {
            img.attr('src', img.data('src'));
        }
        return img;
    }
    vargal_params.get_default_product_gallery = function (gallery) {
        let $gallery = $(gallery), imgs;
        imgs = $gallery.find('.woocommerce-product-gallery__image').clone();
        $.each(imgs, function (k, v) {
            let $img = $(v).find('img');
            if ($img.length && $img.data('src')) {
                $(imgs[k]).find('img').attr('src', $img.data('src'));
            }
        });
        return imgs;
    }
    function handlePswpTrapFocus( e ) {
        let allFocusablesEls      = e.currentTarget.querySelectorAll( 'button:not([disabled])' );
        let filteredFocusablesEls = Array.from( allFocusablesEls ).filter( function( btn ) {
            return btn.style.display !== 'none' && window.getComputedStyle( btn ).display !== 'none';
        } );

        if ( 1 >= filteredFocusablesEls.length ) {
            return;
        }

        let firstTabStop = filteredFocusablesEls[0];
        let lastTabStop  = filteredFocusablesEls[filteredFocusablesEls.length - 1];

        if ( e.key === 'Tab' ) {
            if ( e.shiftKey ) {
                if ( document.activeElement === firstTabStop ) {
                    e.preventDefault();
                    lastTabStop.focus();
                }
            } else if ( document.activeElement === lastTabStop ) {
                e.preventDefault();
                firstTabStop.focus();
            }
        }
    }
    if (vargal_params?.lightbox){
        $(document).on('click','.vargal-product-gallery .woocommerce-product-gallery__image', function (e){
            e.preventDefault();
            e.stopPropagation();
            if (wc_single_product_params.zoom_enabled){
                return false;
            }
            $(document).trigger('vargal-gallery-open-photoswipe',[$(this).closest('.vargal-product-gallery'), $(this)]);
        });
        $(document).on('click','.vargal-product-gallery .woocommerce-product-gallery__trigger', function (){
            $(document).trigger('vargal-gallery-open-photoswipe',[$(this).closest('.vargal-product-gallery'), $(this)]);
        });
    }
    $(document).on('vargal_defined_params',function (){
        vargal_define_params();
    });
    $(document).on('wc-product-gallery-before-init', '.vargal-product-gallery', function (e, gallery, args) {
        if (typeof is_mobile === "undefined"){
            $(document).trigger('vargal_defined_params');
        }
        let product_id, $gallery = $(gallery), $gallery_class = $gallery.attr('class').split(' ');
        if ($gallery_class.length) {
            for (let $galleryClass_v of $gallery_class) {
                if ($galleryClass_v.includes('vargal-product-gallery-product-')) {
                    product_id = $galleryClass_v.replace('vargal-product-gallery-product-', '');
                    break;
                }
            }
        }
        if (!product_id) {
            return;
        }
        $gallery.data('vargal_product_id',product_id);
        if (typeof vargal_params.default_product_gallery[product_id] === "undefined") {
            // vargal_params.default_product_gallery[product_id] =$gallery.find('.woocommerce-product-gallery__image').clone();
            vargal_params.default_product_gallery[product_id] = vargal_params.get_default_product_gallery($gallery);
            // vargal_params.default_product_gallery[product_id] = $('.woocommerce-product-gallery__image', $gallery).clone();
        }
        if (typeof vargal_params.default_product_main[product_id] === "undefined") {
            vargal_params.default_product_main[product_id] = vargal_params.get_default_product_main($gallery);
            // vargal_params.default_product_main[product_id] = $gallery.find('.wp-post-image').closest('.woocommerce-product-gallery__image').clone();
        }
        if ($gallery.hasClass('vargal-product-gallery-wrap')) {
            let tmp_width = $gallery.outerWidth();
            if (tmp_width && ['top', 'bottom'].includes(vargal_params.current_thumbnail_pos)){
                $gallery.css({'max-width': tmp_width + 'px'});
                $gallery.css({'min-width': tmp_width+'px'});
                $gallery.find('.vargal-product-gallery__wrapper').css({'max-width': tmp_width + 'px'});
                $gallery.find('.vargal-product-gallery__wrapper').css({'min-width': tmp_width+'px'});
            }
            if (is_mobile){
                $gallery.addClass('vargal-product-gallery-wrap-mobile');
            }
            if (wc_single_product_params.zoom_enabled){
                $gallery.addClass('vargal-product-gallery-wrap-zoom');
            }
            if (vargal_params?.lightbox){
                $gallery.addClass('vargal-product-gallery-wrap-lightbox');
            }
            if (vargal_params?.transition){
                $gallery.addClass('vargal-product-gallery-wrap-transition-' + vargal_params.transition);
            }
            if (vargal_params?.current_navigation_pos){
                $gallery.addClass('vargal-product-gallery-wrap-nav-wrap vargal-product-gallery-wrap-nav-' + vargal_params.current_navigation_pos);
            }
            if (vargal_params.current_thumbnail_pos) {
                $gallery.addClass('vargal-product-gallery-wrap-' + vargal_params.current_thumbnail_pos);
                if ($gallery.find('.woocommerce-product-gallery__image').length > 1) {
                    $gallery.append('<div class="vargal-control-nav-wrap"><ol class="vargal-control-nav"></ol></div>');
                    $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, vargal_params.default_product_gallery[product_id]]);
                }
            }
            $gallery.trigger('vargal-after-flexsilder',[$gallery]);
        }
    });
    $(document).on('mouseenter','.vargal-control-nav li', function (){
        if (vargal_params?.thumbnail_hover_change || $(this).closest('.vargal-thumbnails-hover-swap').length){
            $(this).find('a').trigger('click');
        }
    });
    $(document).on('vargal-gallery-open-photoswipe', function (e, gallery, clicked) {
        let $gallery = $(gallery), $clicked = $(clicked);
        if (!$('.pswp').length){
            $('body').append(vargal_params.photoswipe_wrap);
            $('.pswp').addClass('vargal-pswp');
        }
        let items = $gallery.find('.woocommerce-product-gallery__image').map(function (k,v) {
            let tmp;
            if ($(v).hasClass('vargal-video')){
                tmp= {
                    alt: $(v).data('alt'),
                    title: $(v).data('caption') || $(v).data('title'),
                    html: '<div class="pswp__video pswp__vargal_video-wrap"><div class="pswp__vargal_video">'+$(v).find('a').html()+'</div></div>'
                }
            }else {
                tmp = {
                    src: $(v).find('img').data('large_image'),
                    w: $(v).find('img').data('large_image_width'),
                    h: $(v).find('img').data('large_image_height'),
                    alt: $(v).find('img').attr('alt'),
                    title: $(v).find('img').data('caption') || $(v).find('img').attr('title'),
                }
            }

            let $custom_html = $(document).triggerHandler('vargal_frontend_get_photoswipe_html',[tmp, $(v),$gallery]);
            return $custom_html || tmp;
        });
        let pswpElement = $( '.pswp' )[0];
        if ( 0 < $clicked.closest( '.woocommerce-product-gallery__trigger' ).length ) {
            $clicked = $gallery.find( '.vargal-product-gallery-active-slide' );
        } else if ( 0 < $clicked.closest( '.vargal-lightbox__trigger' ).length ) {
            $clicked = $gallery.find( '.vargal-product-gallery-active-slide' );
        } else {
            $clicked = $clicked.closest( '.woocommerce-product-gallery__image' );
        }

        let options = $.extend( {
            index: $clicked.index(),
            addCaptionHTMLFn: function( item, captionEl ) {
                if ( ! item.title ) {
                    captionEl.children[0].textContent = '';
                    return false;
                }
                captionEl.children[0].textContent = item.title;
                return true;
            },
            timeToIdle: 0, // Ensure the gallery controls are always visible to avoid keyboard navigation issues.
        }, wc_single_product_params.photoswipe_options );

        // Initializes and opens PhotoSwipe.
        var photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );

        photoswipe.listen( 'afterInit', function() {
            $(document).trigger('vargal-trap-focus-photoswipe',[true]);
        });

        photoswipe.listen( 'close', function() {
            $(document).trigger('vargal-trap-focus-photoswipe',[false]);
            $clicked.trigger('focus');
        });

        photoswipe.init();
    });
    $(document).on('vargal-trap-focus-photoswipe', function (e, trapFocus) {
        let pswp = document.querySelector( '.pswp' );

        if ( ! pswp ) {
            return;
        }

        if ( trapFocus ) {
            pswp.addEventListener( 'keydown', handlePswpTrapFocus );
        } else {
            pswp.removeEventListener( 'keydown', handlePswpTrapFocus );
        }
    });
    $(document).on('clear_reset_announcement', '.variations_form', function (e) {
        let product_id = $(this).closest('.variations_form').data('product_id') || $(this).closest('.variations_form').find('[name="product_id"]').val();
        if (!product_id || !vargal_params.default_product_gallery[product_id]) {
            return;
        }
        $(document).trigger('vargal-loading-start', [product_id]);
    });
    $(document).on('hide_variation', '.variations_form', function (e) {
        let product_id = $(this).data('product_id') || $(this).find('[name="product_id"]').val();
        if (!product_id || !vargal_params.default_product_gallery[product_id]) {
            return;
        }
        $(document).trigger('vargal-gallery-reset', [product_id]);
    });
    $(document).on('show_variation', '.variations_form', function (e, variation, purchasable) {
        let product_id = $(this).data('product_id') || $(this).find('[name="product_id"]').val(), gallery = [];
        if (!product_id) {
            return;
        }
        if (variation?.image && variation?.image_id) {
            gallery = [variation.image];
        }
        if (variation?.vargal) {
            gallery = gallery.concat(variation.vargal);
        }
        $(document).trigger('vargal-gallery-changing', [product_id, gallery, variation]);
    });
    $(document).on('vargal-gallery-changing', function (e, product_id, gallery,variation) {
        if (!product_id) {
            return;
        }
        let $gallery = $('.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return;
        }
        let old_variation = vargal_params.gallery_is_changing[product_id];
        if ((!old_variation || old_variation ==='default') && !$gallery.hasClass('vargal-product-gallery-wrap') && gallery.length === 1 ){
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if ((!old_variation || old_variation ==='default') &&
            ((!vargal_params?.thumbnail_main_img && vargal_params?.thumbnail_default_enable) || !vargal_params.current_thumbnail_pos ) && gallery.length === 1 ){
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (old_variation?.variation_id && old_variation.variation_id == variation.variation_id) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        vargal_params.gallery_is_changing[product_id] = variation;
        $(document).trigger('vargal-loading-start', [product_id]);
        if (!gallery || !gallery.length) {
            $(document).trigger('vargal-gallery-reset', [product_id]);
            return;
        }
        if (!vargal_params.default_product_gallery[product_id]) {
            vargal_params.default_product_gallery[product_id] =$gallery.find('.woocommerce-product-gallery__image').clone();
        }
        if (!vargal_params.default_product_gallery[product_id]) {
            $(document).trigger('vargal-gallery-reset', [product_id]);
            return;
        }
        if (typeof vargal_params.default_product_main[product_id] === "undefined") {
            // vargal_params.default_product_main[product_id] = $gallery.find('.wp-post-image').closest('.woocommerce-product-gallery__image').clone();
            vargal_params.default_product_main[product_id] = vargal_params.get_default_product_main($gallery);
        }
        let $tmp = '',  gallery_video = [];
        for (let i in gallery) {
            let galleryElement = gallery[i];
            let $html = $('<div data-thumb="" data-thumb-alt="" data-thumb-srcset="" data-thumb-sizes="" class="woocommerce-product-gallery__image"></div>'),
                $html_t = $('<div class="vargal-html"></div>');
            $html.append('<a href="' + galleryElement.full_src + '"></a>');
            $html.attr({
                'data-thumb': galleryElement.gallery_thumbnail_src,
                'data-thumb-alt': galleryElement.alt,
                'data-thumb-srcset': galleryElement.gallery_thumbnail_srcset,
                'data-thumb-sizes': galleryElement.gallery_thumbnail_sizes,
            });
            if (galleryElement?.is_video) {
                $html.addClass('vargal-video');
                $html.attr({
                    'data-alt': galleryElement.alt,
                    'data-caption': galleryElement.caption,
                    'data-title': galleryElement.title,
                });
                gallery_video.push(i);
                $html.find('a').append('<video controls><source src="' + galleryElement.full_src + '" type="' + galleryElement.metadata.mime_type + '"></video>');
                if (galleryElement?.poster){
                    $html.find('video').attr({
                        'poster': galleryElement.poster,
                    });
                }
            } else {
                let $img = '<img  src="' + galleryElement.src + '">';
                if (vargal_params.default_product_gallery[product_id].find('img:not(.woocommerce-placeholder)').length) {
                    $img = vargal_params.default_product_gallery[product_id].find('img:not(.woocommerce-placeholder)').eq(0).clone();
                }
                $html.find('a').append($img);
                $html.find('img').attr({
                    'data-large_image_height': galleryElement.full_src_h,
                    'data-large_image_width': galleryElement.full_src_w,
                    'data-large_image': galleryElement.full_src,
                    'data-src': galleryElement.full_src,
                    'alt': galleryElement.alt,
                    'data-caption': galleryElement.caption,
                    'title': galleryElement.title,
                    'sizes': galleryElement.sizes,
                    'srcset': galleryElement.srcset,
                    'width': galleryElement.src_w,
                    'height': galleryElement.src_h,
                    'src': galleryElement.src,
                });
                if (['image/gif'].includes(galleryElement.mime_type)) {
                    $html.find('img').removeAttr('srcset');
                    $html.find('img').attr({'src': galleryElement.full_src});
                }
                if (!galleryElement.srcset) {
                    $html.find('img').removeAttr('srcset');
                }
            }
            let $custom_html = $(document).triggerHandler('vargal_frontend_get_attachment_html',[$html, galleryElement,$gallery]);
            $html_t.html($custom_html || $html);
            $tmp += $html_t.html();
        }
        let vargal_render_type = $gallery.data('vargal_render_type');
        let rendered_gallery = vargal_render_type && $(document).triggerHandler('vargal-'+vargal_render_type+'-gallery-render', [product_id,gallery,variation,gallery_video,$tmp]);
        if (rendered_gallery){
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        //thay thế gallery
        let gallery_args = JSON.parse(JSON.stringify(wc_single_product_params));
        if (gallery_args?.flexslider_enabled){
            // Xóa dữ liệu Flexslider cũ
            $gallery.flexslider("destroy");
            $gallery.removeData("flexslider");
        }
        $gallery.removeData("product_gallery");
        let $tmp_gallery = $gallery.clone();
        let tmp_width = $gallery.outerWidth(), $tmp_img = $tmp_gallery.find('.vargal-product-gallery-active-slide');
        $tmp_img.addClass('vargal-image-remove').removeClass('woocommerce-product-gallery__image');
        if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
            tmp_width = $gallery.outerWidth() - (vargal_params.thumbnail_width + parseFloat(vargal_params.thumbnail_gap_with_main_img));
        }
        if ($gallery.hasClass('vargal-product-gallery-wrap-not-refresh') ) {
            $gallery.find('.vargal-product-gallery__wrapper').prepend($tmp_img);
            $gallery.find('.vargal-woocommerce-product-gallery__wrapper').addClass('woocommerce-product-gallery__wrapper').html($tmp);
            $gallery.find('.vargal-product-gallery__wrapper').append($gallery.find('.vargal-woocommerce-product-gallery__wrapper'));
            $gallery.find('.vargal-product-gallery-viewport').remove();
            $gallery.find('.vargal-control-nav-wrap').remove();
            $gallery.find('.vargal-product-gallery__wrapper').css({'min-height': $gallery.outerWidth()+'px'});
            if ($gallery.closest('.product-images').length){
                $gallery.css({width: '100%'})
            }
        }else {
            let $tmp_wrap = $('<div class="vargal-product-gallery__wrapper"></div>');
            $tmp_wrap.append($tmp_img);
            $gallery.html($tmp_wrap);
            $gallery.append($tmp_gallery.find('.vargal-loading'));
            $gallery.find('.vargal-product-gallery__wrapper').append('<div class="vargal-woocommerce-product-gallery__wrapper woocommerce-product-gallery__wrapper">' + $tmp + '</div>');
        }
        if (vargal_params?.transition !== 'fade'){
            $gallery.find('.vargal-product-gallery__wrapper').css({'overflow':'hidden','max-height': $gallery.find('.vargal-image-remove').height() + 'px'});
        }
        if (tmp_width && ['top','bottom'].includes(vargal_params.current_thumbnail_pos)){
            $gallery.css({'max-width': tmp_width + 'px'});
            $gallery.css({'min-width': tmp_width+'px'});
            $gallery.find('.vargal-product-gallery__wrapper').css({'max-width': tmp_width + 'px'});
            $gallery.find('.vargal-product-gallery__wrapper').css({'min-width': tmp_width+'px'});
        }
        $gallery.removeClass('vargal-product-gallery-one');
        $gallery.find('img').removeClass('wp-post-image');
        $gallery.find('.vargal-woocommerce-product-gallery__wrapper img:not(.vargal-hidden)').eq(0).addClass('wp-post-image');
        //khởi tạo lại gallery
        if ($gallery.hasClass('vargal-product-gallery-wrap') ) {
            if (vargal_params?.thumbnail_main_img && !$gallery.find('.woocommerce-product-gallery__image[data-thumb="'+vargal_params.default_product_main[product_id].data('thumb')+'"]').length){
                $gallery.find('.vargal-woocommerce-product-gallery__wrapper').append(vargal_params.default_product_main[product_id]);
            }
            if (vargal_params?.thumbnail_default_enable){
                for (let defaultProductGallery of (vargal_params.default_product_gallery[product_id])) {
                    if ($(defaultProductGallery).find('.wp-post-image').length){
                        continue;
                    }
                    $gallery.find('.vargal-woocommerce-product-gallery__wrapper').append(defaultProductGallery);
                }
            }
            if (vargal_params.current_thumbnail_pos && $gallery.find('.woocommerce-product-gallery__image').length > 1) {
                $gallery.find('.vargal-product-gallery__wrapper').after('<div class="vargal-control-nav-wrap"><ol class="vargal-control-nav"></ol></div>');
                $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, $gallery.find('.woocommerce-product-gallery__image')]);
            }
            $gallery.find('.woocommerce-product-gallery__image').css({'min-width': tmp_width+'px'});
            if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
                $gallery.find('.woocommerce-product-gallery__image').css({'max-width': tmp_width + 'px'});
            }
            if ($gallery.find('video').length){
                gallery_args.flexslider.video = true;
            }
            gallery_args.flexslider.before = function (slider) {
                if (slider?.animatingTo && $gallery.find('.vargal-control-nav li').eq(slider.animatingTo).find('video').length) {
                    $gallery.find('.woocommerce-product-gallery__trigger').addClass('vargal-hidden');
                } else {
                    $gallery.find('.woocommerce-product-gallery__trigger').removeClass('vargal-hidden');
                }
                $(document).trigger('vargal-flexslider-before', [slider]);
            }
            gallery_args.flexslider.start = function (slider) {
                $gallery.find('.vargal-product-gallery__wrapper').css({'max-height':'100%'});
                $gallery.find('.vargal-image-remove').remove();
                $(document).trigger('vargal-loading-end', [product_id]);
            }
            $gallery.trigger('vargal-after-flexsilder',[$gallery]);
        }else if (gallery_args?.flexslider_enabled){
            if (gallery_video.length) {
                $gallery.append('<ol class="flex-control-nav flex-control-thumbs vargal-control-nav"></ol>');
                $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, $gallery.find('.woocommerce-product-gallery__image')]);
                gallery_args.flexslider.video = true;
                gallery_args.flexslider.controlNav = true;
                gallery_args.flexslider.manualControls = '.flex-control-nav li a';
                let flexslider = gallery_args.flexslider.namespace || 'flex-';
                let observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if ($gallery.find('.' + flexslider + 'viewport').length) {
                            $gallery.find('.zoomImg').remove();
                            $gallery.find('.vargal-product-gallery__wrapper').append($gallery.find('.' + flexslider + 'viewport'));
                            observer.disconnect(); // Ngừng theo dõi sau khi đã di chuyển xong
                        }
                    });
                });
                observer.observe($gallery.get(0), {
                    childList: true,
                    subtree: true
                });
            }
            gallery_args.flexslider.start = function (slider) {
                $gallery.find('.vargal-product-gallery__wrapper').css({'max-height':'100%'});
                $gallery.find('.vargal-image-remove').remove();
                $(document).trigger('vargal-loading-end', [product_id]);
            }
            gallery_args.flexslider.before = function (slider) {
                if (slider?.animatingTo && gallery[slider.animatingTo]?.is_video) {
                    $gallery.find('.woocommerce-product-gallery__trigger').addClass('vargal-hidden');
                } else {
                    $gallery.find('.woocommerce-product-gallery__trigger').removeClass('vargal-hidden');
                }
                $(document).trigger('vargal-flexslider-before', [slider]);
            }
        }
        $gallery.off().wc_product_gallery(gallery_args);
        if ($gallery.find('.woocommerce-product-gallery__image').length === 1) {
            $gallery.addClass('vargal-product-gallery-one');
            $gallery.find('.vargal-image-remove').remove();
            $(document).trigger('vargal-loading-end', [product_id]);
        }
    });
    $(document).on('vargal-gallery-reset', function (e, product_id) {
        let $gallery = $('.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return;
        }
        $(document).trigger('vargal-loading-start', [product_id]);
        let vargal_render_type = $gallery.data('vargal_render_type');
        let reset_gallery = vargal_render_type && $(document).triggerHandler('vargal-'+vargal_render_type+'-gallery-reset', [product_id]);
        if (reset_gallery){
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (vargal_params.default_product_gallery[product_id] && vargal_params.gallery_is_changing[product_id] && vargal_params.gallery_is_changing[product_id] !== 'default') {
            vargal_params.gallery_is_changing[product_id] = 'default';
            // Xóa dữ liệu Flexslider cũ
            $gallery.flexslider("destroy");
            $gallery.removeData("flexslider");
            // let gallery_args = JSON.parse(JSON.stringify(wc_single_product_params));
            if ($gallery.hasClass('vargal-product-gallery-wrap-not-refresh') ) {
                let $tmp_gallery = $gallery.clone();
                let $tmp_img = $tmp_gallery.find('.vargal-product-gallery-active-slide');
                $tmp_img.addClass('vargal-image-remove').removeClass('woocommerce-product-gallery__image');
                $gallery.find('.vargal-product-gallery__wrapper').prepend($tmp_img);
                $gallery.find('.vargal-woocommerce-product-gallery__wrapper').html(vargal_params.default_product_gallery[product_id]);
                $gallery.find('.vargal-product-gallery__wrapper').append($gallery.find('.vargal-woocommerce-product-gallery__wrapper'));
                $gallery.find('.vargal-product-gallery-viewport').remove();
                $gallery.find('.vargal-control-nav-wrap').remove();
                $gallery.find('.vargal-product-gallery__wrapper').css({'min-height': $gallery.outerWidth()+'px'});
                $gallery.find('.vargal-image-remove').hide(500).remove();
            }else {
                $gallery.html($gallery.find('.vargal-loading'));
                $gallery.append('<div class="vargal-product-gallery__wrapper"><div class="vargal-woocommerce-product-gallery__wrapper woocommerce-product-gallery__wrapper"></div></div>');
                $gallery.find('.vargal-woocommerce-product-gallery__wrapper').append(vargal_params.default_product_gallery[product_id]);
            }
            if (vargal_params.current_thumbnail_pos && $gallery.find('.woocommerce-product-gallery__image').length > 1) {
                $gallery.append('<div class="vargal-control-nav-wrap"><ol class="vargal-control-nav"></ol></div>');
                $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, $gallery.find('.woocommerce-product-gallery__image')]);
            }
            $gallery.trigger('vargal-after-flexsilder',[$gallery]);
            $gallery.off().wc_product_gallery();
        }
        $(document).trigger('vargal-loading-end', [product_id]);
    });
    $(document).on('vargal-loading-end', function (e, product_id) {
        let $gallery = $('.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return;
        }
        $gallery.removeClass('vargal-product-gallery-loading');
    });
    $(document).on('vargal-loading-start', function (e, product_id) {
        let $gallery = $('.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return;
        }
        $gallery.addClass('vargal-product-gallery-loading');
        if (!$gallery.find('.vargal-loading').length) {
            $(document).trigger('vargal-loading-render', [$gallery,vargal_params.loading_icon_type]);
        }
    });
    $(document).on('vargal-after-flexsilder',function (e, gallery){
        let $gallery = $(gallery);
        let observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if ($gallery.find('.woocommerce-product-gallery__image').length > 1 &&
                    !$gallery.find('.vargal-product-gallery__wrapper .vargal-product-gallery-viewport').length) {
                    if ( $gallery.find('.vargal-product-gallery-viewport').length){
                        $gallery.find('.zoomImg').remove();
                        $gallery.find('.vargal-product-gallery__wrapper').append($gallery.find('.vargal-product-gallery-viewport'));
                    }
                }else {
                    observer.disconnect(); // Ngừng theo dõi sau khi đã di chuyển xong
                }
            });
        });
        if (vargal_params.transition !== 'fade'){
            observer.observe($gallery.get(0), {
                childList: true,
                subtree: true
            });
        }
        if (vargal_params?.lightbox_icon ){
            if (!$gallery.find('.vargal-product-gallery__wrapper').find('.woocommerce-product-gallery__trigger').length){
                $gallery.find('.woocommerce-product-gallery__trigger').remove();
                $gallery.find('.vargal-product-gallery__wrapper').append(`<a href="#" class="woocommerce-product-gallery__trigger" aria-haspopup="dialog" aria-label="${wc_single_product_params.i18n_product_gallery_trigger_text}"></a>`);
                $gallery.find('.woocommerce-product-gallery__trigger').append('<span aria-hidden="true">🔍</span>');
            }
        }
        if ($gallery.find('.woocommerce-product-gallery__image').length === 1){
            $gallery.addClass('vargal-product-gallery-one');
        }
    });
    $(document).on('vargal-product-gallery-thumbnail-render',function (e, gallery, gallery_image){
        console.log('render')
        let $gallery = $(gallery);
        if (!$gallery.length || !gallery_image.length){
            return;
        }
        console.log('a1')
        let $tmp_thumbnails = '';
        for (let i=0; i < gallery_image.length; i++) {
            let $galleryElement = gallery_image.eq(i),
                $thumbnail = $('<li><a href="#"></a></li>'),
                $html_t = $('<div class="vargal-html"></div>');
            if ($galleryElement.find('video').length){
                if ($galleryElement.find('video').attr('poster')){
                    let $thumbnail_img = $('<img/>', {
                        onload: 'this.width = this.naturalWidth; this.height = this.naturalHeight',
                        src: $galleryElement.data('thumb'),
                        sizes: $galleryElement.data('thumb-sizes'),
                        alt: $galleryElement.data('thumb-alt'),
                    });
                    $thumbnail.find('a').append($thumbnail_img);
                }else {
                    $thumbnail.find('a').append('<video preload="metadata" src="' + $galleryElement.data('thumb') + '"></video>');
                }
                $thumbnail.find('a').addClass('vargal-thumb-video');
            }else {
                let $thumbnail_img = $('<img/>', {
                    onload: 'this.width = this.naturalWidth; this.height = this.naturalHeight',
                    src: $galleryElement.data('thumb'),
                    sizes: $galleryElement.data('thumb-sizes'),
                    alt: $galleryElement.data('thumb-alt'),
                });
                if ($galleryElement.data('thumb-srcset')){
                    $thumbnail_img.attr({ srcset: $galleryElement.data('thumb-srcset')});
                }
                $thumbnail.find('a').append($thumbnail_img);
            }
            let $custom_html = $(document).triggerHandler('vargal_frontend_get_thumb_html',[$thumbnail, $galleryElement,$gallery]);
            $html_t.html($custom_html||$thumbnail);
            $tmp_thumbnails += $html_t.html();
        }
        $gallery.find('.vargal-control-nav').append($tmp_thumbnails);
        let item_width = parseFloat(vargal_params.thumbnail_width);
        let tmp_width = $gallery.outerWidth() - (item_width + parseFloat(vargal_params.thumbnail_gap_with_main_img)) ;
        if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
            $gallery.find('.vargal-control-nav-wrap').css({'height': tmp_width + 'px','overflow':'hidden'});
            $gallery.find('.vargal-product-gallery__wrapper').css({'width': 'calc( 100% - ' + ( item_width + parseFloat(vargal_params.thumbnail_gap_with_main_img) ) + 'px )'});
            $gallery.find('.woocommerce-product-gallery__image').css({'max-width': tmp_width + 'px'});
        }
        if (['top'].includes(vargal_params.current_thumbnail_pos)){
            $gallery.find('.vargal-control-nav-wrap').css({'max-height': item_width+'px'});
        }
        $gallery.find('.vargal-control-nav li, .vargal-control-nav li a').css({'min-width': item_width+'px','max-width': item_width+'px','max-height': item_width+'px','min-height': item_width + 'px'});
        if (vargal_params?.thumbnail_slide){
            let item_margin= parseFloat(vargal_params.thumbnail_gap), wrap_width=tmp_width;
            let max_item = Math.floor((wrap_width - item_width) / (item_width + item_margin)) + 1;
            let min_item = max_item - 1;
            if ($gallery.find('.vargal-control-nav-wrap li').length > max_item){
                let vargal_thumb_init =  new Date().getTime();
                let varal_thumb_class = 'vargal-control-nav-scroll-'+ vargal_thumb_init;
                $gallery.data('vargal_thumb', vargal_thumb_init);
                $gallery.find('.vargal-control-nav-wrap').addClass('vargal-control-nav-slider');
                let options = $(document).triggerHandler('vargal_control_nav_slide_options', [item_width, item_margin, max_item, min_item,varal_thumb_class]);
                if (!options){
                    options = {
                        namespace: 'vargal-control-nav-',
                        selector: '.vargal-control-nav > li',
                        animation: 'slide',
                        animationLoop: true,
                        startAt: 0,
                        itemWidth: item_width,
                        itemMargin: item_margin,
                        controlNav: false,
                        prevText: wc_single_product_params.flexslider.prevText,
                        nextText: wc_single_product_params.flexslider.nextText,
                        maxItems: max_item ,
                        minItems: min_item,
                        move:max_item,
                        slideshow: false,
                        reverse: false,
                        touch: true,
                        before: function (slider) {
                            let thumb_current = slider.container.find('.vargal-product-gallery-active').closest('li').index();
                            let thumb_click = slider.direction === 'prev' ? (thumb_current - max_item): (thumb_current + max_item);
                            if (thumb_click < 0 || thumb_click >= slider.count){
                                thumb_click =slider.direction === 'prev' ? slider.count -1 :  0;
                            }
                            slider.container.find('li').eq(thumb_click).find('a').trigger('click');
                            if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
                                let check_click =  Math.floor(max_item/2);
                                if ((slider.count - thumb_click) < check_click){
                                    thumb_click -= check_click;
                                }
                                $('#'+varal_thumb_class+'-css').html(`.${varal_thumb_class}{transform: translate3d(0px,${(-1*thumb_click*(item_width + item_margin))}px, 0px) !important;`);
                            }
                        },
                        start: function (slider) {
                            if (slider.visible != max_item && slider?.directionNav) {
                                slider.itemW = item_width;
                                slider.visible = max_item;
                                slider.move = (slider.vars.move > 0 && slider.vars.move < slider.visible) ? slider.vars.move : slider.visible;
                                slider.pagingCount = Math.ceil(((slider.count - slider.visible) / slider.move) + 1);
                                slider.last = slider.pagingCount - 1;
                                slider.limit = ((slider.itemW + item_margin) * slider.count) - slider.w - item_margin;
                                slider.container.data('flexslider', slider);
                                slider.container.closest('.vargal-control-nav-wrap').addClass('vargal-control-nav-override');
                                if (slider?.directionNav) {
                                    let namespace = slider.vars.namespace;
                                    let disabledClass = namespace + 'disabled';
                                    if (slider.animatingTo !== slider.last && slider.directionNav.filter('.' + disabledClass + '.' + namespace + "next").length) {
                                        slider.directionNav.filter('.' + namespace + "next").removeClass(disabledClass).attr('tabindex', '-1');
                                    }
                                }
                            }
                        },
                        init: function (slider) {
                            if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
                                if (!$('#'+varal_thumb_class+'-css').length){
                                    $('head').append(`<style id="${varal_thumb_class}-css"></style>`)
                                }
                                slider.container.addClass(varal_thumb_class).css({'width': (item_width+'px'),'display': 'flex','flex-direction': 'column'});
                            }
                        }
                    };
                }
                if (['left','right'].includes(vargal_params.current_thumbnail_pos)){
                    options['direction'] = 'vertical';
                    $gallery.find('.vargal-control-nav li').css({'margin-right': '0px', 'height': item_width+'px','max-height': item_width+'px', 'text-align':'center'});
                    $gallery.find('.vargal-control-nav li img, .vargal-control-nav li video, .vargal-control-nav li iframe').css({'max-height': item_width+'px'});
                }else {
                    $gallery.find('.vargal-control-nav').css({'gap': '0px'});
                }
                $gallery.find('.vargal-control-nav-wrap').flexslider(options);
            }
        }
        $(document).trigger('vargal-product-gallery-thumbnail-rendered',[gallery, gallery_image]);
    });
}(jQuery));