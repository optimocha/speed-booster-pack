<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Custom_Code_Manager extends SBP_Abstract_Module {

	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_special' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			$this->add_script_tags();
		}
	}

	private function add_script_tags() {
		$scripts = sbp_get_option( 'custom_codes' );
		if ( $scripts ) {
			foreach ( $scripts as $script ) {
				if ( isset( $script['custom_codes_item'] ) && '' === $script['custom_codes_item'] ) {
					return;
				}
				if ( isset( $script['custom_codes_place'] ) && 'footer' === $script['custom_codes_place'] ) {
					$hook = 'wp_footer';
				} else {
					$hook = 'wp_head';
				}

				add_action( $hook,
					function () use ( $script ) {

						$output = '<script type="text/javascript">' . PHP_EOL;

						if ( ! isset( $script['custom_codes_method'] ) ) {
							$script['custom_codes_method'] = null;
						}

						switch ( $script['custom_codes_method'] ) {
							case "onload":
								$output .= 'window.addEventListener( \'DOMContentLoaded\', function(e) {' . PHP_EOL;
								$output .= $script['custom_codes_item'] . PHP_EOL;
								$output .= '});' . PHP_EOL;
								break;
							case "delayed":
								$output .= 'window.addEventListener( \'DOMContentLoaded\', function(e) { setTimeout(function(){' . PHP_EOL;
								$output .= $script['custom_codes_item'] . PHP_EOL;
								$output .= '},4000);});' . PHP_EOL;
								break;
							default:
								$output .= $script['custom_codes_item'];
								break;
						}

						$output .= '</script>';

						echo $output;
					} );
			}
		}
	}

}