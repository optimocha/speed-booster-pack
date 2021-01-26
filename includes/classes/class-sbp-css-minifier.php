<?php

namespace SpeedBooster;

// If this file is called directly, abort.
use simplehtmldom\HtmlDocument;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_CSS_Minifier extends SBP_Abstract_Module {
	private $styles_list = [];
	private $exceptions = [
		'admin-bar',
		'dashicons',
	];
	private $dom = null;

	public function __construct() {
		if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'css_inline' ) || sbp_get_option( 'enable_criticalcss' ) ) {
			return;
		}
		$this->dom = new HtmlDocument();

		$this->set_exceptions();

		add_filter( 'sbp_output_buffer', [ $this, 'print_styles' ] );
	}

	public function print_styles( $html ) {
		if ( sbp_get_option( 'css_minify' ) ) {
			$minify = true;
		} else {
			$minify = false;
		}

		$this->dom->load( $html, true, false );

		$this->generate_styles_list();

		foreach ( $this->styles_list as $style ) {
			$inlined_css = $this->inline_css( $style['src'], $minify );
			$links       = $this->dom->find( 'link[rel=stylesheet]' );
			foreach ( $links as $link ) {
				if ( $link->href === $style['src'] && $inlined_css !== false ) {
					$link->outertext = '<style id="' . $link->id . '" media="' . ( isset( $link->media ) && $link->media ? $link->media : 'all' ) . '">' . $inlined_css . '</style>';
				}
			}
		}

		return $this->dom;
	}

	private function set_exceptions() {
		$sbp_exceptions   = SBP_Utils::explode_lines( sbp_get_option( 'css_exclude' ) );
		$this->exceptions = array_merge( $sbp_exceptions, $this->exceptions );
		$this->exceptions = array_merge( $this->exceptions, apply_filters( 'sbp_css_optimizer_exceptions', $this->exceptions ) );
		$this->exceptions = array_unique( $this->exceptions );

		foreach ( $this->exceptions as $key => $exception ) {
			if ( trim( $exception ) != '' ) {
				$css_exceptions[ $key ] = trim( $exception );
			}
		}
	}

	private function generate_styles_list() {
		$links = $this->dom->find( 'link[rel=stylesheet]' );
		foreach ( $links as $link ) {
			if ( ! $this->is_css_excluded( $link ) ) {
				$this->styles_list[] = [
					'src'   => $link->href,
					'media' => $link->media,
					'id'    => $link->id,
				];
			}
		}
	}

	private function inline_css( $url, $minify = true ) {
		$base_url = get_bloginfo( 'wpurl' );

		$cdn_url = sbp_get_option( 'cdn_url' );
		if ( $cdn_url ) {
			$base_url = '//' . $cdn_url;
		}

		$url      = ltrim( $url, 'https:' );
		$url      = ltrim( $url, 'http:' );
		$base_url = ltrim( $base_url, 'https:' );
		$base_url = ltrim( $base_url, 'http:' );

		if ( strpos( $url, $base_url ) !== 0 ) {
			return false;
		}

		$prefix = 'http' . ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ? 's' : null );
		$url    = substr( $url, 0, 2 ) == '//' ? $prefix . ':' . $url : $url;
		$url    = SBP_Utils::clear_hashes_and_question_mark( $url );

		$css = file_get_contents( $url );
		if ( $css ) {
			if ( $minify ) {
				$css = $this->minify_css( $css );
			}

			$css = $this->rebuild_css_urls( $css, $url );

			return $css;
		}

		return false;
	}

	private function rebuild_css_urls( $css, $url ) {
		$css_dir = substr( $url, 0, strrpos( $url, '/' ) );

		// remove empty url() declarations
		$css = preg_replace( "/url\(\s?\)/", "", $css );
		// new regex expression
		$css = preg_replace( "/url\s*(?!\(['\"]?(data:|http:|https:))\(\s*['\"]?([^\/][^'\"\)]*)['\"]?\s*\)/i",
			"url({$css_dir}/$2)",
			$css );

		return $css;
	}

	private function minify_css( $css ) {

		$css = $this->remove_multiline_comments( $css );
		$css = str_replace( [ "\t", "\n", "\r" ], ' ', $css );
		$cnt = 1;

		while ( $cnt > 0 ) {
			$css = str_replace( '  ', ' ', $css, $cnt );
		}

		$css = str_replace( [ ' {', '{ ' ], '{', $css );
		$css = str_replace( [ ' }', '} ', ';}' ], '}', $css );
		$css = str_replace( ': ', ':', $css );
		$css = str_replace( '; ', ';', $css );
		$css = str_replace( ', ', ',', $css );

		return $css;
	}

	private function remove_multiline_comments( $code, $method = 0 ) {

		switch ( $method ) {
			case 1:
			{

				$code = preg_replace( '/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $code );
				break;
			}

			case 0:

			default :
			{

				$open_pos = strpos( $code, '/*' );
				while ( $open_pos !== false ) {
					$close_pos = strpos( $code, '*/', $open_pos ) + 2;
					if ( $close_pos ) {
						$code = substr( $code, 0, $open_pos ) . substr( $code, $close_pos );
					} else {
						$code = substr( $code, 0, $open_pos );
					}

					$open_pos = strpos( $code, '/*', $open_pos );
				}

				break;
			}
		}

		return $code;
	}

	private function is_css_excluded( $link ) {
		foreach ( $this->exceptions as $exception ) {
			if ( strpos( $link->href, $exception ) !== false ) {
				return true;
			}
		}

		return false;
	}
}