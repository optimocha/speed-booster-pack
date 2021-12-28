<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_LiteSpeed_Cache extends SBP_Base_Cache {
	const ROOT_MARKER = 'SBP_LS_CACHE';

	public function __construct() {
		parent::__construct();
		if ( SBP_Utils::is_litespeed() ) {
			add_action( 'init', [ $this, 'clear_lscache_request' ] );
			add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );
			add_filter( 'sbp_output_buffer', [ $this, 'set_headers' ] );
		}
	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			// Cache clear
			$clear_lscache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_lscache' ),
				'sbp_clear_total_lscache',
				'sbp_nonce' );
			$sbp_admin_menu    = [
				'id'     => 'sbp_clear_lscache',
				'parent' => 'speed_booster_pack',
				'title'  => __( 'Clear LiteSpeed Cache', 'speed-booster-pack' ),
				'href'   => $clear_lscache_url,
			];

			$admin_bar->add_node( $sbp_admin_menu );

			// Clear Front Page Cache
			$clear_frontpage_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_frontpage_lscache' ),
					'sbp_clear_frontpage_lscache',
					'sbp_nonce' ) . '&tags=is_front_page';
			$sbp_admin_menu      = [
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

		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_frontpage_lscache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_frontpage_lscache' ) && isset( $_GET['tags'] ) && $tags = $_GET['tags'] ) {
			@header( 'X-LiteSpeed-Purge:' . $tags );
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	public static function insert_htaccess_rules() {
		if ( ! SBP_Utils::is_litespeed() ) {
			return;
		}

		$lines = [];

		if ( sbp_get_option( 'module_caching_ls' ) ) {
			$lines[] = '<IfModule LiteSpeed>';
			$lines[] = 'RewriteEngine On';
			$lines[] = 'CacheLookup On' . PHP_EOL;

			// Add vary, so the logged in users won't see public cache or other users' caches
			$lines[] = '## BEGIN Cache vary for logged in users';
			$lines[] = 'RewriteRule .? - [E="Cache-Vary:,wp-postpass_' . COOKIEHASH . '"]';
			$lines[] = '## END Cache vary for logged in users' . PHP_EOL;

			if ( sbp_get_option( 'caching_ls_separate_mobile' ) ) {
				$lines[] = '## BEGIN Cache vary for mobile browsers';
				$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "Mobile|Android|Silk/|Kindle|BlackBerry|Opera Mini|Opera Mobi"';
				$lines[] = 'RewriteRule .* - [E=Cache-Control:vary=ismobile]';
				$lines[] = '## END Cache vary for mobile browsers' . PHP_EOL;
			}

			// Z_TODO: Exclude cookie rules must be in htaccess

			if ( $query_strings = sbp_get_option( 'caching_ls_include_query_strings' ) ) {
				$keys = explode( PHP_EOL, $query_strings );
				if ( $keys ) {
					$lines[] = '## BEGIN Dropped Query Strings';
					foreach ( $keys as $key ) {
						$lines[] = 'CacheKeyModify -qs:' . $key;
					}
					$lines[] = '## END Dropped Query Strings';
				}
			}
			$lines[] = '</IfModule>';
		}

		SBP_Utils::insert_to_htaccess( self::ROOT_MARKER, implode( PHP_EOL, $lines ) );
	}

	public static function remove_htaccess_rules() {
		SBP_Utils::insert_to_htaccess( self::ROOT_MARKER, '' );
	}

	private function add_tags() {
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

		if ( $tags ) {
			header( 'X-LiteSpeed-Tag: ' . implode( ',', $tags ) );
		}
	}

	public function set_headers( $html ) {
		if ( ! sbp_get_option( 'module_caching_ls' ) ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		} else {
			// Multiply by 3600 because we store this value in hours but this value should be converted to seconds here
			$cache_expire_time = sbp_get_option( 'caching_ls_expiry', 10 ) * HOUR_IN_SECONDS;

			// Check for all exclusions
			if ( true === $this->should_bypass_cache( [ 'is_logged_in' ] ) ) {
				if ( ! sbp_get_option( 'caching_ls_cache_logged_in_users' ) ) {
					header( 'X-LiteSpeed-Cache-Control: no-cache' );
				} else {
					header( 'X-LiteSpeed-Cache-Control: private,max-age=' . $cache_expire_time );
					header( 'X-LiteSpeed-Vary: cookie=wp-postpass_' . COOKIEHASH );
				}
			} else {
				$this->add_tags();
				header( 'X-LiteSpeed-Cache-Control: public,max-age=' . $cache_expire_time );
				$html .= '<!-- LiteSpeed cache controlled by ' . SBP_PLUGIN_NAME . ' -->';
			}
		}

		return $html;
	}
}