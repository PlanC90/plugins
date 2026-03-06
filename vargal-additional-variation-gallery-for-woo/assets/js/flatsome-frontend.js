(function ($) {
    'use strict';
    if (typeof vargal_params === "undefined") {
        return;
    }
    if (typeof wc_single_product_params === 'undefined') {
        return;
    }
    vargal_params.flatsome_default_product_gallery = {};
    vargal_params.flatsome_default_product_gallery_thumb = {};
    $(document).on('wc-product-gallery-before-init', '.vargal-product-gallery', function (e, gallery, args) {
        let $gallery = $(gallery);
        if (!$gallery.length) {
            return;
        }
        if (!$gallery.closest('.product-gallery').find('.vargal-flatsome-product-gallery-thumb').length) {
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
        if (!vargal_params.flatsome_default_product_gallery[product_id]) {
            vargal_params.flatsome_default_product_gallery[product_id] = $gallery.find('.woocommerce-product-gallery__wrapper').clone();
        }
        if (!vargal_params.flatsome_default_product_gallery_thumb[product_id]) {
            if ($gallery.closest('.product-gallery').find('.vertical-thumbnails').length){
                vargal_params.flatsome_default_product_gallery_thumb[product_id] = $gallery.closest('.product-gallery').find('.vertical-thumbnails').clone();
            }else {
                vargal_params.flatsome_default_product_gallery_thumb[product_id] = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb').clone();
            }
        }
        if ($gallery.find('.woocommerce-product-gallery__image').length === 1) {
            $gallery.closest('.product-gallery').find('.vertical-thumbnails').remove();
            $gallery.parent().find('.vargal-flatsome-product-gallery-thumb').remove();
        }
        $gallery.find('.woocommerce-product-gallery__wrapper').removeClass('has-image-zoom');
        $gallery.addClass('vargal-flatsome-product-gallery');
        $gallery.data('vargal_render_type','flatsome');
    });
    $(document).on('vargal-flatsome-gallery-render', function (e, product_id,gallery,variation,gallery_video,gallery_html) {
        let $gallery = $('.vargal-flatsome-product-gallery.vargal-product-gallery-product-' + product_id);
        console.log('vargal-flatsome-gallery-render : '+ product_id)
        if (!$gallery.length) {
            return false;
        }
        let gallery_wrap = vargal_params.flatsome_default_product_gallery[product_id].clone(),
            thumb_wrap = vargal_params.flatsome_default_product_gallery_thumb[product_id].clone(),$thumb_wrap;
        gallery_wrap.html(gallery_html);
        $gallery.find('.woocommerce-product-gallery__wrapper').removeData("flexslider");
        if ($gallery.find('.woocommerce-product-gallery__wrapper').flickity) {
            $gallery.find('.woocommerce-product-gallery__wrapper').flickity('destroy');
        }
        $gallery.find('.woocommerce-product-gallery__wrapper').replaceWith(gallery_wrap);
        if (thumb_wrap.hasClass('vertical-thumbnails')){
            $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails');
            if (!$thumb_wrap.length){
                $gallery.parent().after(thumb_wrap);
                $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails');
            }
            $thumb_wrap.find('.vargal-flatsome-product-gallery-thumb').off();
        }else {
            $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
            if (!$thumb_wrap.length){
                $gallery.parent().append('<div class="vargal-flatsome-product-gallery-thumb"></div>');
                $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
            }
            $thumb_wrap.off();
        }
        let att_count = $gallery.find('.woocommerce-product-gallery__image').length;
        if (att_count > 1) {
            let $tmp_thumbnails = '', tmp_img = thumb_wrap.find('img').eq(0);
            for (let i=0; i < att_count; i++) {
                let $galleryElement = $gallery.find('.woocommerce-product-gallery__image').eq(i),
                    $thumbnail = $('<div class="col"><a href="#"></a></div>'),
                    $html_t = $('<div class="vargal-html"></div>'),$thumb;
                if ($galleryElement.find('video').length){
                    $thumb= $('<video></video>');
                }else {
                    $thumb= $('<img/>');
                }
                $thumb.attr({
                    src: $galleryElement.data('thumb'),
                    alt: $galleryElement.data('thumb-alt'),
                    width: gallery[i].gallery_thumbnail_src_w || tmp_img.attr('width'),
                    height: gallery[i].gallery_thumbnail_src_h || tmp_img.attr('height'),
                    class: 'attachment-woocommerce_thumbnail',
                });
                $thumbnail.find('a').append($thumb);
                $html_t.html($thumbnail);
                $tmp_thumbnails += $html_t.html();
            }
            if (thumb_wrap.hasClass('vertical-thumbnails')){
                $gallery.parent().addClass('large-10');
                thumb_wrap.find('.vargal-flatsome-product-gallery-thumb').html($tmp_thumbnails);
            }else {
                thumb_wrap.html($tmp_thumbnails);
            }
            $thumb_wrap.replaceWith(thumb_wrap);
        }else {
            $thumb_wrap.remove();
            if (thumb_wrap.hasClass('vertical-thumbnails')){
                $gallery.parent().removeClass('large-10');
            }
        }
        if (thumb_wrap.hasClass('vertical-thumbnails')){
            $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails .vargal-flatsome-product-gallery-thumb');
        }else {
            $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
        }
        $gallery.find('.woocommerce-product-gallery__wrapper').flickity($gallery.find('.woocommerce-product-gallery__wrapper').data('flickityOptions'));
        $gallery.find('.woocommerce-product-gallery__wrapper').on('change.flickity', function (event, index) {
            let currentSlide = $gallery.find('.woocommerce-product-gallery__image').eq(index);
            if (currentSlide.find('video').length){
                currentSlide.on('click', function (e_click) {
                   e_click.preventDefault();
                   e_click.stopPropagation();
                   return false;
                });
                $gallery.find('.flickity-viewport').css({'height': gallery[index].src_h + 'px'})
                $gallery.find('.zoom-button ').parent().addClass('vargal-hidden');
            }else {
                $gallery.find('.zoom-button ').parent().removeClass('vargal-hidden');
            }
        });
        if ($thumb_wrap.length){
            // if ($thumb_wrap.flickity) {
            //     $thumb_wrap.flickity('destroy');
            // }
            $thumb_wrap.off();
            $thumb_wrap.removeData("flickity");
            if ($thumb_wrap.find('.col').length < 5){
                $thumb_wrap.addClass('slider-no-arrows');
            }else {
                $thumb_wrap.removeClass('slider-no-arrows');
            }
            $thumb_wrap.flickity($thumb_wrap.data('flickityOptions'));
        }
        $gallery.off().wc_product_gallery();
        return true;
    });
    $(document).on('vargal-flatsome-gallery-reset', function (e, product_id) {
        let $gallery = $('.vargal-flatsome-product-gallery.vargal-product-gallery-product-' + product_id);
        if (!$gallery.length) {
            return false;
        }
        if (vargal_params.flatsome_default_product_gallery[product_id] && vargal_params.gallery_is_changing[product_id] && vargal_params.gallery_is_changing[product_id] !== 'default') {
            console.log('vargal-flatsome-gallery-reset')
            vargal_params.gallery_is_changing[product_id] = 'default';
            let gallery_wrap = vargal_params.flatsome_default_product_gallery[product_id].clone(),
                thumb_wrap = vargal_params.flatsome_default_product_gallery_thumb[product_id].clone(),$thumb_wrap;
            $gallery.find('.woocommerce-product-gallery__wrapper').replaceWith(gallery_wrap);
            if (thumb_wrap.hasClass('vertical-thumbnails')){
                $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails');
                if (!$thumb_wrap.length){
                    $gallery.parent().after(thumb_wrap);
                    $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails');
                }
            }else {
                $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
                if (!$thumb_wrap.length){
                    $gallery.parent().append('<div class="vargal-flatsome-product-gallery-thumb"></div>');
                    $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
                }
            }
            if ($gallery.find('.woocommerce-product-gallery__image').length === 1) {
                $thumb_wrap.remove();
                if (thumb_wrap.hasClass('vertical-thumbnails')){
                    $gallery.parent().removeClass('large-10');
                }
            }else {
                if (thumb_wrap.hasClass('vertical-thumbnails')){
                    $gallery.parent().addClass('large-10');
                }
                $thumb_wrap.replaceWith(thumb_wrap);
            }
            if (thumb_wrap.hasClass('vertical-thumbnails')){
                $thumb_wrap = $gallery.closest('.product-gallery').find('.vertical-thumbnails .vargal-flatsome-product-gallery-thumb');
            }else {
                $thumb_wrap = $gallery.parent().find('.vargal-flatsome-product-gallery-thumb');
            }
            $gallery.find('.woocommerce-product-gallery__wrapper').flickity($gallery.find('.woocommerce-product-gallery__wrapper').data('flickityOptions'));
            if ($thumb_wrap.length){
                // if ($thumb_wrap.flickity) {
                //     // $thumb_wrap.flickity('destroy');
                //     $thumb_wrap.off();
                // }
                $thumb_wrap.off();
                $thumb_wrap.removeData("flickity");
                if ($thumb_wrap.find('.col').length < 5){
                    $thumb_wrap.addClass('slider-no-arrows');
                }else {
                    $thumb_wrap.removeClass('slider-no-arrows');
                }
                $thumb_wrap.flickity($thumb_wrap.data('flickityOptions'));
            }
            $gallery.off().wc_product_gallery();
        }
        return true;
    });
}(jQuery));