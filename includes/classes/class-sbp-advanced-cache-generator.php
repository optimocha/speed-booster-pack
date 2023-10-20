<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Advanced_Cache_Generator {
	private static $options = [];

	public static function generate_advanced_cache_file( $options = [] ) {
		if ($options === []) {
			$options = [
				'caching_separate_mobile' => sbp_get_option('caching_separate_mobile'),
				'caching_include_query_strings' => sbp_get_option('caching_include_query_strings'),
				'caching_expiry' => sbp_get_option('caching_expiry'),
				'caching_exclude_urls' => sbp_get_option('caching_exclude_urls'),
				'caching_exclude_cookies' => sbp_get_option('caching_exclude_cookies'),
			];
		}

		self::$options = $options;

		return self::generate_file_content();
	}

	public static function generate_file_content() {
		$advanced_cache_file_content = '<?php

define( \'SBP_ADVANCED_CACHE\', true );

// check if request method is GET
if ( ! isset( $_SERVER[\'REQUEST_METHOD\'] ) || $_SERVER[\'REQUEST_METHOD\'] != \'GET\' ) {
	return false;
}';

		// Excluded Cookies
		$advanced_cache_file_content .= '
// Check if user logged in
if ( ! empty( $_COOKIE ) ) {
	$cookies       = [ \'comment_author_\', \'wordpress_logged_in_\', \'wp-postpass_\', ' . self::caching_exclude_cookies() . ' ];
	$cookies       = array_map( \'addslashes\', $cookies );
	$cookies       = array_filter( $cookies );
	$cookies_regex = \'/^(\' . implode( \'|\', $cookies ) . \')/\';

	foreach ( $_COOKIE as $key => $value ) {
		if ( preg_match( $cookies_regex, $key ) ) {
			return false;
		}
	}
}';

		// Query Strings
		$advanced_cache_file_content .= '
// Set default file name
$filename = \'index.html\';

// Check for query strings
if ( ! empty( $_GET ) ) {
	// Get included rules
	$include_query_strings = sbp_explode_lines( \'' . self::caching_query_string_includes() . '\' );

	$query_string_file_name = \'\';
	// Put all query string parameters in order to generate same filename even if parameter order is different
	ksort( $_GET );

	foreach ( $_GET as $key => $value ) {
		if ( in_array( $key, $include_query_strings ) ) {
			$query_string_file_name .= "$key-$value-";
		} else {
			return false;
		}
	}

	if ( \'\' !== $query_string_file_name ) {
		$filename = md5( $query_string_file_name ) . \'.html\';
	}
}

// base path
$cache_file_path = get_cache_file_path() . $filename;
if ( ! is_readable( $cache_file_path ) ) {
	return false;
}';

		// Caching Expiry
		$advanced_cache_file_content .= '
// Check if cache file is expired
$caching_expiry = ' . self::caching_expiry() . ' * HOUR_IN_SECONDS;
if ( ( filemtime( $cache_file_path ) + $caching_expiry ) < time() ) {
	return false;
}';

		// Exclude URL's
		$advanced_cache_file_content .= '
$exclude_urls = sbp_explode_lines( \'' . self::caching_exclude_urls() . '\' );
$current_url  = rtrim( $_SERVER[\'HTTP_HOST\'] . $_SERVER[\'REQUEST_URI\'], \'/\' );
if ( count( $exclude_urls ) > 0 && in_array( $current_url, $exclude_urls ) ) {
	return false;
}

// output cached file
readfile( $cache_file_path );
exit;

/**
 * Copied from WordPress wp_is_mobile
 *
 * @return bool
 */
function sbp_is_mobile() {
	if ( empty( $_SERVER[\'HTTP_USER_AGENT\'] ) ) {
		$is_mobile = false;
	} elseif ( strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Mobile\' ) !== false // Many mobile devices (all iPhone, iPad, etc.)
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Android\' ) !== false
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Silk/\' ) !== false
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Kindle\' ) !== false
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'BlackBerry\' ) !== false
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Opera Mini\' ) !== false
	           || strpos( $_SERVER[\'HTTP_USER_AGENT\'], \'Opera Mobi\' ) !== false ) {
		$is_mobile = true;
	} else {
		$is_mobile = false;
	}

	return $is_mobile;
}


// generate cache path
function get_cache_file_path() {
	$cache_dir = WP_CONTENT_DIR . \'/cache/speed-booster\';
	';

		// Separate Mobile
		if (self::$options['caching_separate_mobile']) {
			$advanced_cache_file_content .= 'if ( sbp_is_mobile() ) {
		$cache_dir = WP_CONTENT_DIR . \'/cache/speed-booster/mobile\';
	}';
		}

		$advanced_cache_file_content .= '
	$path = sprintf(
		\'%s%s%s%s\',
		$cache_dir,
		DIRECTORY_SEPARATOR,
		parse_url(
			\'http://\' . strtolower( $_SERVER[\'HTTP_HOST\'] ),
			PHP_URL_HOST
		),
		parse_url(
			$_SERVER[\'REQUEST_URI\'],
			PHP_URL_PATH
		)
	);

	if ( is_file( $path ) > 0 ) {
		wp_die( \'Error occured on SBP cache. Please contact you webmaster.\' );
	}

	return rtrim( $path, "/" ) . "/";
}

function sbp_explode_lines( $text ) {
	if ( ! $text ) {
		return [];
	}

	return array_map( \'trim\', explode( PHP_EOL, $text ) );
}';

		return $advanced_cache_file_content;
	}

	private static function caching_query_string_includes() {
		return addslashes( self::$options['caching_include_query_strings'] );
	}

	private static function caching_exclude_urls() {
		return addslashes( self::$options['caching_exclude_urls'] );
	}

	private static function caching_exclude_cookies() {
		$array_string      = '';
		$excluded_cookies = self::$options['caching_exclude_cookies'];
		if ( $excluded_cookies ) {
			$cookies = SBP_Utils::explode_lines( $excluded_cookies );
			foreach ( $cookies as $cookie ) {
				if ( $cookie ) {
					$cookie      = addslashes( $cookie );
					$array_string .= "'$cookie', ";
				}
			}
		}

		return $array_string;
	}

	private static function caching_expiry() {
		return (int) self::$options['caching_expiry'];
	}
}
