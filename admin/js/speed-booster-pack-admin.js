(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    $(window).on('load', function() {
        $('span .sbp-cloudflare-test').attr('disabled', 'disabled').css('opacity', '0.6');
    });

    $(document).on('click', '.sbp-cloudflare-test', function (e) {
        e.preventDefault();
        $('.sbp-cloudflare-info-text').hide();
        $('.sbp-cloudflare-test .sbp-cloudflare-spinner').show();
        $(e.target).attr('disabled', 'disabled').css('opacity', '0.6');
        $('.sbp-cloudflare-incorrect, .sbp-cloudflare-correct').hide();

        const api_key = $('[data-depend-id="cloudflare_api"]').val();
        const email = $('[data-depend-id="cloudflare_email"]').val();
        const zone_id = $('[data-depend-id="cloudflare_zone"]').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {action: "sbp_check_cloudflare", api_key: api_key, email: email, zone_id: zone_id},
            success: function (response) {
                response = JSON.parse(response);
                if (response.status === 'true') {
                    $('.sbp-cloudflare-correct').show();
                } else if (response.status === 'false') {
                    $('.sbp-cloudflare-incorrect').show();
                } else {
                    $('.sbp-cloudflare-connection-issue').show();
                }
            },
            complete: function () {
                $('.sbp-cloudflare-test .sbp-cloudflare-spinner').hide();
                $(e.target).removeAttr('disabled').css('opacity', '1');
            }
        });
    });

    $.changeInputs = function (match, response, parent = null) {
        let value = '';
        const $field = $('[data-depend-id="' + match.field + '"]');
        if (match.type === 'switcher') {
            if (parent !== null) {
                value = response.results[parent].value[match.id] === 'on' ? '1' : '';
            } else {
                value = response.results[match.id].value === 'on' ? '1' : '';
            }
            $field.val(value);
            if (value === '1') {
                $field.parent().addClass('csf--active');
            } else {
                $field.parent().removeClass('csf--active');
            }
        }

        if (match.type === 'text') {
            if (parent === null) {
                value = response.results[match.id].value;
            } else {
                value = response.results[parent].value[match.id];
            }
            $field.val(value);
        }

        if (match.type === 'array') {
            match.matches.map(function (item) {
                $.changeInputs(item, response, match.id);
            });
        }

        $('.with-preloader').removeClass('with-preloader');
    };

    $.checkCloudflareSettings = function () {
        const id_field_matches = [
            {
                id: 'rocket_loader',
                field: 'cf_rocket_loader_enable',
                type: 'switcher',
            },
            {
                id: 'development_mode',
                field: 'cf_dev_mode_enable',
                type: 'switcher',
            },
            {
                id: 'minify',
                type: 'array',
                matches: [
                    {
                        id: 'css',
                        field: 'cf_css_minify_enable',
                        type: 'switcher',
                    },
                    {
                        id: 'html',
                        field: 'cf_html_minify_enable',
                        type: 'switcher',
                    },
                    {
                        id: 'js',
                        field: 'cf_js_minify_enable',
                        type: 'switcher',
                    },
                ]
            },
            {
                id: 'browser_cache_ttl',
                field: 'cf_browser_cache_ttl',
                type: 'text',
            },
        ];

        $.ajax({
            url: ajaxurl,
            type: 'GET',
            data: {action: 'sbp_get_cloudflare_settings'},
            success: function (response) {
                response = JSON.parse(response);
                if (response.status === 'success') {
                    if (_.size(response.results) > 0) {
                        id_field_matches.map(function (match) {
                            $.changeInputs(match, response);
                        });
                        $('.with-preloader').show();
                    }
                } else if (response.status === 'empty_info') {
                    $('.sbp-cloudflare-warning').show();
                } else {

                    $('.sbp-cloudflare-incorrect').show();
                    $('.with-preloader::before, .with-preloader::after').remove();
                }
            },
            complete: function() {
                $('.sbp-cloudflare-test .sbp-cloudflare-spinner').hide();
                $('.sbp-cloudflare-test').removeAttr('disabled').css('opacity', 1);
                $('.sbp-cloudflare-fetching').remove();
            }
        });
    };

    let hasCloudflareChecked = false;

    // $(document).on('change', '[data-depend-id="cloudflare_enable"]', function () {
    //     $.checkCloudflareSettings();
    //
    //     hasCloudflareChecked = true;
    // });

    $(window).on('hashchange csf.hashchange', function () {

        if (hasCloudflareChecked === false) {
            var hash = window.location.hash.replace('#tab=', '');

            if (hash === 'cdn-proxy') {
                $.checkCloudflareSettings();

                hasCloudflareChecked = true;
            }
        }

    });

})(jQuery);