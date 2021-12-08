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
			$current_htaccess = trim( $wp_filesystem->get_contents( $htaccess_file_path ) );
			$current_htaccess = preg_replace( '/(## BEGIN ' . $marker_name . '.*?## END ' . $marker_name . PHP_EOL . PHP_EOL . ')/msi', '', $current_htaccess );

			if ( $content ) {
				$current_htaccess = str_replace( "# BEGIN WordPress", '## BEGIN ' . $marker_name . PHP_EOL . $content . PHP_EOL . '## END ' . $marker_name . PHP_EOL . PHP_EOL . "# BEGIN WordPress", $current_htaccess );
			}

			$put_files = $wp_filesystem->put_contents( $htaccess_file_path, $current_htaccess );

			return (bool) $put_files;
		}

		return false;
	}

	public static function is_litespeed() {
		return isset( $_SERVER['SERVER_SOFTWARE'] ) && $_SERVER['SERVER_SOFTWARE'] === 'LiteSpeed';
	}
}