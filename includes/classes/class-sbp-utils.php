<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Utils extends SBP_Abstract_Module {
	public static function explode_lines( $text, $unique = true ) {
		if ( ! $text ) {
			return [];
		}

		if ( true === $unique ) {
			return array_filter( array_unique( array_map( 'trim', explode( PHP_EOL, $text ) ) ) );
		} else {
			return array_filter( array_map( 'trim', explode( PHP_EOL, $text ) ) );
		}
	}

	public static function get_file_extension_from_url( $url ) {
		$url = self::clear_hashes_and_question_mark( $url );

		return pathinfo( $url, PATHINFO_EXTENSION );
	}

	public static function clear_hashes_and_question_mark( $url ) {
		// Remove Query String
		if ( strpos( $url, "?" ) !== false ) {
			$url = substr( $url, 0, strpos( $url, "?" ) );
		}
		if ( strpos( $url, "#" ) !== false ) {
			$url = substr( $url, 0, strpos( $url, "#" ) );
		}

		return $url;
	}

	/**
	 * Check if a plugin is active or not.
	 * @since 3.8.3
	 */
	public static function is_plugin_active( $plugin ) {
		$is_plugin_active_for_network = false;

		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			$is_plugin_active_for_network = true;
		}

		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || $is_plugin_active_for_network;
	}

	public static function insert_to_htaccess( $marker_name, $content ) {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$htaccess_file_path = get_home_path() . '/.htaccess';

		if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
			add_action( 'admin_init', function() use ( $htaccess_file_path, $marker_name, $content ) {
				insert_with_markers( $htaccess_file_path, $marker_name, $content );
			} );
		}

		return false;
	}

	public static function is_litespeed() {
		if ( ! defined( 'LITESPEED_SERVER_TYPE' ) ) {
			if ( isset( $_SERVER['HTTP_X_LSCACHE'] ) && $_SERVER['HTTP_X_LSCACHE'] ) {
				define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ADC' );
			} elseif ( isset( $_SERVER['LSWS_EDITION'] ) && strpos( $_SERVER['LSWS_EDITION'], 'Openlitespeed' ) === 0 ) {
				define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_OLS' );
			} elseif ( isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] == 'LiteSpeed' ) {
				define( 'LITESPEED_SERVER_TYPE', 'LITESPEED_SERVER_ENT' );
			} else {
				define( 'LITESPEED_SERVER_TYPE', 'NONE' );
			}
		}

		// Checks if caching is allowed via server variable
		if ( ! empty ( $_SERVER['X-LSCACHE'] ) ||  LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' || defined( 'LITESPEED_CLI' ) ) {
			! defined( 'LITESPEED_ALLOWED' ) &&  define( 'LITESPEED_ALLOWED', true );
		}

		return LITESPEED_SERVER_TYPE !== 'NONE' ? LITESPEED_SERVER_TYPE && LITESPEED_ALLOWED : false;
	}

	/**
	 * Removes the http and https prefixes from url's
	 *
	 * @param $url
	 *
	 * @return void
	 */
	public static function remove_protocol( $url ) {
		return str_replace( [ 'http://', 'https://' ], [ '//', '//' ], $url );
	}
}