jQuery(function() {
    var $ = jQuery;
    $(document).ready(function() {
        if (introJs != undefined) {
            // B_TODO: Add steps
            introJs().setOptions({
                steps: [{
                    intro: sbp_intro_translations.step1
                }, {
                    element: document.querySelector('[data-tab-id="caching"]'),
                    intro: sbp_intro_translations.step2
                }, {
                    element: document.querySelector('.module-caching'),
                    intro: sbp_intro_translations.step3
                }, {
                    element: document.querySelector('[data-tab-id="assets"]'),
                    intro: sbp_intro_translations.step4
                }, {
                    intro: sbp_intro_translations.step5
                }]
            }).start().onbeforechange(function(targetElement) {
                if ($(targetElement).is('[data-tab-id="caching"]') || $(targetElement).is('.module-caching')) {
                    window.location.hash = '#tab=caching';
                }

                if ($(targetElement).is('[data-tab-id="assets"]')) {
                    window.location.hash = '#tab=special';
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