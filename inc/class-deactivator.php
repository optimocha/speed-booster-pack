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

namespace Optimocha\SpeedBooster;

use SpeedBooster\Cache;
use SpeedBooster\LiteSpeed_Cache;

defined( 'ABSPATH' ) || exit;

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
class Deactivator {

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
		SBP_LiteSpeed_Cache::remove_htaccess_rules();

		$adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}
	}

}
