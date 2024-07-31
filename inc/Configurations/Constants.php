<?php
/**
 * This class is responsible for defining plugin constants.
 *
 * It defines all the plugin constants that are used throughout the plugin.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Configurations
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Configurations;

defined( 'ABSPATH' ) || exit;

class Constants {
	public function __construct() {
		//Define plugin base constants
		define( 'SBP_URL', plugin_dir_url( dirname( __FILE__, 2 ) ) ); // Plugin root URL
		define( 'SBP_PATH', trailingslashit( realpath( dirname( __FILE__, 3 ) ) ) ); // Plugin root directory path
		define( 'SBP_PLUGIN_BASENAME', plugin_basename( dirname( __FILE__, 3 ) . '/speed-booster-pack.php' ) ); // Plugin basename
		define( 'SBP_VERSION', '5.0.0' ); // plugin version
		define( 'SBP_PLUGIN_NAME', 'Speed Booster Pack' ); // plugin name
		define( 'SBP_PLUGIN_SLUG', 'speed-booster-pack' ); // plugin slug / id
		define( 'SBP_PLUGIN_HOME', 'https://speedboosterpack.com/' ); // plugin home
		define( 'SBP_OWNER_NAME', 'Optimocha' ); // plugin owner name
		define( 'SBP_OWNER_HOME', 'https://optimocha.com/' ); // plugin owner home
		define( 'SBP_INC_PATH', SBP_PATH . 'inc/' ); // plugin includes directory path
		define( 'SBP_LIB_PATH', SBP_PATH . 'vendor/' ); // plugin 3rd party directory path
		define( 'SBP_CACHE_DIR', WP_CONTENT_DIR . '/cache/speed-booster/' ); // plugin cache directory path
		define( 'SBP_CACHE_URL', WP_CONTENT_URL . '/cache/speed-booster/' ); // plugin cache directory URL
		define( 'SBP_UPLOADS_DIR', WP_CONTENT_DIR . '/uploads/speed-booster/' ); // plugin uploads path
		define( 'SBP_UPLOADS_URL', WP_CONTENT_URL . '/uploads/speed-booster/' ); // plugin uploads URL
		define( 'SBP_MIGRATOR_VERSION', '45000' ); // plugin migrator version
		define( 'SBP_WOOCOMMERCE_ANALYTICS', 1 ); // TODO add description
		define( 'SBP_WOOCOMMERCE_TRACKING', 1 ); // TODO add description
	}

	/**
	 * Get hosting provider constants
	 *
	 * @return array
	 * @since 5.0.0
	 */
	public static function hostingProviderConstants(): array {
		return [
			'GD_SYSTEM_PLUGIN_DIR' => [
				'name'              => 'GoDaddy',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'GoDaddy', 'GoDaddy' ),
			],
			'MM_BASE_DIR'          => [
				'name'              => 'Bluehost',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Bluehost', 'Bluehost' ),
			],
			'PAGELYBIN'            => [
				'name'              => 'Pagely',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Pagely', 'Pagely' ),
			],
			'KINSTAMU_VERSION'     => [
				'name'              => 'Kinsta',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Kinsta', 'Kinsta' ),
			],
			'FLYWHEEL_CONFIG_DIR'  => [
				'name'              => 'Flywheel',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Flywheel', 'Flywheel' ),
			],
			'IS_PRESSABLE'         => [
				'name'              => 'Pressable',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Pressable', 'Pressable' ),
			],
			'VIP_GO_ENV'           => [
				'name'              => 'WordPress VIP',
				'disabled_features' => [],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'WordPress VIP', 'WordPress VIP' ),
			],
			'KINSTA_CACHE_ZONE'    => [
				'name'              => 'Kinsta',
				'disabled_features' => [ 'caching' ],
				'error_message'     => sprintf( __( 'Since you\'re using %s, cache feature is completely disabled to ensure compatibility with internal caching system of %s.', 'speed-booster-pack' ), 'Kinsta', 'Kinsta' ),
			],
		];
	}
}