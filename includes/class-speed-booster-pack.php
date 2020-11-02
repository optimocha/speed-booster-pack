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

use SpeedBooster\SBP_Cache;
use SpeedBooster\SBP_Cache_Warmup;
use SpeedBooster\SBP_CDN;
use SpeedBooster\SBP_Compatibility_Checker;
use SpeedBooster\SBP_Critical_CSS;
use SpeedBooster\SBP_CSS_Minifier;
use SpeedBooster\SBP_Custom_Code_Manager;
use SpeedBooster\SBP_Font_Optimizer;
use SpeedBooster\SBP_HTML_Minifier;
use SpeedBooster\SBP_JS_Optimizer;
use SpeedBooster\SBP_Lazy_Loader;
use SpeedBooster\SBP_Localize_Tracker;
use SpeedBooster\SBP_Migrator;
use SpeedBooster\SBP_Notice_Manager;
use SpeedBooster\SBP_Preboost;
use SpeedBooster\SBP_Special;
use SpeedBooster\SBP_Cloudflare;
use SpeedBooster\SBP_Sucuri;
use SpeedBooster\SBP_Tweaks;

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
		if ( defined( 'SBP_VERSION' ) ) {
			$this->version = SBP_VERSION;
		} else {
			$this->version = '4.0.0';
		}
		$this->plugin_name = 'speed-booster-pack';

		$this->load_dependencies();
		$this->set_locale();
		$this->init_modules();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function should_plugin_run() {
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
		if ( ! $this->should_plugin_run() ) {
			return false;
		}
		new SBP_Migrator();
		new SBP_JS_Optimizer();
		new SBP_Tweaks();
		new SBP_Font_Optimizer();
		new SBP_Compatibility_Checker();
		new SBP_Preboost();
		new SBP_CDN();
		new SBP_Lazy_Loader();
		new SBP_CSS_Minifier();
		new SBP_Critical_CSS();
		new SBP_HTML_Minifier();
		new SBP_Localize_Tracker();
		new SBP_Special();
		new SBP_Custom_Code_Manager();
		new SBP_Cloudflare();
		new SBP_Notice_Manager();
		new SBP_Sucuri();
		new SBP_Cache_Warmup();
		new SBP_Cache();
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
		$plugin_admin = new Speed_Booster_Pack_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Speed_Booster_Pack_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'template_redirect', 9999999 );
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
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     4.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
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

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     4.0.0
	 */
	public function get_version() {
		return $this->version;
	}

}
