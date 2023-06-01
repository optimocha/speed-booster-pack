<?php

namespace Optimocha\SpeedBooster\Features;

defined( 'ABSPATH' ) || exit;

class Base_Cache {
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

		if ( ! in_array( 'include_query_strings', $skipped_conditions ) ) {
			if ( ! empty( $_GET ) ) {
				$include_query_strings = Utils::explode_lines( sbp_get_option( 'caching_include_query_strings' ) );

				foreach ( $_GET as $key => $value ) {
					if ( ! in_array( $key, $include_query_strings ) ) {
						return true;
					}
				}
			}
		}

		if ( $this->check_excluded_urls() ) {
			return true;
		}

		if ( $this->check_cookies() && ! in_array( 'check_cookies', $skipped_conditions ) ) {
			return true;
		}

		return false;
	}

	private function check_excluded_urls() {
		// Check for exclude URLs
		if ( $exclude_urls = sbp_get_option( 'caching_exclude_urls' ) ) {
			$exclude_urls   = array_map( 'trim', Utils::explode_lines( $exclude_urls ) );
			$exclude_urls[] = '/favicon.ico';
			$current_url    = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$current_url = explode( '?', $current_url )[0];
			$current_url = rtrim( $current_url, '/' );
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
			$excluded_cookies = sbp_get_option( 'caching_exclude_cookies' );
			$excluded_cookies = Utils::explode_lines( $excluded_cookies );
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