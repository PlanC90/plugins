jQuery(document).ready(function ($) {
    'use strict';
    $(document).on('dokan_variations_loaded',  function (){
        $('.viwpvs-dokan-setting-wrap.dokan-hide').map(function (k,v) {
            let attr_name = $(v).data('name');
            let wrap = $('[name="'+attr_name+'"').closest('.dokan-product-attribute-item');
            if (!wrap.length){
                wrap = $('.dokan-product-attribute-item').eq(k+1);
            }
            if (wrap.length){
                wrap.find('.viwpvs-dokan-setting-wrap').remove();
                wrap.append($(v).clone());
                wrap.find('.viwpvs-dokan-setting-wrap').removeClass('dokan-hide');
            }
            $(v).remove();
        });
        viwpvs_custom_attribute.init();
    });
    $(document).on('click','.vi-wpvs-attribute-info-custom-open', function (e) {
        e.preventDefault();
        e.stopPropagation();
        let wrap = $(this).closest(".viwpvs-dokan-setting-wrap");
        if (!wrap.length){
            wrap = $(this).closest("td");
        }
        wrap.find('.vi-wpvs-attribute-wrap').toggleClass('vi-wpvs-hidden');
        if (!wrap.find('.vi-wpvs-attribute-wrap').hasClass('vi-wpvs-hidden')){
            wrap.find('.viwpvs_save_attribute').val(1);
        }
        viwpvs_custom_attribute.showSaveAtributeBtn();
    });
    $(document).on('click','.vi-wpvs-attribute-value-title-wrap', function () {
        if (!$(this).hasClass('vi-wpvs-attribute-value-title-toggle')) {
            return false;
        }
        $(this).find('.vi-wpvs-attribute-value-action-icon').toggleClass('vi-wpvs-hidden');
        $(this).closest('.vi-wpvs-attribute-value').find('.vi-wpvs-attribute-value-content-wrap').toggleClass('vi-wpvs-attribute-value-content-open').toggleClass('vi-wpvs-attribute-value-content-close');
    });
    $(document.body).on('woocommerce_attributes_saved', function () {
        viwpvs_custom_attribute.init();
    });
    window.viwpvs_custom_attribute = {
        init: function () {
            this.editAttribute();
            this.ColorPicker();
            this.UploadImage();
            this.duplicateItem();
        },
        showSaveAtributeBtn:function () {
            if (!$('#product_attributes .save_attributes').length){
                return;
            }
            if (!$('#product_attributes .save_attributes').is(':visible')){
                $('#product_attributes .save_attributes').show();
            }
            if ($('#product_attributes .save_attributes').prop('disabled') || $('#product_attributes .save_attributes').attr('disabled')){
                $('#product_attributes .save_attributes').removeAttr('disabled');
            }
            if ($('#product_attributes .save_attributes').attr('aria-disabled')){
                $('#product_attributes .save_attributes').removeAttr('aria-disabled');
            }
            if ($('#product_attributes .save_attributes').hasClass('disabled')){
                $('#product_attributes .save_attributes').removeClass('disabled');
            }
        },
        editAttribute: function () {
            $('.vi-wpvs-attribute-type select').on('change', function () {
                let val = $(this).val(),
                    $container = $(this).closest('.vi-wpvs-attribute-wrap'),
                    $style_wrap = $container.find('.vi-wpvs-attribute-value-content-'+val+'-wrap');
                $container.find('.vi-wpvs-attribute-value-wrap').removeClass('vi-wpvs-hidden');
                if ($style_wrap.length){
                    $container.find('.vi-wpvs-attribute-value-content-item-wrap').addClass('vi-wpvs-hidden');
                    $style_wrap.removeClass('vi-wpvs-hidden');
                }else {
                    $container.find('.vi-wpvs-attribute-value-wrap').addClass('vi-wpvs-hidden');
                }
                viwpvs_custom_attribute.showSaveAtributeBtn();
            }).trigger('change');
        },
        duplicateItem: function () {
            $('.vi-wpvs-attribute-colors-action-clone').off().on('click', function (e) {
                e.stopPropagation();
                var current = $(this).parent().parent();
                var newRow = current.clone();
                newRow.find('.iris-picker').remove();
                newRow.insertAfter(current);
                viwpvs_custom_attribute.init();
                e.stopPropagation();
            });
            $('.vi-wpvs-attribute-value-action-clone').off().on('click', function (e) {
                e.stopPropagation();
                let i = $('.vi-wpvs-attribute-value-wrap').length, j;
                var current = $(this).closest('.vi-wpvs-attribute-value-wrap');
                var newRow = current.clone();
                j = current.data('attribute_number');
                newRow.find('.iris-picker').remove();
                newRow.find('.vi_attribute_colors').each(function () {
                    $(this).attr('name', 'vi_attribute_colors[' + j + '][' + i + '][]');
                });
                newRow.insertAfter(current);
                viwpvs_custom_attribute.init();
                e.stopPropagation();
            });
        },
        viwpvs_img_uploader:null,
        UploadImage: function () {
            $('.vi-attribute-image-remove').unbind().on('click', function (e) {
                let wrap = $(this).closest('.vi-wpvs-attribute-value-content-image-wrap');
                let src_placeholder = wrap.find('.vi-attribute-image-preview img').data('src_placeholder');
                wrap.find('.vi_attribute_image').val('');
                wrap.find('.vi-attribute-image-preview img').attr('src', src_placeholder);
                $(this).addClass('vi-wpvs-hidden');
                viwpvs_custom_attribute.showSaveAtributeBtn();
            });
            $('.vi-attribute-image-add-new').unbind().on('click', function (e) {
                e.preventDefault();
                $('.vi_attribute_image-editing').removeClass('vi_attribute_image-editing');
                $(this).closest('.vi-wpvs-attribute-value-content-image-wrap').addClass('vi_attribute_image-editing');
                //If the uploader object has already been created, reopen the dialog

                if (viwpvs_custom_attribute.viwpvs_img_uploader) {
                    viwpvs_custom_attribute.viwpvs_img_uploader.open();
                    return false;
                }
                //Extend the wp.media object
                viwpvs_custom_attribute.viwpvs_img_uploader = wp.media({
                    title: 'Choose Image',
                    button: {
                        text: 'Choose Image'
                    },
                    multiple: true
                });

                //When a file is selected, grab the URL and set it as the text field's value
                viwpvs_custom_attribute.viwpvs_img_uploader.on('select', function () {
                    let attachment = viwpvs_custom_attribute.viwpvs_img_uploader.state().get('selection').first().toJSON();
                    $('.vi_attribute_image-editing').find('.vi_attribute_image').val(attachment.id);
                    $('.vi_attribute_image-editing').find('.vi-attribute-image-preview img').attr('src', attachment.url);
                    $('.vi_attribute_image-editing').find('.vi-attribute-image-remove').removeClass('vi-wpvs-hidden');
                    $('.vi_attribute_image-editing').removeClass('vi_attribute_image-editing');
                    viwpvs_custom_attribute.showSaveAtributeBtn();
                });

                //Open the uploader dialog
                viwpvs_custom_attribute.viwpvs_img_uploader.open();
            });
        },
        ColorPicker: function () {
            $('.vi-wpvs-color').each(function () {
                $(this).css({backgroundColor: $(this).val()});
            });
            $('.vi-wpvs-color.vi_attribute_colors').off().minicolors({
                change: function (value, opacity) {
                    $(this).parent().find('.vi-wpvs-color.vi_attribute_colors').css({backgroundColor: value});
                    viwpvs_custom_attribute.showSaveAtributeBtn();
                },
                animationSpeed: 50,
                animationEasing: 'swing',
                changeDelay: 0,
                control: 'wheel',
                defaultValue: '',
                format: 'rgb',
                hide: null,
                hideSpeed: 100,
                inline: false,
                keywords: '',
                letterCase: 'lowercase',
                opacity: true,
                position: 'bottom left',
                show: null,
                showSpeed: 100,
                theme: 'default',
                swatches: []
            });
        },
        wpvs_term_color_preview: function () {
        }
    };
    viwpvs_custom_attribute.init();
});
