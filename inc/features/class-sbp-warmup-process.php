<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Warmup_Process extends \WP_Background_Process {
	protected $action = 'sbp_cache_warmup';
	private $begun = false;

	protected function task( $item ) {
		$item['url'] = SBP_Utils::clear_hashes_and_question_mark( $item['url'] );

		$options = isset( $item['options'] ) ? $item['options'] : [];
		$args    = array_merge( [
			'blocking'    => false,
			'httpversion' => '1.1',
			'timeout'     => 0.01,
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
		parent::complete();
	}
}