<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'warmup';
	private $begun = false;

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
		],
			$options );

		wp_remote_get( $item['url'], $args );

		if ( $this->begun === false ) {
			$this->begun = true;
		}

		return false;
	}

	protected function complete() {
		delete_transient( 'sbp_warmup_started' );
		set_transient( 'sbp_warmup_completed', 1 );
		parent::complete();
	}
}