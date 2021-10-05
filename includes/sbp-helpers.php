<?php

use SpeedBooster\SBP_Utils;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'sbp_get_filesystem' ) ) {
	function sbp_get_filesystem() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		return $wp_filesystem;
	}
}

if ( ! function_exists( 'sbp_delete_dir_recursively' ) ) {
	function sbp_delete_dir_recursively( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$dir_objects = @scandir( $dir );
		$objects     = array_filter( $dir_objects,
			function ( $object ) {
				return $object != '.' && $object != '..';
			} );

		if ( empty( $objects ) ) {
			return;
		}

		foreach ( $objects as $object ) {
			$object = $dir . DIRECTORY_SEPARATOR . $object;

			if ( is_dir( $object ) ) {
				sbp_delete_dir_recursively( $object );
			} else {
				@unlink( $object );
			}
		}

		@rmdir( $dir );

		clearstatcache();
	}
}

if ( ! function_exists( 'sbp_get_hosting_restrictions' ) ) {
	function sbp_get_hosting_restrictions() {
		if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) && $_SERVER['KINSTA_CACHE_ZONE'] ) {
			return [
				'name'              => 'Kinsta',
				'disabled_features' => [ 'caching' ],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Kinsta', 'Kinsta' ),
			];
		}

		if ( function_exists( 'is_wpe' ) || function_exists( 'is_wpe_snapshot' ) ) {
			return [
				'name'              => 'WP Engine',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'WP Engine', 'WP Engine' ),
			];
		}

		$hosting_provider_constants = [
			'GD_SYSTEM_PLUGIN_DIR' => [
				'name'              => 'GoDaddy',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'GoDaddy', 'GoDaddy' ),
			],
			'MM_BASE_DIR'          => [
				'name'              => 'Bluehost',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Bluehost', 'Bluehost' ),
			],
			'PAGELYBIN'            => [
				'name'              => 'Pagely',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Pagely', 'Pagely' ),
			],
			'KINSTAMU_VERSION'     => [
				'name'              => 'Kinsta',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Kinsta', 'Kinsta' ),
			],
			'FLYWHEEL_CONFIG_DIR'  => [
				'name'              => 'Flywheel',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Flywheel', 'Flywheel' ),
			],
			'IS_PRESSABLE'         => [
				'name'              => 'Pressable',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Pressable', 'Pressable' ),
			],
			'VIP_GO_ENV'           => [
				'name'              => 'WordPress VIP',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'WordPress VIP', 'WordPress VIP' ),
			],
			'KINSTA_CACHE_ZONE'    => [
				'name'              => 'Kinsta',
				'disabled_features' => [ 'caching' ],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.' ), 'Kinsta', 'Kinsta' ),
			],
		];

		foreach ( $hosting_provider_constants as $constant => $company_info ) {
			if ( defined( $constant ) && constant( $constant ) ) {
				return $company_info;
			}
		}

		return [
			'name'              => null,
			'disabled_features' => [],
			'error_message'     => '',
		]; // Return this structure to avoid undefined index errors.
	}
}

if ( ! function_exists( 'sbp_should_disable_feature' ) ) {
	function sbp_should_disable_feature( $feature_name ) {
		$hosting_restrictions = sbp_get_hosting_restrictions();

		if ( $hosting_restrictions['name'] !== null ) {
			if ( in_array( $feature_name, $hosting_restrictions['disabled_features'] ) ) {
				return $hosting_restrictions;
			}
		}

		if ( ! get_option( 'permalink_structure' ) ) {
			return [
				'name'              => 'Permalink',
				'disabled_features' => [ 'caching' ],
				'error_message'     => __( 'You should enable permalinks for the caching module.', 'speed-booster-pack' ),
			];
		}

		return false;
	}
}

if ( ! function_exists( 'sbp_str_replace_first' ) ) {
	function sbp_str_replace_first( $from, $to, $content ) {
		$from = '/' . preg_quote( $from, '/' ) . '/';

		return preg_replace( $from, $to, $content, 1 );
	}
}

if ( ! function_exists( 'sbp_get_filesystem' ) ) {
	/**
	 * Return WP_Filesystem instance
	 *
	 * @return mixed
	 */
	function sbp_get_filesystem() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		return $wp_filesystem;
	}
}

if ( ! function_exists( 'sbp_posabs' ) ) {
	/**
	 * Returns absolute value of a number. Returns 1 if value is zero.
	 *
	 * @param $value
	 *
	 * @return float|int
	 * @since 4.0.0
	 *
	 */
	function sbp_posabs( $value ) {
		if ( 0 == $value ) {
			return 1;
		}

		return absint( $value );
	}
}

