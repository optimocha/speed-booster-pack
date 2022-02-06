jQuery(function() {
    var $ = jQuery;
    $(document).ready(function() {
        if (introJs != undefined) {
            introJs().setOptions({
                exitOnOverlayClick: false,
                showProgress: true,
                showBullets: false,
                nextLabel: sbp_intro_translations.nextLabel,
                prevLabel: sbp_intro_translations.prevLabel,
                doneLabel: sbp_intro_translations.doneLabel,
                steps: [{
                    intro: sbp_intro_translations.welcome
                }, {
                    element: document.querySelector('.csf-tab-item:nth-child(3)'),
                    title: sbp_intro_translations.cachingTitle,
                    intro: sbp_intro_translations.caching,
                    position: 'right'
                }, {
                    element: document.querySelector('.module-caching'),
                    title: sbp_intro_translations.caching2Title,
                    intro: sbp_intro_translations.caching2,
                    position: 'bottom'
                }, {
                    element: document.querySelector('.csf-tab-item:nth-child(2)'),
                    title: sbp_intro_translations.generalTitle,
                    intro: sbp_intro_translations.general,
                    position: 'right'
                }, {
                    element: document.querySelector('.csf-tab-item:nth-child(4)'),
                    title: sbp_intro_translations.cdnTitle,
                    intro: sbp_intro_translations.cdn,
                    position: 'right'
                }, {
                    element: document.querySelector('.csf-tab-item:nth-child(5)'),
                    title: sbp_intro_translations.cssTitle,
                    intro: sbp_intro_translations.css,
                    position: 'right'
                }, {
                    element: document.querySelector('.csf-tab-item:nth-child(6)'),
                    title: sbp_intro_translations.assetsTitle,
                    intro: sbp_intro_translations.assets,
                    position: 'right'
                }, {
                    title: sbp_intro_translations.endTitle,
                    intro: sbp_intro_translations.end
                }]
            }).start().onbeforechange(async function(targetElement) {
                if ($(targetElement).is('.csf-tab-item:nth-child(3)') || $(targetElement).is('.module-caching')) {
                    window.location.hash = $('.csf-tab-item:nth-child(3) > a').attr('href');
                }

                if ($(targetElement).is('.csf-tab-item:nth-child(2)')) {
                    window.location.hash = $('.csf-tab-item:nth-child(2) > a').attr('href');
                }

                if ($(targetElement).is('.csf-tab-item:nth-child(4)')) {
                    window.location.hash = $('.csf-tab-item:nth-child(4) > a').attr('href');
                }

                if ($(targetElement).is('.csf-tab-item:nth-child(5)')) {
                    window.location.hash = $('.csf-tab-item:nth-child(5) > a').attr('href');
                }

                if ($(targetElement).is('.csf-tab-item:nth-child(6)')) {
                    window.location.hash = $('.csf-tab-item:nth-child(6) > a').attr('href');
                }

            }).onexit(function() {
                $.ajax({
                    url: ajaxurl,
                    data: {'action': 'sbp_dismiss_intro'}
                });
            });
        }
    })
})