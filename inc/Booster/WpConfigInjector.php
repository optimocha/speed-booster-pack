<?php

namespace SpeedBoosterPack\Booster;

use SpeedBoosterPack\Common\Helper;

defined('ABSPATH') || exit;

/** @removal */
class WpConfigInjector {
	private static $options = [];

	private static $wp_config_inject_content = [];

	public static function inject_wp_config() {
		foreach ( self::$options as $option_name => $value ) {
			if ( Helper::getOption( $option_name ) ) {
				$filename = SBP_PATH . 'templates/wp-config/' . $value['filename'];
				if ( \file_exists( $filename ) ) {
					self::$wp_config_inject_content[ $option_name ] = $filename;
				}
			}
		}

		$removeLines = self::remove_wp_config_lines();
		$addLines    = self::add_wp_config_lines();

		return $removeLines && $addLines;
	}

	public static function remove_wp_config_lines() {
		$wp_filesystem = Helper::sbpGetFilesystem();
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( $wp_filesystem->exists( $wp_config_file ) && $wp_filesystem->is_writable( $wp_config_file ) ) {
			$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );
			$modified_content  = preg_replace( '/<\?php' . PHP_EOL . PHP_EOL . '\/\/ BEGIN SBP_WP_Config/si', '<?php' . PHP_EOL . '// BEGIN SBP_WP_Config', $wp_config_content ); // Remove blank lines
			$modified_content  = preg_replace( '/\/\/ END SBP_WP_Config' . PHP_EOL . '/si', '// END SBP_WP_Config', $modified_content ); // Remove blank lines
			$modified_content  = preg_replace( '/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si', '', $modified_content );
			$wp_filesystem->put_contents( $wp_config_file, $modified_content );

			return true;
		}

		return false;
	}

	private static function add_wp_config_lines() {
		$wp_filesystem = Helper::sbpGetFilesystem();
		if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( $wp_filesystem->exists( $wp_config_file ) && $wp_filesystem->is_writable( $wp_config_file ) ) {
			$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );

			if ( ! preg_match( '/\/\/ BEGIN SBP_WP_Config -' . SBP_VERSION . '-(.*?)\/\/ END SBP_WP_Config/si', $wp_config_content ) ) {
				$wp_config_content = preg_replace( '/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si', '', $wp_config_content );
				foreach ( self::$wp_config_inject_content as $option_name => $include_file_path ) {
					$modified_content = str_replace( '<?php', '<?php' . PHP_EOL . PHP_EOL . '// BEGIN SBP_WP_Config -' . SBP_VERSION . '- ' . $option_name . ' -' . PHP_EOL . 'include_once "' . $include_file_path . '";' . PHP_EOL . '// END SBP_WP_Config', $wp_config_content );
					$wp_filesystem->put_contents( $wp_config_file, $modified_content );
				}
			}
			delete_transient( 'sbp_wp_config_error' );

			return true;
		} else {
			set_transient( 'sbp_wp_config_error', 1 );
		}

		return false;
	}
}