<?php

/**
 * Fired during plugin activation
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 */

use SpeedBooster\SBP_Cache;
use SpeedBooster\SBP_WP_Config_Injector;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      4.0.0
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    4.0.0
	 */
	public static function activate() {
        if (sbp_get_option( 'module_caching' ) && ! sbp_should_disable_feature( 'caching' )) {
            SBP_Cache::clear_total_cache();
            SBP_Cache::set_wp_cache_constant( true );
            SBP_Cache::generate_htaccess();
        }
        SBP_WP_Config_Injector::inject_wp_config();

        $adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
        if ( file_exists( $adv_cache_file ) ) {
            unlink( $adv_cache_file );
        }
	}

}
