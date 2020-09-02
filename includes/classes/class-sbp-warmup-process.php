<?php

namespace SpeedBooster;

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'warmup';

	protected function task( $item ) {
		$response = wp_remote_get( $item, [
			'blocking' => false,
			'compress' => true,
			'httpversion' => '1.1',
			'limit_response_size' => 100,
		] );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$transient = get_transient( 'sbp_warmup_errors' );
			$errors    = is_array( $transient ) ? $transient : [];
			/* translator: %s is the url of the page */
			// BEYNTODO: Change Text
			$errors['errors'][] = sprintf( __( 'Error occured while processing url: %s', 'speed-booster' ), $item );
		} else {
			// LAHMACUNTODO: This transient is not setting after task.
			set_transient( 'sbp_warmup_process', get_transient( 'sbp_warmup_process' ) + 1 );
		}

		return false;
	}

	protected function complete() {
		parent::complete();
		set_transient( 'sbp_warmup_complete', get_transient( 'sbp_warmup_process' ) );
		delete_transient( 'sbp_warmup_process' );
	}
}