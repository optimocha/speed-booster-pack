<?php

namespace SpeedBooster;

class SBP_Critical_CSS extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'critical_css' ) || ! is_home() ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'handle_critical_css' ] );
	}

	public function handle_critical_css( $html ) {
		$critical_css = sbp_get_option( 'critical_css' );
		$html         = str_replace( '</title>', '</title>' . PHP_EOL . '<style id="sbp-critical-css">' . $critical_css . '</style>', $html );

		$html = new \simple_html_dom( $html );

		// Find all links
		$links = $html->find( 'link' );
		foreach ( $links as $link ) {
			if ( isset($link->media) ) {
				if ($link->media !== 'print') {
					$link->media = 'print';
					$link->onload = "this.media='all'";
				}
			}
		}

		return $html;
	}
}