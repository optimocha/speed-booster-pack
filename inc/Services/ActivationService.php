<?php
/**
 * This class is responsible for registration activation hooks and other activation operations.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

use SpeedBoosterPack\Common\Helper;

defined( 'ABSPATH' ) || exit;

class ActivationService {

	/**
	 * Activate the plugin
	 * @return void
	 * @since 5.0.0
	 */
	public function activate(): void {

		add_option( 'sbp_activation_defaults', true );

		//TODO test this
		$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
		$checked_items = isset( $_POST['checked'] ) && is_array( $_POST['checked'] )
			? Helper::sanitizeArray( $_POST['checked'] )
			: array();

		if (
			'activate-selected' === $action &&
			count( $checked_items ) > 1
		) {
			return;
		}

		add_option( 'sbp_activation_redirect', wp_get_current_user()->ID );

		//Define custom activation hook
		do_action( 'speed_booster_pack_activation' );
	}

}
