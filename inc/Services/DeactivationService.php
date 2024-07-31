<?php
/**
 * This class is responsible for registration deactivation hooks and other deactivation operations.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

use SpeedBoosterPack\Booster\Cache;
use SpeedBoosterPack\Booster\LiteSpeedCache;
use SpeedBoosterPack\Booster\WpConfigInjector;

defined( 'ABSPATH' ) || exit;

class DeactivationService {

	/**
	 * Deactivate the plugin
	 * @return void
	 * @since 5.0.0
	 */
	public function deactivate(): void {

		Cache::clear_total_cache();
		WpConfigInjector::remove_wp_config_lines();
		Cache::set_wp_cache_constant( false );
        Cache::clean_htaccess();
		LiteSpeedCache::remove_htaccess_rules();

		$adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}

		//Define custom deactivation hook
		do_action( 'speed_booster_pack_deactivation' );
	}

}
