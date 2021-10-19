<?php

namespace SpeedBooster;

use simplehtmldom\HtmlDocument;

class SBP_Image_Dimensions extends SBP_Abstract_Module {
	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'missing_image_dimensions' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			add_filter( 'sbp_output_buffer', [ $this, 'specify_missing_dimensions' ] );
		}
	}

	public function specify_missing_dimensions( $html ) {
		$dom = new HtmlDocument();
		$dom->load( $html, true, false );
		$site_url = get_option( 'siteurl' );

		$images = $dom->find('img');
		if ( $images ) {
			foreach ( $images as &$image ) {
				if ( ! isset( $image->width ) || ! isset( $image->height ) ) {
					$src = $image->hasAttribute('data-src') ? $image->getAttribute('data-src') : $image->getAttribute('src');
					$path = sbp_remove_leading_string( $src, $site_url );
					$image_path = ABSPATH . $path;
					if ( file_exists( $image_path ) ) {
						$sizes = getimagesize( $image_path );
						$image->width = $sizes[0];
						$image->height = $sizes[1];
					}
				}
			}
		}

		return $dom;
	}
}