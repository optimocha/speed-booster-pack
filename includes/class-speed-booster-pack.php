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

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
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
	 * The unique identifier of this plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

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

		$this->version = SBP_VERSION;
		$this->plugin_name = 'speed-booster-pack';

		$this->load_dependencies();
		$this->save_post_types();
		$this->set_locale();
		$this->define_admin_hooks();

		if ( $this->should_plugin_run() ) {
			$this->init_modules();
			$this->define_public_hooks();
		}
		
	}

	private function should_plugin_run() {

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
	 * Instantiate all classes.
	 * Every class has inner documentation.
	 */
	private function init_modules() {

		new SpeedBooster\SBP_WP_Admin();
		new SpeedBooster\SBP_Database_Optimizer();
		new SpeedBooster\SBP_Newsletter();
		new SpeedBooster\SBP_Migrator();
		new SpeedBooster\SBP_Compatibility_Checker();
		new SpeedBooster\SBP_Cloudflare();
		new SpeedBooster\SBP_Sucuri();
		new SpeedBooster\SBP_Notice_Manager();
		new SpeedBooster\SBP_Cache_Warmup();

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
		require_once SBP_PATH . 'vendor/autoload.php';

		/**
		 * Load helper files
		 */
		require_once SBP_INC_PATH . 'sbp-helpers.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SBP_INC_PATH . 'class-speed-booster-pack-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once SBP_INC_PATH . 'class-speed-booster-pack-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once SBP_PATH . 'admin/class-speed-booster-pack-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once SBP_PATH . 'public/class-speed-booster-pack-public.php';

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

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

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
		
		$plugin_admin = new Speed_Booster_Pack_Admin( $this->plugin_name, SBP_VERSION );

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

		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) { return; }

		new SpeedBooster\SBP_JS_Optimizer();
		new SpeedBooster\SBP_Tweaks();
		new SpeedBooster\SBP_Font_Optimizer();
		new SpeedBooster\SBP_Preboost();
		new SpeedBooster\SBP_CDN();
		new SpeedBooster\SBP_Lazy_Loader();
		new SpeedBooster\SBP_CSS_Minifier();
		new SpeedBooster\SBP_Critical_CSS();
		new SpeedBooster\SBP_Image_Dimensions();
		new SpeedBooster\SBP_HTML_Minifier();
		new SpeedBooster\SBP_Localize_Tracker();
		new SpeedBooster\SBP_Woocommerce();
		new SpeedBooster\SBP_Cache();
		new SpeedBooster\SBP_LiteSpeed_Cache();
		
		$plugin_public = new Speed_Booster_Pack_Public( $this->plugin_name, SBP_VERSION );

		$this->loader->add_action( 'init', $plugin_public, 'template_redirect', 1 );

		// $this->loader->add_action( 'shutdown', $plugin_public, 'shutdown', PHP_INT_MAX );

		// $this->loader->add_filter( 'wp_headers', $plugin_public, 'sbp_headers' );
		
		add_filter( 'aioseo_flush_output_buffer', '__return_false' );

	}

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

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Speed_Booster_Pack_Loader    Orchestrates the hooks of the plugin.
	 * @since     4.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

}
