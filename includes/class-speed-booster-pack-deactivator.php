<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use SpeedBooster\SBP_Cache;
use SpeedBooster\SBP_WP_Config_Injector;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      4.0.0
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    4.0.0
	 */
	public static function deactivate() {
		SBP_Cache::clear_total_cache();
		SBP_Cache::set_wp_cache_constant( false );
		SBP_Cache::clean_htaccess();
		SBP_WP_Config_Injector::remove_wp_config_lines();

		$adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}
	}

}
