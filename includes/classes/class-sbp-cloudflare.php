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
		'settings'          => '/settings',
	];

	public function __construct() {
		add_action( 'wp_ajax_sbp_check_cloudflare', [ $this, 'check_credentials_ajax_handler' ] );
		add_action( 'wp_ajax_sbp_get_cloudflare_settings', [ $this, 'sbp_get_cloudflare_settings' ] );

		if ( ! self::is_cloudflare_active() ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'clear_cache_request_handler' ] );
	}

	public static function update_cloudflare_settings( $saved_data ) {
		// Check if settings are updated or not.
		$cf_options = [
			'cloudflare_api',
			'cloudflare_email',
			'cloudflare_zone',
			'cf_rocket_loader_enable',
			'cf_dev_mode_enable',
			'cf_css_minify_enable',
			'cf_html_minify_enable',
			'cf_js_minify_enable',
			'cf_browser_cache_ttl',
		];

		$has_options_changed = false;

		foreach ( $cf_options as $option ) {
			if ( sbp_get_option( $option ) != $saved_data[ $option ] ) {
				$has_options_changed = true;
				break;
			}
		}

		if ( get_transient( 'sbp_do_not_update_cloudflare' ) || ! self::is_cloudflare_active() || ! $has_options_changed ) {
			return;
		}

		$request_data = [
			'items' => [
				[
					'id'    => 'rocket_loader',
					'value' => $saved_data['cf_rocket_loader_enable'] ? 'on' : 'off',
				],
				[
					'id'    => 'development_mode',
					'value' => $saved_data['cf_dev_mode_enable'] ? 'on' : 'off',
				],
				[
					'id'    => 'minify',
					'value' => [
						'css'  => $saved_data['cf_css_minify_enable'] ? 'on' : 'off',
						'html' => $saved_data['cf_html_minify_enable'] ? 'on' : 'off',
						'js'   => $saved_data['cf_js_minify_enable'] ? 'on' : 'off',
					],
				],
				[
					'id'    => 'browser_cache_ttl',
					'value' => (int) $saved_data['cf_browser_cache_ttl'],
				],
			]
		];

		$response = self::send_request( 'settings', 'PATCH', $request_data );

//		die('Settings have been changed');
		if ( $response['success'] ) {
			return true;
		} else {
			return false;
		}
	}

	public static function clear_cache() {
		if ( self::is_cloudflare_active() ) {
			$result = self::send_request( 'purge_cache', 'POST', [ 'purge_everything' => true ] );

			if ( $result && true === $result['success'] ) {
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

	public static function check_credentials( $override_credentials = [] ) {
		$result = self::send_request( 'check_credentials', 'GET', [], $override_credentials );

		if ( $result && true === $result['success'] ) {
			return true;
		}

		if ( !$result ) {
			return null;
		}

		return false;
	}

	public static function send_request( $action, $method = 'GET', $post_fields = [], $override_credentials = [] ) {
		$email   = sbp_get_option( 'cloudflare_email' );
		$api_key = sbp_get_option( 'cloudflare_api' );
		$zone    = sbp_get_option( 'cloudflare_zone' );

		if ( $override_credentials !== [] ) {
			if ( isset( $override_credentials['email'] ) && isset( $override_credentials['api_key'] ) && isset( $override_credentials['zone'] ) ) {
				extract( $override_credentials );
			}
		}

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

	public function check_credentials_ajax_handler() {
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'sbp_check_cloudflare' && current_user_can( 'manage_options' ) ) {
			$status = self::check_credentials( [
				'email'   => $_POST['email'],
				'api_key' => $_POST['api_key'],
				'zone'    => $_POST['zone_id'],
			] );

			if ($status === true) {
				$return_value = 'true';
			} else if ($status === null) {
				$return_value = 'null';
			} else {
				$return_value = 'false';
			}

			echo json_encode( [
				'status' => $return_value,
			] );
			wp_die();
		}
	}

	public function sbp_get_cloudflare_settings() {
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'sbp_get_cloudflare_settings' && current_user_can( 'manage_options' ) ) {
			// Check if empty
			if ( ! sbp_get_option( 'cloudflare_email' ) || ! sbp_get_option( 'cloudflare_api' ) || ! sbp_get_option( 'cloudflare_zone' ) ) {
				echo json_encode( [
					'status'  => 'empty_info',
					'message' => __( 'You did not provide any CloudFlare credentials.', 'speed-booster-pack' )
				] );
				wp_die();
			}

			$settings_to_fetch = [
				'browser_cache_ttl',
				'development_mode',
				'minify',
				'rocket_loader',
			];
			$settings          = [];

			$result = self::send_request( 'settings' );
			if ( $result && $result['success'] ) {
				foreach ( $result['result'] as $setting ) {
					if ( in_array( $setting['id'], $settings_to_fetch ) ) {
						$settings[ $setting['id'] ] = $setting;
					}
				}
				delete_transient( 'sbp_do_not_update_cloudflare' );
				echo json_encode( [ 'status' => 'success', 'results' => $settings ] );
			} else {
				set_transient( 'sbp_do_not_update_cloudflare', 1 );
				echo json_encode( [
					'status'  => 'failure',
					'message' => 'Error occurred while fetching CloudFlare settings. Your changes will not affect your CloudFlare settings until CloudFlare connection provided successfully.'
				] );
			}
			wp_die();
		}
	}

	public static function is_cloudflare_active() {
		return sbp_get_option( 'cloudflare_enable' ) && sbp_get_option( 'cloudflare_email' ) && sbp_get_option( 'cloudflare_api' ) && sbp_get_option( 'cloudflare_zone' );
	}
}