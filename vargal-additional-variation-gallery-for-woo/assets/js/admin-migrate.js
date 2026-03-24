jQuery(document).ready(function ($) {
    'use strict';
    $('.vi-ui.dropdown:not(.vargal-dropdown-init)').addClass('vargal-dropdown-init').off().dropdown({ fullTextSearch: true});
    $(document).on('click', '.vargal-button-migrate',function () {
        let $button = $(this),
            $container = $button.closest('.vi-ui.segment'),
            $step_2 = $container.find('.vargal-migrate-step-content-2'),
            $progress = $step_2.find('.vargal-migrate-progress');
        $container.find('.step').removeClass('active');
        $container.find('.step.vargal-migrate-step-2').removeClass('disabled').addClass('active');
        $container.find('.vargal-migrate-step-content').addClass('vargal-hidden');
        $step_2.removeClass('vargal-hidden');
        $progress.progress('set percent', 1);
        migrate_products(1, 1, $progress);
    });
    function migrate_products(page, max_page, $progress) {
        $.ajax({
            url: vargal_params.ajax_url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'vargal_migrate_gallery',
                step: 'migrate',
                _vargal_nonce: vargal_params.nonce,
                product_source_meta: $('#vargal-source').val(),
                page: page,
                max_page: max_page,
            },
            success: function (response) {
                switch (response.status) {
                    case 'success':
                        $progress.progress('set percent', parseInt(response.percent));
                        if (page < parseInt(response.page)) {
                            migrate_products(response.page, response.max_page, $progress);
                        } else {
                            $progress.progress('set label', response.message ? response.message : 'Completed').progress('complete');
                            if (response?.message) {
                                jQuery(document.body).trigger('villatheme_show_message',response.message, 'success', '', false, 5000);
                            }
                        }
                        break;
                    case 'retry':
                        migrate_products(response.page, response.max_page, $progress);
                        break;
                    default:
                        $progress.progress('set label', response.message ? response.message : vargal_params.i18n_error).progress('set error');
                }
            },
            error: function (err) {
                $progress.progress('set label', vargal_params.i18n_error).progress('set error');
            },
            complete: function () {

            }
        })
    }
});