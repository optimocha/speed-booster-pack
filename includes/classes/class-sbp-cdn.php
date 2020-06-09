<?php

namespace SpeedBooster;

class SBP_CDN extends SBP_Abstract_Module {
	public function __construct() {
		if ( ! sbp_get_option( 'module_special' ) || ! sbp_get_option( 'cdn_url' ) ) {
			return;
		}

		add_filter( 'sbp_output_buffer', [ $this, 'cdn_rewriter' ] );
	}

	public function cdn_rewriter( $html ) {
		// LAHMACUNTODO: değişken isimlerini küçük harflerle değiştir
		//Prep Site URL
		$escapedSiteURL = quotemeta( get_option( 'home' ) );
		$regExURL       = '(https?:|)' . substr( $escapedSiteURL, strpos( $escapedSiteURL, '//' ) );

		//Prep Included Directories
		// LAHMACUNTODO: apply_filters ile değiştir
		$directories = 'wp\-content|wp\-includes';

		// LAHMACUNTODO: PHP'ler hariç tutulmalı & bir filter daha yaratalım dosya uzantıları için

		//Rewrite URLs + Return
		$regEx    = '#(?<=[(\"\'])(?:' . $regExURL . ')?/(?:((?:' . $directories . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';
		$cdn_HTML = preg_replace_callback( $regEx, [ $this, 'rewrite_url' ], $html );

		return $cdn_HTML;
	}

	public function rewrite_url( $url ) {
		global $sbp_options;
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