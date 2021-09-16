<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Advisor {
	private $messages = [];

	public function __construct() {
		add_action( 'wp_ajax_sbp_get_advisor_messages', [ $this, 'get_messages_html' ] );
	}

	public function set_messages() {
		$this->check_php_version();
		$this->check_http_protocol_version();
	}

	private function check_http_protocol_version() {
		$checked = false;
		$message_id = 'update_http_protocol';

		$response = wp_safe_remote_get( get_home_url() );
		if ( $response instanceof \WP_Error) {
			return;
		}

		$protocol_version = $response['http_response']->get_response_object()->protocol_version;

		if ( version_compare( $protocol_version, 1.1 ) === 1 ) {
			$checked = true;
		}

		$this->messages[ $message_id ] = [
			'type'    => 'non-dismissible',
			'content' => __( 'You\'re using HTTP/1.1. For best performance, you should upgrade to HTTP/2 or, if possible, HTTP/3.', 'speed-booster-pack' ),
			'checked' => $checked,
		];
	}

	private function check_php_version() {
		$checked = false;
		$message_id = 'update_php';

		if ( version_compare( PHP_VERSION, '7.3' ) !== -1 ) {
			$checked = true;
		}

		$this->messages[ $message_id ] = [
			'type'    => 'non-dismissible',
			'content' => __( 'You\'re using and old version of PHP. For best performance, you should upgrade PHP to version 7.3 or above.', 'speed-booster-pack' ),
			'checked' => $checked,
		];
	}

	public function get_messages_html() {
		$this->set_messages();

		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_get_advisor_messages' && current_user_can( 'manage_options' ) && isset( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'sbp_ajax_nonce' ) ) {

			usort($this->messages, function($a, $b) {
				if ($a['checked'] > $b['checked']) {
					return 1;
				} else if ($a['checked'] == $b['checked']) {
					return 0;
				} else {
					return -1;
				}
			});
			$advisor_messages_content = '';
			foreach ( $this->messages as $message_id => $message ) {
				$advisor_messages_content .= '<div class="sbp-advice" data-message-id="' . $message_id . '">
                <input type="checkbox" disabled="disabled" ' . ( $message["checked"] ? "checked" : "" ) . ' /><span class="circle"></span> <p> ' . $message['content'] . '</p>
            </div>';
			}

			echo $advisor_messages_content;
		}
		wp_die();
	}
}