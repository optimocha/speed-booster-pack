<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_CDN extends SBP_Abstract_Module {

	private $included_dirs = [];
	private $excluded_extensions = [
		'php',
	];
	private $site_url = null;

	public function __construct() {
		if ( ! sbp_get_option( 'cdn_url' ) ) {
			return;
		}

		$this->site_url = get_site_url( get_current_blog_id() ); // For Multisite
		$this->set_included_dirs();
		$this->set_excluded_extensions();

		add_filter( 'sbp_output_buffer', [ $this, 'run_rewriter' ] );
	}

	public function run_rewriter( $html ) {
		// Don't run in preview
		if ( is_admin_bar_showing() && isset( $_GET['preview'] ) && $_GET['preview'] == 'true' ) {
			return $html;
		}

		$urls = $this->fetch_all_urls( $html );

		foreach ( $urls as $url ) {
			// Check if has excluded extension
			if ( $this->is_excluded( $url ) ) {
				continue;
			}

			// Replace the url
			$new_url = $this->replace_url( $url );

			// Replace URL With CDN URL
			$html = sbp_str_replace_first( $url, $new_url, $html );
		}

		return $html;
	}

	private function set_included_dirs() {
		// Get WP_CONTENT directory name
		$wp_content_dir_name = str_replace( ABSPATH, '', WP_CONTENT_DIR );

		$this->included_dirs = [
			$wp_content_dir_name,
			'wp-includes',
		];

		$includes            = sbp_get_option( 'cdn_includes' );
		$lines               = SBP_Utils::explode_lines( $includes, true );
		$this->included_dirs = array_merge( $this->included_dirs, $lines );
		$this->included_dirs = apply_filters( 'sbp_cdn_included_directories', $this->included_dirs );
	}

	private function set_excluded_extensions() {
		$excludes                  = sbp_get_option( 'cdn_excludes' );
		$lines                     = SBP_Utils::explode_lines( $excludes, true );
		$this->excluded_extensions = array_merge( $this->excluded_extensions, $lines );
		$this->excluded_extensions = apply_filters( 'sbp_cdn_excluded_extensions', $this->excluded_extensions );
	}

	private function fetch_all_urls( $html ) {
		$site_url = get_site_url();

		$included_dirs = implode( "|", $this->included_dirs );
		$regex         = '#(?<=[(\"\'])(?:' . $site_url . ')?/(?:((?:' . $included_dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

		preg_match_all( $regex, $html, $matches );

		if ( isset( $matches[0] ) && $matches[0] ) {
			return $matches[0];
		}

		return [];
	}

	private function replace_url( $url ) {
		$cdn_url = '//' . sbp_get_option( 'cdn_url' );

		if ( preg_match( '/^(http|https):\/\//', $url ) ) {
			return str_replace( $this->site_url, $cdn_url, $url );
		}

		// Check if relative path
		// We'll need this in the future
//		if ( substr( $url, 0, 2 ) == '//' ) {
//			// Remove http/s from site url
//			$site_url = preg_replace( '#^(http|https)://#', '', $this->site_url );
//			return $site_url . 'cukubikbik';
//
//			return str_replace( $site_url, $cdn_url, $url );
//		}

		if ( substr( $url, 0, 1 ) === '/' ) {
			return $cdn_url . $url;
		}

		return $url;
	}

	private function is_excluded( $url ) {
		$extension = SBP_Utils::get_file_extension_from_url( $url );

		return in_array( $extension, $this->excluded_extensions );
	}
}