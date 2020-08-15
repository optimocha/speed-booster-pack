<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Utils extends SBP_Abstract_Module {
	public static function explode_lines( $text, $unique = true ) {
		if ( '' === $text ) {
			return [];
		}

		if ( true === $unique ) {
			return array_filter( array_unique( array_map( 'trim', explode( PHP_EOL, $text ) ) ) );
		} else {
			return array_filter( array_map( 'trim', explode( PHP_EOL, $text ) ) );
		}
	}

	public static function get_file_extension_from_url( $url ) {
		// Remove Query String
		if ( strpos( $url, "?" ) !== false ) {
			$url = substr( $url, 0, strpos( $url, "?" ) );
		}
		if ( strpos( $url, "#" ) !== false ) {
			$url = substr( $url, 0, strpos( $url, "#" ) );
		}

		return pathinfo( $url, PATHINFO_EXTENSION );
	}

	/**
	 * Check if a plugin is active or not.
	 * @since 3.8.3
	 */
	public static function is_plugin_active( $path ) {
		return in_array( $path, get_option( 'active_plugins' ) );
	}

}