<?php

namespace SpeedBooster;

class SBP_CDN extends SBP_Abstract_Module {
	private $excluded_file_extensions = [
		'php',
	];
	private $included_directories = 'wp\-content|wp\-includes';

	public function __construct() {
		if ( ! sbp_get_option( 'module_special' ) || ! sbp_get_option( 'cdn_url' ) ) {
			return;
		}

		add_action( 'get_footer', [ $this, 'apply_cdn_filters' ] );
		add_filter( 'sbp_output_buffer', [ $this, 'cdn_rewriter' ] );
	}

	public function apply_cdn_filters() {
		$new_filters                    = apply_filters( 'sbp_cdn_excluded_extensions', $this->excluded_file_extensions );
		$this->excluded_file_extensions = array_merge( $this->excluded_file_extensions, $new_filters );
		$this->excluded_file_extensions = array_unique( $this->excluded_file_extensions );

		//Prep Included Directories
		$this->included_directories = apply_filters( 'sbp_cdn_included_directories', $this->included_directories );
	}

	public function cdn_rewriter( $html ) {
		//Prep Site URL
		$escaped_site_url = quotemeta( get_option( 'home' ) );
		$regex_url        = '(https?:|)' . substr( $escaped_site_url, strpos( $escaped_site_url, '//' ) );


		//Rewrite URLs + Return
		$regex    = '#(?<=[(\"\'])(?:' . $regex_url . ')?/(?:((?:' . $this->included_directories . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
		$cdn_html = preg_replace_callback( $regex, [ $this, 'rewrite_url' ], $html );

		return $cdn_html;
	}

	public function rewrite_url( $url ) {
		// Simply check if it's php or not
		$file_extension = SBP_Utils::get_file_extension_from_url( $url[0] );
		if ( in_array( $file_extension, $this->excluded_file_extensions ) ) {
			return $url[0];
		}

		$sbp_cdn_url = sbp_get_option( 'cdn_url' );

		//Make Sure CDN URL is Set
		if ( ! empty( $sbp_cdn_url ) ) {
			//Don't Rewrite if Previewing
			if ( is_admin_bar_showing() && isset( $_GET['preview'] ) && $_GET['preview'] == 'true' ) {
				return $url[0];
			}

			//Prep Site URL
			$site_url = get_option( 'home' );
			$site_url = substr( $site_url, strpos( $site_url, '//' ) );

			//Replace URL w/ No HTTP/S Prefix
			if ( strpos( $url[0], '//' ) === 0 ) {
				return str_replace( $site_url, $sbp_cdn_url, $url[0] );
			}

			//Found Site URL, Replace Non Relative URL w/ HTTP/S Prefix
			if ( strstr( $url[0], $site_url ) ) {
				return str_replace( [ 'http:' . $site_url, 'https:' . $site_url ], $sbp_cdn_url, $url[0] );
			}

			//Replace Relative URL
			return $sbp_cdn_url . $url[0];
		}

		//Return Original URL
		return $url[0];
	}
}