(function ($) {
    'use strict';
    $(document).on('click', '.vi-wpvs-variation-style', function (e) {
        $('.vi-wpvs-variation-wrap-option-available').remove();
        $('.vi-wpvs-variation-wrap-option.vi-wpvs-variation-wrap-option-show').removeClass('vi-wpvs-variation-wrap-option-show');
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
    });
    $(document).on('click', 'body', function (e) {
        $('.vi-wpvs-variation-wrap-option-available').remove();
        $('.vi-wpvs-variation-wrap-option.vi-wpvs-variation-wrap-option-show').removeClass('vi-wpvs-variation-wrap-option-show');
    });
    $(document).on('click', '.vi-wpvs-variation-wrap-option-available .vi-wpvs-option-wrap', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        if ($(this).hasClass('vi-wpvs-option-wrap-disable')) {
            return false;
        }
        let current_index = $('.vi-wpvs-variation-wrap-option-available .vi-wpvs-option-wrap').index($(this));
        $('.vi-wpvs-variation-wrap-option.vi-wpvs-variation-wrap-option-show').removeClass('vi-wpvs-variation-wrap-option-show').find('.vi-wpvs-option-wrap').eq(current_index).trigger('click');
        $('.vi-wpvs-variation-wrap-option-available').remove();
    });
    $(document).on('mouseenter', '.vi-wpvs-variation-wrap-option-available .vi-wpvs-option-wrap', function (e) {
        if (!$(this).hasClass('vi-wpvs-option-wrap-selected') && !$(this).hasClass('vi-wpvs-option-wrap-disable') && !$(this).hasClass('vi-wpvs-product-link')) {
            $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-hover');
        }
    });
    $(document).on('mouseleave', '.vi-wpvs-variation-wrap-option-available .vi-wpvs-option-wrap', function (e) {
        if (!$(this).hasClass('vi-wpvs-option-wrap-selected') && !$(this).hasClass('vi-wpvs-option-wrap-disable')) {
            $(this).removeClass('vi-wpvs-option-wrap-hover').addClass('vi-wpvs-option-wrap-default');
        }
    });
    let event_init=[
        'ajaxComplete',
        'woodmart-quick-view-displayed',
        'wc-composite-component-loaded', /*Compatible with WooCommerce Composite Products plugin*/
        'wc_variation_form',
        'flatsome-flickity-ready',
        'woosq_loaded',
        'tc_product_variation_form',
    ];
    $(document).on(event_init.join(' '), function (event) {
        setTimeout(function () {
            viwpvs_frontend_init();
        },200);
    });
    $(document).on('vi_wpvs_variation_form', function () {
        viwpvs_frontend_init();
    });
    $(document).ready(function () {
        viwpvs_frontend_init();
    });

    $(window).on('load', function () {
        viwpvs_frontend_init();
    });

    function viwpvs_frontend_init() {
        if (!$('.vi_wpvs_variation_form:not(.vi_wpvs_variation_form_init),.variations_form:not(.vi_wpvs_variation_form),.variations_form:not(.vi_wpvs_variation_form_init)').length) {
            setTimeout(function () {
                viwpvs_frontend_init();
            }, 100);
            return false;
        }
        $('.vi_wpvs_variation_form:not(.vi_wpvs_variation_form_init)').each(function () {
            $(this).addClass('vi_wpvs_variation_form_init').viwpvs_woo_product_variation_swatches();
        });
        $('.variations_form:not(.vi_wpvs_variation_form),.variations_form:not(.vi_wpvs_variation_form_init)').each(function () {
            if ($(this).find('.composited_product_details_wrapper').length){
                return true;
            }
            $(this).addClass('vi_wpvs_variation_form vi_wpvs_variation_form_init').viwpvs_woo_product_variation_swatches();
        });
        $('.composited_product_details_wrapper .variations:not(.vi_wpvs_variation_form), .composited_product_details_wrapper .variations:not(.vi_wpvs_variation_form_init)').each(function () {
            $(this).addClass('vi_wpvs_variation_form vi_wpvs_variation_form_init viwpvs_variation_form_refresh');
            $(this).closest('.variations_form').viwpvs_woo_product_variation_swatches();
        });
        if (vi_wpvs_frontend_param.wjecf_wc_discounts && vi_wpvs_frontend_param.is_checkout) {
            $('.variations').each(function () {
                $(this).addClass('vi_wpvs_variation_form vi_wpvs_variation_form_init').viwpvs_woo_product_variation_swatches();
            });
        }
    }
    window.viwpvs_frontend = function ($form) {
        this.form = $form;
        this.viwpvs_form_id = Date.now();
        this.variationData = $form.data('product_variations');
        this.init();
    };

    viwpvs_frontend.prototype.init = function () {
        let viwpvs_frontend = this,
            form = this.form,
            variations = this.variationData,
            checking = this.viwpvs_form_id;
        form.data('viwpvs_form_id',checking);
        if (variations && form.find('.vi-wpvs-option-wrap.vi-wpvs-option-wrap-selected').length && form.find('.vi-wpvs-option-wrap.vi-wpvs-option-wrap-selected').length === form.find('.vi-wpvs-select-attribute select').length) {
            form.addClass('vi_wpvs_variation_form_has_selected');
            form.on('hide_variation viwpvs_hide_variation tc_hide_variation', function () {
                if (form.hasClass('vi_wpvs_variation_form_has_selected')) {
                    form.removeClass('vi_wpvs_variation_form_has_selected');
                    viwpvs_frontend.hide_variation();
                }
            });
        }
        form.on('woocommerce_update_variation_values tc_update_variation_values', function (e) {
            viwpvs_frontend.select_variation_item();
        });
        viwpvs_frontend.design_variation_item();
        if (form.find('.vi-wpvs-variation-wrap-select-wrap').length) {
            form.find('.vi-wpvs-variation-wrap-select-wrap').each(function (k, item) {
                if (!viwpvs_frontend.is_item_in_form(item,checking)){
                    return true;
                }
                $(item).parent().parent().parent().css({width: '100%'});
                let select_wrap, select_button;
                select_wrap = $(item).find('.vi-wpvs-variation-wrap-option');
                if (!select_wrap.attr('data-offset_height')) {
                    select_wrap.attr('data-offset_height', select_wrap.outerHeight()).removeClass('vi-wpvs-select-hidden').addClass('vi-wpvs-hidden');
                }
                select_button = $(item).find('.vi-wpvs-variation-button-select');
                if (select_wrap.find('.vi-wpvs-option-wrap-selected').length) {
                    select_button.find('span').html(select_wrap.find('.vi-wpvs-option-wrap-selected .vi-wpvs-option-select').html());
                }
                select_button.on('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (select_wrap.hasClass('vi-wpvs-variation-wrap-option-render')) {
                        return;
                    }
                    if (select_wrap.hasClass('vi-wpvs-variation-wrap-option-show')) {
                        $('.vi-wpvs-variation-wrap-option-available').remove();
                        select_wrap.removeClass('vi-wpvs-variation-wrap-option-show')
                    } else {
                        select_wrap.addClass('vi-wpvs-variation-wrap-option-render');
                        let select_wrap_height, scroll_top, window_height, view_able_offset;
                        select_wrap_height = parseFloat(select_wrap.attr('data-offset_height'));
                        scroll_top = $(window).scrollTop();
                        window_height = $(window).outerHeight();
                        view_able_offset = $(this).offset().top - scroll_top;
                        $('.vi-wpvs-variation-wrap-option.vi-wpvs-variation-wrap-option-show').removeClass('vi-wpvs-variation-wrap-option-show');
                        select_wrap.addClass('vi-wpvs-variation-wrap-option-show');
                        $('.vi-wpvs-variation-wrap-option-available').remove();
                        let new_select = $(item).closest('.vi-wpvs-variation-wrap').clone();
                        new_select.find('.vi-wpvs-variation-button-select').remove();
                        new_select.find('.vi-wpvs-variation-wrap-option').removeClass('vi-wpvs-hidden vi-wpvs-variation-wrap-option-show');
                        new_select.addClass('vi-wpvs-variation-wrap-option-available').css({
                            width: $(this).outerWidth(),
                            left: $(this).offset().left
                        });
                        if (scroll_top > view_able_offset || scroll_top < select_wrap_height || window_height > (view_able_offset + select_wrap_height + 40)) {
                            new_select.toggleClass('vi-wpvs-variation-wrap-select-bottom');
                            new_select.css({top: ($(this).offset().top + $(this).outerHeight())});
                        } else {
                            new_select.toggleClass('vi-wpvs-variation-wrap-select-top');
                            new_select.css({top: ($(this).offset().top - select_wrap.outerHeight())});
                        }
                        setTimeout(function (select_wrap){
                            select_wrap.removeClass('vi-wpvs-variation-wrap-option-render');
                        },500,select_wrap)
                        $('body').append(new_select);
                    }
                });
            });
        }
        form.find('.vi-wpvs-option-wrap').each(function (k, item) {
            if (!viwpvs_frontend.is_item_in_form(item,checking)){
                return true;
            }
            let attr_div, attr_select, attr_value, val;
            attr_div = $(item).closest('.vi-wpvs-variation-wrap-wrap');
            attr_select = attr_div.find('select.vi-wpvs-select-attribute');
            if (attr_select.length === 0) {
                attr_select = attr_div.find('.vi-wpvs-select-attribute select').eq(0);
            }
            attr_select.find('option').removeClass('vi-wpvs-option-disabled');
            $(item).on('mouseenter', function () {
                if (!$(this).hasClass('vi-wpvs-option-wrap-selected') && !$(this).hasClass('vi-wpvs-option-wrap-disable')) {
                    $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-hover');
                }
            }).on('mouseleave', function () {
                if (!$(this).hasClass('vi-wpvs-option-wrap-selected') && !$(this).hasClass('vi-wpvs-option-wrap-disable')) {
                    $(this).removeClass('vi-wpvs-option-wrap-hover').addClass('vi-wpvs-option-wrap-default');
                }
            }).on('click', function (e) {
                e.stopPropagation();
                if ($(this).hasClass('vi-wpvs-option-wrap-disable')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                if (!$(this).parent().hasClass('vi-wpvs-variation-wrap-radio')) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                $('.vi-wpvs-variation-wrap-option').addClass('vi-wpvs-hidden');
                form.find('.reset_variations').removeClass('vi-wpvs-hidden');
                attr_div.find('.vi-wpvs-option-wrap').removeClass('vi-wpvs-option-wrap-selected vi-wpvs-option-wrap-hover').addClass('vi-wpvs-option-wrap-default');
                if (attr_div.find('.vi-wpvs-variation-wrap').hasClass('vi-wpvs-variation-wrap-select')) {
                    attr_div.find('.vi-wpvs-variation-button-select >span ').html($(this).find('.vi-wpvs-option-select').html());
                }
                if ($(this).find('.vi-wpvs-option-radio').length > 0) {
                    attr_div.find('.vi-wpvs-option-radio').prop('checked', false);
                    $(this).find('.vi-wpvs-option-radio').prop('checked', true);
                    $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-selected');
                }
                attr_value = viwpvs_to_string(attr_select.val());
                val = viwpvs_to_string($(this).data('attribute_value'));
                if (val !== attr_value) {
                    $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-selected');
                    attr_select.val(val).trigger('change');
                } else if (!$(this).parent().hasClass('vi-wpvs-variation-wrap-radio')) {
                    if (form.hasClass('vi_wpvs_loop_variation_form')) {
                        if (form.data('wpvs_double_click')) {
                            attr_select.val('').trigger('change');
                        } else {
                            $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-selected');
                        }
                    } else {
                        if (attr_div.data('wpvs_double_click')) {
                            attr_select.val('').trigger('change');
                        } else {
                            $(this).removeClass('vi-wpvs-option-wrap-default').addClass('vi-wpvs-option-wrap-selected');
                        }
                    }
                }
                e.stopPropagation();
            });
        });
        form.find('select:not(.vi-wpvs-select-attribute):not(.vi-wpvs-variation-style-select)').on('change', function () {
            setTimeout(function () {
                viwpvs_frontend.select_variation_item();
            }, 500);
        });
        form.find('.reset_variations,.tc-epo-element-variable-reset-variations').on('click', function () {
            viwpvs_frontend.select_variation_item();
            viwpvs_frontend.hide_variation();
        });
    };
    viwpvs_frontend.prototype.design_variation_item = function () {
        let form = this.form, checking = this.viwpvs_form_id,self=this;
        form.find('.vi-wpvs-variation-wrap-wrap').each(function (k,v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            let $wrap = $(this), variation_wrap = $wrap.parent().parent();
            $wrap.parent().addClass('vi-wpvs-variation-style-content');
            $wrap.find(`div.vi-wpvs-select-attribute select[data-attribute_name="${$wrap.data('wpvs_attribute_name')}"]`).addClass('vi-wpvs-select-attribute');
            /*Compatible with wjecf_wc_discounts*/
            if (!vi_wpvs_frontend_param.wjecf_wc_discounts || !vi_wpvs_frontend_param.is_checkout) {
                variation_wrap.addClass($wrap.data('display_type'));
            }
            if (!$wrap.data('wpvs_attr_title')) {
                variation_wrap.find('.label').addClass('vi-wpvs-hidden');
            }
        });
        form.find('.vi-wpvs-option.vi-wpvs-option-color').each(function (color_item_k, color_item) {
            if (!self.is_item_in_form(color_item,checking)){
                return true;
            }
            let colors = $(color_item).data('option_color');
            $(color_item).css({background: colors});
        });
        form.find('.vi-wpvs-variation-wrap-wrap').each(function (k, v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            $(v).removeClass('vi-wpvs-hidden');
        });
    };
    viwpvs_frontend.prototype.select_variation_item = function () {
        let form = this.form;
        let product_variations = this.variationData, checking = this.viwpvs_form_id, self= this;
        form.find('.vi-wpvs-label-selected').each(function (k, v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            $(v).addClass('vi-wpvs-hidden');
        })
        form.find('.vi-wpvs-option-wrap-out-of-stock').each(function (k, v) {
            if (!self.is_item_in_form(v,checking)){
                return true;
            }
            $(v).removeClass('vi-wpvs-option-wrap-out-of-stock');
        })
        form.find('.vi-wpvs-variation-wrap-wrap').each(function (k, v) {
            if (!self.is_item_in_form(v,checking)){
                return true;
            }
            let $wrap = $(v);
            if ($wrap.data('hide_outofstock')) {
                let attrs_value = $(v).find('select option:not(.vi-wpvs-option-disabled)').map(function () {
                    return $(this).val();
                });
                $(v).find('.vi-wpvs-option-wrap:not(.vi-wpvs-product-link)').each(function (option_item_k, option_item) {
                    let val = viwpvs_to_string($(option_item).data('attribute_value'));
                    if ($.inArray(val, attrs_value) > -1) {
                        $(option_item).removeClass('vi-wpvs-option-wrap-disable');
                    } else {
                        $(option_item).removeClass('vi-wpvs-option-wrap-selected').addClass('vi-wpvs-option-wrap-default vi-wpvs-option-wrap-disable');
                    }
                });
            } else {
                let attrs_value = $(v).find('select option:not(.vi-wpvs-option-disabled)').map(function () {
                    return $(this).val();
                });
                $(v).find('.vi-wpvs-option-wrap:not(.vi-wpvs-product-link)').each(function (option_item_k, option_item) {
                    let val = viwpvs_to_string($(option_item).data('attribute_value'));
                    if ($.inArray(val, attrs_value) > -1) {
                        $(option_item).removeClass('vi-wpvs-hidden');
                    } else {
                        $(option_item).removeClass('vi-wpvs-option-wrap-selected').addClass('vi-wpvs-option-wrap-default vi-wpvs-hidden');
                    }
                });
                if (product_variations) {
                    let $current_select = $wrap.data('swatch_type') === 'viwpvs_default' ? $wrap.find(`select[name="${$wrap.data('wpvs_attribute_name')}"]`) : $wrap.find('select.vi-wpvs-select-attribute');
                    let attribute_name = viwpvs_to_string($current_select.data('attribute_name'));
                    let attribute_value = $current_select.val();
                    if (!$wrap.hasClass('vi-wpvs-option-wrap-out-of-stock-attribute-checked')) {
                        let $container = $wrap.find(`.vi-wpvs-variation-wrap`);
                        $container.find('.vi-wpvs-option-wrap').each(function () {
                            let $option = $(this), attr_value = viwpvs_to_string($option.data('attribute_value'));
                            if (attr_value) {
                                let v_count = 0,
                                    v_out = 0;
                                for (let product_variation_k in product_variations) {
                                    if (product_variations.hasOwnProperty(product_variation_k)) {
                                        let product_variation = product_variations[product_variation_k];
                                        if (product_variation['attributes'] !== null && product_variation['attributes'] !== undefined) {
                                            if (product_variation['attributes'][attribute_name] === '') {
                                                v_count++;
                                                if ((product_variation.hasOwnProperty('is_purchasable') && !product_variation.is_purchasable) || (product_variation.hasOwnProperty('is_in_stock') && !product_variation.is_in_stock) || product_variation.hasOwnProperty('viwpvs_not_available')) {
                                                    v_out++;
                                                }
                                            } else if (viwpvs_to_string(product_variation['attributes'][attribute_name]) === attr_value) {
                                                v_count++;
                                                if (product_variation.hasOwnProperty('viwpvs_not_available')) {
                                                    v_out++;
                                                }
                                            }
                                        }
                                    }
                                }
                                if (v_count === v_out) {
                                    $option.addClass('vi-wpvs-option-wrap-out-of-stock-attribute');
                                }
                            }
                        });
                        $wrap.addClass('vi-wpvs-option-wrap-out-of-stock-attribute-checked');
                    }
                    if (attribute_value) {
                        for (let product_variation_k in product_variations) {
                            if (product_variations.hasOwnProperty(product_variation_k)) {
                                let product_variation = product_variations[product_variation_k];
                                if (product_variation['attributes'][attribute_name] === attribute_value && product_variation.hasOwnProperty('viwpvs_not_available')) {
                                    for (let attr_name in product_variation['attributes']) {
                                        let attr_value = product_variation['attributes'][attr_name];
                                        if (attr_name !== attribute_name) {
                                            let $container = form.find(`.vi-wpvs-variation-wrap[data-attribute="${attr_name}"]`);
                                            $container.find('.vi-wpvs-option-wrap').each(function () {
                                                let $current_option = $(this);
                                                if (!$current_option.hasClass('vi-wpvs-option-wrap-out-of-stock-attribute') && !$current_option.hasClass('vi-wpvs-option-wrap-out-of-stock') && viwpvs_to_string($current_option.data('attribute_value')) === attr_value) {
                                                    let maybe_outofstock = true;
                                                    for (let product_variation_k in product_variations) {
                                                        if (product_variations.hasOwnProperty(product_variation_k)) {
                                                            let product_variation = product_variations[product_variation_k];
                                                            // console.log(`["${attribute_name}"=>"${attr_value}","${attr_name}"=>"${$current_option.data('attribute_value')}"]`);
                                                            if (product_variation['attributes'][attribute_name] === attribute_value && product_variation['attributes'][attr_name] === viwpvs_to_string($current_option.data('attribute_value')) && !product_variation.hasOwnProperty('viwpvs_not_available')) {
                                                                maybe_outofstock = false;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if (maybe_outofstock) {
                                                        $current_option.addClass('vi-wpvs-option-wrap-out-of-stock');
                                                    }
                                                    return false;
                                                }
                                            });
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($(v).data('show_selected_item') && $(v).find('.vi-wpvs-option-wrap-selected').length && $(v).find('.vi-wpvs-option-wrap-selected').data('attribute_label')) {
                if ($(v).parent().parent().find('.vi-wpvs-label-selected').length) {
                    $(v).parent().parent().find('.vi-wpvs-label-selected.vi-wpvs-label-selected-title').html($(v).find('.vi-wpvs-option-wrap-selected').data('attribute_label'));
                    $(v).parent().parent().find('.vi-wpvs-label-selected').removeClass('vi-wpvs-hidden');
                } else {
                    let $append_wrap = $(v).parent().parent().find('.label');
                    if (!$append_wrap.length) {
                        $append_wrap = $(v).parent().parent().find('label');
                    }
                    if ($append_wrap.length) {
                        if (vi_wpvs_frontend_param?.single_attr_selected_separator){
                            $append_wrap.append('<span class="vi-wpvs-label-selected vi-wpvs-label-selected-separator">' + vi_wpvs_frontend_param.single_attr_selected_separator + '</span>')
                        }
                        $append_wrap.append('<span class="vi-wpvs-label-selected vi-wpvs-label-selected-title">' +  $(v).find('.vi-wpvs-option-wrap-selected').data('attribute_label') + '</span>');
                        $append_wrap.css({
                            display: 'inline-flex',
                            flexWrap: 'wrap',
                            alignItems: 'center',
                        });
                        $append_wrap.find(' *').css({
                            margin: '0',
                        });
                        $append_wrap.find('.vi-wpvs-label-selected-title').css({
                            margin: '0 5px',
                        });
                    }
                }
            }
        });
    };
    viwpvs_frontend.prototype.hide_variation = function () {
        let form = this.form,checking =this.viwpvs_form_id,self=this;
        form.find('.reset_variations,.vi-wpvs-variation-wrap-option,.vi-wpvs-variation-style .vi-wpvs-label-selected').each(function (k, v) {
            if (!self.is_item_in_form(v,checking)){
                return true;
            }
            $(v).addClass('vi-wpvs-hidden');
        });
        form.find('.vi-wpvs-option-wrap').each(function (k, v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            $(v).removeClass('vi-wpvs-option-wrap-selected vi-wpvs-option-wrap-out-of-stock').addClass('vi-wpvs-option-wrap-default')
        });
        form.find('.vi-wpvs-option-radio').each(function (k, v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            $(v).prop('checked', false)
        });
        form.find('.vi-wpvs-variation-button-select >span ').each(function (k, v) {
            if (!self.is_item_in_form(v,checking) ){
                return true;
            }
            $(v).html(form.find('.vi-wpvs-option-select:first-child').html());
        });
    };
    viwpvs_frontend.prototype.is_item_in_form=function (item,form_id){
        if ($(item).closest('.composited_product_details_wrapper').length && $(item).closest('.variations_form').data('viwpvs_form_id') == form_id){
            return true;
        }
        if ($(item).closest('.tc-epo-element-product-container').length && $(item).closest('.tc-epo-element-product-container').data('viwpvs_form_id') == form_id){
            return true;
        }
        if ($(item).closest('.vi_wpvs_variation_form').length && $(item).closest('.vi_wpvs_variation_form').data('viwpvs_form_id') != form_id){
            return false;
        }
        if ($(item).closest('.variations_form').length && $(item).closest('.variations_form').data('viwpvs_form_id') != form_id){
            return false;
        }
        return true;
    }
    $.fn.viwpvs_woo_product_variation_swatches = function () {
        new viwpvs_frontend(this);
        return this;
    };

    window.viwpvs_to_string = function (str) {
        return str ? str.toString() : '';
    }
}(jQuery));