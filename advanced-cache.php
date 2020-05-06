<?php

// check if request method is GET
if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] != 'GET' ) {
	return false;
}

// Check if user logged in
if ( ! empty( $_COOKIE ) ) {
	$cookies_regex = '/^(comment_author|wordpress_logged_in|wp-postpass)_/';

	foreach ( $_COOKIE as $key => $value ) {
		if ( preg_match( $cookies_regex, $key ) ) {
			return false;
		}
	}
}

// Get settings
$settings_file = WP_CONTENT_DIR . '/cache/speed-booster/settings.json';
$settings      = sbp_parse_settings_file( $settings_file );

// Set default file name
$filename = 'index.html';

// Check if mobile cache is active
if ( sbp_is_mobile() && isset( $settings['show_mobile_cache'] ) && ! $settings['show_mobile_cache'] ) {
	return false;
}

// Check for query strings
if ( ! empty( $_GET ) && isset( $settings['include_query_strings'] ) ) {
	// Get included rules
	$include_query_strings = sbp_explode_lines( $settings['include_query_strings'] );

	$query_string_file_name = '';
	// Put all query string parameters in order to generate same filename even if parameter order is different
	ksort( $_GET );

	foreach ( $_GET as $key => $value ) {
		if ( in_array( $key, $include_query_strings ) ) {
			$query_string_file_name .= "$key-$value-";
		}
	}

	if ( '' !== $query_string_file_name ) {
		$query_string_file_name .= '.html';
		$filename               = $query_string_file_name;
	}
}

// base path
$cache_file_path = get_cache_file_path() . $filename;
if ( ! is_readable( $cache_file_path ) ) {
	return false;
}

// Check if cache file is expired
if ( isset( $settings['cache_expire_time'] ) && ! empty( $settings['cache_expire_time'] ) ) {
	if ( ( filemtime( $cache_file_path ) + $settings['cache_expire_time'] ) < time() ) {
		return false;
	}
}

if ( isset( $settings['exclude_urls'] ) ) {
	$exclude_urls = array_map( 'trim', explode( PHP_EOL, $settings['exclude_urls'] ) );
	if ( count( $exclude_urls ) > 0 && in_array( $_SERVER['REQUEST_URI'], $exclude_urls ) ) {
		return false;
	}
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
	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
		$is_mobile = false;
	} elseif ( strpos( $_SERVER['HTTP_USER_AGENT'], 'Mobile' ) !== false // Many mobile devices (all iPhone, iPad, etc.)
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Android' ) !== false
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Silk/' ) !== false
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Kindle' ) !== false
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'BlackBerry' ) !== false
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mini' ) !== false
	           || strpos( $_SERVER['HTTP_USER_AGENT'], 'Opera Mobi' ) !== false ) {
		$is_mobile = true;
	} else {
		$is_mobile = false;
	}

	return $is_mobile;
}


// generate cache path
function get_cache_file_path() {
	global $settings;
	$cache_dir = WP_CONTENT_DIR . '/cache/speed-booster';

	if ( sbp_is_mobile() && isset( $settings['show_mobile_cache'] ) && $settings['show_mobile_cache'] && isset( $settings['separate_mobile_cache'] ) && $settings['separate_mobile_cache'] ) {
		$cache_dir = WP_CONTENT_DIR . '/cache/speed-booster/.mobile';
	}

	$path = sprintf(
		'%s%s%s%s',
		$cache_dir,
		DIRECTORY_SEPARATOR,
		parse_url(
			'http://' . strtolower( $_SERVER['HTTP_HOST'] ),
			PHP_URL_HOST
		),
		parse_url(
			$_SERVER['REQUEST_URI'],
			PHP_URL_PATH
		)
	);

	if ( is_file( $path ) > 0 ) {
		wp_die( __('Error occured on SBP cache. Please contact you webmaster.', 'speed-booster') );
	}

	return rtrim( $path, "/" ) . "/";
}

// read settings file
function sbp_parse_settings_file( $settings_file ) {
	if ( ! file_exists( $settings_file ) ) {
		return [];
	}

	if ( ! $settings = json_decode( file_get_contents( $settings_file ), true ) ) {
		return [];
	}

	return $settings;
}

function sbp_explode_lines( $text ) {
	if ( $text === '' ) {
		return [];
	}

	return array_map( 'trim', explode( PHP_EOL, $text ) );
}