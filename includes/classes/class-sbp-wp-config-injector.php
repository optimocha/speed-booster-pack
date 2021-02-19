<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Config_Injector {
	private static $options = [
		'pagespeed_tricker' => [
			'filename' => 'pagespeed-tricker.php',
		],
	];

	private static $wp_config_inject_content = [];

	public static function inject_wp_config() {
		foreach ( self::$options as $option_name => $value ) {
			if ( sbp_get_option( $option_name ) ) {
				$filename = SBP_PATH . 'includes/templates/' . $value['filename'];
				if ( \file_exists( $filename ) ) {
					self::$wp_config_inject_content[$option_name] = $filename;
				}
			}
		}

		self::remove_wp_config_lines();
		self::add_wp_config_lines();
	}

	public static function remove_wp_config_lines() {
		$wp_filesystem = sbp_get_filesystem();
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( $wp_filesystem->exists( $wp_config_file ) && is_writable( $wp_config_file ) ) {
			$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );
			$modified_content = preg_replace( '/' . PHP_EOL . PHP_EOL . '\/\/ BEGIN SBP_WP_Config/si', '// BEGIN SBP_WP_Config', $wp_config_content ); // Remove blank lines
			$modified_content = preg_replace( '/\/\/ END SBP_WP_Config' . PHP_EOL . '/si', '// END SBP_WP_Config', $modified_content ); // Remove blank lines
			$modified_content = preg_replace( '/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si', '', $modified_content );
			$wp_filesystem->put_contents( $wp_config_file, $modified_content );
		}
	}

	private static function add_wp_config_lines() {
		$wp_filesystem = sbp_get_filesystem();
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( $wp_filesystem->exists( $wp_config_file ) && is_writable( $wp_config_file ) ) {
			$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );

			if ( ! preg_match( '/\/\/ BEGIN SBP_WP_Config -' . SBP_VERSION . '-(.*?)\/\/ END SBP_WP_Config/si', $wp_config_content ) ) {
				$wp_config_content = preg_replace('/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si', '', $wp_config_content);
				foreach (self::$wp_config_inject_content as $option_name => $include_file_path) {
					$modified_content = str_replace( '<?php', '<?php' . PHP_EOL . PHP_EOL . '// BEGIN SBP_WP_Config -' . SBP_VERSION . '- ' . $option_name . ' -' . PHP_EOL . 'include_once "' . $include_file_path . '";' . PHP_EOL . '// END SBP_WP_Config' . PHP_EOL, $wp_config_content );
					$wp_filesystem->put_contents( $wp_config_file, $modified_content );
				}
			}
			delete_transient( 'sbp_wp_config_error' );
		} else {
			set_transient( 'sbp_wp_config_error', 1 );
		}
	}
}