<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      4.0.0
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/includes
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      Speed_Booster_Pack_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

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

		$this->load_dependencies();
		$this->save_post_types();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

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
			( isset( $_GET[ Brizy_Editor::prefix( '-edit' ) ] ) || isset( $_GET[ Brizy_Editor::prefix( '-edit-iframe' ) ] ) )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Speed_Booster_Pack_Loader. Orchestrates the hooks of the plugin.
	 * - Speed_Booster_Pack_i18n. Defines internationalization functionality.
	 * - Speed_Booster_Pack_Admin. Defines all hooks for the admin area.
	 * - Speed_Booster_Pack_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * Composer autoload file.
		 */
		require_once SPEED_BOOSTER_PACK['path'] . '/vendor/autoload.php';

		/**
		 * Load helper files
		 */
		require_once SPEED_BOOSTER_PACK['path'] . '/inc/sbp-helpers.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		// TODO: delete below, if the autotloader (probably) loads this file
		// require_once SPEED_BOOSTER_PACK['path'] . '/inc/class-speed-booster-pack-loader.php';
		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		// require_once SPEED_BOOSTER_PACK['path'] . '/inc/class-speed-booster-pack-i18n.php';
		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		// require_once SPEED_BOOSTER_PACK['path'] . '/inc/admin/class-speed-booster-pack-admin.php';
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		// require_once SPEED_BOOSTER_PACK['path'] . '/inc/public/class-speed-booster-pack-public.php';

		$this->loader = new Speed_Booster_Pack_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Speed_Booster_Pack_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Speed_Booster_Pack_i18n();

		$this->loader->add_action( 'plugins_loaded', [ 'Core', 'load_plugin_textdomain' ] );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		if ( ! is_admin() || wp_doing_cron() || wp_doing_ajax() ) { return; }

		add_filter( 'rocket_plugins_to_deactivate', '__return_empty_array' );
		
		$plugin_admin = new Speed_Booster_Pack_Admin();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'set_up_defaults' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'redirect' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		
		if ( ! $this->should_plugin_run() ) { return; }
		
		$plugin_public = new Speed_Booster_Pack_Public();

		$this->loader->add_action( 'template_redirect', $plugin_public, 'template_redirect', 2 );

		add_filter( 'aioseo_flush_output_buffer', '__return_false' );

	}

	// TODO: don't run this on every admin init!
	private function save_post_types() {
		add_action('admin_init', function() {
			$post_types = array_keys( get_post_types( [ 'public' => true ] ) );
			$saved_post_types = get_option( 'sbp_public_post_types' );

			if ( ! $saved_post_types || $saved_post_types != $post_types ) { 
				update_option( 'sbp_public_post_types', $post_types );
			}
		});
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
