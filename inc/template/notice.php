<div class='notice notice-warning' id='sbp-news' style="padding-top: 7px">
    <div style="float:right;"><a href="javascript:dismissNews()" class="button" style="margin-top:10px;"><?php _e('Dismiss','sb-pack');?></a></div>
    <strong><?php _e('Speed Booster Pack','sb-pack');?></strong>
    <p><?php printf(__('Check out the %s Plugin settings %s for new features that can make your site load faster.','sb-pack'), '<a href="options-general.php?page=sbp-options">', '</a>');?></p>
</div>
<script>
    function dismissNews() {
        jQuery("#sbp-news").hide();
        var data = { action  : 'sbp_dismiss_notices'};
        jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) {
            data = JSON.parse(response);
            if(data["Status"] == 0) {
                console.log("dismissed");
            }
        });
    }
</script>
