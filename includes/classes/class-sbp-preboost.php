<?php

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_Preboost {
	private $extension_type_matches = [
		'css'   => 'style',
		'js'    => 'script',
		'woff'  => 'font',
		'woff2' => 'font',
		'ttf'   => 'font',
		'eot'   => 'font',
		'jpg'   => 'image',
		'jpeg'  => 'image',
		'webp'  => 'image',
		'png'   => 'image',
		'gif'   => 'image',
		'tif'   => 'image',
		'tiff'  => 'image',
	];

	private $extension_mime_types = [
		'otf'   => 'font/otf',
		'eot'   => 'application/vnd.ms-fontobject',
		'svg'   => 'image/svg+xml',
		'ttf'   => 'font/ttf',
		'woff'  => 'font/woff',
		'woff2' => 'font/woff2',
	];

	private $appending_script = "";

	public function __construct() {
		if ( ! $this->should_run() ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_preload_tags' ] );
	}

	private function should_run() {
		global $sbp_options;
		if ( isset( $sbp_options['sbp_enable_preboost'] ) && $sbp_options['sbp_enable_preboost'] != true ) {
			return false;
		}

		return true;
	}

	public function add_preload_tags() {
		// Apply filters to mime types and matches
		$this->extension_type_matches = apply_filters( 'sbp_preboost_extension_type_matches', $this->extension_type_matches );
		$this->extension_mime_types   = apply_filters( 'sbp_preboost_extension_mime_types', $this->extension_mime_types );

		// Prepare tags
		$this->prepare_preload_tags();
		echo $this->appending_script;
	}

	private function prepare_preload_tags() {
		$sbp_preboost = get_option( 'sbp_preboost' );

		$rules = array_filter( explode( PHP_EOL, $sbp_preboost ) );

		if ( count( $rules ) ) {
			foreach ( $rules as $rule ) {
				// Trim spaces
				if ( ! trim( $rule ) ) {
					continue;
				}

				// Parse URL and Type
				$rule = trim( ltrim( $rule, ">>" ) );
				$url  = $this->get_url( $rule );
				$type = $this->get_type( $rule );

				// Check for illegal characters
				if ( false !== strpos( $url, '>' ) ) {
					continue;
				}

				// Check one last time if it's empty or not
				if ( empty( trim( $url ) ) ) {
					continue;
				}

				$mime_type              = $this->get_mime_type( SBP_Utils::get_file_extension( $rule ) );
				$mime_type_attribute    = $mime_type ? " type='" . esc_attr( $mime_type ) . "'" : '';
				$link_tag               = "<link rel='preload' href='" . esc_url($url) . "' as='" . esc_attr( $type ) . "'$mime_type_attribute />";
				$this->appending_script .= $link_tag . PHP_EOL;
			}
		}
	}

	private function get_type( $url ) {
		if ( $this->get_explicit_type( $url ) ) {
			return $this->get_explicit_type( $url );
		}

		$extension = strtolower( SBP_Utils::get_file_extension( $url ) );
		if ( array_key_exists( $extension, $this->extension_type_matches ) ) {
			return $this->extension_type_matches[ $extension ];
		}

		return "other";
	}

	private function get_explicit_type( $entry ) {
		if ( strpos( $entry, ">>" ) !== false ) {
			return explode( ">>", $entry )[0];
		}

		return false;
	}

	private function get_url( $entry ) {
		if ( $this->get_explicit_type( $entry ) !== false ) {
			return explode( ">>", $entry )[1];
		}

		return $entry;
	}

	private function get_mime_type( $extension ) {
		if ( array_key_exists( $extension, $this->extension_mime_types ) ) {
			return $this->extension_mime_types[ $extension ];
		}

		return false;
	}

}

new SBP_Preboost();