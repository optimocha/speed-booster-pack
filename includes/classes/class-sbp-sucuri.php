<?php

namespace SpeedBooster;

// If this file is called directly, abort.
use Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Sucuri extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! sbp_get_option( 'sucuri_enable' ) ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'clear_cache_request_handler' ] );
	}

	public static function clear_cache() {
		$api_key    = sbp_get_option( 'sucuri_api' );
		$api_secret = sbp_get_option( 'sucuri_secret' );
		$url        = "https://waf.sucuri.net/api?k=$api_key&s=$api_secret&a=clearcache";

		if ( ! sbp_get_option( 'sucuri_enable' ) ) {
			delete_transient( 'sbp_sucuri_status' );
		}

		if ( trim( $api_key ) && trim( $api_secret ) ) {
			$request       = wp_remote_get( $url );
			$response_body = wp_remote_retrieve_body( $request );
			if ( strpos( $response_body, "OK:" ) === 0 ) {
				delete_transient( 'sbp_sucuri_error' ); // Just in case
				return true;
			} else {
				set_transient( 'sbp_sucuri_error', $response_body );
			}
		}

		return false;
	}

	public function clear_cache_request_handler() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_sucuri_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_sucuri_cache' ) ) {
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			$result       = self::clear_cache();
			$notice_value = $result == true ? '1' : '2';
			set_transient( 'sbp_clear_sucuri_cache', $notice_value, 60 );
			wp_redirect( $redirect_url );
		}
	}
}