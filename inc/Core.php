<?php

//	TODO:
//	?	run (hook methods below, hooks in parantheses)
//	+	load_plugin_textdomain (plugins_loaded)
//	+	activate (admin_init) // set defaults & redirect & delete option: sbp_activated
//	+	deactivate (register_deactivation_hook)
//	+	upgrade (upgrader_process_complete) // add_option( 'sbp_upgraded', [ 'from' => 'x.y.z', 'to' => 'a.b.c' ] )
//	+	upgrade_process (plugins_loaded) // otomatik de olabilir, çıkartılacak notice'teki linke tıklayarak da olabilir

/**
 * The file that defines the core plugin class
 *
 * @since      4.0.0
 *
 * @package    Optimocha\SpeedBooster
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

use Optimocha\SpeedBooster\Frontend\Cache;
use Optimocha\SpeedBooster\Frontend\AdvancedCacheGenerator;

/**
 * The core plugin class.
 *
 * @since      4.0.0
 * @package    Optimocha\SpeedBooster
 * @author     Optimocha
 */
final class Core {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The main options array of the plugin.
	 *
	 * @since    5.0.0
	 * @access   protected
	 */
	protected $options;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    4.0.0
	 */
	public function __construct() {

		$this->options = get_option( 'sbp_options' );

		$this->loader = new Loader();
		$this->init_modules();
		$this->define_public_hooks();
		$this->define_admin_hooks();

		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		// TODO: upgrader_process_complete hook'una bağlanan metodun admin_init'e hook'lanan başka bir metoda bağlı çalışması lazımmış: https://wordpress.stackexchange.com/a/408306
		add_action( 'upgrader_process_complete', [ $this, 'upgrade' ] );
		add_action( 'admin_init', [ $this, 'upgrade_process' ] );
		add_action( 'admin_init', [ $this, 'activate' ] );
		// add_action( 'admin_init', [ $this, 'define_admin_hooks' ] );

		// TODO: don't run this on every admin init!
		// this should run only in post/cpt edit screens & maybe the sbp settings page (load-toplevel_page_sbp-settings)
		add_action( 'admin_init', [ $this, 'save_post_types' ] );

	}

	/**
	 * Does stuff when the plugin is activated.
	 *
	 * @since    5.0.0
	 */
    public static function activate() {

		if( ! get_option( 'sbp_activated' ) ) { return; }

        if ( sbp_get_option( 'module_caching' ) && ! sbp_should_disable_feature( 'caching' ) ) {

            Cache::clear_total_cache();
            Cache::set_wp_cache_constant( true );
            Cache::generate_htaccess();

            $advanced_cache_file_content = AdvancedCacheGenerator::generate_advanced_cache_file();
            $advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
            if ( $advanced_cache_file_content ) {
                file_put_contents( $advanced_cache_path, $advanced_cache_file_content );
            }

        }

        delete_option( 'sbp_activated' );

    }

	/**
	 * Does stuff when the plugin is deactivated.
	 *
	 * @since    5.0.0
	 */
	public static function deactivate() {
		Cache::set_wp_cache_constant( false );
		Cache::clean_htaccess();
		Cache::clear_total_cache();
		$adv_cache_file = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( file_exists( $adv_cache_file ) ) {
			unlink( $adv_cache_file );
		}
	}

	/**
	 * Adds the sbp_upgraded option to the _options table.
	 *
	 * @since    5.0.0
	 */
	public static function upgrade() {

		$sbp_upgraded = get_option( 'sbp_upgraded', null );

		$old_version = '0.0';
		if( isset( $sbp_upgraded ) ) {
			$old_version = $sbp_upgraded[ 'from' ];
		}
		$new_version = SBP_VERSION;

		update_option( 'sbp_upgraded', [ 'from' => $old_version, 'to' => $new_version ] );

	}

	/**
	 * Does stuff when the plugin is upgraded.
	 *
	 * @since    5.0.0
	 */
	public static function upgrade_process() {
		// TODO: populate this method.
		// idea: https://wordpress.stackexchange.com/questions/25910/uninstall-activate-deactivate-a-plugin-typical-features-how-to/25979#25979
		// idea: https://dream-encode.com/determining-when-a-wordpress-plugin-or-theme-is-updated/
	}