if ( ! function_exists( 'sbp_clear_cdn_url' ) ) {
	/**
	 * Removes http(s?):// and trailing slash from the url
	 *
	 * @param $url
	 *
	 * @return string
	 * @since 4.0.0
	 *
	 */
	function sbp_clear_cdn_url( $url ) {
		$url = str_replace( [ '"', "'" ], [ '', '' ], $url );

		return preg_replace( "#^[^:/.]*[:/]+#i", "", rtrim( $url, '/' ) );
	}
}

if ( ! function_exists( 'sbp_sanitize_url' ) ) {
	/**
	 * @param $url
	 *
	 * @return mixed|string|void
	 *
	 * Modified version of WordPress's esc_url function
	 */
	function sbp_sanitize_url( $url ) {
		$original_url = $url;

		if ( '' === $url ) {
			return $url;
		}

		$url = str_replace( [ ' ', '"', "'" ], [ '%20', '%22', '%27' ], ltrim( $url ) );
		$url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url );

		if ( '' === $url ) {
			return $url;
		}

		if ( 0 !== stripos( $url, 'mailto:' ) ) {
			$strip = array( '%0d', '%0a', '%0D', '%0A' );
			$url   = _deep_replace( $strip, $url );
		}

		$url = str_replace( ';//', '://', $url );

		if ( ( false !== strpos( $url, '[' ) ) || ( false !== strpos( $url, ']' ) ) ) {

			$parsed = wp_parse_url( $url );
			$front  = '';

			if ( isset( $parsed['scheme'] ) ) {
				$front .= $parsed['scheme'] . '://';
			} elseif ( '/' === $url[0] ) {
				$front .= '//';
			}

			if ( isset( $parsed['user'] ) ) {
				$front .= $parsed['user'];
			}

			if ( isset( $parsed['pass'] ) ) {
				$front .= ':' . $parsed['pass'];
			}

			if ( isset( $parsed['user'] ) || isset( $parsed['pass'] ) ) {
				$front .= '@';
			}

			if ( isset( $parsed['host'] ) ) {
				$front .= $parsed['host'];
			}

			if ( isset( $parsed['port'] ) ) {
				$front .= ':' . $parsed['port'];
			}

			$end_dirty = str_replace( $front, '', $url );
			$end_clean = str_replace( array( '[', ']' ), array( '%5B', '%5D' ), $end_dirty );
			$url       = str_replace( $end_dirty, $end_clean, $url );

		}

		return $url;
	}
}

if ( ! function_exists( 'sbp_clear_http' ) ) {
	/**
	 * Removes http:// from the url
	 *
	 * @param $url
	 *
	 * @return string
	 * @since 4.0.0
	 *
	 */
	function sbp_clear_http( $url ) {
		return strip_tags( str_replace( "http://", "//", $url ) );
	}
}

if ( ! function_exists( 'sbp_remove_duplicates_and_empty' ) ) {
	/**
	 * Removes duplicated and empty elements from an array.
	 *
	 * @param $value array
	 *
	 * @return array
	 * @since 4.2.0
	 *
	 */
	function sbp_remove_duplicates_and_empty( $value ) {
		$value = array_filter( $value );

		return array_unique( $value );
	}
}

if ( ! function_exists( 'sbp_sanitize_strip_tags' ) ) {
	/**
	 * Trims and strips the tags from given value. Takes one dimensional array or string as argument. Returns the modified value.
	 *
	 * @param $value array|string
	 *
	 * @return array|string
	 */
	function sbp_sanitize_strip_tags( $value ) {
		if ( is_array( $value ) ) {
			$value = array_map(
				function ( $item ) {
					return trim( strip_tags( $item ) );
				},
				$value
			);
		} else {
			$value = trim( strip_tags( $value ) );
		}

		return $value;
	}
}

if ( ! function_exists( 'sbp_remove_leading_string' ) ) {
	function sbp_remove_leading_string( $string, $remove ) {
		if ( substr( $string, 0, strlen( $remove ) ) == $remove ) {
			$string = substr( $string, strlen( $remove ) );
		}

		return $string;
	}
}

