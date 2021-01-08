<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use simplehtmldom\HtmlDocument;

class SBP_Critical_CSS extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'enable_criticalcss' ) ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'handle_criticalcss' ] );
	}

	public function handle_criticalcss( $html ) {
		if ( is_embed() ) {
			return $html;
		}


		// Find Default Critical CSS Code if exists
		$criticalcss_code = sbp_get_option( 'criticalcss_default' );

		$conditions = [
			'is_front_page',
			'is_home',
			'is_single',
			'is_page',
			'is_category',
			'is_tag',
			'is_archive',
		];

		foreach ( $conditions as $condition ) {
			if ( call_user_func( $condition ) ) {
				$criticalcss_codes = sbp_get_option( 'criticalcss_codes' );
				if ( isset( $criticalcss_codes[ $condition ] ) && $criticalcss_codes[ $condition ] ) {
					$criticalcss_code = $criticalcss_codes[ $condition ];
					break;
				}
			}
		}

		$criticalcss_code = wp_strip_all_tags( trim( $criticalcss_code ) );
		if ( empty( $criticalcss_code ) ) {
			return $html;
		}

		$html = str_replace( '</title>', '</title>' . PHP_EOL . '<style id="sbp-critical-css">' . wp_strip_all_tags( $criticalcss_code ) . '</style>', $html );
		if ( sbp_get_option( 'remove_criticalcss' ) ) {
			$html = str_replace( '</body>', '<script>window.addEventListener("load", function(event) {document.getElementById("sbp-critical-css").media="none";})</script>' . PHP_EOL . '</body>', $html );
		}

		$dom = new HtmlDocument();
		$dom->load( $html, true, false );

		// Find all links
		$links = $dom->find( 'link[rel=stylesheet]' );
		foreach ( $links as $link ) {
			if ( ! isset( $link->media ) || $link->media !== 'print' ) {
				$link->media  = 'print';
				$link->onload = "this.media='all'";
			}
		}

		return $dom;
	}
}