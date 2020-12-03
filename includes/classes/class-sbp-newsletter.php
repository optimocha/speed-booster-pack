<?php

namespace SpeedBooster;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SBP_Newsletter {
	public function __construct() {
		// Only admins can view SpeedBoosterPack things.
		if ( ! get_transient( 'sbp_hide_newsletter_pointer' ) ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'my_admin_enqueue_scripts' ] );
		}

		add_action( 'wp_ajax_sbp_hide_newsletter_pointer', [ $this, 'hide_newsletter_pointer' ] );
	}

	function my_admin_enqueue_scripts() {
	    if ( current_user_can( 'manage_options' ) ) {
		    wp_enqueue_style( 'wp-pointer' );
		    wp_enqueue_script( 'wp-pointer' );
		    add_action( 'admin_print_footer_scripts', [ $this, 'my_admin_print_footer_scripts' ] );
	    }
	}

	function my_admin_print_footer_scripts() {
		$current_user    = wp_get_current_user();
		$pointer_content = sprintf( __( '<h3>%s</h3>', 'speed-booster-pack' ), SBP_PLUGIN_NAME );
		$pointer_content .= sprintf( __( '<p>If you want updates from %s, enter your email and hit the subscribe button!</p>\'+', 'speed-booster-pack' ), SBP_PLUGIN_NAME );
		$pointer_content .= '\'<div>\'+
            	\'<form method="POST" action="https://sendfox.com/form/104ezx/3o64jv" id="sbp-subscribe-newsletter-form">\'+
                    \'<div class="sbp-subscribe-content-wrapper">\'+
                        \'<div>\'+
                            \'<div class="mc-field-group" style="    margin-left: 15px;    width: 195px;    float: left;">\'+
                                \'<input type="text" name="first_name" class="form-control" placeholder="Name" hidden value="' . $current_user->display_name . '" style="display:none">\'+
                                \'<input type="text" value="' . $current_user->user_email . '" name="email" class="form-control" placeholder="Email*"  style="      width: 180px;    padding: 6px 5px;">\'+
                                \'<input type="hidden" name="ml-submit" value="1" />\'+
                            \'</div>\'+
                            \'<input type="submit" value="Subscribe" name="subscribe" id="sbp-newsletter-subscribe-button" class="button mc-newsletter-sent" style="background: #0085ba; border-color: #006799; padding: 0px 16px; text-shadow: 0 -1px 1px #006799,1px 0 1px #006799,0 1px 1px #006799,-1px 0 1px #006799; height: 40px; margin-top: 1px; color: #fff; box-shadow: 0 1px 0 #006799;">\'+
                        \'</div>\'+
                        \'<div style="padding: 20px;"><label><input type="checkbox" name="gdpr" value="1" required=""> <span>I agree to receive email updates and promotions.</span></label></div>\'+
                    \'</div>\'+
                    \'<div style="padding: 10px 20px; color: darkgreen; display: none;" class="sbp-newsletter-success">' . __( 'You have successfully subscribed to our newsletter.', 'speed-booster-pack' ) . '</div>\'+
            	\'</form>\'+
            \'</div>';
		?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                $('#toplevel_page_sbp-settings').pointer({
                    content: '<?php echo $pointer_content; ?>',
                    position: 'left',
                    close: function () {
                        $.post(ajaxurl, {
                            action: 'sbp_hide_newsletter_pointer'
                        });
                    }
                }).pointer('open');
            });
            //]]>
        </script>
		<?php
	}

	public function hide_newsletter_pointer() {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'sbp_hide_newsletter_pointer' && current_user_can( 'manage_options' ) ) {
			set_transient( 'sbp_hide_newsletter_pointer', '1' );
			echo json_encode( [ 'status' => 'success', 'message' => 'hidden' ] );
			wp_die();
		}
	}
}

?>