<?php

namespace Optimocha\SpeedBooster\Features;

defined( 'ABSPATH' ) || exit;

class Custom_Code_Manager {

	public function __construct() {
		add_action( 'wp_ajax_sbp_clear_ccm', [ $this, 'clear_custom_codes' ] );
	}

	public function clear_custom_codes() {
		if (
			current_user_can( 'manage_options' ) &&
			isset( $_GET['action'] ) &&
			$_GET['action'] === 'sbp_clear_ccm'
		) {
			if ( ! wp_verify_nonce( $_GET['nonce'], 'sbp_ajax_nonce' ) ) {
				echo wp_json_encode( [
					'status'  => 'failure',
					'message' => __( 'Invalid nonce.', 'speed-booster-pack' ),
				] );
				wp_die();
			}

			$options = get_option( 'sbp_options' );

			if ( $options ) {
				unset( $options['custom_codes'] );
				update_option( 'sbp_options', $options );
			}

			echo wp_json_encode( [ 'status' => 'success', 'message' => 'Custom codes successfully removed.' ] );
			wp_die();
		}
	}

}