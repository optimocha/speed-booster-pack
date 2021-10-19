<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

use simplehtmldom\HtmlDocument;

class SBP_Critical_CSS extends SBP_Abstract_Module {
	private $excluded_handles = [
		'admin-bar-css',
	];

	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'enable_criticalcss' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			add_filter( 'sbp_output_buffer', [ $this, 'handle_criticalcss' ] );
		}
	}

	public function handle_criticalcss( $html ) {
		if ( is_embed() ) {
			return $html;
		}

		$run_main_setting = false;

		// Content Specific Option
		if ( is_singular() ) {
			$content_specific_criticalcss_status = sbp_get_post_meta( get_the_ID(), 'sbp_criticalcss_status', 'main_setting' );

			if ( $content_specific_criticalcss_status == 'off' ) {
				return $html;
			} elseif ( $content_specific_criticalcss_status == 'custom' ) {
				$content_specific_criticalcss = sbp_get_post_meta( get_the_ID(), 'sbp_criticalcss' );
			} else {
				$run_main_setting = true;
			}
		} else {
			$run_main_setting = true;
		}

		// Find main_setting Critical CSS Code if exists
		if ( $run_main_setting ) {
			$criticalcss_code = sbp_get_option( 'criticalcss_default' );
		} else {
			$criticalcss_code = $content_specific_criticalcss;
		}

		if ( $run_main_setting ) {
			$conditions = [
				'is_front_page',
				'is_home',
				'is_single',
				'is_page',
				'is_category',
				'is_tag',
				'is_archive',
				'is_shop',
				'is_product',
				'is_product_category',
			];

			foreach ( $conditions as $condition ) {
				if ( function_exists( $condition ) && call_user_func( $condition ) ) {
					$criticalcss_codes = sbp_get_option( 'criticalcss_codes' );
					if ( isset( $criticalcss_codes[ $condition ] ) && $criticalcss_codes[ $condition ] ) {
						$criticalcss_code = $criticalcss_codes[ $condition ];
						break;
					}
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

		// Get excluded urls
		$excluded_urls = SBP_Utils::explode_lines( sbp_get_option( 'criticalcss_excludes' ) );

		// Find all links
		$links = $dom->find( 'link[rel=stylesheet]' );
		foreach ( $links as $link ) {
			foreach ( $excluded_urls as $url ) {
				if ( strpos( $link, $url ) !== false ) {
					continue 2;
				}
			}

			if ( ( ! isset( $link->media ) || $link->media !== 'print' ) && ! in_array( $link->id, $this->excluded_handles ) ) {
				$link->media  = 'print';
				$link->onload = "this.media='all'";
			}
		}

		return $dom;
	}
}