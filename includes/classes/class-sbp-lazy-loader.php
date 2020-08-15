<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Lazy_Loader extends SBP_Abstract_Module {
	private $noscript_placeholder = '<!--SBP_NOSCRIPT_PLACEHOLDER-->';
	private $noscripts = [];

	public function __construct() {
		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'lazyload' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'add_lazy_load_script' ] );
		add_filter( 'sbp_output_buffer', [ $this, 'lazy_load_handler' ] );
	}

	function add_lazy_load_script() {
		wp_enqueue_script( 'sbp-lazy-load', SBP_URL . 'public/js/lazyload.js', false, '17.1.0', true );
		wp_add_inline_script( 'sbp-lazy-load',
			'
                (function() {
                    var ll = new LazyLoad({
                        elements_selector: "[loading=lazy]",
                        use_native: true
                    });
                })();
                ' );
	}

	function lazy_load_handler( $html ) {
		$this->replace_with_noscripts( $html );

		$lazyload_exclusions = SBP_Utils::explode_lines( sbp_get_option( 'lazyload_exclude' ) );
		// Add default lazyload exclusions
		$lazyload_exclusions[] = 'data-no-lazy';
		$lazyload_exclusions[] = 'skip-lazy';
		$lazyload_exclusions[] = 'loading=eager';
		$lazyload_exclusions[] = 'loading="eager';
		$lazyload_exclusions[] = "loading='eager";
		$lazyload_exclusions[] = 'loading=auto';
		$lazyload_exclusions[] = 'loading="auto';
		$lazyload_exclusions[] = "loading='auto";
		$lazyload_exclusions   = apply_filters( 'sbp_lazyload_exclusions', $lazyload_exclusions );
		$placeholder           = 'data:image/svg+xml,%3Csvg%20xmlns%3D%27http://www.w3.org/2000/svg%27%20viewBox%3D%270%200%203%202%27%3E%3C/svg%3E';

		// Find all images
		preg_match_all( '/<(img|source|iframe)(.*?) (src=)[\'|"](.*?)[\'|"](.*?)>/is', $html, $resource_elements );

		$elements_to_be_changed = [];

		// Determine which images will be changed
		foreach ( $resource_elements[0] as $element ) {
			$exclude_element = false;
			if ( count( $lazyload_exclusions ) > 0 ) {
				foreach ( $lazyload_exclusions as $exclusion ) {
					$exclusion = trim( $exclusion );
					if ( false !== strpos( $element, $exclusion ) ) {
						$exclude_element = true;
					}
				}
			}

			// If not excluded element, put it into the to be changed list.
			if ( false === $exclude_element ) {
				$elements_to_be_changed[] = $element;
			}
		}

		// Clean the possible repeated elements
		$elements_to_be_changed = array_unique( $elements_to_be_changed );

		// Process all elements marked as to be changed
		foreach ( $elements_to_be_changed as $element ) {
			// Change src with placeholder
			$newElement = preg_replace(
				"/<(img|source|iframe)(.*?) (src=)(.*?)>/is",
				'<$1$2 $3"' . $placeholder . '" data-$3$4>',
				$element
			);

			// change srcset
			$newElement = preg_replace(
				"/<(img|source|iframe)(.*?) (srcset=)(.*?)>/is",
				'<$1$2 $3"' . $placeholder . '" data-$3$4>',
				$newElement
			);

			// add loading attribute, but only if the tag doesn't have one
			if( ! strpos( $newElement, 'loading=' ) ) {
			$newElement = preg_replace(
					"/<(img|source|iframe)(.*?) ?(\/?)>/is",
					'<$1$2 loading="lazy" $3>',
					$newElement
				);
			}

			// prevent mixed content errors
			$newElement = str_replace( 'http://', '//', $newElement );

			$html = str_replace( $element, $newElement, $html );
		}

		$this->add_noscripts( $html );

		return $html;
	}

	/**
	 * Replaces noscript tags with placeholder and sets the noscripts property
	 *
	 * @param $html
	 *
	 * @return mixed
	 */
	private function replace_with_noscripts( &$html ) {
		$regex = '/<noscript(.*?)>(.*?)<\/noscript>/si';
		preg_match_all( $regex, $html, $matches );
		$this->noscripts = $matches[0];
		if ( count( $this->noscripts ) > 0 ) {
			$html = preg_replace( $regex, $this->noscript_placeholder, $html );
		}
	}

	/**
	 * Replaces noscript placeholders with noscripts.
	 *
	 * @param $html
	 */
	private function add_noscripts( &$html ) {
		foreach ( $this->noscripts as $noscript ) {
			$pos = strpos( $html, $this->noscript_placeholder );
			if ( false !== $pos ) {
				$html = substr_replace( $html, $noscript, $pos, strlen( $this->noscript_placeholder ) );
			}
		}
	}
}