<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_LiteSpeed_Cache extends SBP_Abstract_Module {
	public function __construct() {
		parent::__construct();

		if (isset($_SERVER['SERVER_SOFTWARE']) && $_SERVER['SERVER_SOFTWARE'] === 'LiteSpeed') {
			$this->run();

			add_action( 'init', [ $this, 'clear_lscache_request' ] );
			add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );
			add_filter( 'sbp_output_buffer', [ $this, 'add_tags' ] );
		}
	}

	private function run() {

	}

	public function add_tags( $html ) {
		$tags = [];

		$template_functions = [
			'is_front_page',
			'is_home',
			'is_single',
			'is_page',
			'is_category',
			'is_tag',
			'is_archive',
			'is_shop',
			'is_product',
			'is_product_category',
		];

		foreach ( $template_functions as $function ) {
			if ( function_exists( $function ) && call_user_func( $function ) ) {
				$tags[] = $function;
			}
		}

		if (!is_user_logged_in()) {
			header('X-LiteSpeed-Cache-Control: public');

			if ( $tags ) {
				header( 'X-LiteSpeed-Tag: ' . implode( ',', $tags ) );
			}

			$html .= PHP_EOL . '<!-- LS CACHED BY SPEED BOOSTER PACK -->';
		} else {
			header('X-LiteSpeed-Cache-Control: no-cache');
		}

		return $html;
	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			// Cache clear
			$clear_lscache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_lscache' ),
				'sbp_clear_total_lscache',
				'sbp_nonce' );
			$sbp_admin_menu  = [
				'id'     => 'sbp_clear_lscache',
				'parent' => 'speed_booster_pack',
				'title'  => __( 'Clear LiteSpeed Cache', 'speed-booster-pack' ),
				'href'   => $clear_lscache_url,
			];

			$admin_bar->add_node( $sbp_admin_menu );

			// Clear Front Page Cache
			$clear_frontpage_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_frontpage_cache' ),
				'sbp_clear_frontpage_cache',
				'sbp_nonce' ) . '&tags=is_front_page';
			$sbp_admin_menu  = [
				'id'     => 'sbp_clear_frontpage_lscache',
				'parent' => 'speed_booster_pack',
				'title'  => __( 'Clear Front Page Cache', 'speed-booster-pack' ),
				'href'   => $clear_frontpage_url,
			];

			$admin_bar->add_node( $sbp_admin_menu );
		}
	}

	/**
	 * Handles the HTTP request to catch cache clear action
	 */
	public function clear_lscache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_lscache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_total_lscache' ) ) {
			@header( 'X-LiteSpeed-Purge:*' );
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_frontpage_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_frontpage_cache' ) && isset( $_GET['tags'] ) && $tags = $_GET['tags'] ) {
			@header( 'X-LiteSpeed-Purge:' . $tags );
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	public function generate_htaccess() {

	}
}