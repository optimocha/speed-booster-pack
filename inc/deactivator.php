<?php // TODO: delete this obsolete file.

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

defined( 'ABSPATH' ) || exit;

use Optimocha\SpeedBooster\Features\Cache;
use Optimocha\SpeedBooster\Features\LiteSpeed_Cache;

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
		Cache::set_wp_cache_constant( false );
		Cache::clean_htaccess();
		LiteSpeed_Cache::remove_htaccess_rules();
		Cache::clear_total_cache();

		$adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}
	}

}
