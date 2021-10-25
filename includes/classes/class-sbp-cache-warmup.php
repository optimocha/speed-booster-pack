<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use simplehtmldom\HtmlDocument;

class SBP_Cache_Warmup extends SBP_Abstract_Module {
	private $warmup_process;

	public function __construct() {
		if ( ! sbp_get_option( 'module_caching' ) ) {
			return;
		}

		$this->warmup_process = new SBP_Warmup_Process();

		add_action( 'init', [ $this, 'handle_warmup_request' ] );
	}

	public function handle_warmup_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_warmup_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_warmup_cache' ) ) {
			$this->start_process();
			set_transient( 'sbp_warmup_started', 1 );
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	public function start_process() {
		$urls = $this->get_urls_to_warmup();
		if ( $urls ) {
			foreach ( $urls as $item ) {
				$this->warmup_process->push_to_queue( $item );
			}
			set_transient( 'sbp_warmup_process', 0 );
			$this->warmup_process->save()->dispatch();
		}
	}

	private function get_urls_to_warmup() {
		$home_url = get_home_url();

		$remote_get_args = [
			'timeout'    => 5,
			'user-agent' => 'Speed Booster Pack/Cache Warmup',
			'sslverify'  => false,
		];

		$response = wp_remote_get( $home_url, $remote_get_args );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		} else {
			$urls = [];
			$body = wp_remote_retrieve_body( $response );
			$dom  = new HtmlDocument();
			$dom->load( $body, true, false );

			foreach ( $dom->find( 'a' ) as $anchor_tag ) {
				$href = $anchor_tag->href;

				if ( substr( $href, 0, 1 ) == '#' ) {
					continue;
				}

				$remote_url_host = wp_parse_url( $href, PHP_URL_HOST );
				$home_url_host   = wp_parse_url( $home_url, PHP_URL_HOST );

				// Check if relative url or includes home url
				if (
					! (
						( strpos( $href, 'http://' ) === 0 && strlen( $href ) > 7 ) ||
						( strpos( $href, 'https://' ) === 0 && strlen( $href ) > 8 ) ||
						substr( $href, 0, 2 ) != '//'
					) ||
					( strpos( $href, '/' ) !== 0 && $remote_url_host != $home_url_host ) ||
					! trim( $href ) ||
					rtrim( $href, '/' ) == $home_url
				) {
					continue;
				}

				if ( substr( $href, 0, 1 ) == '/' ) {
					$href = rtrim( $home_url, '/' ) . $href;
				}

				if ( ! in_array( $href, $urls ) ) {
					$urls[] = $href;

					$this->warmup_process->push_to_queue( [
						'url'     => $href,
						'options' => [ 'user-agent' => 'Speed Booster Pack/Cache Warmup' ],
					] );

					if ( sbp_get_option( 'caching_separate_mobile' ) ) {
						$this->warmup_process->push_to_queue( [
							'url'     => $href,
							'options' => [ 'user-agent' => 'Mobile' ],
						] );
					}
				}
			}

			$this->warmup_process->save()->dispatch();
		}

		return false;
	}
}