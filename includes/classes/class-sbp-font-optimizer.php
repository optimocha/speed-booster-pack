<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Font_Optimizer extends SBP_Abstract_Module {
	private $families = [];
	private $subsets = [];

	private $css2_families = [];

	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'optimize_gfonts' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			add_filter( 'sbp_output_buffer', [ $this, 'process_google_fonts' ], 10 );
		}
	}

	public function process_google_fonts( $html ) {

		if ( is_embed() ) {
			return $html;
		}

		$html = $this->process_google_fonts_api( $html );
		$html = $this->process_new_google_fonts_api( $html );

		$html     = preg_replace( "/<link[^<>\/]+href=['\"?]((https?:)?\/\/fonts\.googleapis\.com\/css\?(.*?))['\"?].*?>/i", '', $html );
		$link_tag = $this->create_tag();
		$html     = str_replace( '</title>', '</title>' . PHP_EOL . '<link rel="preconnect" href="https://fonts.googleapis.com" />' . PHP_EOL . '<link rel="preconnect" href="https://fonts.gstatic.com" />' . PHP_EOL . $link_tag, $html );

		return $html;
	}

	public function process_google_fonts_api( $html ) {
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

		return $html;
	}

	public function process_new_google_fonts_api( $html ) {
		preg_match_all( "/<link[^<>\/]+href=['\"?]((https?:)?\/\/fonts\.googleapis\.com\/css2\?(.*?))['\"?].*?>/is", $html, $matches );
		if ( ! isset( $matches[1] ) || empty( $matches[1] ) ) {
			return $html;
		}

		$urls  = $matches[1];
		$fonts = [];

		// Process each url
		foreach ( $urls as $url ) {
			$fonts[] = $this->parse_css2_attributes( $url );
		}

		$list = [];
		foreach ( $fonts as $font ) {
			if ( is_array( $font['family'] ) ) {
				foreach ( $font['family'] as $font ) {
					$this->append_css2_fonts_list( $font );
					$list[] = $font;
				}
			} else {
				$this->append_css2_fonts_list( $font['family'] );
				$list[] = $font['family'];
			}
		}

		$link_tag = $this->generate_css2_link_tag();

		$html = preg_replace( "/<link[^<>\/]+href=['\"?]((https?:)?\/\/fonts\.googleapis\.com\/css2\?(.*?))['\"?].*?>/i", '', $html );
		$html = str_replace( '</title>', '</title>' . PHP_EOL . $link_tag, $html );

		return $html;
	}

	private function append_css2_fonts_list( $font ) {
		$font_array  = explode( ':', $font );
		$font_family = $font_array[0];

		if ( isset( $font_array[1] ) ) {
			$font_attributes = explode( '@', $font_array[1] );

			if ( isset( $font_attributes[1] ) ) {
				$weights = explode( ';', $font_attributes[1] );

				if ( ! isset( $this->css2_families[ $font_family ]['weights'] ) ) {
					$this->css2_families[ $font_family ]['weights'] = [];
				}

				$this->css2_families[ $font_family ]['weights'] = array_merge( $this->css2_families[ $font_family ]['weights'], $weights );
			}

			$styles = explode( ',', $font_attributes[0] );

			if ( $styles ) {
				foreach ( $styles as $style ) {
					$this->css2_families[ $font_family ]['styles'][] = $style;
				}
			}
		} else {
			if ( ! isset( $this->css2_families[ $font_family ] ) ) {
				$this->css2_families[ $font_family ] = [];
			}
		}
	}

	private function generate_css2_link_tag() {
		$query_strings = [];

		// Sort and clear arrays first
		$this->css2_families = array_map( function ( $item ) {
			$item['styles'] = array_unique( $item['styles'] );
			sort( $item['styles'] );
			$item['weights'] = array_unique( $item['weights'] );
			sort( $item['weights'] );

			return $item;
		}, $this->css2_families );

		if ( $this->css2_families ) {
			foreach ( $this->css2_families as $family_name => $attributes ) {
				$query_string = 'family=' . $family_name;

				if ( isset( $attributes['styles'] ) && $attributes['styles'] ) {
					$query_string .= ':' . implode( ',', $attributes['styles'] );
				}

				if ( isset( $attributes['weights'] ) && $attributes['weights'] ) {
					$query_string .= '@' . implode( ';', $attributes['weights'] );
				}

				$query_strings[] = $query_string;
			}
		}

		return '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?' . implode( '&', $query_strings ) . '" />';
	}

	private function parse_css2_attributes( $url ) {
		return sbp_proper_parse_str( parse_url( $url )['query'] );
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