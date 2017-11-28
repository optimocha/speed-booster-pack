jQuery(document).ready(function () {
    jQuery(".sbp-slider").slider({
        value: jpegCompression,
        min: 0,
        max: 100,
        step: 1,
        slide: function (event, ui) {
            jQuery(".sbp-amount").val(ui.value);
            jQuery("#sbp_integer").val(ui.value);
        }
    });
    jQuery(".sbp-amount").val(jQuery(".sbp-slider").slider("value"));
});