<?php
/**
* Plugin Name: Speed Booster Pack
* Plugin URI: http://wordpress.org/plugins/speed-booster-pack/
* Description: Speed Booster Pack allows you to improve your page loading speed and get a higher score on the major speed testing services such as <a href="http://gtmetrix.com/">GTmetrix</a>, <a href="http://developers.google.com/speed/pagespeed/insights/">Google PageSpeed</a> or other speed testing tools.
* Version: 3.6.1
* Author: Macho Themes
* Author URI: https://www.machothemes.com/
* License: GPLv2
*/

/*  Copyright 2017 Macho Themes (email : support [at] machothemes [dot] com)

    THIS PROGRAM IS FREE SOFTWARE; YOU CAN REDISTRIBUTE IT AND/OR MODIFY
    IT UNDER THE TERMS OF THE GNU GENERAL PUBLIC LICENSE AS PUBLISHED BY
    THE FREE SOFTWARE FOUNDATION; EITHER VERSION 2 OF THE LICENSE, OR
    (AT YOUR OPTION) ANY LATER VERSION.

    THIS PROGRAM IS DISTRIBUTED IN THE HOPE THAT IT WILL BE USEFUL,
    BUT WITHOUT ANY WARRANTY; WITHOUT EVEN THE IMPLIED WARRANTY OF
    MERCHANTABILITY OR FITNESS FOR A PARTICULAR PURPOSE.  SEE THE
    GNU GENERAL PUBLIC LICENSE FOR MORE DETAILS.

    YOU SHOULD HAVE RECEIVED A COPY OF THE GNU GENERAL PUBLIC LICENSE
    ALONG WITH THIS PROGRAM; IF NOT, WRITE TO THE FREE SOFTWARE
    FOUNDATION, INC., 51 FRANKLIN ST, FIFTH FLOOR, BOSTON, MA  02110-1301  USA
*/

/*----------------------------------------------------------------------------------------------------------
	Global Variables
-----------------------------------------------------------------------------------------------------------*/

$sbp_options = get_option( 'sbp_settings' );	// retrieve the plugin settings from the options table

/*----------------------------------------------------------------------------------------------------------
	Define some useful plugin constants
-----------------------------------------------------------------------------------------------------------*/

define( 'SPEED_BOOSTER_PACK_RELEASE_DATE', date_i18n( 'F j, Y', strtotime('2017-08-21')) );	// Defining plugin release date
define( 'SPEED_BOOSTER_PACK_PATH', plugin_dir_path( __FILE__ ) );					// Defining plugin dir path
define( 'SPEED_BOOSTER_PACK_VERSION', 'v3.6.1');										// Defining plugin version
define( 'SPEED_BOOSTER_PACK_NAME', 'Speed Booster Pack Plugin');					// Defining plugin name
define( 'SBP_FOOTER', 10 );															// Defining css position
define( 'SBP_FOOTER_LAST', 99999 );													// Defining css last position


