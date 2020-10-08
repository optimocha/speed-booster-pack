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

if ( ! function_exists( 'sbp_get_hosting_restrictions' ) ) {
	function sbp_get_hosting_restrictions() {
		if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) && $_SERVER['KINSTA_CACHE_ZONE'] ) {
			return 'Kinsta';
		}

		if ( function_exists( 'is_wpe' ) || function_exists( 'is_wpe_snapshot' ) ) { // LAHMACUNTODO: Check here
			return [ 'name' => 'WP Engine', 'disable_features' => [] ];
		}

		$hosting_provider_constants = [
			'GD_SYSTEM_PLUGIN_DIR' => [
				'name'              => 'GoDaddy',
				'disabled_features' => []
			],
			'MM_BASE_DIR'          => [
				'name'              => 'Bluehost',
				'disabled_features' => []
			],
			'PAGELYBIN'            => [
				'name'              => 'Pagely',
				'disabled_features' => []
			],
			'KINSTAMU_VERSION'     => [
				'name'              => 'Kinsta',
				'disabled_features' => []
			],
			'FLYWHEEL_CONFIG_DIR'  => [
				'name'              => 'Flywheel',
				'disabled_features' => []
			],
			'IS_PRESSABLE'         => [
				'name'              => 'Pressable',
				'disabled_features' => []
			],
			'VIP_GO_ENV'           => [
				'name'              => 'WordPress VIP',
				'disabled_features' => [],
			],
			'KINSTA_CACHE_ZONE'    => [
				'name'              => 'Kinsta',
				'disabled_features' => [ 'caching', 'lazyload' ],
			],
		];

		foreach ( $hosting_provider_constants as $constant => $company_info ) {
			if ( defined( $constant ) && constant( $constant ) ) {
				return $company_info;
			}
		}

		return [ 'name' => null, 'disabled_features' => [] ]; // Return this structure to avoid undefined index errors.
	}
}

if ( ! function_exists( 'sbp_should_disable_feature' ) ) {
	function sbp_should_disable_feature( $feature_name ) {
		$hosting_restrictions = sbp_get_hosting_restrictions();

		if ($hosting_restrictions['name'] !== null) {
			if (in_array($feature_name, $hosting_restrictions['disabled_features'])) {
				return $hosting_restrictions['name'];
			}
		}

		return false;
	}
}

function sbp_str_replace_first( $from, $to, $content ) {
	$from = '/' . preg_quote( $from, '/' ) . '/';

	return preg_replace( $from, $to, $content, 1 );
}