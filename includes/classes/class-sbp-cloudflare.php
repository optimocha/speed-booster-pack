<?php

namespace SpeedBooster;

use Cloudflare\Api;
use Cloudflare\Zone\Cache;

class SBP_Cloudflare extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_caching' ) || ! sbp_get_option( 'cloudflare' )['cloudflare_enable'] ) {
			return;
		}
	}

	public static function clear_cache() {
		if ( sbp_get_option( 'module_caching' ) && sbp_get_option( 'cloudflare' )['cloudflare_enable'] ) {
			$email   = sbp_get_option( 'cloudflare' )['cloudflare_email'];
			$api_key = sbp_get_option( 'cloudflare' )['cloudflare_api'];
			$zone    = sbp_get_option( 'cloudflare' )['cloudflare_zone'];


			if ( false !== self::get_zone( $email, $api_key, $zone ) ) {
				$cache = new Cache();
//				$purge = $cache->purge( $zone, true ); // Just need to solve authorization problem and we're ready to go
			} else {
				return false;
			}
		}

		return false;
	}

	public static function check_credentials( $saved_data ) {
		// Check if old value is same as new value
		if ( sbp_get_option( 'cloudflare' ) == $saved_data['cloudflare'] ) {
			return;
		}

		if ( $saved_data['module_caching'] || $saved_data['cloudflare']['cloudflare_enable'] ) {
			$email   = $saved_data['cloudflare']['cloudflare_email'];
			$api_key = $saved_data['cloudflare']['cloudflare_api'];
			$zone    = $saved_data['cloudflare']['cloudflare_zone'];
			if ( empty( $email ) || empty( $api_key ) || empty( $zone ) ) {
				wp_send_json_success( [ 'notice' => esc_html__( 'Options saved but Cloudflare API settings are empty.', 'speed-booster-pack' ), 'errors' => [] ] );
			}

			$zone = self::get_zone( $email, $api_key, $zone );
			if ( false == $zone ) {
				wp_send_json_success( [ 'notice' => esc_html__( 'Options saved but Cloudflare Zone ID is not valid.', 'speed-booster-pack' ), 'errors' => [] ] );
			}
		}
	}

	public static function get_zone( $email, $api_key, $zone ) {
		$cloudflare_client = new Api();
		$cloudflare_client->setEmail( $email );
		$cloudflare_client->setAuthKey( $api_key );
		$zone = $cloudflare_client->get( 'zones/' . $zone );
		if ( $zone->success == false ) {
			return false;
		}

		return $zone;
	}
}