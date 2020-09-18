<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Cloudflare extends SBP_Abstract_Module {
	private static $api_url = 'https://api.cloudflare.com/client/v4/zones/';
	private static $action_paths = [
		'check_credentials' => '',
		'rocket_loader'     => '/settings/rocket_loader',
		'purge_cache'       => '/purge_cache',
	];

	public function __construct() {
		if ( ! sbp_get_option( 'cloudflare_enable' ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'clear_cache_request_handler' ] );
	}

	public static function clear_cache() {
		if ( sbp_get_option( 'cloudflare_enable' ) ) {
			$result = self::send_request( 'purge_cache', 'POST', [ 'purge_everything' => true ] );

			if ( true === $result['success'] ) {
				return true;
			}
		}

		return false;
	}

	public static function reset_transient( $saved_data = [] ) {
		if ( sbp_get_option( 'cloudflare_zone' ) != $saved_data['cloudflare_zone'] ||
		     sbp_get_option( 'cloudflare_email' ) != $saved_data['cloudflare_email'] ||
		     sbp_get_option( 'cloudflare_api' ) != $saved_data['cloudflare_api'] ) {
			delete_transient( 'sbp_cloudflare_status' );
		}
	}

	public static function check_credentials() {
		if ( 1 != get_transient( 'sbp_cloudflare_status' ) ) {
			$result = self::send_request( 'check_credentials' );

			if ( true !== $result['success'] ) {
				set_transient( 'sbp_cloudflare_status', 0 );
			} else {
				set_transient( 'sbp_cloudflare_status', 1 );
			}
		}
	}

	public static function get_rocket_loader_status() {
		$result = self::send_request( 'rocket_loader' );

		$rocket_loader_status = $result['result']['value'] == 'off';

		if ( $result['success'] == true ) {
			$rocket_loader_status = $result['result']['id'] == 'rocket_loader' && $result['result']['value'] == 'on';
		}

		return $rocket_loader_status;
	}

	public static function set_rocket_loader_status() {
		$rocket_loader_status = sbp_get_option( 'cf_rocket_loader_enable' ) ? 'on' : 'off';
		$result               = self::send_request( 'rocket_loader', 'PATCH', [ 'value' => $rocket_loader_status ] );

		if ( $result['success'] == true ) {
			delete_transient( 'rocket_loader_error' );

			return;
		}

		set_transient( 'rocket_loader_error', 1 );
	}

	public static function send_request( $action, $method = 'GET', $post_fields = [] ) {
		$email   = sbp_get_option( 'cloudflare_email' );
		$api_key = sbp_get_option( 'cloudflare_api' );
		$zone    = sbp_get_option( 'cloudflare_zone' );

		if ( ! $email || ! $api_key || ! $zone ) {
			return;
		}

		if ( ! isset( self::$action_paths[ $action ] ) ) {
			return [
				'success' => false,
				'errors'  => [ __( 'Invalid action.', 'speed-booster-pack' ) ]
			];
		}

		if ( ! empty( $zone ) ) {
			$headers = [
				'x_auth_key'   => 'X-Auth-Key: ' . $api_key,
				'x_auth_email' => 'X-Auth-Email: ' . $email,
				'content_type' => 'Content-Type: application/json',
			];

			if ( ! function_exists( 'curl_init' ) ) {
				return [
					'success' => false,
					'errors'  => [ __( 'Curl is not enabled in your hosting.', 'speed-booster-pack' ) ]
				];
			}

			$curl_connection = curl_init();
			$fields          = wp_json_encode( $post_fields );

			curl_setopt( $curl_connection, CURLOPT_URL, self::$api_url . $zone . self::$action_paths[ $action ] );
			curl_setopt( $curl_connection, CURLOPT_CUSTOMREQUEST, $method );
			curl_setopt( $curl_connection, CURLOPT_POSTFIELDS, $fields );
			curl_setopt( $curl_connection, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl_connection, CURLOPT_HTTPHEADER, array_values( $headers ) );
			curl_setopt( $curl_connection, CURLOPT_CONNECTTIMEOUT, 5 );
			curl_setopt( $curl_connection, CURLOPT_TIMEOUT, 10 );
			curl_setopt( $curl_connection, CURLOPT_USERAGENT, '"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.87 Safari/537.36"' );
			curl_setopt( $curl_connection, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $curl_connection, CURLOPT_SSL_VERIFYPEER, false );


			$request_response = curl_exec( $curl_connection );
			$result           = json_decode( $request_response, true );
			curl_close( $curl_connection );

			if ( ! is_array( $result ) ) {
				return [
					'success' => false,
					'errors'  => [ __( 'Cloudflare didn\'t respond correctly.', 'speed-booster-pack' ) ],
					'result'  => $request_response,
				];
			}

			return $result;
		}
	}

	public function clear_cache_request_handler() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_cloudflare_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_cloudflare_cache' ) ) {
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			$result       = self::clear_cache();
			$notice_value = $result == true ? '1' : '2';
			set_transient( 'sbp_notice_cloudflare', $notice_value, 60 );
			wp_redirect( $redirect_url );
		}
	}
}