<?php

//	TODO:
//	UTILITIES
//		check_debug_mode (???)
//		notice manager
//		background worker
//		file handler (crud)
//		validate_option();
//		sanitize_option();
//		deactivation survey

/**
 * Speed Booster Pack
 *
 * @package		Optimocha\SpeedBooster
 * @author		Optimocha
 * @license		https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License
 *
 * @wordpress-plugin
 * Plugin Name:	Speed Booster Pack
 * Plugin URI:	https://speedboosterpack.com
 * Description:	PageSpeed optimization is vital for SEO: A faster website equals better conversions. Optimize & cache your site with this smart plugin!
 * Version:		5.0.0
 * Author:		Optimocha
 * Author URI:	https://optimocha.com
 * License:		GPLv3 or later
 * License URI:	https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:	speed-booster-pack
 *
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

/**
 * Defines plugin constants.
 *
 * @since	5.0.0
 */
define( 'SPEED_BOOSTER_PACK', [
	'version'		=> '5.0.0-alpha',
	'slug'			=> 'speed-booster-pack',
	'path'			=> __DIR__,
	'basename'		=> plugin_basename( __FILE__ ),
	'url'			=> plugin_dir_url( __FILE__ ),
	'cache_path'	=> WP_CONTENT_DIR . '/cache/speed-booster/',
] );

/**
 * Requires the Composer autoloader.
 *
 * @since   5.0.0
 */
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Registers the activation hook.
 *
 * @since	5.0.0
 */
register_activation_hook( __FILE__, function() {
	add_option( 'sbp_activated', true );
} );

/**
 * Registers the deactivation hook.
 *
 * @since	5.0.0
 */
register_deactivation_hook( __FILE__, [ 'Core', 'deactivate' ] );

/**
 * Begins execution of the plugin (hooked to the `plugins_loaded` action).
 *
 * @since	5.0.0
 */
add_action( 'plugins_loaded', function() {
	$plugin = new Core();
	$plugin->run();
} );