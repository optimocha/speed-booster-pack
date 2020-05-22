<?php

/**
 * @link              https://speedboosterpack.com
 * @since             4.0
 * @package           Speed_Booster_Pack
 *
 * @wordpress-plugin
 * Plugin Name:       Speed Booster Pack
 * Plugin URI:        https://speedboosterpack.com
 * Description:       Speed optimization is vital for SEO. Optimize your PageSpeed scores today!
 * Version:           4.0
 * Author:            Optimocha
 * Author URI:        https://optimocha.com
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       speed-booster-pack
 *
 * Copyright 2015-2017 Tiguan (office@tiguandesign.com)
 * Copyright 05/05/2017 - 10/04/2017 ShortPixel (alex@shortpixel.com)
 * Copyright 2017-2019 MachoThemes (office@machothemes.com)
 * Copyright 2019-...  Optimocha (hey@optimocha.com)
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Plugin name.
 */
define( 'SBP_PLUGIN_NAME', 'Speed Booster Pack' );

/**
 * Current plugin version.
 */
define( 'SBP_VERSION', '4.0' );

/**
 * Plugin website URL.
 */
define( 'SBP_PLUGIN_HOME', 'https://speedboosterpack.com/' );

/**
 * Plugin owner's name.
 */
define( 'SBP_OWNER_NAME', 'Optimocha' );

/**
 * Plugin owner's website URL.
 */
define( 'SBP_OWNER_HOME', 'https://optimocha.com/' );

/**
 * Plugin Path
 */
define( 'SBP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin Path
 */
define( 'SBP_PATH', realpath( dirname( __FILE__ ) ) . '/' );

/**
 * Plugin includes path
 */
define( 'SBP_INC_PATH', SBP_PATH . 'includes/' );

/**
 * Plugin libraries path
 */
define( 'SBP_LIB_PATH', SBP_INC_PATH . 'libs/' );

/**
 * Cache directory path
 */
define( 'SBP_CACHE_DIR', WP_CONTENT_DIR . '/cache/speed-booster' );

/**
 * Cache directory url
 */
define( 'SBP_CACHE_URL', WP_CONTENT_URL . '/cache/speed-booster' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-speed-booster-pack-activator.php
 */
function activate_speed_booster_pack() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-speed-booster-pack-activator.php';
	Speed_Booster_Pack_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-speed-booster-pack-deactivator.php
 */
function deactivate_speed_booster_pack() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-speed-booster-pack-deactivator.php';
	Speed_Booster_Pack_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_speed_booster_pack' );
register_deactivation_hook( __FILE__, 'deactivate_speed_booster_pack' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SBP_INC_PATH . 'class-speed-booster-pack.php';

$sbp_options = get_option( 'sbp_options' );

if ( ! function_exists( 'sbp_get_option' ) ) {
	function sbp_get_option( $option = '', $default = null ) {
		global $sbp_options;

		return ( isset( $sbp_options[ $option ] ) ) ? $sbp_options[ $option ] : $default;
	}
}

if ( ! function_exists( 'posabs' ) ) {
	/**
	 * Returns absolute value of a number. Returns 1 if value is zero.
	 *
	 * @param $value
	 *
	 * @return float|int
	 */
	function posabs( $value ) {
		if ( 0 == $value ) {
			return 1;
		}

		return absint( $value );
	}
}

spl_autoload_register( 'sbp_autoloader' );

function sbp_autoloader( $class_name ) {
	if ( false === strpos( $class_name, 'SpeedBooster\\' ) ) {
		return;
	}

	$class_name = str_replace( 'SpeedBooster\\', '', $class_name );

	// Make filename lower case, it's not necessary but do it just in "case" :P (Did you get the joke?)
	$filename = strtolower( str_replace( '_', '-', $class_name ) );
	$path     = SBP_INC_PATH . 'classes/class-' . $filename . '.php';
	if ( file_exists( $path ) ) {
		require_once( $path );
	}
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_speed_booster_pack() {

	$plugin = new Speed_Booster_Pack();
	$plugin->run();

}

run_speed_booster_pack();
