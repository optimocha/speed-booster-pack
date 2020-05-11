<?php

/**
 * Fired during plugin activation
 *
 * @link       https://optimocha.com
 * @since      1.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
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
	 * @since    1.0.0
	 */
	public static function activate() {
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
		$sbp_advanced_cache  = SBP_PATH . '/advanced-cache.php';

		SBP_Cache::set_wp_cache_constant( true );

		if ( ! file_exists( $advanced_cache_path ) ) {
			file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', file_get_contents( $sbp_advanced_cache ) );
		} else {
			// Compare file contents
			if ( file_get_contents( $advanced_cache_path ) != file_get_contents( $sbp_advanced_cache ) ) {
				wp_die( __( 'advanced-cache.php file is already exists and created by other plugin. Delete wp-content/advanced-cache.php file to continue.', 'speed-booster' ) );
			}
		}
	}

}
