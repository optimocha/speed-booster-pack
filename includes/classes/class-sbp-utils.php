<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
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

//	public static function on_plugin_update( $callback ) {
//		add_action( 'upgrader_process_complete',
//			function ( $upgrader_object, $hook_extra ) use ( $callback ) {
//				$our_plugin = 'speed-booster-pack/speed-booster-pack.php';
//				if ( $hook_extra['action'] == 'update' && $hook_extra['type'] == 'plugin' && isset( $hook_extra['plugins'] ) ) {
//					if ( in_array( $our_plugin, $hook_extra['plugins'] ) ) {
//						file_get_contents(SBP_CACHE_DIR . 'test1.txt', 'here');
//						if ( is_callable( $callback ) ) {
//							file_get_contents(SBP_CACHE_DIR . 'test2.txt', 'here');
//							$arguments = func_get_args();
//							file_get_contents(SBP_CACHE_DIR . 'test3.txt', 'here');
//							array_shift( $arguments );
//							file_get_contents(SBP_CACHE_DIR . 'test4.txt', 'here');
//							$cuf = call_user_func_array( $callback, $arguments );
//							file_get_contents(SBP_CACHE_DIR . 'testtest.txt', $cuf);
//						}
//					}
//				}
//			},
//			10,
//			2 );
//	}
}