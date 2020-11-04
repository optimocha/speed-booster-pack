<?php

namespace SpeedBooster;

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'warmup';
	private $done = [];
	private $success = [];
	private $failed = [];
	private $began = false;

	protected function task( $item ) {
		$item['url'] = SBP_Utils::clear_hashes_and_question_mark( $item['url'] );
		if ( in_array( $item['url'], $this->done ) ) {
			return false;
		}

		$options = isset( $item['options'] ) ? $item['options'] : [];
		$args    = array_merge( [
			'blocking'            => false,
			'compress'            => true,
			'httpversion'         => '1.1',
			'limit_response_size' => 100,
		], $options );

		$this->done[] = $item;

		$response = wp_remote_get( $item['url'], $args );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->failed[] = $item;
		} else {
			$this->success[] = $item;
		}

		if ( $this->began === false ) {
			set_transient( 'sbp_warmup_started', 1 );
			$this->began = true;
		}

		return false;
	}

	protected function complete() {
		/* translator: %s is the url of the page */
		// BEYNTODO: Change Text
		set_transient( 'sbp_warmup_errors', $this->failed );

		set_transient( 'sbp_warmup_complete', true );
		delete_transient( 'sbp_warmup_started' );
		parent::complete();
	}
}