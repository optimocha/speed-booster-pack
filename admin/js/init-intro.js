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
                    element: document.querySelector('[data-tab-id="caching"]'),
                    intro: sbp_intro_translations.caching,
                    position: 'right'
                }, {
                    element: document.querySelector('.module-caching'),
                    intro: sbp_intro_translations.caching2,
                    position: 'bottom'
                }, {
                    element: document.querySelector('[data-tab-id="general"]'),
                    intro: sbp_intro_translations.general,
                    position: 'right'
                }, {
                    element: document.querySelector('[data-tab-id="cdn-proxy"]'),
                    intro: sbp_intro_translations.cdn,
                    position: 'right'
                }, {
                    element: document.querySelector('[data-tab-id="optimize-css"]'),
                    intro: sbp_intro_translations.css,
                    position: 'right'
                }, {
                    element: document.querySelector('[data-tab-id="assets"]'),
                    intro: sbp_intro_translations.assets,
                    position: 'right'
                }, {
                    intro: sbp_intro_translations.end
                }]
            }).start().onbeforechange(function(targetElement) {

                if ($(targetElement).is('[data-tab-id="caching"]') || $(targetElement).is('.module-caching')) {
                    window.location.hash = '#tab=caching';
                }

                if ($(targetElement).is('[data-tab-id="general"]')) {
                    window.location.hash = '#tab=general';
                }

                if ($(targetElement).is('[data-tab-id="cdn-proxy"]')) {
                    window.location.hash = '#tab=cdn-proxy';
                }

                if ($(targetElement).is('[data-tab-id="optimize-css"]')) {
                    window.location.hash = '#tab=optimize-css';
                }

                if ($(targetElement).is('[data-tab-id="assets"]')) {
                    window.location.hash = '#tab=assets';
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