<?php

namespace SpeedBooster;

// Security control for vulnerability attempts
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class SBP_Preboost extends SBP_Abstract_Module {
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
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'preboost' )['preboost_enable'] ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_preload_tags' ] );
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
		$urls = SBP_Utils::explode_lines( sbp_get_option( 'preboost' )['preboost_include'] );

		if ( count( $urls ) ) {
			foreach ( $urls as $url ) {
				$type                   = $this->get_type( $url );
				$mime_type              = $this->get_mime_type( SBP_Utils::get_file_extension_from_url( $url ) );
				$mime_type_attribute    = $mime_type ? " type='" . esc_attr( $mime_type ) . "'" : '';
				$link_tag               = "<link rel='preload' href='" . esc_url( $url ) . "' as='" . esc_attr( $type ) . "'$mime_type_attribute />";
				$this->appending_script .= $link_tag . PHP_EOL;
			}
		}
	}

	private function get_type( $url ) {
		$extension = strtolower( SBP_Utils::get_file_extension_from_url( $url ) );
		if ( array_key_exists( $extension, $this->extension_type_matches ) ) {
			return $this->extension_type_matches[ $extension ];
		}

		return "other";
	}

	private function get_mime_type( $extension ) {
		if ( array_key_exists( $extension, $this->extension_mime_types ) ) {
			return $this->extension_mime_types[ $extension ];
		}

		return false;
	}

}