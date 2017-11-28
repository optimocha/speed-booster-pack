if (typeof (jQuery) != 'undefined') {
    jQuery(document).ready(function () {
        validate();
        jQuery('input').change(function () {
            validate();
        })
    });

    function validate() {
        if (jQuery('input[id=sbp_css_async]').is(':checked')) {
            jQuery('#sbp-css-content').show();
        } else {
            jQuery('#sbp-css-content').hide();
        }
    }
}