<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
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
		parent::__construct();

		if ( is_array( sbp_get_option( 'preboost' ) ) && ( ! sbp_get_option( 'module_assets' ) ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			$preboost = sbp_get_option( 'preboost' );

			if ( isset( $preboost['preboost_enable'] ) && $preboost['preboost_enable'] ) {
				add_action( 'wp_head', [ $this, 'add_preload_tags' ] );
			}

			if ( isset( $preboost['preboost_featured_image'] ) && $preboost['preboost_featured_image'] ) {
				add_action( 'wp_head', [ $this, 'add_featured_image_preload_tag' ] );
			}
		}
	}

	public function add_featured_image_preload_tag() {
		if ( is_singular() ) {
			$thumbnail = get_the_post_thumbnail_url();
			if ( $thumbnail ) {
				echo '<link rel="preload" href="' . $thumbnail . '" as="image" />' . PHP_EOL;
			}
		}
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
		$urls = [];
		if ( isset( sbp_get_option( 'preboost' )['preboost_include'] ) ) {
			$urls = SBP_Utils::explode_lines( sbp_get_option( 'preboost' )['preboost_include'] );
		}

		if ( is_singular() ) {
			$content_specific_preload_rules = sbp_get_post_meta( get_the_ID(), 'sbp_preload' );
			if ( $content_specific_preload_rules !== null ) {
				$content_specific_preload_rules_array = SBP_Utils::explode_lines( $content_specific_preload_rules );
				$urls                                 = array_merge( $urls, $content_specific_preload_rules_array );
			}
		}

		if ( isset( $urls ) && count( $urls ) ) {
			foreach ( $urls as $url ) {
				$type                   = $this->get_type( $url );
				$mime_type              = $this->get_mime_type( SBP_Utils::get_file_extension_from_url( $url ) );
				$mime_type_attribute    = $mime_type ? " type='" . esc_attr( $mime_type ) . "' crossorigin" : '';
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