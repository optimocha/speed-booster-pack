<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       Speed Booster Pack
 * Plugin URI:        https://speedboosterpack.com
 * Description:       PageSpeed optimization is vital for SEO: A faster website equals better conversions. Optimize & cache your site with this smart plugin!
 * Version:           4.5.7
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

defined( 'ABSPATH' ) || exit;

/**
 * Plugin constants.
 */
define( 'SBP_VERSION', '4.5.7' ); // plugin version
define( 'SBP_PLUGIN_NAME', 'Speed Booster Pack' ); // plugin name
define( 'SBP_PLUGIN_HOME', 'https://speedboosterpack.com/' ); // plugin home
define( 'SBP_OWNER_NAME', 'Optimocha' ); // plugin owner name
define( 'SBP_OWNER_HOME', 'https://optimocha.com/' ); // plugin owner home
define( 'SBP_URL', plugin_dir_url( __FILE__ ) ); // plugin root URL
define( 'SBP_PATH', realpath( dirname( __FILE__ ) ) . '/' ); // plugin root directory path
define( 'SBP_INC_PATH', SBP_PATH . 'includes/' ); // plugin includes directory path
define( 'SBP_LIB_PATH', SBP_PATH . 'vendor/' ); // plugin 3rd party directory path
define( 'SBP_CACHE_DIR', WP_CONTENT_DIR . '/cache/speed-booster/' ); // plugin cache directory path
define( 'SBP_CACHE_URL', WP_CONTENT_URL . '/cache/speed-booster/' ); // plugin cache directory URL
define( 'SBP_UPLOADS_DIR', WP_CONTENT_DIR . '/uploads/speed-booster/' ); // plugin uploads path
define( 'SBP_UPLOADS_URL', WP_CONTENT_URL . '/uploads/speed-booster/' ); // plugin uploads URL
define( 'SBP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // plugin basename
define( 'SBP_MIGRATOR_VERSION', '45000' ); // plugin migrator version

/**
 * Load all plugin options
 */
if ( ! function_exists( 'sbp_get_option' ) ) {
	/**
	 * Returns the value of the option with given name, if option doesn't exists function returns the default value (from second variable)
	 *
	 * @param string $option
	 * @param null $default
	 *
	 * @return mixed|null
	 */
	function sbp_get_option( $option = '', $default = null ) {
		$sbp_options = get_option( 'sbp_options' );

		return ( isset( $sbp_options[ $option ] ) ) ? $sbp_options[ $option ] : $default;
	}
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-speed-booster-pack-activator.php
 */
function activate_speed_booster_pack() {
	require_once SBP_INC_PATH . 'class-speed-booster-pack-activator.php';
	Speed_Booster_Pack_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-speed-booster-pack-deactivator.php
 */
function deactivate_speed_booster_pack() {
	require_once SBP_INC_PATH . 'class-speed-booster-pack-deactivator.php';
	Speed_Booster_Pack_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_speed_booster_pack' );
register_deactivation_hook( __FILE__, 'deactivate_speed_booster_pack' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SBP_INC_PATH . 'class-speed-booster-pack.php';

/**
 * Autoload classes which has SpeedBooster namespace
 *
 * @param $class_name
 *
 * @since 4.0.0
 *
 */
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
 * @since    4.0.0
 */
function run_speed_booster_pack() {

	$plugin = new Speed_Booster_Pack();
	$plugin->run();

}

run_speed_booster_pack();
