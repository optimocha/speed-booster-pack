<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use simplehtmldom\HtmlDocument;

class SBP_Critical_CSS extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'enable_critical_css' ) ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'handle_critical_css' ] );
	}

	public function handle_critical_css( $html ) {
		if ( is_embed() ) {
			return $html;
		}


		// Find Default Critical CSS Code if exists
		$critical_css_code = sbp_get_option( 'critical_css_default' );

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
				$critical_css_codes = sbp_get_option( 'critical_css_codes' );
				if ( isset( $critical_css_codes[ $condition ] ) && $critical_css_codes[ $condition ] ) {
					$critical_css_code = $critical_css_codes[ $condition ];
					break;
				}
			}
		}

		$critical_css_code = wp_strip_all_tags( trim( $critical_css_code ) );
		if ( empty( $critical_css_code ) ) {
			return $html;
		}

		$html = str_replace( '</title>', '</title>' . PHP_EOL . '<style id="sbp-critical-css">' . wp_strip_all_tags( $critical_css_code ) . '</style>', $html );
		if ( sbp_get_option( 'remove_critical_css' ) ) {
			$html = str_replace( '</body>', '<script>window.addEventListener("load", function(event) {document.getElementById("sbp-critical-css").media="none";})</script>' . PHP_EOL . '</body>', $html );
		}

		$dom = new HtmlDocument();
		$dom->load( $html );

		// Find all links
		$links = $dom->find( 'link[rel=stylesheet]' );
		foreach ( $links as $link ) {
			if ( ! isset( $link->media ) || $link->media !== 'print' ) {
				$link->media  = 'print';
				$link->onload = "this.media='all'";
				$link->outertext = '<link rel="preload" href="' . $link->href . '" as="style">' . PHP_EOL . $link->outertext;
			}
		}

		return $dom;
	}
}