	// TODO: move this into helpers.php or Utils.php
	private function should_plugin_run() {

		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) {
			return false;
		}

		if ( preg_match( '/(_wp-|\.txt|\.pdf|\.xml|\.xsl|\.svg|\.ico|\/wp-json|\.gz|\/feed\/?)/', $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$login_path = parse_url( wp_login_url(), PHP_URL_PATH );

		if( false !== stripos( $_SERVER[ 'REQUEST_URI' ], $login_path ) ) {
			return false;
		}

		$query_strings_to_exclude = [
			"sbp_disable"                   => "1", // speed booster pack
			"elementor-preview"             => "elementor", // elementor
			"ai-debug-blocks"               => "1", // ad inserter
			"ao_noptimize"                  => "1", // autoptimize
			"ao_noptirocket"                => "1", // autoptimize & wp rocket
			"bt-beaverbuildertheme"         => "show", // beaver builder 2
			"ct_builder"                    => "true", // oxygen builder
			"customize_changeset_uuid"      => null, // wordpress core customizer
			"et_fb"                         => "1", // divi builder
			"fb-edit"                       => "1", // fusion builder
			"fl_builder"                    => null, // beaver builder 1
			"PageSpeed"                     => "off", // mod_pagespeed
			"preview"                       => "true", // wordpress core preview
			"siteorigin_panels_live_editor" => null, // siteorigin page builder
			"tb_preview"                    => "1", // themify builder
			"tipi_builder"                  => "1", // tipi builder
			"tve"                           => "true", // thrive architect
			"vc_action"                     => "vc_inline", // wpbakery page builder
		];

		foreach ( $query_strings_to_exclude as $query_string => $value ) {
			if ( isset( $_GET[ $query_string ] ) && ( $value == $_GET[ $query_string ] || null == $value ) ) {
				return false;
			}
		}

		// Brizy Editor
		if (
			class_exists( 'Brizy_Editor' )
			&&
			( isset( $_GET[ \Brizy_Editor::prefix( '-edit' ) ] ) || isset( $_GET[ \Brizy_Editor::prefix( '-edit-iframe' ) ] ) )
		) {
			return false;
		}

		return true;
	}

	/**
	 * TODO: Instantiate classes in frontend.php and backend.php
	 */
	private function init_modules() {

		new Compatibility();
		new Backend\WPAdmin();
		new Backend\Notices();
		new Backend\Newsletter();

		new Frontend\DatabaseOptimizer();
		new Frontend\Cloudflare();
		new Frontend\Sucuri();
		new Frontend\CacheWarmup();
		new Frontend\JSOptimizer();
		new Frontend\Tweaks();
		new Frontend\FontOptimizer();
		new Frontend\Preboost();
		new Frontend\CDN();
		new Frontend\LazyLoader();
		new Frontend\CSSMinifier();
		new Frontend\CriticalCSS();
		new Frontend\ImageDimensions();
		new Frontend\HTMLMinifier();
		new Frontend\WooCommerce();
		new Frontend\Cache();

	}

	/**
	 * Defines the locale for this plugin for internationalization.
	 *
	 * @since    5.0.0
	 * @access   private
	 */
	private function load_plugin_textdomain() {
		load_plugin_textdomain( 'speed-booster-pack' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	public function define_admin_hooks() {

		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() ) { return; }

		add_filter( 'rocket_plugins_to_deactivate', '__return_empty_array' );

		$plugin_admin = new Backend( $this->options, $this->loader );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_admin_assets' );
		$this->loader->add_action( 'csf_loaded', $plugin_admin, 'create_settings_page' );
		$this->loader->add_action( 'csf_loaded', $plugin_admin, 'create_metaboxes' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	public function define_public_hooks() {

		if ( ! $this->should_plugin_run() ) { return; }

		$plugin_public = new Frontend( $this->options, $this->loader );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'template_redirect', 2 );

		add_filter( 'aioseo_flush_output_buffer', '__return_false' );

	}

	public function save_post_types() {

		$post_types = array_keys( get_post_types( [ 'public' => true ] ) );
		$saved_post_types = get_option( 'sbp_public_post_types' );

		if ( $saved_post_types && $saved_post_types == $post_types ) { return; }

		update_option( 'sbp_public_post_types', $post_types );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    4.0.0
	 */
	public function run() {
		$this->loader->run();
	}

}
