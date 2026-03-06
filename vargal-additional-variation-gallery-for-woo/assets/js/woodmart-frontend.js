(function ($) {
    'use strict';
    if (typeof vargal_params === "undefined") {
        return;
    }
    if (typeof wc_single_product_params === 'undefined') {
        return;
    }
    if (typeof woodmartThemeModule === 'undefined') {
        return;
    }
    vargal_params.woodmart_default_product_gallery = {};
    vargal_params.woodmart_default_product_gallery_thumb = {};
    if (vargal_params.override_template){
        wc_single_product_params.flexslider.selector = '.vargal-woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image';
    }
    $(document).on('wc-product-gallery-before-init', '.vargal-product-gallery', function (e, gallery, args) {
        let $gallery = $(gallery);
        if (!$gallery.length) {
            return;
        }
        if (!$gallery.find('.vargal-woodmart-product-gallery-image').length && !$gallery.hasClass('vargal-woodmart-product-gallery')) {
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
        if (!vargal_params.woodmart_productImagesGallery) {
            vargal_params.woodmart_productImagesGallery = woodmartThemeModule.productImagesGallery;
        }
        if ($gallery.hasClass('vargal-product-gallery-wrap')){
            woodmartThemeModule.productImagesGallery = function (){};
            $gallery.find('.vargal-woocommerce-product-gallery__wrapper').removeClass('woocommerce-product-gallery__wrapper');
        }else {
            $gallery.data('vargal_render_type','woodmart');
            if (!vargal_params.woodmart_default_product_gallery[product_id]) {
                vargal_params.woodmart_default_product_gallery[product_id] = $gallery.find('.wd-gallery-images').clone();
            }
            if (!vargal_params.woodmart_default_product_gallery_thumb[product_id]) {
                vargal_params.woodmart_default_product_gallery_thumb[product_id] = $gallery.find('.wd-gallery-thumb').clone();
            }
        }
        $gallery.addClass('vargal-woodmart-product-gallery vargal-product-gallery-wrap-not-refresh');
    });
    $(document).on('wc-product-gallery-after-init', '.vargal-product-gallery', function (e, gallery, args) {
        let $gallery = $(gallery);
        if (vargal_params?.woodmart_productImagesGallery && $gallery.hasClass('vargal-product-gallery-wrap')){
            woodmartThemeModule.productImagesGallery = vargal_params.woodmart_productImagesGallery;
        }
    });
    $(document).on('vargal-woodmart-gallery-render', function (e, product_id,gallery,variation,gallery_video,gallery_html) {
        let $gallery = $('.vargal-woodmart-product-gallery.vargal-product-gallery-product-' + product_id);
        console.log('vargal-woodmart-gallery-render : '+ product_id)
        if (!$gallery.length) {
            return false;
        }
        let gallery_wrap = vargal_params.woodmart_default_product_gallery[product_id].clone();
        gallery_wrap.find('.wd-carousel-wrap').html('');
        for (let i in gallery) {
            let galleryElement = gallery[i];
            let $html = $('<div class="wd-carousel-item"></div>');
            $html.attr({
                'data-thumb': galleryElement.gallery_thumbnail_src,
                'data-thumb-alt': galleryElement.alt,
                'data-thumb-srcset': galleryElement.gallery_thumbnail_srcset,
                'data-thumb-sizes': galleryElement.gallery_thumbnail_sizes,
            });
            if (galleryElement?.is_video) {
                $html.append('<div class="woocommerce-product-gallery__image" data-thumb="' + galleryElement.gallery_thumbnail_src + '"></div>');
                $html.find('.woocommerce-product-gallery__image').append('<a href="' + galleryElement.full_src + '" data-elementor-open-lightbox="no"></a>');
                $html.addClass('vargal-video');
                $html.find('a').append('<video controls><source src="' + galleryElement.full_src + '" type="' + galleryElement.metadata.mime_type + '"></video>');
                $html.find('a').append('<img class="vargal-hidden wd-product-video" src="' + galleryElement.gallery_thumbnail_src + '"/>');
                $html.find('img').attr({
                    'data-large_image_height': galleryElement.full_src_h,
                    'data-large_image_width': galleryElement.full_src_w,
                    'data-large_image': galleryElement.full_src,
                    'data-src': galleryElement.full_src,
                    'alt': galleryElement.alt,
                    'data-caption': galleryElement.caption,
                    'title': galleryElement.title,
                    'width': galleryElement.src_w,
                    'height': galleryElement.src_h,
                    'src': galleryElement.src,
                });
                if (galleryElement?.poster){
                    $html.find('video').attr({
                        'poster': galleryElement.poster,
                    });
                }
            } else {
                $html.append('<figure class="woocommerce-product-gallery__image" data-thumb="' + galleryElement.gallery_thumbnail_src + '"></figure>');
                $html.find('figure').append('<a href="' + galleryElement.full_src + '" data-elementor-open-lightbox="no"></a>');
                $html.find('a').append(vargal_params.default_product_gallery[product_id].find('img').eq(0).clone());
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
            gallery_wrap.find('.wd-carousel-wrap').append($html);
        }
        $gallery.find('.wd-gallery-images').replaceWith(gallery_wrap);
        $('.woocommerce-product-gallery__image').trigger('zoom.destroy');
        woodmartThemeModule.$document.trigger('wdReplaceMainGallery');
        woodmartThemeModule.$document.trigger('wdReplaceMainGalleryNotQuickView');
        woodmartThemeModule.$window.trigger('resize');
        return true;
    });
    $(document).on('vargal-woodmart-gallery-reset', function (e, product_id) {
        let $gallery = $('.vargal-woodmart-product-gallery.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return false;
        }
        if (vargal_params.default_product_gallery[product_id] && vargal_params.gallery_is_changing[product_id] && vargal_params.gallery_is_changing[product_id] !== 'default') {
            console.log('vargal-goya-gallery-reset : '+product_id)
            vargal_params.gallery_is_changing[product_id] = 'default';
            woodmartThemeModule.$document.trigger('wdResetVariation');
            let gallery_wrap = vargal_params.woodmart_default_product_gallery[product_id].clone();
            $gallery.find('.wd-gallery-images').replaceWith(gallery_wrap);
            $('.woocommerce-product-gallery__image').trigger('zoom.destroy');
            woodmartThemeModule.$document.trigger('wdReplaceMainGallery');
            woodmartThemeModule.$document.trigger('wdReplaceMainGalleryNotQuickView');
            woodmartThemeModule.$window.trigger('resize');
        }
        return true;
    });
}(jQuery));