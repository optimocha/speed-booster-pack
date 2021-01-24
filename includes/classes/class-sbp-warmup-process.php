<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'warmup';
	private $done = [];
	private $success = [];
	private $failed = [];
	private $begun = false;

	protected function task( $item ) {
		$item['url'] = SBP_Utils::clear_hashes_and_question_mark( $item['url'] );
		if ( in_array( $item['url'], $this->done ) ) {
			return false;
		}

		$options = isset( $item['options'] ) ? $item['options'] : [];
		$args    = array_merge( [
			'compress'            => true,
			'httpversion'         => '1.1',
		], $options );

		$this->done[] = $item;

		$response = wp_remote_get( $item['url'], $args );
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->failed[] = $item;
			// ZTODO: Clear cache for failed pages.
            // Future reference: url_to_postid
		} else {
			$this->success[] = $item;
		}

		if ( $this->begun === false ) {
			set_transient( 'sbp_warmup_started', 1 );
			$this->begun = true;
		}

		return false;
	}

	protected function complete() {
		set_transient( 'sbp_warmup_errors', $this->failed );
		// Clear cache for failed items.
		set_transient( 'sbp_warmup_complete', true );
		delete_transient( 'sbp_warmup_started' );
		parent::complete();
	}
}