if ( ! function_exists( 'sbp_sanitize_caching_urls' ) ) {
	/**
	 * Sanitizes excluded URLs for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.0.0
	 *
	 */
	function sbp_sanitize_caching_urls( $urls ) {
		$urls = strip_tags( $urls );
		$urls = SBP_Utils::explode_lines( $urls );
		$urls = sbp_remove_duplicates_and_empty( $urls );
		foreach ( $urls as &$url ) {
			$url = sbp_remove_leading_string( $url, 'https://' );
			$url = sbp_remove_leading_string( $url, 'http://' );
			$url = sbp_remove_leading_string( $url, '//' );
			$url = rtrim( $url, '/' );
			$url = sbp_sanitize_url( $url );
		}

		return implode( PHP_EOL, $urls );
	}
}

if ( ! function_exists( 'sbp_sanitize_caching_cookies' ) ) {
	/**
	 * Sanitizes excluded cookies for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.2.0
	 *
	 */
	function sbp_sanitize_caching_cookies( $urls ) {
		$urls = strip_tags( $urls );
		$urls = str_replace( [ '(', ')', '[', ']', '*', '$', '/', '|', '.' ], [ '', '', '', '', '', '', '', '', '\.' ], $urls );
		$urls = SBP_Utils::explode_lines( $urls );
		$urls = sbp_remove_duplicates_and_empty( $urls );

		return implode( PHP_EOL, $urls );
	}
}

if ( ! function_exists( 'sbp_sanitize_caching_included_query_strings' ) ) {
	/**
	 * Sanitizes included query strings for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.2.0
	 *
	 */
	function sbp_sanitize_caching_included_query_strings( $urls ) {
		$urls = strip_tags( $urls );
		$urls = SBP_Utils::explode_lines( $urls );
		$urls = sbp_remove_duplicates_and_empty( $urls );

		return implode( PHP_EOL, $urls );
	}
}

if ( ! function_exists( 'sbp_sanitize_special_characters' ) ) {
	function sbp_sanitize_special_characters( $param ) {
		return filter_var( $param, FILTER_SANITIZE_SPECIAL_CHARS );
	}
}

if ( ! function_exists( 'sbp_sanitize_boolean' ) ) {
	function sbp_sanitize_boolean( $value ) {
		return $value == '1' ? '1' : '0';
	}
}

if ( ! function_exists( 'sbp_get_post_meta' ) ) {
	function sbp_get_post_meta( $post_id, $option_key, $default = null ) {
		$post_meta = get_post_meta( $post_id, 'sbp_post_meta', true );

		return ( isset( $post_meta[ $option_key ] ) ) ? $post_meta[ $option_key ] : $default;
	}
}

if ( ! function_exists( 'sbp_check_file_permissions' ) ) {
	function sbp_check_file_permissions( $file_path, $check = 'write' ) {
		if ( 'write' !== $check && 'read' !== $check && 'both' !== $check ) {
			return null;
		}

		$wp_filesystem = sbp_get_filesystem();

		switch ( $check ) {
			case "write":
				return $wp_filesystem->is_writable( $file_path );
			case "read":
				return $wp_filesystem->is_readable( $file_path );
			case "both":
				return $wp_filesystem->is_writable( $file_path ) && $wp_filesystem->is_readable( $file_path );
		}
	}
}

if ( ! function_exists( 'sbp_sanitize_titles_in_array' ) ) {
	function sbp_sanitize_titles_in_array( $array ) {
		if ( is_array( $array ) ) {
			return array_map( 'sanitize_title', $array );
		}

		return $array;
	}
}

if ( ! function_exists( 'sbp_proper_parse_str' ) ) {
	function sbp_proper_parse_str( $str ) {
		# result array
		$arr = array();

		# split on outer delimiter
		$pairs = explode( '&', $str );

		# loop through each pair
		foreach ( $pairs as $i ) {
			# split into name and value
			list( $name, $value ) = explode( '=', $i, 2 );

			# if name already exists
			if ( isset( $arr[ $name ] ) ) {
				# stick multiple values into an array
				if ( is_array( $arr[ $name ] ) ) {
					$arr[ $name ][] = $value;
				} else {
					$arr[ $name ] = array( $arr[ $name ], $value );
				}
			} # otherwise, simply stick it in a scalar
			else {
				$arr[ $name ] = $value;
			}
		}

		# return result array
		return $arr;
	}
}

if ( ! function_exists( 'sbp_get_public_post_types' ) ) {
	function sbp_get_public_post_types() {
		$post_types = get_option( 'sbp_public_post_types' );
		return is_array( $post_types ) ? $post_types : [];
	}
}
