<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
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

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      4.0.0
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    4.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain( 'speed-booster-pack' );

	}


}
