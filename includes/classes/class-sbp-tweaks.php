<?php

class SBP_General {
	public function __construct() {
		if ( sbp_get_option( 'remove_query_strings' ) ) {
			add_action( 'script_loader_src', [ $this, 'remove_query_strings' ], 15 );
		}

		if ( sbp_get_option( 'remove_query_strings' ) ) {
			add_action( 'style_loader_src', [ $this, 'remove_query_strings' ], 15 );
		}

		if ( sbp_get_option( 'remove_emojis' ) ) {
			add_action( 'init', [ $this, 'remove_emoji_script' ] );
		}

		if ( sbp_get_option( 'disable_self_pingback' ) ) {
			add_action( 'pre_ping', [ $this, 'disable_self_pingback' ] );
		}

		if ( sbp_get_option( 'remove_jquery_migrate' ) ) {
			add_action( 'wp_default_scripts', [ $this, 'remove_jquery_migrate' ] );
		}

		if ( sbp_get_option( 'remove_dashicons' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'remove_dashicons' ] );
		}

		if (sbp_get_option('disable_hearbeat')) {
			add_action('init', wp_deregister_script( 'heartbeat' ));
		}

		// No need to write separated methods for these options
		if ( sbp_get_option( 'remove_shortlink' ) ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		}

		if ( sbp_get_option( 'remove_adjacent' ) ) {
			remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		}

		if ( sbp_get_option( 'wml_link' ) ) {
			remove_action( 'wp_head', 'wlwmanifest_link' );
		}

		if ( sbp_get_option( 'wp_generator' ) ) {
			remove_action( 'wp_head', 'wp_generator' );
		}
	}

	// Remove Query Strings

	/**
	 * Removes query string parameters (rev and ver) from styles and scripts
	 *
	 * @param $src
	 *
	 * @return mixed|string
	 */
	public function remove_query_strings( $src ) {
		if ( ! is_admin() ) {
			return preg_split( "/(\?rev|&ver|\?ver)/", $src )[0];
		}

		return $src;
	}

	// Remove Emoji Scripts

	/**
	 * Removes WordPress emoji script
	 */
	public function remove_emoji_script() {
		if ( ! is_admin() ) {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_filter( 'embed_head', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			add_filter( 'tiny_mce_plugins', [ $this, 'disable_emojis_tinymce' ] );
			add_filter( 'wp_resource_hints', [ $this, 'disable_emojis_dns_prefetch' ], 10, 2 );
		}
	}

	/**
	 * Disables emojis in Tinymce
	 *
	 * @param $plugins
	 *
	 * @return array
	 */
	public function disable_emojis_tinymce( $plugins ) {
		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, [ 'wpemoji' ] );
		} else {
			return [];
		}
	}


	/**
	 * @param $urls
	 * @param $relation_type
	 *
	 * @return array
	 */
	public function disable_emojis_dns_prefetch( $urls, $relation_type ) {
		if ( 'dns-prefetch' == $relation_type ) {
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2.2.1/svg/' );
			$urls          = array_diff( $urls, [ $emoji_svg_url ] );
		}

		return $urls;
	}

	/**
	 * @param $links
	 */
	public function disable_self_pingback( &$links ) {
		$home = get_option( 'home' );
		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $l ] );
			}
		}
	}

	/**
	 * @param $scripts
	 */
	public function remove_jquery_migrate( $scripts ) {
		if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
			$jquery_script = $scripts->registered['jquery'];

			if ( $jquery_script->deps ) { // Check whether the script has any dependencies
				$jquery_script->deps = array_diff( $jquery_script->deps, [ 'jquery-migrate' ] );
			}
		}
	}

	public function remove_dashicons() {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}
}

new SBP_General();