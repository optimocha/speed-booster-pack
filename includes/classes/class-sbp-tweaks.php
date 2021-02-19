<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Tweaks extends SBP_Abstract_Module {
	private $tweak_settings = [
		'trim_query_strings'           => 'trim_query_strings',
		'dequeue_emoji_scripts'        => 'dequeue_emoji_scripts',
		'disable_self_pingbacks'       => 'disable_self_pingbacks',
		'dequeue_dashicons'            => 'dequeue_dashicons',
		'post_revisions'               => 'post_revisions',
		'autosave_interval'            => 'autosave_interval',
		'dequeue_block_library'        => 'dequeue_block_library',
		'disable_post_embeds'          => 'disable_post_embeds',
		'instant_page'                 => 'instant_page',
		'heartbeat_settings'           => 'heartbeat_settings',
		'declutter_head'               => [
			'declutter_shortlinks'           => 'declutter_shortlinks',
			'declutter_adjacent_posts_links' => 'declutter_adjacent_posts_links',
			'declutter_wlw'                  => 'declutter_wlw',
			'declutter_rsd'                  => 'declutter_rsd',
			'declutter_rest_api_links'       => 'declutter_rest_api_links',
			'declutter_feed_links'           => 'declutter_feed_links',
			'declutter_wp_version'           => 'declutter_wp_version',
		],
		'dequeue_comment_reply_script' => 'dequeue_comment_reply_script',
	];

	public function __construct() {
		if ( ! sbp_get_option( 'module_tweaks' ) ) {
			return;
		}

		$this->call_option_methods( $this->tweak_settings );
	}

	private function call_option_methods( $settings, $parent = null ) {
		foreach ( $settings as $option_name => $function ) {
			if ( is_array( $settings[ $option_name ] ) ) {
				$this->call_option_methods( $settings[ $option_name ], $option_name );
			} else {
				if ( null !== $parent ) {
					if ( is_array( sbp_get_option( $parent ) ) && isset( sbp_get_option( $parent )[ $option_name ] ) ) {
						$this->$function();
					}
				} else {
					if ( sbp_get_option( $option_name ) ) {
						$this->$function();
					}
				}
			}
		}
	}

	/**
	 * Hook trim_query_string_process to proper actions
	 */
	private function trim_query_strings() {
		if ( ! is_admin() ) {
			if ( sbp_get_option( 'trim_query_strings' ) ) {
				add_action( 'script_loader_src', [ $this, 'trim_query_strings_handle' ], 15 );
			}

			if ( sbp_get_option( 'trim_query_strings' ) ) {
				add_action( 'style_loader_src', [ $this, 'trim_query_strings_handle' ], 15 );
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
	public function trim_query_strings_handle( $src ) {
		return preg_split( "/(\?rev|&ver|\?ver)/", $src )[0];
	}

	// Remove Emoji Scripts

	private function dequeue_emoji_scripts() {
		add_action( 'init', [ $this, 'dequeue_emoji_scripts_handle' ] );
	}

	/**
	 * Removes WordPress emoji script
	 */
	public function dequeue_emoji_scripts_handle() {
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

	private function disable_self_pingbacks() {
		add_action( 'pre_ping', [ $this, 'disable_self_pingbacks_handle' ] );
	}

	/**
	 * @param $links
	 */
	public function disable_self_pingbacks_handle( &$links ) {
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
	public function dequeue_jquery_migrate_handle( $scripts ) {
		if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
			$jquery_script = $scripts->registered['jquery'];

			if ( $jquery_script->deps ) { // Check whether the script has any dependencies
				$jquery_script->deps = array_diff( $jquery_script->deps, [ 'jquery-migrate' ] );
			}
		}
	}

	// Remove Dash Icons
	private function dequeue_dashicons() {
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_dashicons_handle' ] );
	}

	public function dequeue_dashicons_handle() {
		if ( ! is_user_logged_in() ) {
			wp_dequeue_style( 'dashicons' );
			wp_deregister_style( 'dashicons' );
		}
	}

	private function post_revisions() {
		if ( ! empty( sbp_get_option( 'post_revisions' ) ) && ! defined( 'WP_POST_REVISIONS' ) ) {
			define( 'WP_POST_REVISIONS', sbp_get_option( 'post_revisions' ) );
		}
	}

	private function autosave_interval() {
		if ( ! empty( sbp_get_option( 'autosave_interval' ) ) && ! defined( 'AUTOSAVE_INTERVAL' ) ) {
			define( 'AUTOSAVE_INTERVAL', sbp_get_option( 'autosave_interval' ) );
		}
	}

	private function dequeue_block_library() {
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_block_library_handle' ] );
	}

	public function dequeue_block_library_handle() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	}

	private function disable_post_embeds() {
		add_action( 'init', [ $this, 'remove_embeds_from_init' ], 9999 );

	}

	public function remove_embeds_from_init() {
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

		// Remove all embeds rewrite rules.
		add_filter( 'rewrite_rules_array', [ $this, 'disable_embeds_rewrites' ] );

		// Remove filter of the oEmbed result before any HTTP requests are made.
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}

	public function disable_embeds_rewrites( $rules ) {
		foreach ( $rules as $rule => $rewrite ) {
			if ( false !== strpos( $rewrite, 'embed=true' ) ) {
				unset( $rules[ $rule ] );
			}
		}

		return $rules;
	}

	public function disable_post_embeds_handle() {
		wp_dequeue_script( 'wp-embed' );
	}

	private function heartbeat_settings() {
		switch ( sbp_get_option( 'heartbeat_settings' ) ) {
			case "optimized":
				add_filter( 'heartbeat_settings', [ $this, 'heartbeat_settings_handle' ] );
				break;
			case "disabled":
				add_action( 'wp_enqueue_scripts', [ $this, 'disable_heartbeat' ] );
				add_action( 'admin_enqueue_scripts', [ $this, 'disable_heartbeat' ] );
				break;
		}
	}

	public function disable_heartbeat() {
		wp_deregister_script( 'heartbeat' );
	}

	public function heartbeat_settings_handle() {
		$settings['interval'] = sbp_get_option( 'heartbeat_frequency' );

		return $settings;
	}

	// Instant Page
	private function instant_page() {
		add_action( 'wp_enqueue_scripts', [ $this, 'instant_page_handle' ] );
	}

	public function instant_page_handle() {
		wp_enqueue_script( 'sbp-ins-page', SBP_URL . 'public/js/inspage.js', false, '5.1.0', true );
	}

	private function dequeue_comment_reply_script() {
		add_action( 'init', [ $this, 'comment_reply_script_handle' ] );
	}

	public function comment_reply_script_handle() {
		wp_deregister_script( 'comment-reply' );
	}

	private function declutter_shortlinks() {
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}

	private function declutter_adjacent_posts_links() {
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
	}

	private function declutter_wlw() {
		remove_action( 'wp_head', 'wlwmanifest_link' );
	}

	private function declutter_rsd() {
		remove_action( 'wp_head', 'rsd_link' );
	}

	private function declutter_rest_api_links() {
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	}

	private function declutter_feed_links() {
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'feed_links', 2 );
	}

	private function declutter_wp_version() {
		remove_action( 'wp_head', 'wp_generator' );
	}
}