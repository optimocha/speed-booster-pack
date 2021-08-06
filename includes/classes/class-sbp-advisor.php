<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Advisor {
	private $messages = [];
	private $dismissed_messages = [];
	private $user_meta_key = 'sbp_dismissed_messages';

	public function __construct() {
		$this->get_dismissed_messages();
		$this->set_messages();

		add_action( 'wp_ajax_sbp_dismiss_advisor_message', [ $this, 'dismiss_advisor_message' ] );
	}

	public function set_messages() {
		$this->check_php_version();
		$this->check_http_protocol_version();
		$total_message_count = count( $this->messages );
		SBP_Notice_Manager::$notice_count += $total_message_count;
	}

	public function get_dismissed_messages() {
		$dismissed_messages = get_user_meta( get_current_user_id(), $this->user_meta_key, true );

		if ( ! is_array( $dismissed_messages ) ) {
			$this->dismissed_messages = [];
		} else {
			$this->dismissed_messages = $dismissed_messages;
		}
	}

	private function check_http_protocol_version() {
		$message_id = 'update_http_protocol';

		if ( isset( $_SERVER['SERVER_PROTOCOL'] ) && $_SERVER['SERVER_PROTOCOL'] !== 'HTTP/1.1' ) {
			return;
		}

		if ( ! in_array( $message_id, $this->dismissed_messages ) ) {
			$this->messages[ $message_id ] = [
				'style'   => 'warning',
				'type'    => 'non-dismissible',
				'content' => __( 'You\'re using HTTP/1.1. For best performance, you should upgrade to HTTP/2 or, if possible, HTTP/3.', 'speed-booster-pack' ),
			];
		}
	}

	private function check_php_version() {
		$message_id = 'update_php';

		if ( version_compare( PHP_VERSION, '7.3' ) !== -1 ) {
			return;
		}

		if ( ! in_array( $message_id, $this->dismissed_messages ) ) {
			$this->messages[ $message_id ] = [
				'style'   => 'warning',
				'type'    => 'non-dismissible',
				'content' => __( 'You\'re using and old version of PHP. For best performance, you should upgrade PHP to version 7.3 or above.', 'speed-booster-pack' ),
			];
		}
	}

	public function get_messages() {
		return $this->messages;
	}

	public function dismiss_advisor_message() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_dismiss_advisor_message' && current_user_can( 'manage_options' ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'sbp_ajax_nonce' ) ) {
			$message_id = $_GET['sbp_dismiss_message_id'];
			$this->get_dismissed_messages();
			$dismissed_messages = $this->dismissed_messages;
			$dismissed_messages[] = $message_id;
			$dismissed_messages = array_unique($dismissed_messages);
			update_user_meta( get_current_user_id(), $this->user_meta_key, $dismissed_messages );
			wp_die();
		}
	}
}