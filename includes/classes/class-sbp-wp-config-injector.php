<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Config_Injector {
	private static $options = [
		'pagespeed_tricker',
	];

	private static $wp_config_inject_content = [];

	public static function generate_wp_config_inject_file() {
		foreach ( self::$options as $option ) {
			if ( sbp_get_option( $option ) ) {
				$filename = SBP_PATH . 'includes/wp-config-options/' . $option . '.php';
				if ( \file_exists( $filename ) ) {
					self::$wp_config_inject_content[] = file_get_contents( $filename );
				}
			}
		}

		$inject_file_content = implode( PHP_EOL, self::$wp_config_inject_content );
		$inject_file_content = str_replace( '?>' . PHP_EOL . '<?php', '', $inject_file_content );
		$wp_filesystem       = sbp_get_filesystem();

		$inject_file = SBP_PATH . 'includes/wp-config-options/wp-config-inject.php';

		// Write content to wp-config-inject.php file
		if ( $wp_filesystem->exists( $inject_file ) ) {
			$write_wp_config_file = $wp_filesystem->put_contents( $inject_file, $inject_file_content );
			if ( ! $write_wp_config_file ) {
				set_transient( 'sbp_wp_config_inject_error', 1 );
			} else {
				delete_transient( 'sbp_wp_config_inject_error' );
			}

			self::modify_wp_config();
		}
	}

	private static function modify_wp_config() {
		$wp_filesystem = sbp_get_filesystem();
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( $wp_filesystem->exists( $wp_config_file ) && is_writable( $wp_config_file ) ) {
			if ( $wp_filesystem->is_writable( $wp_config_file ) ) {
				$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );
				if ( ! preg_match( '/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si', $wp_config_content ) ) {
					$modified_content = str_replace( '<?php', '<?php' . PHP_EOL . PHP_EOL . '// BEGIN SBP_WP_Config' . PHP_EOL . 'include_once "' . SBP_PATH . 'includes/wp-config-options/wp-config-inject.php";' . PHP_EOL . '// END SBP_WP_Config' . PHP_EOL, $wp_config_content );
					$wp_filesystem->put_contents( $wp_config_file, $modified_content );
				}
				delete_transient( 'sbp_wp_config_error' );
			}
		} else {
			set_transient( 'sbp_wp_config_error', 1 );
		}
	}
}