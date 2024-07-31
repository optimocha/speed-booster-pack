<?php

namespace SpeedBoosterPack\Booster;

defined('ABSPATH') || exit;

use simplehtmldom\HtmlDocument;
use SpeedBoosterPack\Common\Helper;

class CriticalCss  extends AbstractModule {
	private $excluded_handles = [
		'admin-bar-css',
	];

	public function __construct() {

		parent::__construct();

		if ( ! Helper::getOption( 'module_css' ) || ! Helper::getOption( 'enable_criticalcss' ) ) {
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


		// Content Specific Option
		$content_specific_criticalcss_status = Helper::sbpGetPostMeta( get_the_ID(), 'sbp_criticalcss_status', 'main_setting' );
		$run_main_setting                    = $content_specific_criticalcss_status == 'main_setting';
		$criticalcss_code                    = Helper::getOption( 'criticalcss_default' );

		if ( is_singular() ) {
			if ( $content_specific_criticalcss_status == 'off' ) {
				return $html;
			} elseif ( $content_specific_criticalcss_status == 'custom' ) {
				$criticalcss_code = Helper::sbpGetPostMeta( get_the_ID(), 'sbp_criticalcss' );
			}
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
					$criticalcss_codes = Helper::getOption( 'criticalcss_codes' );
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
		if ( Helper::getOption( 'remove_criticalcss' ) ) {
			$html = str_replace( '</body>', '<script>window.addEventListener("load", function(event) {document.getElementById("sbp-critical-css").media="none";})</script>' . PHP_EOL . '</body>', $html );
		}

		$dom = new HtmlDocument();
		$dom->load( $html, true, false );

		// Get excluded urls
		$excluded_urls = Helper::explodeLines( Helper::getOption( 'criticalcss_excludes' ) );

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