<?php

/**
 * This class is responsible for handling AJAX requests.
 * Define AJAX actions and their corresponding methods here.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined( 'ABSPATH' ) || exit;

class AjaxService {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'defineAjaxVariables' ] );
	}

	/**
	 * Define AJAX variables
	 * @return void
	 * @since 5.0.0
	 */
	public function defineAjaxVariables() {
		wp_localize_script( SBP_PLUGIN_SLUG,
			'sbp_ajax_vars',
			[
				'nonce' => wp_create_nonce( 'sbp_ajax_nonce' ),
			] );
	}

}