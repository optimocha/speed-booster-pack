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
	    // Check timestamp
        $display_time = get_user_meta( get_current_user_id(), 'sbp_newsletter_display_time', true );
        if ( ! $display_time ) {
            $display_time = strtotime('+1 day');
            update_user_meta( get_current_user_id(), 'sbp_newsletter_display_time', $display_time );
        }

        if ( time() > $display_time && current_user_can( 'manage_options' ) && ! get_user_meta( get_current_user_id(), 'sbp_hide_newsletter_pointer', true ) ) {
            wp_enqueue_style( 'wp-pointer' );
            wp_enqueue_script( 'wp-pointer' );
            add_action( 'admin_print_footer_scripts', [ $this, 'my_admin_print_footer_scripts' ] );
        }
	}

	function my_admin_print_footer_scripts() {
		$current_user    = wp_get_current_user();
		$pointer_content = '<h3>' . SBP_PLUGIN_NAME . '</h3>';
		$pointer_content .= '<p>' . __( 'Subscribe to our newsletter with <strong>tens of thousands of users</strong>, and get infrequent email updates from our plugin <em>and</em> more performance tips &amp; tricks!', 'speed-booster-pack' ) . '</p>';
		$pointer_content .= '<p class="sbp-subscription"><a href="https://speedboosterpack.com/go/subscribe" rel="external noopener" target="_blank" class="sbp-subscribe-button">' . __( 'Visit the subscription page', 'speed-booster-pack' ) . '</a></p>';
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

                $('.sbp-subscribe-button').on('click', function () {
                    $('.sbp-subscription').html('<div style="padding: 10px 20px; color: darkgreen;" class="sbp-newsletter-success"><?php _e( 'Thank you! ❤', 'speed-booster-pack' ) ?></div>');
                    $.post(ajaxurl, {
                        action: 'sbp_hide_newsletter_pointer'
                    });
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