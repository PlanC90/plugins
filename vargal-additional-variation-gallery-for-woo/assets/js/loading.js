(function ($) {
    'use strict';
    $(document).ready(function () {
        $(document).on('vargal-loading-render', function (e, gallery, type) {
            let $gallery = $(gallery);
            if (!$gallery.length) {
                return;
            }
            if (!$gallery.find('.vargal-loading').length) {
                let loading_html = '';
                switch (type) {
                    case 'roller':
                        loading_html = '<div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>';
                        break;
                    case 'loader_balls_1':
                    case 'ring':
                    case 'animation_face_2':
                        loading_html = '<div></div><div></div><div></div><div></div>';
                        break;
                    case 'loader_balls_3':
                    case 'loader_balls_2':
                    case 'animation_face_1':
                        loading_html = '<div></div><div></div><div></div>';
                        break;
                    case 'ripple':
                        loading_html = '<div></div><div></div>';
                        break;
                    case 'spinner':
                    case 'default':
                        loading_html = '<div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>';
                        break;
                }
                if (loading_html || ['dual_ring'].includes(type)) {
                    loading_html = '<div class="vargal-loading-icon vargal-loading-icon-' + type + '">' + loading_html + '</div>';
                }
                $gallery.append('<div class="vargal-loading">' + loading_html + '</div>');
            }
        });
    });
}(jQuery));