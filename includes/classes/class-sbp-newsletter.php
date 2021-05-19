<?php

namespace SpeedBooster;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SBP_Newsletter {
	public function __construct() {
		// Only admins can view SpeedBoosterPack things.
		add_action( 'admin_enqueue_scripts', [ $this, 'my_admin_enqueue_scripts' ] );
		add_action( 'wp_ajax_sbp_hide_newsletter_pointer', [ $this, 'hide_newsletter_pointer' ] );
	}

	function my_admin_enqueue_scripts() {
		if ( current_user_can( 'manage_options' ) && ! get_user_meta( get_current_user_id(), 'sbp_hide_newsletter_pointer', true ) ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			add_action( 'admin_print_footer_scripts', [ $this, 'my_admin_print_footer_scripts' ] );
		}
	}

	function my_admin_print_footer_scripts() {
		$current_user    = wp_get_current_user();
		$pointer_content = sprintf( __( '<h3>%s</h3>', 'speed-booster-pack' ), SBP_PLUGIN_NAME );
		$pointer_content .= sprintf( __( '<p>Subscribe to the <i>infrequent</i> newsletter of %s, with <b>tens of thousands of users</b>!</p>', 'speed-booster-pack' ), SBP_PLUGIN_NAME );
		$pointer_content .= '
<div id="sbp-subscription-form">
    <div id="revue-embed">
        <form action="https://www.getrevue.co/profile/optimocha/add_subscriber" method="post" id="revue-form" name="revue-form"  target="_blank">
            <div class="revue-form-group">
                <label for="member_email">Email address</label>
                <input class="revue-form-field" placeholder="Your email address..." type="email" name="member[email]" id="member_email" value="' . $current_user->user_email . '">
            </div>
            <div class="revue-form-actions">
                <input type="submit" value="Subscribe" name="member[subscribe]" id="member_submit">
            </div>
            <div class="revue-form-footer">By subscribing, you agree with Revue’s <a target="_blank" href="https://www.getrevue.co/terms">Terms</a> and <a target="_blank" href="https://www.getrevue.co/privacy">Privacy Policy</a>.</div>
        </form>
    </div>
</div>';
		$pointer_content = str_replace( PHP_EOL, '', $pointer_content );
		?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                $('#toplevel_page_sbp-settings').pointer({
                    content: '<?php echo $pointer_content; ?>',
                    position: {
                        edge: 'left',
                        align: 'center',
                    },
                    close: function () {
                        $.post(ajaxurl, {
                            action: 'sbp_hide_newsletter_pointer'
                        });
                    }
                }).pointer('open');

                $('#sbp-subscription-form #revue-form').on('submit', function () {
                    setTimeout(function () {
                        // B_TODO: Change Text
                        $('#sbp-subscription-form').html('<div style="padding: 10px 20px; color: darkgreen;" class="sbp-newsletter-success"><?php _e( 'Thank you for subscribing to our newsletter.', 'speed-booster-pack' ) ?></div>');
                        $.post(ajaxurl, {
                            action: 'sbp_hide_newsletter_pointer'
                        });
                    }, 3000);
                });
            });
            //]]>
        </script>
		<?php
	}

	public function hide_newsletter_pointer() {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'sbp_hide_newsletter_pointer' && current_user_can( 'manage_options' ) ) {
			update_user_meta( get_current_user_id(), 'sbp_hide_newsletter_pointer', '1' );
			echo json_encode( [ 'status' => 'success', 'message' => 'hidden' ] );
			wp_die();
		}
	}
}

?>