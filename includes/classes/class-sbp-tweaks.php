<?php

class SBP_General {
	private $tweak_settings = [
		'remove_query_strings'  => 'remove_query_strings',
		'remove_emojis'         => 'remove_emojis',
		'disable_self_pingback' => 'disable_pingback',
		'remove_jquery_migrate' => 'remove_migrate',
		'remove_dashicons'      => 'remove_wp_dashicons',
		'remove_shortlink'      => 'remove_shortlink',
		'remove_adjacent'       => 'remove_adjacent',
		'wml_link'              => 'remove_wml_link',
		'wp_generator'          => 'remove_wp_generator',
		'post_revision_limit'   => 'limit_post_revisions',
		'autosave_interval'     => 'autosave_interval',
		'gutenberg_scripts'     => 'remove_gutenberg_scripts',
		'disable_embeds'        => 'disable_embeds',
		'heartbeat_frequency'    => 'set_heartbeat_frequency',
	];

	public function __construct() {
		foreach ( $this->tweak_settings as $option_name => $function ) {
			if ( sbp_get_option( $option_name ) ) {
				$this->$function();
			}
		}
	}

	/**
	 * Hook remove_query_string_process to proper actions
	 */
	private function remove_query_strings() {
		if ( ! is_admin() ) {
			if ( sbp_get_option( 'remove_query_strings' ) ) {
				add_action( 'script_loader_src', [ $this, 'remove_query_strings_process' ], 15 );
			}

			if ( sbp_get_option( 'remove_query_strings' ) ) {
				add_action( 'style_loader_src', [ $this, 'remove_query_strings_process' ], 15 );
			}
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
	public function remove_query_strings_process( $src ) {
		return preg_split( "/(\?rev|&ver|\?ver)/", $src )[0];
	}

	// Remove Emoji Scripts

	private function remove_emojis() {
		add_action( 'init', [ $this, 'remove_emoji_script' ] );
	}

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

	// Disable Self Pingback

	private function disable_pingback() {
		add_action( 'pre_ping', [ $this, 'disable_self_pingback' ] );
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

	// Remove jQuery Migrate

	private function remove_migrate() {
		add_action( 'wp_default_scripts', [ $this, 'remove_jquery_migrate' ] );
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

	// Remove Dash Icons
	private function remove_wp_dashicons() {
		add_action( 'wp_enqueue_scripts', [ $this, 'remove_dashicons' ] );
	}

	public function remove_dashicons() {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}

	private function remove_shortlink() {
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}

	private function remove_adjacent() {
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	private function remove_wml_link() {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	private function remove_wp_generator() {
		remove_action( 'wp_head', 'wp_generator' );
	}

	private function limit_post_revisions() {
		if ( ! empty( sbp_get_option( 'post_revision_limit' ) ) && ! defined( 'WP_POST_REVISIONS' ) ) {
			define( 'WP_POST_REVISIONS', sbp_get_option( 'post_revision_limit' ) );
		}
	}

	private function autosave_interval() {
		if ( ! empty( sbp_get_option( 'autosave_interval' ) ) && ! defined( 'AUTOSAVE_INTERVAL' ) ) {
			define( 'AUTOSAVE_INTERVAL', sbp_get_option( 'autosave_interval' ) );
		}
	}

	private function remove_gutenberg_scripts() {
		add_action( 'wp_enqueue_scripts', 'dequeue_gutenberg_scripts' );
	}

	private function dequeue_gutenberg_scripts() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	}

	private function disable_embeds() {
		add_action( 'init', [ $this, 'disable_embeds' ] );
		add_action( 'wp_footer', [ $this, 'deregister_embed_script' ] );
	}

	private function remove_embeds_from_init() {
		// Remove the REST API endpoint.
		remove_action( 'rest_api_init', 'wp_oembed_register_route' );

		// Turn off oEmbed auto discovery.
		add_filter( 'embed_oembed_discover', '__return_false' );

		// Don't filter oEmbed results.
		remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );

		// Remove oEmbed discovery links.
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );

		// Remove oEmbed-specific JavaScript from the front-end and back-end.
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		add_filter( 'tiny_mce_plugins', 'disable_embeds_tiny_mce_plugin' );

		// Remove all embeds rewrite rules.
		add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );

		// Remove filter of the oEmbed result before any HTTP requests are made.
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}

	public function disable_embeds_tiny_mce_plugin( $plugins ) {
		return array_diff( $plugins, array( 'wpembed' ) );
	}

	public function disable_embeds_rewrites( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}

		return $rules;
	}

	private function deregister_embed_script() {
		wp_dequeue_script( 'wp-embed' );
	}

	private function set_heartbeat_frequency() {
		switch(sbp_get_option('heartbeat_frequency')) {
			case "optimized":
				add_filter( 'heartbeat_settings', [ $this, 'heartbeat_frequency' ]);
				break;
			case "disabled":
				add_action( 'init', wp_deregister_script( 'heartbeat' ) );
				break;
		}
	}

	public function heartbeat_frequency() {
		$settings['interval'] = sbp_get_option('heartbeat_frequency');

		return $settings;
	}
}

new SBP_General();