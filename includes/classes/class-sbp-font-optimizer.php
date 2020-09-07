<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Font_Optimizer extends SBP_Abstract_Module {
	private $families;
	private $subsets;

	public function __construct() {
		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'optimize_gfonts' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'run' ] );
	}

	public function run() {
		if ( is_embed() ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'process_google_fonts' ], 10 );
	}

	public function process_google_fonts( $html ) {
		preg_match_all( "/<link[^<>\/]+href=['\"?]((https?:)?\/\/fonts\.googleapis\.com\/css\?(.*?))['\"?].*?>/is", $html, $matches );
		if ( ! isset( $matches[1] ) || empty( $matches[1] ) ) {
			return $html;
		}

		$urls = $matches[1];

		// Process each url
		foreach ( $urls as $url ) {
			$attributes = $this->parse_attributes( $url );

			if ( isset( $attributes['family'] ) ) {
				$this->parse_family( $attributes['family'] );
			}

			if ( isset( $attributes['subset'] ) ) {
				$this->parse_subset( $attributes['subset'] );
			}
		}

		$html     = preg_replace( "/<link[^<>\/]+href=['\"?]((https?:)?\/\/fonts\.googleapis\.com\/css\?(.*?))['\"?].*?>/i", '', $html );
		$link_tag = $this->create_tag();
		$html     = str_replace( '</head>', '<link rel="dns-prefetch" href="//fonts.googleapis.com" />' . PHP_EOL . '<link rel="dns-prefetch" href="//fonts.gstatic.com" />' . PHP_EOL . $link_tag . PHP_EOL . '</head>', $html );

		return $html;
	}

	private function parse_attributes( $url ) {
		$url = htmlspecialchars_decode( $url );
		parse_str( parse_url( $url )['query'], $attributes );

		return $attributes;
	}

	private function parse_family( $family ) {
		$families = explode( '|', $family ); // if there is no pipe, explode will return 1 element array
		foreach ( $families as $family ) {
			if ( strpos( $family, ':' ) !== false ) {
				$family                          = explode( ':', $family );
				$name                            = $family[0];
				$this->families[ $name ]['name'] = $name;

				// Explode sizes
				$sizes = $family[1];
				$sizes = explode( ',', $sizes );
				foreach ( $sizes as $size ) {
					$this->families[ $name ]['sizes'][] = $size;
				}
			} else {
				$this->families[ $family ]['name']  = $family;
				$this->families[ $family ]['sizes'] = array_merge( $this->families[ $family ]['sizes'], [] );
			}
		}
	}

	private function parse_subset( $subset ) {
		$subsets = explode( ',', $subset );
		foreach ( $subsets as $subset ) {
			$this->subsets[] = $subset;
		}
	}

	private function create_tag() {
		// parse families
		$families = [];
		foreach ( $this->families as $family ) {
			if ( isset( $family['sizes'] ) && ! empty( $family['sizes'] ) ) {
				$family['sizes'] = array_unique( $family['sizes'] );
				$families[]      .= $family['name'] . ":" . implode( ',', $family['sizes'] );
			} else {
				$families[] .= $family['name'];
			}
		}

		$families = implode( '|', $families );

		// parse subsets
		$subsets = null;
		if ( null !== $this->subsets ) {
			$subsets = implode( ",", array_unique( $this->subsets ) );
		}

		$attributes = []; // Don't put attributes that doesn't exists

		if ( $families ) {
			$attributes[] = 'family=' . esc_attr( $families );
		}

		if ( $subsets ) {
			$attributes[] = 'subset=' . esc_attr( $subsets );
		}

		$attributes[] = 'display=swap';

		$final_gfont_url = 'https://fonts.googleapis.com/css?' . implode( '&', $attributes );

		return '<link rel="preload" as="style" href="' . $final_gfont_url . '" />' . PHP_EOL . '<link rel="stylesheet" href="' . $final_gfont_url . '" media="print" onload="this.media=\'all\'">';
	}
}