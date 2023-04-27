<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Tweaks extends SBP_Abstract_Module {

	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_tweaks' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {

		if ( ! $this->should_sbp_run ) { return; }

		$this->trim_query_strings();
		$this->dequeue_emoji_scripts();
		$this->disable_self_pingbacks();
		$this->dequeue_dashicons();
		$this->post_revisions();
		$this->autosave_interval();
		$this->dequeue_block_library();
		$this->dequeue_global_styles();
		$this->disable_post_embeds();
		$this->instant_page();
		$this->heartbeat_settings();
		$this->declutter_shortlinks();
		$this->declutter_adjacent_posts_links();
		$this->declutter_wlw();
		$this->declutter_rsd();
		$this->declutter_rest_api_links();
		$this->declutter_feed_links();
		$this->declutter_wp_version();
		$this->dequeue_comment_reply_script();

	}

	/**
	 * Hook trim_query_string_process to proper actions
	 */
	private function trim_query_strings() {

		if ( is_admin() || ! sbp_get_option( 'trim_query_strings' ) ) { return; }

		add_action( 'script_loader_src', [ $this, 'trim_query_strings_handle' ], 15 );
		add_action( 'style_loader_src', [ $this, 'trim_query_strings_handle' ], 15 );

	}

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

		if ( ! sbp_get_option( 'dequeue_emoji_scripts' ) || is_admin() ) { return; }

		add_action( 'init', [ $this, 'dequeue_emoji_scripts_handle' ] );
	}

	/**
	 * Removes WordPress emoji script
	 */
	public function dequeue_emoji_scripts_handle() {

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

	public function disable_emojis_tinymce( $plugins ) {

		if ( is_array( $plugins ) ) {
			return array_diff( $plugins, [ 'wpemoji' ] );
		} else {
			return [];
		}

	}

	public function disable_emojis_dns_prefetch( $urls, $relation_type ) {

		if ( 'dns-prefetch' == $relation_type ) {
			$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2.2.1/svg/' );
			$urls          = array_diff( $urls, [ $emoji_svg_url ] );
		}

		return $urls;

	}

	private function disable_self_pingbacks() {

		if ( ! sbp_get_option( 'disable_self_pingbacks' ) ) { return; }

		add_action( 'pre_ping', [ $this, 'disable_self_pingbacks_handle' ] );
	}

	public function disable_self_pingbacks_handle( &$links ) {

		$home = get_option( 'home' );

		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $l ] );
			}
		}

	}

	private function dequeue_dashicons() {

		if ( ! sbp_get_option( 'dequeue_dashicons' ) ) { return; }
		
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_dashicons_handle' ] );

	}

	public function dequeue_dashicons_handle() {

		if ( is_user_logged_in() ) { return; }
		
		wp_deregister_style( 'dashicons' );

	}

	private function post_revisions() {

		$post_revisions = sbp_get_option( 'post_revisions' );

		if ( empty( $post_revisions ) || ! (int) $post_revisions ) { return; }

		add_filter( 'wp_revisions_to_keep', function( $num, $post ) use ( $post_revisions ) {
			return $post_revisions;
		}, 50, 2 );

	}

	private function autosave_interval() {

		$autosave_interval = sbp_get_option( 'autosave_interval' );

		if ( empty( $autosave_interval ) || ! (int) $autosave_interval || defined( 'AUTOSAVE_INTERVAL' ) ) { return; }
		
		define( 'AUTOSAVE_INTERVAL', sbp_get_option( 'autosave_interval' ) * 60 );

	}

	private function dequeue_block_library() {

		if ( ! sbp_get_option( 'dequeue_block_library' ) ) { return; }
		
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_block_library_handle' ] );

	}

	public function dequeue_block_library_handle() {

		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );

	}

	private function dequeue_global_styles() {

		if ( ! sbp_get_option( 'dequeue_global_styles' ) ) { return; }
		
		add_action( 'init', [ $this, 'dequeue_global_styles_handle' ], 9999 );

	}

	public function dequeue_global_styles_handle() {

		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );

	}

	private function disable_post_embeds() {

		if ( ! sbp_get_option( 'disable_post_embeds' ) ) { return; }

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

	public function heartbeat_settings_handle( $settings ) {

		$settings['interval'] = 120;

		return $settings;

	}

	public function disable_heartbeat() {

		wp_deregister_script( 'heartbeat' );

	}

	// Instant Page
	private function instant_page() {

		if ( ! sbp_get_option( 'instant_page' ) ) { return; }

		add_action( 'wp_enqueue_scripts', [ $this, 'instant_page_handle' ] );

	}

	public function instant_page_handle() {

		wp_enqueue_script( 'sbp-ins-page', SPEED_BOOSTER_PACK['url'] . 'public/js/inspage.js', false, '5.1.0', true );

	}

	private function dequeue_comment_reply_script() {

		if ( ! sbp_get_option( 'dequeue_comment_reply_script' ) ) { return; }

		add_action( 'init', [ $this, 'comment_reply_script_handle' ] );

	}

	public function comment_reply_script_handle() {

		wp_deregister_script( 'comment-reply' );

	}

	private function declutter_shortlinks() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) || ! sbp_get_option( 'declutter_head' )[ 'declutter_shortlinks' ] ) { return; }

		remove_action( 'wp_head', 'wp_shortlink_wp_head' );

	}

	private function declutter_adjacent_posts_links() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_adjacent_posts_links' ] ) { return; }

		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );

	}

	private function declutter_wlw() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_wlw' ] ) { return; }

		remove_action( 'wp_head', 'wlwmanifest_link' );

	}

	private function declutter_rsd() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_rsd' ] ) { return; }

		remove_action( 'wp_head', 'rsd_link' );

	}

	private function declutter_rest_api_links() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_rest_api_links' ] ) { return; }

		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );

	}

	private function declutter_feed_links() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_feed_links' ] ) { return; }

		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'feed_links', 2 );

	}

	private function declutter_wp_version() {

		if ( ! is_array( sbp_get_option( 'declutter_head' ) ) ||  ! sbp_get_option( 'declutter_head' )[ 'declutter_wp_version' ] ) { return; }

		remove_action( 'wp_head', 'wp_generator' );

	}
}