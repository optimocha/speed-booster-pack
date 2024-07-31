<?php

namespace SpeedBoosterPack\Booster;

defined('ABSPATH') || exit;

use simplehtmldom\HtmlDocument;
use SpeedBoosterPack\Common\Helper;

class ImageDimensions  extends AbstractModule {
	public function __construct() {

		parent::__construct();

		if ( ! Helper::getOption( 'module_assets' ) || ! Helper::getOption( 'missing_image_dimensions' ) ) {
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
		$site_url = Helper::remove_protocol( $site_url );

		$images = $dom->find('img');
		if ( $images ) {
			foreach ( $images as &$image ) {
				if ( ! isset( $image->width ) || ! isset( $image->height ) ) {
					$src = $image->hasAttribute('data-src') ? $image->getAttribute('data-src') : $image->getAttribute('src');
					$src = Helper::remove_protocol( $src );
					$path = sbp_remove_leading_string( $src, $site_url );
					$image_path = ABSPATH . $path;
					if ( file_exists( $image_path ) ) {
						// https://developer.wordpress.org/reference/functions/file_is_valid_image/
						$sizes = getimagesize( $image_path );
						if( isset( $sizes[1] ) ) {
							$image->width = $sizes[0];
							$image->height = $sizes[1];
						}
					}
				}
			}
		}

		return $dom;
	}
}
