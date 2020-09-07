<?php

namespace SpeedBooster;

class SBP_Notice_Manager {
	public function __construct() {
		add_action( 'wp_ajax_sbp_dismiss_notice', [ $this, 'dismiss_notice' ] );
		add_action( 'wp_ajax_sbp_remove_notice_transient', [ $this, 'remove_notice_transient' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function dismiss_notice() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'sbp_dismiss_notice' ) { // Dismiss notice for ever
			$id = $_GET['notice_id'];
			$dismissed_notices   = self::get_dismissed_notices();
			$dismissed_notices[] = $id;
			update_user_meta( get_current_user_id(), 'sbp_dismissed_notices', $dismissed_notices );
		}
	}

	public function remove_notice_transient() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'sbp_remove_notice_transient' ) { // Remove notice transient for one time processes like "cache cleared"
			$id = $_GET['notice_id'];
			delete_transient( $id );
		}
	}

	/**
	 * @param $id
	 * @param $text
	 * @param string $type
	 * @param bool $is_dismissible
	 * @param false $recurrent
	 *
	 * If notice is like "Cache cleared" etc. set recurrent to true. If recurrent is true, notice manager will check transient.
	 */
	public static function display_notice( $id, $text, $type = 'success', $is_dismissible = true, $recurrent = false ) {
		$action = $recurrent ? 'sbp_remove_notice_transient' : 'sbp_dismiss_notice';
		if ( self::should_display( $id ) || ( $recurrent == true && get_transient( $id ) ) ) {
			add_action( 'admin_notices',
				function () use ( $type, $is_dismissible, $id, $text, $action ) {
					echo '<div class="notice sbp-notice notice-' . $type . ' ' . ( $is_dismissible ? 'is-dismissible' : null ) . '"data-notice-action="' . $action . '" data-notice-id="' . $id . '">' . $text . '</div>';
				} );
		}
	}

	public static function should_display( $id ) {
		$dismissed_notices = self::get_dismissed_notices();
		if ( in_array( $id, $dismissed_notices ) ) {
			return false;
		}

		return true;
	}

	public static function get_dismissed_notices() {
		$dismissed_notices = get_user_meta( get_current_user_id(), 'sbp_dismissed_notices', true );

		return is_array( $dismissed_notices ) ? $dismissed_notices : [];
	}

	public function enqueue_scripts() {
		wp_add_inline_script( 'jquery',
			'jQuery(document).on(\'click\', \'.sbp-notice .notice-dismiss\', function() {
		    var $notice = jQuery(this).parent();
		    var notice_id = $notice.data(\'notice-id\');
		    var action = $notice.data(\'notice-action\');
		    var data = {action: action, notice_id: notice_id};
		    jQuery.get(ajaxurl, data);
		});' );
	}
}