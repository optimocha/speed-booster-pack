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
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_redirect( $redirect_url );
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
			'timeout'    => 10,
			'user-agent' => 'Speed Booster Pack/Cache Warmup',
			'sslverify'  => apply_filters( 'https_local_ssl_verify', false ), // WPCS: prefix ok.
		];

		$response = wp_remote_get( $home_url, $remote_get_args );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$transient          = get_transient( 'sbp_warmup_errors' );
			$errors             = is_array( $transient ) ? $transient : [];
			$errors['errors'][] = __( 'Error occured while processing home page url', 'speed-booster' );

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
				if ( substr( $href, 0, 1 ) !== '/' && $remote_url_host !== $home_url_host ) {
					continue;
				}

				if ( ! in_array( $href, $urls ) ) {
					$urls[] = $href;
					$this->warmup_process->push_to_queue( [ 'url' => $href ] );
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