<?php

namespace SpeedBooster;

class SBP_Notice_Manager {
	private $id;
	private $text;
	private $is_dismissible;

	public function __construct( $id, $text, $is_dismissible ) {
		$this->id             = $id;
		$this->text           = $text;
		$this->is_dismissible = $is_dismissible;

		add_action( 'wp_ajax_sbp_dismiss_notice', [ $this, 'dismiss_notice' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function dismiss_notice() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'sbp_dismiss_notice' ) {
			$id                  = $_GET['notice_id'];
			$dismissed_notices   = self::get_dismissed_notices();
			$dismissed_notices[] = $id;
			update_user_meta( get_current_user_id(), 'sbp_dismissed_notices', $dismissed_notices );
		}
	}

	public static function display_notice( $id, $text, $type = 'success', $is_dismissible = true ) {
		if ( self::should_display( $id ) ) {
			echo '<div class="notice sbp-notice notice-' . $type . ' ' . $is_dismissible ? 'is-dismissible' : null . '" data-notice-id=" ' . $id . ' ">' . $text . '</div>';
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
		    var data = {action: \'sbp_dismiss_notice\', notice_id: notice_id};
		    jQuery.get(ajaxurl, data);
		});' );
	}
}