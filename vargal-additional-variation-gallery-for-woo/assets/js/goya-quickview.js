(function ($) {
    'use strict';
    window.vargal_goya_quickview_params = {
        gallery_is_changing: {},
        default_product_gallery: {}
    }
    $(document).on('vargal-loading-end', function (e, product_id) {
        let $gallery = $('#et-quickview #product-' + product_id + ' .et-qv-product-image');
        if (!$gallery.length) {
            return;
        }
        $gallery.removeClass('vargal-product-gallery-loading');
        $gallery.find('.vargal-loading').addClass('vargal-hidden');
    });
    $(document).on('vargal-loading-start', function (e, product_id) {
        let $gallery = $('#et-quickview #product-' + product_id + ' .et-qv-product-image');
        if (!$gallery.length) {
            return;
        }
        $gallery.addClass('vargal-product-gallery-loading');
        if (!$gallery.find('.vargal-loading').length) {
            $(document).trigger('vargal-loading-render', [$gallery, vargal_params.loading_icon_type]);
        }
        $gallery.find('.vargal-loading').removeClass('vargal-hidden');
    });
    $(document).on('clear_reset_announcement', '#et-quickview .variations_form', function (e) {
        let product_id = $(this).closest('.variations_form').data('product_id') || $(this).closest('.variations_form').find('[name="product_id"]').val();
        if (!product_id) {
            return;
        }
        if (!vargal_goya_quickview_params.default_product_gallery[product_id]) {
            vargal_goya_quickview_params.default_product_gallery[product_id] = $('#et-quickview').find('.slick-slide:not(.slick-cloned)').length ? $('#et-quickview').find('.slick-slide:not(.slick-cloned) .woocommerce-product-gallery__image').clone() : $('#et-quickview').find('.woocommerce-product-gallery__image').clone();
        }
        let $gallery = $('#et-quickview #product-' + product_id + ' .et-qv-product-image');
        if (!$gallery.length) {
            return;
        }
        $(document).trigger('vargal-loading-start', [product_id]);
        if (!$gallery.data('vargal_render_type')) {
            $gallery.data('vargal_render_type', 'goya-quickview');
        }
    });
    $(document).on('hide_variation', '.variations_form', function (e) {
        let product_id = $(this).data('product_id') || $(this).find('[name="product_id"]').val();
        if (!product_id || !vargal_goya_quickview_params.default_product_gallery[product_id]) {
            return;
        }
        $(document).trigger('vargal-gallery-reset', [product_id]);
    });
    $(document).on('show_variation', '#et-quickview .variations_form', function (e, variation, purchasable) {
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
    $(document).on('vargal-gallery-changing', function (e, product_id, gallery, variation) {
        if (!product_id || !$('#et-quickview').is(':visible')) {
            return;
        }
        let $gallery = $('#et-quickview #product-' + product_id + ' .et-quickview-gallery-slider');
        if (!$gallery.length) {
            return;
        }
        let old_variation = vargal_goya_quickview_params.gallery_is_changing[product_id];
        if (old_variation?.variation_id && old_variation.variation_id == variation.variation_id) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (!gallery || !gallery.length) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (!vargal_goya_quickview_params.default_product_gallery[product_id]) {
            vargal_goya_quickview_params.default_product_gallery[product_id] = $gallery.find('.slick-slide:not(.slick-cloned) .woocommerce-product-gallery__image').clone();
        }
        if (!vargal_goya_quickview_params.default_product_gallery[product_id]) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (gallery.length === 1 && (!variation?.vargal || !variation.vargal.length)) {
            if (!$gallery.find('.slick-slide').length) {
                $(document).trigger('vargal-loading-end', [product_id]);
                return;
            }
            let image_default = false;
            for (let i = 0; i < vargal_goya_quickview_params.default_product_gallery[product_id].length; i++) {
                let img_url =jQuery(vargal_goya_quickview_params.default_product_gallery[product_id][i]).find('img').attr('src');
                if (img_url == gallery[0]?.full_src || img_url == gallery[0]?.url|| img_url == gallery[0]?.src) {
                    image_default = i;
                    break;
                }
            }
            if (image_default !== false){
                $(document).trigger('vargal-gallery-reset', [product_id]);
                setTimeout(function (){
                    $gallery.slick('slickGoTo', image_default);
                },500);
                return;
            }
        }
        $(document).trigger('vargal-loading-start', [product_id]);
        vargal_goya_quickview_params.gallery_is_changing[product_id] = variation;
        $gallery.find('.wp-post-image').removeClass('wp-post-image');
        $gallery.find('img').removeAttr('data-srcset').removeAttr('srcset');
        let $tmp = '', gallery_video = [];
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
                if (galleryElement?.poster) {
                    $html.find('video').attr({
                        'poster': galleryElement.poster,
                    });
                }
            } else {
                $html.find('a').append(vargal_goya_quickview_params.default_product_gallery[product_id].find('img').eq(0).clone());
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
                $html.find('img').removeAttr('data-src');
                $html.find('img').removeAttr('data-o_data-src');
                $html.find('img').removeAttr('data-srcset');
                $html.find('img').removeAttr('data-o_data-srcset');
                $html.find('img').removeAttr('data-o_srcset');
                if (['image/gif'].includes(galleryElement.mime_type)) {
                    $html.find('img').removeAttr('srcset');
                    $html.find('img').attr({'src': galleryElement.full_src});
                }
                if (!galleryElement.srcset) {
                    $html.find('img').removeAttr('srcset');
                }
            }
            let $custom_html = $(document).triggerHandler('vargal_frontend_get_attachment_html', [$html, galleryElement, $gallery]);
            $html_t.html($custom_html || $html);
            $tmp += $html_t.html();
        }
        let rendered_gallery = $(document).triggerHandler('vargal-goya-quickview-gallery-render', [product_id, gallery, variation, gallery_video, $tmp]);
        if (rendered_gallery) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        $(document).trigger('vargal-goya-quickview-gallery-slider-render', [$($tmp), $gallery, product_id]);
    });
    $(document).on('vargal-goya-quickview-gallery-slider-render', function (e, items, gallery, product_id) {
        let $gallery = $(gallery);
        if (!$gallery.length || !items.length) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (!$gallery.find('.slick-slide').length) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        let new_slides = $(items).length;
        $(items).find('img').removeClass('wp-post-image lazyloading');
        let slides_count = $gallery.slick('getSlick').slideCount;
        for (let i = slides_count - 1; i >= 0; i--) {
            $gallery.slick('slickRemove', i);
        }
        for (let i = 0; i < new_slides; i++) {
            let $v = $(items).eq(i);
            if (i === 0) {
                $v.find('img').addClass('vargal-wp-post-image')
            }
            $gallery.slick('slickAdd', $v);
        }
        $gallery.find('.vargal-wp-post-image').one('load', function () {
            $(this).removeClass('vargal-wp-post-image').addClass('wp-post-image');
            $(document).trigger('vargal-loading-end', [product_id]);
        })
    });
    $(document).on('vargal-gallery-reset', function (e, product_id) {
        if (!product_id || !$('#et-quickview').is(':visible')) {
            return;
        }
        let $gallery = $('#et-quickview #product-' + product_id + ' .et-quickview-gallery-slider');
        if (!$gallery.length) {
            return;
        }
        $(document).trigger('vargal-loading-start', [product_id]);
        let reset_gallery = $(document).triggerHandler('vargal-goya-quickview-gallery-reset', [product_id]);
        if (reset_gallery) {
            $(document).trigger('vargal-loading-end', [product_id]);
            return;
        }
        if (vargal_goya_quickview_params.default_product_gallery[product_id] &&
            vargal_goya_quickview_params.gallery_is_changing[product_id] && vargal_goya_quickview_params.gallery_is_changing[product_id] !== 'default') {
            vargal_goya_quickview_params.gallery_is_changing[product_id] = 'default';
            $(document).trigger('vargal-goya-quickview-gallery-slider-render', [vargal_goya_quickview_params.default_product_gallery[product_id], $gallery, product_id]);
        } else {
            $(document).trigger('vargal-loading-end', [product_id]);
        }
    });

}(jQuery));