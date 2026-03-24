jQuery(document).ready(function ($) {
    'use strict';
    $( document.body ).on( 'wc-enhanced-select-init', function () {
        $('.vargal-settings:not(.vargal-settings-init)').each(function (k,v) {
           $(v).vargal_settings_init();
        });
    });
    $( document ).on( 'dokan_variations_loaded.dokan-product-variation-wrapper, dokan_variations_added.dokan-product-variation-wrapper', function () {
        $('.vargal-settings:not(.vargal-settings-init)').each(function (k,v) {
           $(v).vargal_settings_init();
        });
    });
    $.fn.vargal_settings_init = function () {
        let vargal =  new vargal_setting_init(this);
        vargal.init();
        vargal.sortable();
        return this;
    };
    window.vargal_setting_init = function (gallery) {
        this.$gallery = gallery;
        this.gallery_input_name = gallery.data('gallery_input_name');
        this.upload_frame = null;
        let self = this;
        let $gallery = self.$gallery;
        this.init = function (){
            $gallery.addClass('vargal-settings-init');
            $gallery.closest('.woocommerce_variation').find('.form-flex-box').after($gallery);
            $gallery.closest('.dokan-product-variation-itmes').find('.variable_pricing').before($gallery);
            $(document).trigger('vargal-settings-init',self);
            $gallery.on('click','.vargal-settings-header',function () {
                if (!$gallery.hasClass('closed')){
                    $gallery.addClass('closed');
                    $gallery.find('.vargal-settings-content').slideUp(300);
                }else {
                    $gallery.removeClass('closed');
                    $gallery.find('.vargal-settings-content').slideDown(300);
                }
            });
            $gallery.on('click','.vargal-img-delete',function () {
                $(this).closest('.vargal-img-selected').remove();
                self.update();
            });
            $gallery.on('click','.vargal-btn-upload',function () {
                if (self.upload_frame) {
                    self.upload_frame.open();
                    return;
                }
                let frame = wp.media({
                    title: vargal_params.upload_frame_title ,
                    button: {
                        text: vargal_params.upload_frame_btn
                    },
                    library: {
                        type: ['image','video']
                    },
                    multiple: true
                });
                frame.on('select', function () {
                    // Get media attachment details from the frame state
                    frame.state().get('selection').map( function ( attachment ) {
                        self.render_img(attachment.toJSON());
                    } );
                });
                self.upload_frame = frame;
                self.upload_frame.open();
            });
        }
        this.render_img = function (attachment){
            if (!attachment?.id || $gallery.find('.vargal-img-selected-' + attachment.id ).length){
                $(document.body).trigger('villatheme_show_message', [vargal_params.duplicate_img_mes, ['vargal-img-notice'], '', false, 1500]);
                return;
            }
            let attachment_url,attachment_html, attachment_type = attachment?.type, attachment_subtype = attachment?.subtype;
            if (!['image', 'video'].includes(attachment_type)){
                $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_type_mes, ['vargal-img-error','error'], '', false, 5500]);
                return;
            }
            if (attachment_type === 'video' && !['mp4','webm'].includes(attachment_subtype)){
                $(document.body).trigger('villatheme_show_message', [vargal_params.invalid_video_mes, ['vargal-img-error','error'], '', false, 5500]);
                return;
            }
            let html = $('<div class="vargal-img vargal-img-selected vargal-img-selected-' + attachment.id + '"><input type="hidden" name="' + self.gallery_input_name + '" value="' + attachment.id + '"><span class="vargal-img-delete"><i class="dashicons dashicons-dismiss"></i></span></div>')
            attachment_url = attachment?.url;
            attachment_html = $(document).triggerHandler('vargal_get_attachment_html',[attachment, attachment_type, attachment_url]);
            if (typeof attachment_html === "undefined"){
                if (attachment_type === 'image'){
                    if (attachment?.sizes?.thumbnail?.url) {
                        attachment_url = attachment.sizes.thumbnail.url;
                    } else if (attachment?.sizes?.medium?.url) {
                        attachment_url = attachment.sizes.medium.url;
                    } else if (attachment?.sizes?.large?.url) {
                        attachment_url = attachment.sizes.large.url;
                    } else if (attachment?.sizes?.full?.url) {
                        attachment_url = attachment.sizes.full.url;
                    }
                    attachment_html = attachment_url ? '<img src="' + attachment_url + '">' :'';
                }else {
                    html.addClass('vargal-thumb-video');
                    attachment_html = attachment_url ? '<video preload="metadata" src="' + attachment_url + '"></video>' : '';
                }
            }
            if (!attachment_html ){
                return;
            }
            html.append(attachment_html);
            $gallery.find('.vargal-btn-upload').before(html);
            self.update();
        }
        this.sortable = function (){
            $gallery.find('.vargal-settings-content').sortable( {
                items: '.vargal-img-selected',
                cursor: 'move',
                scrollSensitivity: 40,
                forcePlaceholderSize: true,
                forceHelperSize: false,
                helper: 'clone',
                opacity: 0.65,
                placeholder: 'vargal-sortable-placeholder',
                start: function ( event, ui ) {
                    ui.item.css( 'background-color', '#f6f6f6' );
                },
                stop: function ( event, ui ) {
                    ui.item.removeAttr( 'style' );
                },
                update: function () {
                    self.update();
                }
            } );
        }
        this.update = function (){
            self.sortable();
            $gallery.closest('.woocommerce_variation').addClass('variation-needs-update');
            $('button.cancel-variation-changes, button.save-variation-changes').removeAttr('disabled');
            $(document).trigger('vargal-settings-updated',self);
        }
    };
});