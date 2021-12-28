<?php

namespace SpeedBooster;

class SBP_Base_Cache extends SBP_Abstract_Module {
	protected $is_litespeed = false;

	public function __construct() {
		$this->is_litespeed = SBP_Utils::is_litespeed();
	}

	/**
	 * Decides to run cache or not.
	 *
	 * @return bool
	 */
	protected function should_bypass_cache( $skipped_conditions = [] ) {
		// Do not cache for logged in users
		if ( is_user_logged_in() && ! in_array( 'is_logged_in', $skipped_conditions ) ) {
			return true;
		}

		// Check for several special pages
		if ( is_search() || is_404() || is_feed() || is_trackback() || is_robots() || is_preview() || post_password_required() ) {
			return true;
		}

		// DONOTCACHEPAGE
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE === true ) {
			return true;
		}

		// Woocommerce checkout check
		if ( function_exists( 'is_checkout' ) ) {
			if ( is_checkout() ) {
				return true;
			}
		}

		// Woocommerce cart check
		if ( function_exists( 'is_cart' ) ) {
			if ( is_cart() ) {
				return true;
			}
		}

		// Check request method. Only cache get methods
		if ( $_SERVER['REQUEST_METHOD'] != 'GET' ) {
			return true;
		}

		if ( ! empty( $_GET ) ) {
			$include_query_strings = SBP_Utils::explode_lines( sbp_get_option( 'caching_include_query_strings' ) );

			foreach ( $_GET as $key => $value ) {
				if ( ! in_array( $key, $include_query_strings ) ) {
					return true;
				}
			}
		}

		$is_litespeed = SBP_Utils::is_litespeed();

		if ( $this->check_excluded_urls() ) {
			return true;
		}

		if ( $this->check_cookies() ) {
			return true;
		}

		return false;
	}

	private function check_excluded_urls() {
		// Check for exclude URLs
		if ( $exclude_urls = sbp_get_option( 'caching_exclude_urls' . ( $this->is_litespeed !== false ? '_ls' : '' ) ) ) {
			$exclude_urls   = array_map( 'trim', SBP_Utils::explode_lines( $exclude_urls ) );
			$exclude_urls[] = '/favicon.ico';
			$current_url    = rtrim( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '/' );
			if ( count( $exclude_urls ) > 0 && in_array( $current_url, $exclude_urls ) ) {
				return true;
			}
		}
	}

	private function check_cookies() {
		// Check if user logged in
		if ( ! empty( $_COOKIE ) ) {
			// Default Cookie Excludes
			$cookies          = [ 'comment_author_', 'wordpress_logged_in_', 'wp-postpass_' ];
			$excluded_cookies = sbp_get_option( 'caching_exclude_cookies' . ( $this->is_litespeed !== false ? '_ls' : '' ) );
			$excluded_cookies = SBP_Utils::explode_lines( $excluded_cookies );
			$cookies          = array_merge( $cookies, $excluded_cookies );

			$cookies_regex = '/^(' . implode( '|', $cookies ) . ')/';

			foreach ( $_COOKIE as $key => $value ) {
				if ( preg_match( $cookies_regex, $key ) ) {
					return true;
				}
			}
		}
	}
}