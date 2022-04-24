(function ($) {
    $(document).ready(function () {
        var targetElement = 'tr[data-slug="speed-booster-pack"] span.deactivate a';
        var redirectUrl = $(targetElement).attr('href');

        $(document).on('click', targetElement, function(e) {
            e.preventDefault();
            $('.sbp-deactivation-survey').stop().css({opacity: 1, visibility: 'visible'});
        });

        $(document).on('click', '.cancel-deactivation-survey', function(e) {
            e.preventDefault();
            $('.sbp-deactivation-survey').stop().css({opacity: 0});
            setTimeout(function() {
                $('.sbp-deactivation-survey').stop().css({visibility: 'hidden'});
            }, 350)
            $('.sbp-deactivation-survey form input[type=radio]').prop('checked', false);
        })

        $(document).on('click', '.deactivate-plugin', function(e) {
            e.preventDefault();
            window.location.href = redirectUrl;
        })

        $(document).on('change', 'input[name=sbp_reason]', function(e) {
            if (e.target.value !== 'other' && e.target.value) {
                $('.submit-and-deactivate').removeAttr('disabled');
            } else {
                $('.submit-and-deactivate').attr('disabled', 'disabled');
            }
        })

        $(document).on('keyup', '[name=sbp_deactivation_description]', function(e) {
            if (e.target.value !== '') {
                $('.submit-and-deactivate').removeAttr('disabled');
            } else {
                $('.submit-and-deactivate').attr('disabled', 'disabled');
            }
        })

        $(document).on('click', '.submit-and-deactivate', function(e) {
            e.preventDefault();
            var reason = $.trim($('input[name=sbp_reason]:checked').val());
            var description = $.trim($('textarea[name=sbp_deactivation_description]').val());
            var share_email = $('[name=sbp_reply]').prop('checked');
            var email = $('[name=sbp_reply_email]').val();
            var version = $('[name=sbp_version]').val();

            if (reason === 'other' && !description) {
                alert('Please fill the description.');
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'https://speedboosterpack.com/wp-json/sbp_survey/v1/vote',
                data: {
                    reason: reason,
                    description: description,
                    site_url: $('input[name=sbp_site_url]').val(),
                    email: share_email ? email : '',
                    version: version,
                },
                success: function (response) {
                    console.log('RESPONSE', response);
                },
                complete: function() {
                    window.location.href = redirectUrl;
                }
            })
        })

        $(document).on('change', '[name=sbp_reply]', function() {
            var $target = $('[name=sbp_reply]');

            $('[name=sbp_reply_email]').fadeToggle('100');
        })
    });
})(jQuery);
