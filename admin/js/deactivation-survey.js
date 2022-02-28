(function ($) {
    $(document).ready(function () {
        var targetElement = 'tr[data-slug="speed-booster-pack"] span.deactivate a';
        var redirectUrl = $(targetElement).attr('href');

        $(document).on('click', targetElement, function(e) {
            e.preventDefault();
            $('.sbp-deactivation-survey').stop().css({opacity: 1, visibility: 'visible'});
        });

        $(document).on('click', '.cancel-deactivation-survey', function() {
            $('.sbp-deactivation-survey').stop().css({opacity: 0});
            setTimeout(function() {
                $('.sbp-deactivation-survey').stop().css({visibility: 'hidden'});
            }, 350)
            $('.sbp-deactivation-survey form input[type=radio]').prop('checked', false);
        })

        $(document).on('click', '.deactivate-plugin', function() {
            window.location.href = redirectUrl;
        })
    });
})(jQuery);
