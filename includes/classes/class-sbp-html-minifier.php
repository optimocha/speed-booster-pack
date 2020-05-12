<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_HTML_Minifier extends SBP_Abstract_Module {
	public function __construct() {
		if (!parent::should_plugin_run()) {
			return;
		}
	}
}