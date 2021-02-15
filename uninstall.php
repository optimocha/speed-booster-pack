<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! defined( 'SBP_CACHE_DIR' ) ) {
	define( 'SBP_CACHE_DIR', WP_CONTENT_DIR . '/cache/speed-booster/' );
}

if ( ! defined( 'SBP_UPLOADS_DIR' ) ) {
	define( 'SBP_UPLOADS_DIR', WP_CONTENT_DIR . '/uploads/speed-booster/' );
}

// Delete Directory Function
function delete_dir( $dir ) {
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
			delete_dir( $object );
		} else {
			@unlink( $object );
		}
	}

	@rmdir( $dir );

	clearstatcache();
}

delete_option( 'sbp_options' );
delete_option( 'sbp_notice_error' );
delete_option( 'sbp_transient_error' );
delete_dir( SBP_CACHE_DIR );
delete_dir( SBP_UPLOADS_DIR );

// Clear htaccess
global $wp_filesystem;

require_once( ABSPATH . '/wp-admin/includes/file.php' );
WP_Filesystem();

$htaccess_file_path = get_home_path() . '/.htaccess';

if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
	$current_htaccess = $wp_filesystem->get_contents( $htaccess_file_path );

	if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
		$current_htaccess = trim( $wp_filesystem->get_contents( $htaccess_file_path ) );
		$current_htaccess = preg_replace( '/(# BEGIN Speed Booster Pack.*?# END Speed Booster Pack' . PHP_EOL . PHP_EOL . ')/msi', '', $current_htaccess );
	}

	$wp_filesystem->put_contents( get_home_path() . '/.htaccess', $current_htaccess );
}

// Remove SBP Announcements
delete_option( 'sbp_announcements' );
delete_transient( 'sbp_notice_cache' );
delete_transient( 'sbp_cloudflare_status' );
delete_transient( 'sbp_upgraded_notice' );

// Delete user metas
$users = get_users( 'role=administrator' );
foreach ( $users as $user ) {
	delete_user_meta( $user->ID, 'sbp_dismissed_notices' );
	delete_user_meta( $user->ID, 'sbp_dismissed_compat_notices' );
}

// Delete injected lines from wp-config.php
if ( $wp_filesystem->exists( ABSPATH . 'wp-config.php' ) ) {
	$wp_config_file = ABSPATH . 'wp-config.php';
} else {
	$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
}

$wp_config_content = $wp_filesystem->get_contents( $wp_config_file );
$config_regex = '/\/\/ BEGIN SBP_WP_Config(.*?)\/\/ END SBP_WP_Config/si';
if ( preg_match( $config_regex, $wp_config_content ) ) {
	if ($wp_filesystem->is_writable($wp_config_file)) {
		$modified_wp_config_content = preg_replace( $config_regex, '', $wp_config_content );
		$wp_filesystem->put_contents( $wp_config_file, $modified_wp_config_content );
	}
}
