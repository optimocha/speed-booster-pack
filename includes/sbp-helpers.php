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
		$hosting_provider_constants = [
			'GD_SYSTEM_PLUGIN_DIR' => __( 'Since you\'re using GD Managed WP, cache feature is completely disabled to ensure compatibility with GD Managed WP\'s internal caching system.', 'speed-booster-pack' ),
			'MM_BASE_DIR'          => __( 'Since you\'re using Bluehost, cache feature is completely disabled to ensure compatibility with Bluehost\'s internal caching system.', 'speed-booster-pack' ),
			'PAGELYBIN'            => __( 'Since you\'re using Pagely, cache feature is completely disabled to ensure compatibility with Pagely\'s internal caching system.', 'speed-booster-pack' ),
			'KINSTAMU_VERSION'     => __( 'Since you\'re using Kinsta, cache feature is completely disabled to ensure compatibility with Kinsta\'s internal caching system.', 'speed-booster-pack' ),
			'KINSTA_CACHE_ZONE'    => __( 'Since you\'re using Kinsta, cache feature is completely disabled to ensure compatibility with Kinsta\'s internal caching system.', 'speed-booster-pack' ),
			'FLYWHEEL_CONFIG_DIR'  => __( 'Since you\'re using Flywheel, cache feature is completely disabled to ensure compatibility with Flywheel\'s internal caching system.', 'speed-booster-pack' ),
			'IS_PRESSABLE'         => __( 'Since you\'re using Pressable, cache feature is completely disabled to ensure compatibility with Pressable\'s internal caching system.', 'speed-booster-pack' ),
			'VIP_GO_ENV'           => __( 'Since you\'re using Vip Go, cache feature is completely disabled to ensure compatibility with Vip Go\'s internal caching system.', 'speed-booster-pack' ),
		];

		foreach ( $hosting_provider_constants as $constant => $message ) {
			if ( defined( $constant ) && constant( $constant ) ) {
				return $message;
			}
		}

		return null;
	}
}