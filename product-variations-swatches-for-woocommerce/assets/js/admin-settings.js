jQuery(document).ready(function ($) {
    'use strict';
    if (vi_wpvs_admin_settings?.auto_detect_swatches_profile){
        $(document).on('change','select#vi-wpvs-attribute_display_default',function (){
            switch ($(this).val()){
                case 'button':
                case 'radio':
                    $('#vi-wpvs-attribute_profile_default').val('vi_wpvs_button_design').trigger('change');
                    break;
                case 'variation_img':
                    $('#vi-wpvs-attribute_profile_default').val('vi_wpvs_image_design').trigger('change');
                    break;
            }
        });
    }
    $(document).on('change','select#vi-wpvs-attribute_display_default',function (){
        let val = $(this).val();
        if (val === 'variation_img'){
            $('.vi-wpvs-attribute_display_default-variation_img').removeClass('vi-wpvs-hidden');
        }else {
            $('.vi-wpvs-attribute_display_default-variation_img').addClass('vi-wpvs-hidden');
        }
        if (val==='none'){
            $('.vi-wpvs-attribute_profile_default-wrap').addClass('vi-wpvs-hidden');
        }else {
            $('.vi-wpvs-attribute_profile_default-wrap').removeClass('vi-wpvs-hidden');
        }
    });
    $(document).on('change','select#vi-wpvs-attribute_variation_img_apply',function (){
        switch ($(this).val()){
            case '2':
                $('.vi-wpvs-attribute_display_default-variation_img-2').removeClass('vi-wpvs-hidden');
                break;
            default:
                $('.vi-wpvs-attribute_display_default-variation_img-2').addClass('vi-wpvs-hidden');

        }
    });
    $('.vi-ui.vi-ui-main.tabular.menu .item').vi_tab({
        history: true,
        historyType: 'hash'
    });

    $('.ui-sortable').sortable({
        placeholder: 'wpvs-place-holder',
    });
    handleInit();

    function handleInit() {
        $('.vi-ui.accordion').vi_accordion('refresh');
        $('.vi-ui.dropdown').unbind().dropdown();
        handleValueChange();
        handleCheckBox();
        handleColorPicker();
    }

    // change name
    function handleValueChange() {
        $('.vi-wpvs-names').unbind().on('keyup', function () {
            $(this).parent().parent().parent().find('.vi-wpvs-accordion-name').html($(this).val());
        });
        $('input[type = "number"]').unbind().on('change', function () {
            let min = parseFloat($(this).attr('min')) || 0,
                max = parseFloat($(this).attr('max')),
                val = parseFloat($(this).val()) || 0;
            if (min > val) {
                $(this).val(min);
            } else {
                $(this).val(val);
            }
            if (max && max < val) {
                $(this).val(max);
            }
        });
    }

    function handleCheckBox() {
        $('.vi-ui.checkbox').unbind().checkbox();

        $('input[type="checkbox"]').unbind().on('change', function () {
            if ($(this).prop('checked')) {
                $(this).parent().find('input[type="hidden"]').val('1');
                if ($(this).hasClass('vi-wpvs-single_attr_title-checkbox')) {
                    $('.vi-wpvs-single_attr_title-enable').removeClass('vi-wpvs-hidden');
                }
            } else {
                $(this).parent().find('input[type="hidden"]').val('');
                if ($(this).hasClass('vi-wpvs-single_attr_title-checkbox')) {
                    $('.vi-wpvs-single_attr_title-enable').addClass('vi-wpvs-hidden');
                }
            }
        });
    }

    function handleColorPicker() {
        $('.vi-wpvs-color').each(function () {
            $(this).css({backgroundColor: $(this).val()});
        });
        $('.vi-wpvs-color').unbind().minicolors({
            change: function (value, opacity) {
                $(this).parent().find('.vi-wpvs-color').css({backgroundColor: value});
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
    }


    $(document).on('click','.vi-wpvs-reset', function () {
        if (confirm('All settings will be deleted. Are you sure you want to reset yours settings?')){
            $(this).attr('type','submit');
        }
    });
    $(document).on('click','.vi-wpvs-import', function () {
        $('.vi-wpvs-import-wrap-wrap').toggleClass('vi-wpvs-hidden');
    });
    $('.vi-wpvs-save').on('click', function () {
        $(this).addClass('loading');
        let nameArr = $('input[name="names[]"]');
        let z, v;
        for (z = 0; z < nameArr.length; z++) {
            if (!$('input[name="names[]"]').eq(z).val()) {
                alert('Name cannot be empty!');
                if (!$('.vi-wpvs-accordion').eq(z).hasClass('vi-wpvs-active-accordion')) {
                    $('.vi-wpvs-accordion').eq(z).addClass('vi-wpvs-active-accordion');
                }
                $('.vi-wpvs-save').removeClass('loading');
                return false;
            }
        }

        for (z = 0; z < nameArr.length - 1; z++) {
            for (v = z + 1; v < nameArr.length; v++) {
                if ($('input[name="names[]"]').eq(z).val() === $('input[name="names[]"]').eq(v).val()) {
                    alert("Names are unique!");
                    if (!$('.vi-wpvs-accordion').eq(v).hasClass('vi-wpvs-active-accordion')) {
                        $('.vi-wpvs-accordion').eq(v).addClass('vi-wpvs-active-accordion');
                    }
                    $('.vi-wpvs-save').removeClass('loading');
                    return false;
                }
            }
        }

        $(this).attr('type', 'submit');
    });
});