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

$filename = 'index.html';

// Check for query strings
if ( ! empty( $_GET ) && isset( $settings['include_query_strings'] ) ) {
	// Get included rules
	$include_query_strings = sbp_explode_lines( $settings['include_query_strings'] );

	$query_string_file_name = '';
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

// check GET variables
if ( ! empty( $_GET ) ) {

	$excluded_parameters = [ 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ];
	if ( count( array_intersect( array_keys( $_GET ), $excluded_parameters ) ) > 0 ) {
		return false;
	}
}

// TODO: This is not finished yet.
if ( isset( $settings['exclude_urls'] ) ) {
	$exclude_urls = array_map( 'trim', explode( PHP_EOL, $settings['exclude_urls'] ) );
	if ( count( $exclude_urls ) > 0 && in_array( $_SERVER['REQUEST_URI'], $exclude_urls ) ) {
		return false;
	}
}

// deliver cached file (default)
readfile( $cache_file_path );
exit;


// generate cache path
function get_cache_file_path() {
	$cache_dir = WP_CONTENT_DIR . '/cache/speed-booster';
//	if ( wp_is_mobile() && sbp_get_option( 'separate-mobile-cache', false ) ) {
//		$cache_dir = SBP_CACHE_DIR . '/.mobile';
//	}

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
		wp_die( 'Error occured on SBP cache. Please contact you webmaster.' );
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