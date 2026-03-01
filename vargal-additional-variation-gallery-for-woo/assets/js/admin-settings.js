jQuery(document).ready(function ($) {
    'use strict';
    let loading_preview;
    $('.vi-ui.menu.main .item').tab({history: true, historyType: 'hash'});
    $('.vi-ui.menu:not(.main) .item').tab();
    $('.vi-ui.checkbox:not(.vargal-checkbox-init)').addClass('vargal-checkbox-init').off().checkbox();
    $('.vi-ui.dropdown:not(.vargal-dropdown-init)').addClass('vargal-dropdown-init').off().dropdown();
    $(document).on('change','.vargal-thumbnail_pos input', function () {
        $(this).closest('.fields').find('.vargal-thumbnail_pos').removeClass('vargal-thumbnail_pos-selected');
        $(this).closest('div.vargal-thumbnail_pos').addClass('vargal-thumbnail_pos-selected');
    });
    $(document).on('change','input[type="checkbox"]', function () {
        if ($(this).prop('checked')) {
            $(this).parent().find('input[type="hidden"]').val('1');
        } else {
            $(this).parent().find('input[type="hidden"]').val('');
        }
    });
    $(document).on('change','#vargal-loading_icon_type', function () {
        $(document).trigger('vargal-loading-preview');
    });
    $(document).on('change', '.vargal-navigation_pos select', function () {
        let val = $(this).val();
        if (!val || val == '0') {
            $('.vagarl-navigation-design-wrap').closest('tr').slideUp(200);
        } else {
            $('.vagarl-navigation-design-wrap').closest('tr').slideDown(200);
        }
    });
    $(document).on('vargal-loading-preview',function (){
        let type = $('#vargal-loading_icon_type').val(),
            color = $('.vargal-loading_icon_color').val(),
            $preview= $('.vagal-loading-icon-preview');
        if(!type || type === '0'){
            $preview.css({height:'0px'});
            $preview.find('.vargal-loading').remove();
            return;
        }
        if (loading_preview){
            clearTimeout(loading_preview);
        }
        $preview.css({height:'300px'});
        $preview.find('.vargal-loading').remove();
        $(document).trigger('vargal-loading-render', [$preview,type]);
        if (!$('#vargal-loading-preview-css').length){
            $('head').append('<style id="vargal-loading-preview-css"></style>')
        }
        $('#vargal-loading-preview-css').html('');
        $('#vargal-loading-preview-css').append('.vargal-loading-icon-default div, .vargal-loading-icon-animation_face_1 div, .vargal-loading-icon-animation_face_2 div, .vargal-loading-icon-roller div:after, .vargal-loading-icon-loader_balls_1 div, .vargal-loading-icon-loader_balls_2 div, .vargal-loading-icon-loader_balls_3 div, .vargal-loading-icon-spinner div:after{background:'+color+'}');
        $('#vargal-loading-preview-css').append('.vargal-loading-icon-ripple div{border:4px solid '+color+'}');
        $('#vargal-loading-preview-css').append('.vargal-loading-icon-ring div{border-color:'+color+' transparent transparent transparent}');
        $('#vargal-loading-preview-css').append('.vargal-loading-icon-dual_ring:after{border-color:'+color+' transparent '+color+' transparent}');
        // loading_preview = setTimeout(function ($preview){
        //     $preview.css({height:'0px'});
        //     $preview.find('.vargal-loading').remove();
        // }, 3000, $preview);
    });
    handleColorPicker();
    function handleColorPicker() {
        $('.vargal-color').each(function () {
            $(this).css({backgroundColor: $(this).val()});
            if ($(this).hasClass('vargal-loading_icon_color')){
                $(document).trigger('vargal-loading-preview');
            }
        });
        $('.vargal-color').off().minicolors({
            change: function (value, opacity) {
                $(this).parent().find('.vargal-color').css({backgroundColor: value});
                if ($(this).parent().find('.vargal-color').hasClass('vargal-loading_icon_color')){
                    $(document).trigger('vargal-loading-preview');
                }
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
});