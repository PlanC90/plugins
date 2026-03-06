jQuery(document).ready(function ($) {
    'use strict';
    if (typeof wbeParams === "undefined") {
        return;
    }
    $(document).on('bulky_gallery_attachment_render', function (e, src, id, is_gallery = true) {
        let $tag = is_gallery ? $(`<li></li>`): $('<div></div>'), html = '', width = is_gallery ? 95 : 40;
        if (!src){
            return false;
        }
        if (/^(http(s?):)\/\/.*\.(?:mp4|webm)$/i.test(src)) {
            html = `<video preload="metadata" src="${src}"></video>`;
        }
        if (html){
            let $wrap = $('<div></div>');
            $tag.addClass('vi-wbe-gallery-image vargal-thumb-video').attr({'data-id': id});
            if (is_gallery) {
                $tag.append('<i class="vi-wbe-remove-image dashicons dashicons-no-alt"> </i>');
            }
            $tag.append(html);
            $wrap.html($tag);
            return $wrap.html();
        }
        return false;
    });
    $(document).on('bulky_gallery_approve_attachment', function (e, cell, obj, attachment) {
        let y = cell.getAttribute('data-y');
        if (!obj?.options?.data[y] ) {
            return false;
        }
        let attachment_type = attachment?.type, attachment_subtype = attachment?.subtype;
        if (!attachment?.id) {
            return false;
        }
        if (!['image', 'video'].includes(attachment_type)) {
            $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_type_mes, ['vargal-img-error', 'error'], '', false, 5500]);
            return false;
        }
        if (attachment_type === 'video' &&  !['mp4', 'webm'].includes(attachment_subtype)) {
            $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_video_mes, ['vargal-img-error', 'error'], '', false, 5500]);
            return;
        }
        if ($('.vi-wbe-cell-popup .vi-wbe-gallery-image[data-id="' + attachment.id + '"]').length) {
            $(document.body).trigger('villatheme_show_message', [vargal_params.duplicate_img_mes, ['vargal-img-notice'], '', false, 3500]);
            return false;
        }
        return true;
    });
});