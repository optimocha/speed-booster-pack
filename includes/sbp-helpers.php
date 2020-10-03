<?php

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

if ( ! function_exists( 'sbp_is_restricted_hosting' ) ) {
	function sbp_is_restricted_hosting() {
		if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) && $_SERVER['KINSTA_CACHE_ZONE'] ) {
			return 'Kinsta';
		}

		if ( function_exists( 'is_wpe' ) || function_exists( 'is_wpe_snapshot' ) ) { // LAHMACUNTODO: Check here
			return 'WP Engine';
		}

		$hosting_provider_constants = [
			'GD_SYSTEM_PLUGIN_DIR' => 'GoDaddy',
			'MM_BASE_DIR'          => 'Bluehost',
			'PAGELYBIN'            => 'Pagely',
			'KINSTAMU_VERSION'     => 'Kinsta',
			'FLYWHEEL_CONFIG_DIR'  => 'Flywheel',
			'IS_PRESSABLE'         => 'Pressable',
			'VIP_GO_ENV'           => 'WordPress VIP',
		];

		foreach ( $hosting_provider_constants as $constant => $company_name ) {
			if ( defined( $constant ) && constant( $constant ) ) {
				return $company_name;
			}
		}

		return false;
	}
}

function sbp_str_replace_first( $from, $to, $content ) {
	$from = '/' . preg_quote( $from, '/' ) . '/';

	return preg_replace( $from, $to, $content, 1 );
}