<?php

namespace SpeedBooster;

class SBP_Special extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_special' ) ) {
			return;
		}
	}
}