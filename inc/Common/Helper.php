<?php
/**
 * Helper class for common functions
 *
 * @package SpeedBoosterPack
 * @subpackage SpeedBoosterPack\Common
 * @since 5.0.0
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Common;

use SpeedBoosterPack\Configurations\Constants;

defined( 'ABSPATH' ) || exit;

class Helper {

	/**
	 * Returns the value of the option with given name, if option doesn't exist function returns the default value (from second variable)
	 *
	 * @param string $option
	 * @param null $default
	 *
	 * @return mixed|null
	 *
	 * @since 5.0.0
	 */
	public static function getOption( string $option = '', $default = null ) {

		$sbp_options = get_option( 'sbp_options' );

		return ( isset( $sbp_options[ $option ] ) ) ? $sbp_options[ $option ] : $default;

	}

	/**
	 * Sanitize input
	 *
	 * @param string $name Input name
	 * @param string $method GET or POST
	 * @param string $type Type of input (title, id, textarea, url, email, username, text, bool)
	 *
	 * @return array|bool|int|string|null
	 *
	 * @since 5.0.0
	 */
	public static function sanitize( string $name, string $method, string $type = '' ) {
		$input = strtolower( $method ) === 'post' ? $_POST : $_GET;

		if ( ! isset( $input[ $name ] ) ) {
			return null;
		}

		$value = $input[ $name ];

		if ( is_array( $value ) ) {
			return self::sanitizeArray( $value );
		}

		$value = sanitize_text_field( $value );

		switch ( $type ) {
			case "title":
				return sanitize_title( $value );
			case "id":
				return absint( $value );
			case "textarea":
				return sanitize_textarea_field( $value );
			case "url":
				return esc_url_raw( $value );
			case "email":
				return sanitize_email( $value );
			case "username":
				return sanitize_user( $value );
			case "text":
				return $value; // Already sanitized, but for consistency
			case "bool":
				return rest_sanitize_boolean( $value );
			default:
				return $value;
		}
	}

	/**
	 * Sanitize array recursively
	 *
	 * @param array $array
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public static function sanitizeArray( array $array ): array {
		return array_map( function ( $value ) {
			if ( is_array( $value ) ) {
				return $this->sanitizeArray( $value );
			}

			return sanitize_text_field( $value );
		}, array_combine(
			array_map( 'sanitize_text_field', array_keys( $array ) ),
			$array
		) );
	}

	public static function explodeLines( $text, $unique = true ): array {
		if ( ! $text ) {
			return [];
		}

		if ( true === $unique ) {
			return array_filter( array_unique( array_map( 'trim', explode( PHP_EOL, $text ) ) ) );
		} else {
			return array_filter( array_map( 'trim', explode( PHP_EOL, $text ) ) );
		}
	}

	public static function getFileExtensionFromUrl( $url ) {
		$url = self::clearHashesAndQuestionMark( $url );

		return pathinfo( $url, PATHINFO_EXTENSION );
	}

	public static function clearHashesAndQuestionMark( $url ) {
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
	public static function isPluginActive( $plugin ): bool {
		$is_plugin_active_for_network = false;

		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( isset( $plugins[ $plugin ] ) ) {
			$is_plugin_active_for_network = true;
		}

		return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || $is_plugin_active_for_network;
	}

	public static function insertToHtaccess( $marker_name, $content ): bool {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$htaccess_file_path = get_home_path() . '/.htaccess';

		if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
			add_action( 'admin_init', function () use ( $htaccess_file_path, $marker_name, $content ) {
				insert_with_markers( $htaccess_file_path, $marker_name, $content );
			} );
		}

		return false;
	}

	public static function isLitespeed(): bool {
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
		if ( ! empty ( $_SERVER['X-LSCACHE'] ) || LITESPEED_SERVER_TYPE === 'LITESPEED_SERVER_ADC' || defined( 'LITESPEED_CLI' ) ) {
			! defined( 'LITESPEED_ALLOWED' ) && define( 'LITESPEED_ALLOWED', true );
		}

		return LITESPEED_SERVER_TYPE !== 'NONE' ? LITESPEED_SERVER_TYPE && LITESPEED_ALLOWED : false;
	}

	/**
	 * Removes the http and https prefixes from url's
	 *
	 * @param $url
	 *
	 * @return array|string|string[]
	 */
	public static function removeProtocol( $url ) {
		return str_replace( [ 'http://', 'https://' ], [ '//', '//' ], $url );
	}

	public static function sbpGetFilesystem() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		return $wp_filesystem;
	}

	public static function sbpDeleteDirRecursively( $dir ) {
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
				self::sbpDeleteDirRecursively( $object );
			} else {
				@unlink( $object );
			}
		}

		@rmdir( $dir );

		clearstatcache();
	}

	public static function sbpGetHostingRestrictions(): array {
		if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) && $_SERVER['KINSTA_CACHE_ZONE'] ) {
			return [
				'name'              => 'Kinsta',
				'disabled_features' => [ 'caching' ],
				/* translators: both %s instances are the names of the hosting company.  */
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Kinsta', 'Kinsta' ),
			];
		}

		if ( function_exists( 'is_wpe' ) || function_exists( 'is_wpe_snapshot' ) ) {
			return [
				'name'              => 'WP Engine',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'WP Engine', 'WP Engine' ),
			];
		}

		$hosting_provider_constants = Constants::hostingProviderConstants();

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

	public static function sbpShouldDisableFeature( $feature_name ) {
		$hosting_restrictions = self::sbpGetHostingRestrictions();

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

	public static function sbpStrReplaceFirst( $from, $to, $content ) {
		$from = '/' . preg_quote( $from, '/' ) . '/';

		return preg_replace( $from, $to, $content, 1 );
	}

	/**
	 * Returns absolute value of a number. Returns 1 if value is zero.
	 *
	 * @param $value
	 *
	 * @return float|int
	 * @since 4.0.0
	 *
	 */
	public static function sbpPosabs( $value ) {
		if ( 0 == $value ) {
			return 1;
		}

		return absint( $value );
	}

	// TODO: use esc_url() instead

	/**
	 * @param $url
	 *
	 * @return array|string|string[]
	 *
	 * Modified version of WordPress's esc_url function
	 */
	public static function sbpSanitizeUrl( $url ) {
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

	/**
	 * Removes http:// from the url
	 *
	 * @param $url
	 *
	 * @return string
	 * @since 4.0.0
	 *
	 */
	public static function sbpClearHttp( $url ): string {
		return strip_tags( str_replace( "http://", "//", $url ) );
	}

	/**
	 * Removes duplicated and empty elements from an array.
	 *
	 * @param $value array
	 *
	 * @return array
	 * @since 4.2.0
	 *
	 */
	public static function sbpRemoveDuplicatesAndEmpty( $value ): array {
		$value = array_filter( $value );

		return array_unique( $value );
	}

	/**
	 * Trims and strips the tags from given value. Takes one dimensional array or string as argument. Returns the modified value.
	 *
	 * @param $value array|string
	 *
	 * @return array|string
	 */
	public static function sbpSanitizeStripTags( $value ) {
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

	public static function sbpRemoveLeadingString( $string, $remove ) {
		if ( substr( $string, 0, strlen( $remove ) ) == $remove ) {
			$string = substr( $string, strlen( $remove ) );
		}

		return $string;
	}

	/**
	 * Sanitizes excluded URLs for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.0.0
	 *
	 */
	public static function sbpSanitizeCachingUrls( $urls ): string {
		$urls = strip_tags( $urls );
		$urls = self::explodeLines( $urls );
		$urls = self::sbpRemoveDuplicatesAndEmpty( $urls );
		foreach ( $urls as &$url ) {
			$url = self::sbpRemoveLeadingString( $url, 'https://' );
			$url = self::sbpRemoveLeadingString( $url, 'http://' );
			$url = self::sbpRemoveLeadingString( $url, '//' );
			$url = rtrim( $url, '/' );
			$url = self::sbpSanitizeUrl( $url );
		}

		return implode( PHP_EOL, $urls );
	}

	// TODO: use rawurlencode() instead (and rawurldecode() while retrieving)

	/**
	 * Sanitizes excluded cookies for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.2.0
	 *
	 */
	public static function sbpSanitizeCachingCookies( $urls ): string {
		$urls = strip_tags( $urls );
		$urls = str_replace( [ '(', ')', '[', ']', '*', '$', '/', '|', '.' ], [
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'',
			'\.'
		], $urls );
		$urls = self::explodeLines( $urls );
		$urls = self::sbpRemoveDuplicatesAndEmpty( $urls );

		return implode( PHP_EOL, $urls );
	}

	/**
	 * Sanitizes included query strings for caching
	 *
	 * @param $urls
	 *
	 * @return string
	 * @since 4.2.0
	 *
	 */
	public static function sbpSanitizeQueryStrings( $urls ): string {
		$urls = strip_tags( $urls );
		$urls = self::explodeLines( $urls );
		$urls = self::sbpRemoveDuplicatesAndEmpty( $urls );

		return implode( PHP_EOL, $urls );
	}

	// TODO: use boolval() instead
	public static function sbpSanitizeBoolean( $value ): string {
		return $value == '1' ? '1' : '0';
	}

	public static function sbpGetPostMeta( $post_id, $option_key, $default = null ) {
		$post_meta = get_post_meta( $post_id, 'sbp_post_meta', true );

		return ( isset( $post_meta[ $option_key ] ) ) ? $post_meta[ $option_key ] : $default;
	}

	public static function sbpCheckFilePermissions( $file_path, $check = 'write' ) {
		if ( 'write' !== $check && 'read' !== $check && 'both' !== $check ) {
			return null;
		}

		$wp_filesystem = self::sbpGetFilesystem();

		switch ( $check ) {
			case "write":
				return $wp_filesystem->is_writable( $file_path );
			case "read":
				return $wp_filesystem->is_readable( $file_path );
			case "both":
				return $wp_filesystem->is_writable( $file_path ) && $wp_filesystem->is_readable( $file_path );
		}
	}

	public static function sbpProperParseStr( $str ): array {
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

	//TODO check paths
	public static function sbpGetWpConfigPath(): string {
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		return $wp_config_file;
	}

	public static function sbpIsWpConfigWritable(): bool {
		$wp_config_file = self::sbpGetWpConfigPath();

		return file_exists( $wp_config_file ) && self::sbpCheckFilePermissions( $wp_config_file );
	}

}