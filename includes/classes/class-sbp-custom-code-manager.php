<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_Custom_Code_Manager extends SBP_Abstract_Module {
	private $current_script = [];
	private $add_delay_script = false;
	private $add_onload_script = false;

	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_special' ) ) {
			return;
		}

		$this->add_script_tags();
	}

	private function add_script_tags() {
		$scripts = sbp_get_option( 'custom_codes' );
		foreach ( $scripts as $script ) {
			if ( 'footer' === $script['custom_codes_place'] ) {
				$hook = 'wp_footer';
			} else {
				$hook = 'wp_head';
			}
			// TODO: Find another way to pass this argument
			add_action( $hook, function () use ( $script ) {
				switch ( $script['custom_codes_method'] ) {
					case "onload":
						$output                  = '<script type="sbp/javascript" data-method="onload">';
						$this->add_onload_script = true;
						break;
					case "delayed":
						$output                 = '<script type="sbp/javascript" data-method="delayed">';
						$this->add_delay_script = true;
						break;
					default:
						$output = '<script type="text/javascript">';
						break;
				}
				$output .= $script['custom_codes_item'];
				$output .= '</script>';

				echo $output;
			} );
		}

		add_action( 'wp_footer', [ $this, 'add_sbp_loader_script' ] );
	}

	public function add_sbp_loader_script() {
		echo "<script>window.onload = function(e) {";
		if ( $this->add_onload_script ) {
			echo 'var scripts=document.querySelectorAll("script[type=\'sbp/javascript\'][data-method=onload]");scripts.forEach(function(t){var e=t.innerHTML,r=document.createElement("script");r.type="text/javascript",r.innerHTML=e,t.after(r),t.remove()});';
		}
		if ( $this->add_delay_script ) {
			echo 'setTimeout(function(){document.querySelectorAll("script[type=\'sbp/javascript\'][data-method=delayed]").forEach(function(e){var t=e.innerHTML,r=document.createElement("script");r.type="text/javascript",r.innerHTML=t,e.after(r),e.remove()})},4e3);';
		}
		echo '};</script>';
	}

	// Unminified versions of replacer scripts TODO: Clean this when you done.
	/*
	var scripts = document.querySelectorAll("script[type='sbp/javascript'][data-method=onload]");
	scripts.forEach(function(tag) {
		var script = tag.innerHTML;
		var newScript = document.createElement('script');
		newScript.type = 'text/javascript';
		newScript.innerHTML = script;
		tag.after(newScript);
		tag.remove();
	})

	setTimeout(function() {
		var scripts = document.querySelectorAll("script[type='sbp/javascript'][data-method=delayed]");
			scripts.forEach(function(tag) {
			var script = tag.innerHTML;
			var newScript = document.createElement('script');
			newScript.type = 'text/javascript';
			newScript.innerHTML = script;
			tag.after(newScript);
			tag.remove();
		})
	}, 4000);
	 */
}