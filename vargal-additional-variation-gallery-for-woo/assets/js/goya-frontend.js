(function ($) {
    'use strict';
    if (typeof vargal_params === "undefined") {
        return;
    }
    if (typeof wc_single_product_params === 'undefined') {
        return;
    }
    vargal_params.goya_default_product_gallery = {};
    vargal_params.goya_default_product_gallery_thumb = {};
    $(document).on('wc-product-gallery-before-init', '.vargal-product-gallery', function (e, gallery, args) {
        let $gallery = $(gallery);
        if (!$gallery.length) {
            return;
        }
        if (!$gallery.closest('.vargal-goya-product-gallery').length) {
            return;
        }
        let product_id, $gallery_class = $gallery.attr('class').split(' ');
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
        if ($gallery.closest('.thumbnails-hover-swap').length){
            $gallery.addClass('vargal-thumbnails-hover-swap');
        }
        $gallery.addClass('vargal-goya-product-gallery');
        $gallery.data('vargal_render_type','goya');
    });
    $(document).on('vargal-goya-gallery-render', function (e, product_id,gallery,variation,gallery_video,gallery_html) {
        let $gallery = $('.vargal-goya-product-gallery.vargal-product-gallery-product-' + product_id);
        console.log('vargal-goya-gallery-render : '+ product_id)
        if (!$gallery.length) {
            return false;
        }
        let gallery_args = JSON.parse(JSON.stringify(wc_single_product_params));
        if (gallery_args?.flexslider_enabled){
            // Xóa dữ liệu Flexslider cũ
            $gallery.flexslider("destroy");
            $gallery.removeData("flexslider");
        }
        $gallery.html($gallery.find('.vargal-loading'));
        $gallery.append('<div class="vargal-product-gallery__wrapper"><div class="woocommerce-product-gallery__wrapper">' + gallery_html + '</div></div>');
        $gallery.find('img').removeClass('wp-post-image');
        $gallery.find('img').eq(0).addClass('wp-post-image');
        if (vargal_params.default_product_gallery[product_id]){
            for (let defaultProductGallery of (vargal_params.default_product_gallery[product_id])) {
                if ($(defaultProductGallery).find('.wp-post-image').length){
                    continue;
                }
                $gallery.find('.woocommerce-product-gallery__wrapper').append(defaultProductGallery);
            }
        }
        if ($gallery.closest('.et-product-gallery-grid').length){

        }else if ($gallery.closest('.et-product-gallery-column').length){

        }else {
            if ($gallery.find('.woocommerce-product-gallery__image').length > 1) {
                $gallery.append('<ol class="flex-control-nav flex-control-thumbs vargal-control-nav"></ol>');
                $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, $gallery.find('.woocommerce-product-gallery__image')]);
                gallery_args.flexslider.controlNav = true;
                gallery_args.flexslider.manualControls = '.flex-control-nav li a';
            }
            if (gallery_video.length) {
                gallery_args.flexslider.video = true;
            }
        }
        $gallery.trigger('vargal-after-flexsilder',[$gallery]);
        $gallery.off().wc_product_gallery(gallery_args);
        return true;
    });
    $(document).on('vargal-goya-gallery-reset', function (e, product_id) {
        let $gallery = $('.vargal-goya-product-gallery.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return false;
        }
        if (vargal_params.default_product_gallery[product_id] && vargal_params.gallery_is_changing[product_id] && vargal_params.gallery_is_changing[product_id] !== 'default') {
            console.log('vargal-goya-gallery-reset : '+product_id)
            vargal_params.gallery_is_changing[product_id] = 'default';
            $gallery.html($gallery.find('.vargal-loading'));
            $gallery.append('<div class="vargal-product-gallery__wrapper"><div class="woocommerce-product-gallery__wrapper"></div></div>');
            $gallery.find('.woocommerce-product-gallery__wrapper').append(vargal_params.default_product_gallery[product_id]);
            let gallery_args = JSON.parse(JSON.stringify(wc_single_product_params));
            $gallery.trigger('vargal-after-flexsilder',[$gallery]);
            if ($gallery.find('.woocommerce-product-gallery__image').length > 1) {
                $gallery.append('<ol class="flex-control-nav flex-control-thumbs vargal-control-nav"></ol>');
                $gallery.trigger('vargal-product-gallery-thumbnail-render', [$gallery, $gallery.find('.woocommerce-product-gallery__image')]);
                gallery_args.flexslider.controlNav = true;
                gallery_args.flexslider.manualControls = '.flex-control-nav li a';
            }
            if (gallery_args?.flexslider_enabled){
                // Xóa dữ liệu Flexslider cũ
                $gallery.flexslider("destroy");
                $gallery.removeData("flexslider");
            }
            $gallery.off().wc_product_gallery(gallery_args);
        }
        return true;
    });
    $(document).on('vargal-product-gallery-thumbnail-rendered', function (e, gallery, gallery_image) {
        let $gallery = $(gallery);
        if (!$gallery.length || !gallery_image.length){
            return;
        }
        if ($gallery.closest('.thumbnails-vertical').length && $gallery.find('.woocommerce-product-gallery__image').length > 6){
            let $slider=$gallery.find('.flex-control-nav');
            let  $args = {
                infinite: false,
                arrows: true,
                speed: 600,
                slidesToShow: 6,
                slidesToScroll: 6,
                vertical: true,
                verticalSwiping: true,
                prevArrow: '<a class="slick-prev">'+ goya_theme_vars.icons.prev_arrow +'</a>',
                nextArrow: '<a class="slick-next">'+ goya_theme_vars.icons.next_arrow +'</a>',
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            vertical: true,
                            slidesToShow: 5,
                            slidesToScroll: 5,
                            swipe: true
                        }
                    },
                    {
                        breakpoint: 992,
                        settings: {
                            vertical: false,
                            slidesToShow: 5,
                            slidesToScroll: 5,
                            swipe: true
                        }
                    },
                    {
                        breakpoint: 576,
                        settings: {
                            vertical: false,
                            slidesToShow: 4,
                            slidesToScroll: 4,
                            swipe: true,
                            verticalSwiping: false
                        }
                    }
                ]
            };

            $slider.not('.slick-initialized').slick($args);

            $slider.on('afterChange', function(event, slick, currentSlide) {
                $slider.find('.slick-track .slick-current li img').trigger('click');
            });
        }
    });
    $(document).on('vargal-after-flexsilder',function (e, gallery){
        let $gallery = $(gallery);
        let flexslider = wc_single_product_params.flexslider.namespace || 'flex-';
        let observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if ($gallery.find('.' + flexslider + 'viewport').length) {
                    $gallery.find('.vargal-product-gallery__wrapper').append($gallery.find('.' + flexslider + 'viewport'));
                    observer.disconnect(); // Ngừng theo dõi sau khi đã di chuyển xong
                }
            });
        });
        observer.observe($gallery.get(0), {
            childList: true,
            subtree: true
        });
        if ($gallery.closest('.zoom-enabled').length){
            $gallery.find('.woocommerce-product-gallery__image img').closest('.woocommerce-product-gallery__image').easyZoom();
        }
        $gallery.find('.woocommerce-product-gallery__image video').closest('.woocommerce-product-gallery__image').css({'pointer-events': 'none'});
    });
}(jQuery));