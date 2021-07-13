<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Advisor extends SBP_Abstract_Module {
	private $messages = [];
	private $dismissed_messages = [];

	public function __construct() {
		add_action( 'admin_init', [ $this, 'get_dismissed_messages' ] );
		add_action( 'admin_init', [ $this, 'set_messages' ] );
	}

	public function set_messages() {
		$this->check_php_version();
		$this->check_http_protocol_version();
		$total_message_count = count( $this->messages );
		SBP_Notice_Manager::$notice_count += $total_message_count;
	}

	public function get_dismissed_messages() {
		$dismissed_messages = get_user_meta( get_current_user_id(), 'sbp_dismissed_messages', true );

		if ( ! is_array( $dismissed_messages ) ) {
			$this->dismissed_messages = [];
		} else {
			$this->dismissed_messages = $dismissed_messages;
		}
	}

	private function check_http_protocol_version() {
		$message_id = 'update_http_protocol';

		if ( ! in_array( $message_id, $this->dismissed_messages ) ) {
			$this->messages[ $message_id ] = [
				'style'   => 'warning',
				'type'    => 'dismissible',
				'content' => __( 'We detected that you\'re using HTTP/1.1. For best performance, you need to update to HTTP/2 or HTTP/3', 'speed-booster-pack' ),
			];
		}
	}

	private function check_php_version() {
		$message_id = 'update_php';

		if ( ! in_array( $message_id, $this->dismissed_messages ) ) {
			$this->messages[ $message_id ] = [
				'style'   => 'warning',
				'type'    => 'dismissible',
				'content' => __( 'We detected that you\'re using and old version of PHP. For best performance, you recommend you to upgrade to PHP 7.3', 'speed-booster-pack' ),
			];
		}
	}

	public function get_messages() {
		return $this->messages;
	}
}