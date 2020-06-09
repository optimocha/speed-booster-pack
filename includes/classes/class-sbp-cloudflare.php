<?php

namespace SpeedBooster;

class SBP_Cloudflare extends SBP_Abstract_Module {
	private static $api_url = 'https://api.cloudflare.com/client/v4/zones/';

	public function __construct() {
		if ( ! sbp_get_option( 'module_caching' ) || ! sbp_get_option( 'cloudflare' )['cloudflare_enable'] ) {
			return;
		}
	}

	public static function clear_cache() {
		if ( is_array( sbp_get_option( 'cloudflare' ) ) && sbp_get_option( 'cloudflare' )['cloudflare_enable'] ) {
			$email   = sbp_get_option( 'cloudflare' )['cloudflare_email'];
			$api_key = sbp_get_option( 'cloudflare' )['cloudflare_api'];
			$zone    = sbp_get_option( 'cloudflare' )['cloudflare_zone'];

			$headers = [
				'x_auth_key'   => 'X-Auth-Key: ' . $api_key,
				'x_auth_email' => 'X-Auth-Email: ' . $email,
			];

			$result = self::send_request( '7f25cfbd49f3559ea31a78badb1220ea', '/purge_cache', [ 'purge_everything' => true ], $headers, 'POST' );
			if ( true === $result['success'] ) {
				return true;
			}
		}

		return false;
	}

	public static function check_credentials( $saved_data ) {
		// Check if old value is same as new value
		if ( sbp_get_option( 'cloudflare' ) == $saved_data['cloudflare'] ) {
			return;
		}

		if ( isset( $saved_data['cloudflare']['cloudflare_enable'] ) && $saved_data['cloudflare']['cloudflare_enable'] ) {
			$email   = $saved_data['cloudflare']['cloudflare_email'];
			$api_key = $saved_data['cloudflare']['cloudflare_api'];
			$zone    = $saved_data['cloudflare']['cloudflare_zone'];

			$headers = [
				'x_auth_key'   => 'X-Auth-Key: ' . $api_key,
				'x_auth_email' => 'X-Auth-Email: ' . $email,
			];

			$result = self::send_request( $zone, '' );

			if ( true !== $result['success'] ) {
				wp_send_json_success( [ 'notice' => esc_html__( 'Options saved but Cloudflare API credentials are not valid.', 'speed-booster-pack' ), 'errors' => [] ] );
			}
		}
	}

	/**
	 * @param $zone
	 * @param $path
	 * @param array $post_fields
	 * @param array $headers Valid HTTP headers to add.
	 * @param string $method
	 *
	 * @return array|bool[]|mixed
	 */
	private static function send_request( $zone, $path, $post_fields = [], $headers = [], $method = 'GET' ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return [ 'success' => false ];
		}

		$curl_connection = curl_init();

		$default_headers = [
			'content_type' => 'Content-Type: application/json',
		];

		$headers = array_filter( array_values( array_merge( $default_headers, $headers ) ) );

		$fields = wp_json_encode( $post_fields );

		curl_setopt( $curl_connection, CURLOPT_URL, self::$api_url . $zone . $path );
		curl_setopt( $curl_connection, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $curl_connection, CURLOPT_POSTFIELDS, $fields );
		curl_setopt( $curl_connection, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl_connection, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $curl_connection, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt( $curl_connection, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $curl_connection, CURLOPT_USERAGENT, '"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.87 Safari/537.36"' );

		$request_response = curl_exec( $curl_connection );
		$result           = json_decode( $request_response, true );
		curl_close( $curl_connection );

		if ( ! is_array( $result ) ) {
			return [ 'success' => false, 'errors' => [ __( 'Cloudflare didn\'t respond correctly.' ) ] ];
		}

		return $result;
	}
}