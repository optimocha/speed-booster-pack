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

        add_option( 'sbp_activation_defaults', true );

        // Don't do redirects when multiple plugins are bulk activated
        if (
            ( isset( $_REQUEST['action'] ) && 'activate-selected' === $_REQUEST['action'] ) &&
            ( isset( $_POST['checked'] ) && count( $_POST['checked'] ) > 1 ) ) {
            return;
        }
        add_option( 'sbp_activation_redirect', wp_get_current_user()->ID );

	}

}
