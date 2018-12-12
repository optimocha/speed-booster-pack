<div class="notice sbp-notice" id="sbp-notice">
	<img src="<?php echo esc_url( plugins_url( 'images/logo.png', dirname( __FILE__ ) ) ); ?>" width="80">

	<h1><?php esc_html_e( 'Welcome to Speed Booster Pack', 'speed-booster-pack' ); ?></h1>

	<p><?php printf( esc_html__( 'Welcome! Thank you for installing Speed Booster Pack! Check out the %sPlugin settings%s for new features that can make your site load faster.', 'speed-booster-pack' ), '<a href="admin.php?page=sbp-options">', '</a>' ); ?></p>

	<p><a href="admin.php?page=sbp-options" class="button button-primary button-hero"><?php esc_html_e( 'Get started with Speed Booster Pack', 'speed-booster-pack' ); ?></a></p>

	<button type="button" onclick="sbp_dismissNotice();" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'speed-booster-pack' ); ?></span></button>
</div>

<style>
	.sbp-notice {
		background: #e9eff3;
		border: 10px solid #fff;
		color: #608299;
		padding: 30px;
		text-align: center;
		position: relative;
	}
</style>

<script>
	function sbp_dismissNotice() {
		jQuery("#sbp-notice").hide();
		var data = { action  : 'sbp_dismiss_notices'};
		jQuery.get('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function(response) {
			data = JSON.parse(response);
			if(data["Status"] == 0) {
				console.log("dismissed");
			}
		});
	}
</script>
