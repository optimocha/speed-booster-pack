<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Advanced_Cache_Generator {
	private static $options = [];
	private static $advanced_cache_template = SBP_PATH . 'templates/cache/advanced-cache.php';
	private static $placeholders = [
		'\'__SEPARATE_MOBILE_CACHING__\';'          => [
			'option_name' => 'caching_separate_mobile',
			'method_name' => 'separate_mobile_caching',
		],
		'\'{{__CACHING_QUERY_STRING_INCLUDES__}}\'' => [
			'option_name' => 'caching_include_query_strings',
			'method_name' => 'caching_query_string_includes',
		],
		'\'{{__CACHING_EXPIRY__}}\''                => [
			'option_name' => 'caching_expiry',
			'method_name' => 'caching_expiry',
		],
		'\'{{__CACHING_EXCLUDE_URLS__}}\''          => [
			'option_name' => 'caching_exclude_urls',
			'method_name' => 'caching_exclude_urls',
		],
		'\'{{__CACHING_EXCLUDE_COOKIES__}}\''       => [
			'option_name' => 'caching_exclude_cookies',
			'method_name' => 'caching_exclude_cookies',
		],
	];

	public static function generate_advanced_cache_file( $options = [] ) {
		self::$options = $options;
		$wp_filesystem = sbp_get_filesystem();
		$file_content  = $wp_filesystem->get_contents( self::$advanced_cache_template );
		foreach ( self::$placeholders as $placeholder => $props ) {
			$method_name = 'SpeedBooster\SBP_Advanced_Cache_Generator::' . $props['method_name'];
			if ( ! method_exists( SBP_Advanced_Cache_Generator::class, $props['method_name'] ) ) {
				continue;
			}
			$option_value = false;
			if ( self::$options === [] ) {
				$option_value = sbp_get_option( $props['option_name'], '' );
			} else {
				$option_value = isset( self::$options[ $props['option_name'] ] ) ? self::$options[ $props['option_name'] ] : '';
			}
			$replace_content = '';
			if ( $option_value !== false ) {
				$replace_content = call_user_func( $method_name );
			}
			$file_content = str_replace( "$placeholder", $replace_content, $file_content );
		}

		return $file_content;
	}

	private static function separate_mobile_caching() {
		return 'if ( sbp_is_mobile() ) {
		$cache_dir = WP_CONTENT_DIR . \'/cache/speed-booster/mobile\';
	}';
	}

	private static function caching_query_string_includes() {
		return '\'' . self::$options['caching_include_query_strings'] . '\'';
	}

	private static function caching_exclude_urls() {
		return '\'' . self::$options['caching_exclude_urls'] . '\'';
	}

	private static function caching_exclude_cookies() {
		$arrayString      = '';
		$excluded_cookies = self::$options['caching_exclude_cookies'];
		if ( $excluded_cookies ) {
			$cookies = SBP_Utils::explode_lines( $excluded_cookies );
			foreach ( $cookies as $cookie ) {
				if ( $cookie ) {
					$cookie      = addslashes( $cookie );
					$arrayString .= "'$cookie', ";
				}
			}
		}

		return $arrayString;
	}

	private static function caching_expiry() {
		return self::$options['caching_expiry'];
	}
}