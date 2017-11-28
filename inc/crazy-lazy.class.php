<?php
/**
 * CrazyLazy plugin class
 *
 * @package CrazyLazy
 */

/* Quit */
defined( 'ABSPATH' ) or exit;


/**
 * Class CrazyLazy
 */
final class CrazyLazy {


	/**
	 * Class instance
	 *
	 * @since   0.0.1
	 * @change  0.0.1
	 */
	public static function instance() {
		new self();
	}


	/**
	 * Class constructor
	 *
	 * @since   0.0.1
	 * @change  0.0.9
	 */
	public function __construct() {
		/* Go home */
		if ( is_feed() || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}

		/* Hooks */
		add_filter(
			'the_content',
			array(
				__CLASS__,
				'prepare_images',
			),
			12 /* Important for galleries */
		);
		add_filter(
			'post_thumbnail_html',
			array(
				__CLASS__,
				'prepare_images',
			)
		);
		add_action(
			'wp_enqueue_scripts',
			array(
				__CLASS__,
				'print_scripts',
			)
		);
	}


	/**
	 * Prepare content images for Crazy Lazy usage
	 *
	 * @since   0.0.1
	 * @change  1.0.0
	 *
	 * @param   string $content The original post content.
	 *
	 * @return  string The modified post content.
	 */
	public static function prepare_images( $content ) {
		/* No lazy images? */
		if ( strpos( $content, '-image' ) === false ) {
			return $content;
		}

		/* Replace images */
		return preg_replace_callback(
			'/(?P<all>                                                              (?# match the whole img tag )
				<img(?P<before>[^>]*)                                               (?# the opening of the img and some optional attributes )
				(                                                                   (?# match a class attribute followed by some optional ones and the src attribute )
					class=["\'](?P<class1>.*?(?:wp-image-|wp-post-image)[^>"\']*)["\']
					(?P<between1>[^>]*)
					src=["\'](?P<src1>[^>"\']*)["\']
					|                                                               (?# match same as before, but with the src attribute before the class attribute )
					src=["\'](?P<src2>[^>"\']*)["\']
					(?P<between2>[^>]*)
					class=["\'](?P<class2>.*?(?:wp-image-|wp-post-image)[^>"\']*)["\']
				)
				(?P<after>[^>]*)                                                    (?# match any additional optional attributes )
				(?P<closing>\/?)>                                                   (?# match the closing of the img tag with or without a self closing slash )
			)/x',
			array( 'CrazyLazy', 'replace_images' ),
			$content
		);
	}

	/**
	 * The callback function for the preg_match_callback to modify the img tags.
	 *
	 * @since   1.0.0
	 *
	 * @param array $matches The regex matches.
	 *
	 * @return string The modified content string.
	 */
	public static function replace_images( $matches ) {
		/* Empty gif */
		$null = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
		// Return unmodified image if the "data skip" attribute was found or the image has already been processed.
		if ( false !== strpos( $matches['all'], 'data-crazy-lazy="exclude"' ) || false !== strpos( $matches['class1'] . $matches['class2'], 'crazy_lazy' ) ) {
			return $matches['all'];
		} else {
            return '<img ' . $matches['before']
                   . ' style="display:none" '
                   . ' class="crazy_lazy ' . $matches['class1'] . $matches['class2'] . '" src="' . $null . '" '
                   . $matches['between1'] . $matches['between2']
                   . ' data-src="' . $matches['src1'] . $matches['src2'] . '" '
                   . $matches['after']
                   . $matches['closing'] . '><noscript>' . $matches['all'] . '</noscript>';
		}
	}


	/**
	 * Print lazy load scripts in footer
	 *
	 * @since   0.0.1
	 * @change  0.0.6
	 */
	public static function print_scripts() {
		/* Globals */
		global $wp_scripts;

		/* Check for jQuery */
		if ( ! empty( $wp_scripts ) && (bool) $wp_scripts->query( 'jquery' ) ) { /* hot fix for buggy wp_script_is() */
			self::_print_jquery_lazyload();
		} else {
			self::_print_javascript_lazyload();
		}
	}


	/**
	 * Call unveil lazy load jQuery plugin
	 *
	 * @since   0.0.5
	 * @change  0.0.9
	 */
	private static function _print_jquery_lazyload() {
		// wp_enqueue_script( 'unveil.js', plugins_url( '/js/jquery.unveil.min.js', CRAZY_LAZY_BASE ), array( 'jquery' ), '', true );
		wp_enqueue_script( 'unveil.js',  plugin_dir_url( __FILE__ ) . 'js/jquery.unveil.min.js', array( 'jquery' ), SPEED_BOOSTER_PACK_VERSION, true );
	}


	/**
	 * Call pure javascript lazyload.js
	 *
	 * @since   0.0.5
	 * @change  0.0.9
	 */
	private static function _print_javascript_lazyload() {
		// wp_enqueue_script( 'lazyload.js', plugins_url( '/js/lazyload.min.js', CRAZY_LAZY_BASE ), array(), '', true );
		wp_enqueue_script( 'lazyload.js',  plugin_dir_url( __FILE__ ) . 'js/lazyload.min.js', array( 'jquery' ), SPEED_BOOSTER_PACK_VERSION, true );
	}
}
