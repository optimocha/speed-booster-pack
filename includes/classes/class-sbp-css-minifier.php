<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_CSS_Minifier extends SBP_Abstract_Module {
	private $styles_list = [];
	private $exceptions = [
		'admin-bar',
		'dashicons',
	];

	public function __construct() {
		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'css_inline' ) ) {
			return;
		}

		$this->set_exceptions();

		add_action( 'wp_print_styles', [ $this, 'print_styles' ] );
	}

	public function print_styles() {
		if ( sbp_get_option( 'css_minify' ) ) {
			$minify = true;
		} else {
			$minify = false;
		}

		$this->generate_styles_list();

		$not_inlined = [];

		foreach ( $this->styles_list as $style ) {
			echo "<style type=\"text/css\" " . ( $style['media'] ? "media=\"{$style['media']}\"" : '' ) . ">";
			if ( ! $this->inline_css( $style['src'], $minify ) ) {
				$not_inlined[] = $style;
			}
			echo "</style>";
		}
		if ( ! empty( $not_inlined ) ) {
			foreach ( $not_inlined as $style ) {
				?>
                <link rel="stylesheet" href="<?php echo $style['src'] ?>" type="text/css" <?php echo $style['media'] ? "media=\"{$style['media']}\"" : '' ?> /><?php
			}
		}

		$this->unregister_styles();
	}

	private function set_exceptions() {
		$sbp_exceptions   = SBP_Utils::explode_lines( sbp_get_option( 'css_exclude' ) );
		$this->exceptions = array_merge( $sbp_exceptions, $this->exceptions );

		foreach ( $this->exceptions as $key => $exception ) {
			if ( trim( $exception ) != '' ) {
				$css_exceptions[ $key ] = trim( $exception );
			}
		}
	}

	private function generate_styles_list() {
		global $wp_styles;

		if ( isset( $wp_styles->queue ) && is_array( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $style ) {
				if ( ! $this->is_css_excluded( $style ) ) {
					$this->styles_list[] = [
						'src'   => $wp_styles->registered[ $style ]->src,
						'media' => $wp_styles->registered[ $style ]->args,
					];
				}
			}
		}
	}

	private function unregister_styles() {
		global $wp_styles;

		if ( isset( $wp_styles->queue ) && is_array( $wp_styles->queue ) ) {
			foreach ( $wp_styles->queue as $style ) {
				if ( $this->is_css_excluded( $style ) ) {
					continue;
				}

				wp_dequeue_style( $style );
				wp_deregister_style( $style );
			}
		}
	}

	private function inline_css( $url, $minify = true ) {
		$base_url = get_bloginfo( 'wpurl' );
		$path     = false;

		if ( strpos( $url, $base_url ) !== false ) {

			$path = str_replace( $base_url, rtrim( ABSPATH, '/' ), $url );

		} elseif ( $url[0] == '/' && $url[1] != '/' ) {

			$path = rtrim( ABSPATH, '/' ) . $url;
			$url  = $base_url . $url;
		}

		if ( $path && file_exists( $path ) ) {

			$css = file_get_contents( $path );

			if ( $minify ) {
				$css = $this->minify_css( $css );
			}

			$css = $this->rebuilding_css_urls( $css, $url );

			echo $css;

			return true;

		} else {

			return false;
		}

	}

	private function rebuilding_css_urls( $css, $url ) {
		$css_dir = substr( $url, 0, strrpos( $url, '/' ) );

		// remove empty url() declarations
		$css = preg_replace( "/url\(\s?\)/", "", $css );
		// new regex expression
		$css = preg_replace( "/url(?!\(['\"]?(data:|http:|https:))\(['\"]?([^\/][^'\"\)]*)['\"]?\)/i",
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

	private function is_css_excluded( $file ) {
		global $wp_styles;

		if ( is_string( $file ) && isset( $wp_styles->registered[ $file ] ) ) {
			$file = $wp_styles->registered[ $file ];
		}

		foreach ( $this->exceptions as $exception ) {
			if ( $file->handle == $exception || strpos( $file->src, $exception ) !== false ) {
				return true;
			}
		}

		return false;
	}
}