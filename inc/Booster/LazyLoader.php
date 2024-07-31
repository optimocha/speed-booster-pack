<?php

namespace SpeedBoosterPack\Booster;

use SpeedBoosterPack\Common\Helper;

defined('ABSPATH') || exit;

class LazyLoader  extends AbstractModule {

	private $noscript_placeholder = '<!--SBP_NOSCRIPT_PLACEHOLDER-->';

	private $noscripts = [];

	public function __construct() {

		parent::__construct();

		if ( ! Helper::getOption( 'module_assets' ) || ! Helper::getOption( 'lazyload' ) || Helper::sbpShouldDisableFeature( 'lazyload' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );

	}

	public function run_class() {

		if ( $this->should_sbp_run ) {

			add_action( 'wp_enqueue_scripts', [ $this, 'add_lazy_load_script' ] );

			add_action( 'wp_enqueue_scripts', [ $this, 'deregister_media_elements' ] );

			add_filter( 'script_loader_tag', [ $this, 'add_attribute_to_tag' ], 10, 2 );

			add_filter( 'sbp_output_buffer', [ $this, 'lazy_load_handler' ] );

		}

	}

	function add_lazy_load_script() {

		wp_enqueue_script( 'sbp-lazy-load', SBP_URL . 'public/js/lazyload.js', false, '17.7.0', true );

		$lazy_loader_script = 'window.lazyLoadOptions = {
					elements_selector: "[loading=lazy]"
				};
				window.addEventListener(
				"LazyLoad::Initialized",
				function (event) {
				    window.lazyLoadInstance = event.detail.instance;
						if (window.MutationObserver) {
							var observer = new MutationObserver(function (mutations) {
							    mutations.forEach(function (mutation) {
							        for (i = 0; i < mutation.addedNodes.length; i++) {
							            if (typeof mutation.addedNodes[i].getElementsByTagName !== \'function\') {
							                return;
							            }
							            if (typeof mutation.addedNodes[i].getElementsByClassName !== \'function\') {
							                return;
							            }
							            imgs = mutation.addedNodes[i].getElementsByTagName(\'img\');
							            iframes = mutation.addedNodes[i].getElementsByTagName(\'iframe\');

							            if (0 === imgs.length && 0 === iframes.length) {
							                return;
							            }
							            lazyLoadInstance.update();
							        }
							    });
							});

							var b = document.getElementsByTagName("body")[0];
							var config = {childList: true, subtree: true};

							observer.observe(b, config);
						}
					},
					false
				);';

		$lazy_loader_script = apply_filters( 'sbp_lazyload_script', $lazy_loader_script );

		wp_add_inline_script( 'sbp-lazy-load', $lazy_loader_script, 'before' );

	}

	 function deregister_media_elements() {

	   wp_deregister_script( 'wp-mediaelement' );
	   wp_deregister_style( 'wp-mediaelement' );

	}


	function lazy_load_handler( $html ) {

		if ( is_embed() != false ) {
			return $html;
		}

		$this->replace_with_noscripts( $html );

		$lazyload_exclusions = Helper::explodeLines( Helper::getOption( 'lazyload_exclude' ) );
		// Add default lazyload exclusions
		$default_lazyload_exclusions = [
			'data-no-lazy',
			'skip-lazy',
			'loading=eager',
			'loading="eager"',
			'loading=\'eager\'',
			'loading=auto',
			'loading="auto"',
			'loading=\'auto\'',
			'wp-embedded-content',
			'images.dmca.com/Badges/',
		];
		$lazyload_exclusions         = apply_filters( 'sbp_lazyload_exclusions', array_merge( $lazyload_exclusions, $default_lazyload_exclusions ) );
		$placeholder                 = "data:image/svg+xml,%3Csvg xmlns='http%3A%2F%2Fwww.w3.org/2000/svg' style='width:auto;height:auto'%2F%3E";

		// Find all images
		preg_match_all( '/<(img|source|video|iframe)(.*?) (src=)[\'|"](.*?)[\'|"](.*?)>/is', $html, $resource_elements );

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
			// $newElement = preg_replace(
			// 	"/<(img|source|iframe)(.*?) (srcset=)(.*?)>/is",
			// 	'<$1$2 $3"' . $placeholder . '" data-$3$4>',
			// 	$newElement
			// );

			// add loading attribute, but only if the tag doesn't have one
			if ( ! strpos( $newElement, 'loading=' ) ) {
				$newElement = preg_replace(
					"/<(img|source|video|iframe)(.*?) ?(\/?)>/is",
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

	public function add_attribute_to_tag( $tag, $handle ) {

		if ( 'sbp-lazy-load' !== $handle ) {
			return $tag;
		}

		return str_replace( ' src=', ' async src=', $tag );

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
