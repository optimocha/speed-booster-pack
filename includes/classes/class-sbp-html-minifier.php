<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_HTML_Minifier extends SBP_Abstract_Module {
	private $minify_css = true;
	private $minify_js = false;
	private $remove_comments = true;

	private $html;

	public function __construct() {
		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'minify_html' ) ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'handle_html_minify' ], 11 );
	}

	public function handle_html_minify( $html ) {
		$this->html = $html;
		$this->minifyHTML();

		return $this->html;
	}

	private function minifyHTML() {
		$pattern = '/<(?<script>script).*?<\/script\s*>|<(?<style>style).*?<\/style\s*>|<!(?<comment>--).*?-->|<(?<tag>[\/\w.:-]*)(?:".*?"|\'.*?\'|[^\'">]+)*>|(?<text>((<[^!\/\w.:-])?[^<]*)+)|/si';
		preg_match_all( $pattern, $this->html, $matches, PREG_SET_ORDER );
		$overriding = false;
		$raw_tag    = false;
		// Variable reused for output
		$html = '';
		foreach ( $matches as $token ) {
			$tag = ( isset( $token['tag'] ) ) ? strtolower( $token['tag'] ) : null;

			$content = $token[0];

			if ( is_null( $tag ) ) {
				if ( ! empty( $token['script'] ) ) {
					$strip = $this->minify_js;
				} elseif ( ! empty( $token['style'] ) ) {
					$strip = $this->minify_css;
				} elseif ( $content == '<!--sbp-html-minifier no minifier-->' ) {
					$overriding = ! $overriding;

					// Don't print the comments
					continue;
				} elseif ( $this->remove_comments ) {
					if ( ! $overriding && $raw_tag != 'textarea' ) {
						// Remove any HTML comments, except MSIE conditional comments
						$content = preg_replace( '/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $content );
					}
				}
			} else {
				if ( $tag == 'pre' || $tag == 'textarea' ) {
					$raw_tag = $tag;
				} elseif ( $tag == '/pre' || $tag == '/textarea' ) {
					$raw_tag = false;
				} else {
					if ( $raw_tag || $overriding ) {
						$strip = false;
					} else {
						$strip = true;
						// Remove all empty attributes, except action, alt, content, src
						$content = preg_replace( '/(\s+)(\w++(?<!\baction|\balt|\bcontent|\bsrc)="")/',
							'$1',
							$content );
						// Remove all space before the end of self-closing XHTML tags
						// JavaScript excluded
						$content = str_replace( ' />', '/>', $content );
					}
				}
			}

			if ( $strip ) {
				$content = $this->removeWhiteSpace( $content );
			}

			$html .= $content;
		}

		$this->html = $html;
	}

	private function removeWhiteSpace( $str ) {
		$str = str_replace( "\t", ' ', $str );
		$str = str_replace( "\n", '', $str );
		$str = str_replace( "\r", '', $str );

		while ( stristr( $str, '  ' ) ) {
			$str = str_replace( '  ', ' ', $str );
		}

		return $str;
	}
}
