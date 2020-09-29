<?php

namespace SpeedBooster;

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'warmup';

	protected function task( $item ) {
		$response = wp_remote_get( $item, [
			'blocking'            => false,
			'compress'            => true,
			'httpversion'         => '1.1',
			'limit_response_size' => 100,
		] );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$transient = get_transient( 'sbp_warmup_errors' );
			$errors    = is_array( $transient ) ? $transient : [];
			/* translator: %s is the url of the page */
			// BEYNTODO: Change Text
			$errors['errors'][] = sprintf( __( 'Error occured while processing url: %s', 'speed-booster' ), $item );
		}

		set_transient( 'sbp_warmup_started', true );

		return false;
	}

	protected function complete() {
		set_transient( 'sbp_warmup_complete', true );
		delete_transient( 'sbp_warmup_started' );
		parent::complete();
	}
}