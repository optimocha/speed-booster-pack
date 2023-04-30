<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @since      4.0.0
 *
 * @package    Optimocha\SpeedBooster
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
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
delete_dir( WP_CONTENT_DIR . '/cache/speed-booster/' );
delete_dir( wp_get_upload_dir()['basedir'] . '/speed-booster/' );

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
delete_transient( 'sbp_warmup_errors' );
delete_transient( 'sbp_warmup_completed' );

// Delete user metas
$users = get_users( 'role=administrator' );
foreach ( $users as $user ) {
	delete_user_meta( $user->ID, 'sbp_dismissed_notices' );
	delete_user_meta( $user->ID, 'sbp_dismissed_compat_notices' );
	delete_user_meta( $user->ID, 'sbp_tweet_notice_display_time' );
	delete_user_meta( $user->ID, 'sbp_rate_wp_org_notice_display_time' );
	delete_user_meta( $user->ID, 'sbp_hide_newsletter_pointer' );
	delete_user_meta( $user->ID, 'sbp_newsletter_display_time' );
	delete_user_meta( $user->ID, 'sbp_dismissed_messages' );
	delete_user_meta( $user->ID, 'sbp_intro' );
}

// TODO: let's make a tool called "Cleanup SBP metadata" in a future version
// $posts = new WP_Query([
//     'post_type' => 'any',
//     'meta_key' => 'sbp_post_meta',
// ]);
// foreach ($posts->get_posts() as $post) {
//     delete_post_meta($post->ID, 'sbp_post_meta');
// }