/*----------------------------------------------------------------------------------------------------------
	Main Plugin Class
-----------------------------------------------------------------------------------------------------------*/

	if ( !class_exists( 'Speed_Booster_Pack' ) ) {

		class Speed_Booster_Pack {

/*----------------------------------------------------------------------------------------------------------
	Function Construct
-----------------------------------------------------------------------------------------------------------*/

	public function __construct() {
		global $sbp_options;

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'sbp_admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'sbp_collapsible' ) );

		// load plugin textdomain
		add_action('plugins_loaded', array( $this, 'sbp_load_translation' ) );

        add_action('admin_notices', array( &$this, 'sbp_display_notices'));
        add_action('wp_ajax_sbp_dismiss_notices', array(&$this, 'sbp_dismiss_notices'));

		// Load plugin settings page
		require_once( SPEED_BOOSTER_PACK_PATH . 'inc/settings.php' );
		$Speed_Booster_Pack_Options = new Speed_Booster_Pack_Options();

		// Load main plugin functions
		require_once( SPEED_BOOSTER_PACK_PATH . 'inc/core.php' );
		$Speed_Booster_Pack_Core = new Speed_Booster_Pack_Core();

		// Enqueue admin style
		add_action( 'admin_enqueue_scripts',  array( $this, 'sbp_enqueue_styles' ) );

		if ( isset( $sbp_options['lazy_load'] ) ) {
			add_action('wp_head', array( $this, 'sbp_fade_in_style' ), 100);
		}

		// Render debugging information
		add_action( 'wp_footer', array( $this, 'sbp_debugg' ), SBP_FOOTER_LAST );

		// Filters
		$this->path = plugin_basename( __FILE__ );
		add_filter( "plugin_action_links_$this->path", array( $this, 'sbp_settings_link' ) );

		}	// END public function __construct


/*----------------------------------------------------------------------------------------------------------
	Load plugin textdomain
-----------------------------------------------------------------------------------------------------------*/

		function sbp_load_translation() {
			load_plugin_textdomain( 'sb-pack', false, SPEED_BOOSTER_PACK_PATH . '/lang/' );
		}


/*----------------------------------------------------------------------------------------------------------
	Display/dismiss admin notices if needed
-----------------------------------------------------------------------------------------------------------*/

		function sbp_display_notices() {
                    if(!get_option( 'sbp_news')) {
                        global $sbp_settings_page;
                        $screen = get_current_screen();
                        if ( $screen->id != $sbp_settings_page ) {
                            require_once( SPEED_BOOSTER_PACK_PATH . 'inc/template/notice.php' );
                        }
                    }
		}

		function sbp_dismiss_notices() {
			update_option( 'sbp_news', true);
			return json_encode(array("Status" => 0));
		}

/*----------------------------------------------------------------------------------------------------------
	Activate the plugin
-----------------------------------------------------------------------------------------------------------*/

	public static function sbp_activate() {

		$sbp_options = get_option( 'sbp_settings', '' );
		$timer_stop = timer_stop( 0, 2 );
		$get_num_queries = get_num_queries();

		$url = get_site_url();
        $response = wp_remote_get( $url, array() );

		$get_enqueued_scripts_handle = get_option( 'all_theme_scripts_handle' );
		$get_enqueued_scripts_src = get_option( 'all_theme_scripts_src' );
		$get_enqueued_styles_handle = get_option( 'all_theme_styles_handle' );

		if ( get_option('sbp_page_time') == '' ) {
			update_option( 'sbp_page_time', $timer_stop );
		}

		if ( get_option( 'sbp_page_queries') == '' ) {
			update_option( 'sbp_page_queries', $get_num_queries );
		}

		if ( get_option( 'all_theme_scripts_handle') == '' ) {
			update_option( 'all_theme_scripts_handle', $get_enqueued_scripts_handle );
		}

		if ( get_option( 'all_theme_scripts_src') == '' ) {
			update_option( 'all_theme_scripts_src', $get_enqueued_scripts_src );
		}

		if ( get_option( 'all_theme_styles_handle') == '' ) {
			update_option( 'all_theme_styles_handle', $get_enqueued_styles_handle );
		}


		if ( get_option('sbp_css_async' ) === FALSE ) {
			update_option( 'sbp_css_async', 1 );
			update_option( 'sbp_css_minify', 1 );
			update_option( 'sbp_footer_css', 0 );
			update_option( 'sbp_is_mobile', 0 );
		}

	} // END public static function sb_activate


/*----------------------------------------------------------------------------------------------------------
	Deactivate the plugin
-----------------------------------------------------------------------------------------------------------*/

	public static function sbp_deactivate() {
			delete_option( 'sbp_integer' );
		}


/*-----------------------------------------------------------------------------------------------------------------------------------------
    Add a small css to activate a fade-in effect on lazy load images & wll be also used to output some frontend css in future development
--------------------------------------------------------------------------------------------------------------------------------------------*/

	public function sbp_fade_in_style() {
		echo "<style>img.crazy_lazy {opacity:0}</style>";
	}


/*----------------------------------------------------------------------------------------------------------
	CSS style of the plugin options page
-----------------------------------------------------------------------------------------------------------*/

	function sbp_enqueue_styles( $hook ) {

		// load stylesheet only on plugin options page
		global $sbp_settings_page;
		if ( $hook != $sbp_settings_page )
			return;
		wp_enqueue_style( 'sbp-styles', plugin_dir_url( __FILE__ ) . 'css/style.dev.css' );	//	change to style.dev.css to debug the plugin style  sbp_style.min
		wp_enqueue_style( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'css/jquery-ui.min.css' );

		}	//	End function sbp_enqueue_styles


/*----------------------------------------------------------------------------------------------------------
    Enqueue admin scripts to plugin options page
-----------------------------------------------------------------------------------------------------------*/

        public function sbp_admin_enqueue_scripts( $hook_sbp ) {
            // load scripts only on plugin options page
            global $sbp_settings_page;
            if ( $hook_sbp != $sbp_settings_page )
                return;
            wp_enqueue_script( 'jquery-ui-slider' );
            wp_enqueue_script( 'sbp-slide', plugins_url('inc/js/sbp-slide.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider' ), SPEED_BOOSTER_PACK_VERSION, true );
            wp_enqueue_script( 'sbp-hide', plugins_url('inc/js/sbp-hide.js', __FILE__ ), array( 'jquery' ), SPEED_BOOSTER_PACK_VERSION, true );

        }


/*----------------------------------------------------------------------------------------------------------
    Enqueue script to plugin options page for collapsible options
-----------------------------------------------------------------------------------------------------------*/

        function sbp_collapsible( $sbp_suffix ) {
            global $sbp_settings_page;
            if ( $sbp_suffix != $sbp_settings_page )
                return;
                wp_enqueue_script( 'postbox' );
                wp_enqueue_script( 'postbox-edit', plugins_url('inc/js/post-tabs-edit.js', __FILE__ ), array( 'jquery', 'postbox' ) );
        }


/*----------------------------------------------------------------------------------------------------------
	Add settings link on plugins page
-----------------------------------------------------------------------------------------------------------*/

	function sbp_settings_link( $links ) {

		$settings_link = '<a href="options-general.php?page=sbp-options">Settings</a>';
		array_unshift( $links, $settings_link );
		return $links;

		}	//	End function sbp_settings_link


/*----------------------------------------------------------------------------------------------------------
	Render the plugin name, its version and active options in page source, useful for debugging
-----------------------------------------------------------------------------------------------------------*/

		function sbp_debugg() {

			global $sbp_options;

			echo '<!-- We need this for debugging themes using ' . SPEED_BOOSTER_PACK_NAME . ' ' . SPEED_BOOSTER_PACK_VERSION . ' -->' . "\n";

			if ( isset( $sbp_options['jquery_to_footer'] ) ) {
				echo '<!-- Scripts to footer: enabled -->' . "\n";
			}

			if ( isset( $sbp_options['sbp_footer_css'] ) ) {
				echo '<!-- CSS to footer: enabled -->' . "\n";
			}

			if ( isset( $sbp_options['defer_parsing'] ) ) {
				echo '<!-- Defer parsing of js: enabled -->' . "\n";
			}

			if ( isset( $sbp_options['sbp_css_async'] ) ) {
				echo '<!-- CSS Async: enabled -->' . "\n";
			}


		}	//	End function sbp_debugg

	}	//	End class Speed_Booster_Pack

}	//	End if (!class_exists("Speed_Booster_Pack")) (1)

if( class_exists( 'Speed_Booster_Pack' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook( __FILE__, array( 'Speed_Booster_Pack', 'sbp_activate' ) );
	register_deactivation_hook( __FILE__, array( 'Speed_Booster_Pack', 'sbp_deactivate' ) );

	// instantiate the plugin class
	$speed_booster_pack = new Speed_Booster_Pack();

}	//	End if (!class_exists("Speed_Booster_Pack")) (2)
