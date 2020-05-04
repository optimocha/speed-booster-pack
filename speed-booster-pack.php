<?php

/**
 * @link              https://optimocha.com
 * @since             4.0.0
 * @package           Speed_Booster_Pack
 *
 * @wordpress-plugin
 * Plugin Name:       Speed Booster Pack
 * Plugin URI:        https://speedboosterpack.com
 * Description:       Speed optimization is vital for SEO. Optimize your PageSpeed scores today!
 * Version:           4.0.0
 * Author:            Optimocha
 * Author URI:        https://optimocha.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       speed-booster-pack
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'SBP_VERSION', '4.0.0' );

/**
 * Plugin Path
 */
define( 'SBP_URL', plugin_dir_url(__FILE__) . '/' );

/**
 * Plugin Path
 */
define( 'SBP_PATH', realpath(dirname(__FILE__)) . '/' );

/**
 * Plugin includes path
 */
define( 'SBP_INC_PATH', SBP_PATH . 'includes/' );

/**
 * Plugin libraries path
 */
define( 'SBP_LIB_PATH', SBP_INC_PATH . 'libs/' );


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
