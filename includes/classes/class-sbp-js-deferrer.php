<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

//require_once SBP_LIB_PATH . "simple_html_dom/simple_html_dom.php";

class SBP_JS_Deferrer extends SBP_Abstract_Module {
	private $excluded_scripts = [];

	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'js_defer' ) || ! sbp_get_option( 'module_assets' ) ) {
			return;
		}

		$this->excluded_scripts = SBP_Utils::explode_lines( sbp_get_option( 'js_exclude' ) );

		add_filter( 'sbp_output_buffer', [ $this, 'js_deferrer' ] );
	}

	public function js_deferrer( $html ) {
		if ( is_embed() ) {
			return $html;
		}

		// lahmacuntodo: rewrite this function

		return $html;
	}

	private function check_if_excluded( $script ) {
		foreach ( $this->excluded_scripts as $excluded_script ) {
			if ( strpos( $script, $excluded_script ) !== false ) {
				return true;
			}
		}

		return false;
	}
}