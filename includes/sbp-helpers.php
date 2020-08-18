<?php

function sbp_get_filesystem() {
	global $wp_filesystem;

	require_once( ABSPATH . '/wp-admin/includes/file.php' );
	WP_Filesystem();

	return $wp_filesystem;
}