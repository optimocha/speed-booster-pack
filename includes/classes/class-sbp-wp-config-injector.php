<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_WP_Config_Injector {
	private $options = [
		'pagespeed_tricker_module',
	];

	private static $wp_config_inject_content = [];

	public function __construct() {
		foreach ( $this->options as $option ) {
			if ( sbp_get_option( $option ) ) {
				$filename = SBP_PATH . 'includes/wp-config-options/' . $option . '.php';
				if ( \file_exists( $filename ) ) {
					self::$wp_config_inject_content[] = include_once($filename);
				}
			}
		}

		var_dump(self::$wp_config_inject_content);
	}
}