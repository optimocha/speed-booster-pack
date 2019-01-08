<?php
/**
 * Plugin Name: Speed Booster Pack
 * Plugin URI: http://wordpress.org/plugins/speed-booster-pack/
 * Description: Speed Booster Pack allows you to improve your page loading speed and get a higher score on the major speed testing services such as <a href="http://gtmetrix.com/">GTmetrix</a>, <a href="http://developers.google.com/speed/pagespeed/insights/">Google PageSpeed</a> or other speed testing tools.
 * Version: 3.7.7
 * Author: Macho Themes
 * Author URI: https://www.machothemes.com/
 * License: GPLv3
 * Text Domain : speed-booster-pack
 * Domain Path: /lang
 */

/*  Copyright 2018 Macho Themes (email : support [at] machothemes [dot] com)

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

/**
 * Default plugin values
 *
 * @since 3.7
 */
$sbp_defaults = array(
	'remove_emojis'          => 1, // remove emoji scripts
	'remove_wsl'             => 1, // remove WSL link in header
	'remove_adjacent'        => 1, // remove post adjacent links
	'wml_link'               => 1, // remove Windows Manifest Live link
	'rsd_link'               => 1, // remove really simple discovery
	'wp_generator'           => 1, // remove WP version
	'remove_all_feeds'       => 1, // remove all WP feeds
	'disable_xmlrpc'         => 1, // disable XML-RPC pingbacks
	'font_awesome'           => 1, // remove extra font awesome styles
	'query_strings'          => 1, // remove query strings
	'use_google_libs'        => 0, // serve JS assets (when possible) from Google CDN
	'heartbeat_frequency'    => 15,
	'autosave_interval'      => 1,
	'limit_post_revisions'   => 30,
    'sbp_tracking_position'   => 'header'
);

$sbp_options = get_option( 'sbp_settings', (array) $sbp_defaults );    // retrieve the plugin settings from the options table

/*----------------------------------------------------------------------------------------------------------
	Define some useful plugin constants
-----------------------------------------------------------------------------------------------------------*/

define( 'SPEED_BOOSTER_PACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPEED_BOOSTER_PACK_URL', plugin_dir_url( __FILE__ ) );// Defining plugin dir path
define( 'SPEED_BOOSTER_PACK_VERSION', '3.7.7' );                                       // Defining plugin version
define( 'SBP_FOOTER', 10 );                                                          // Defining css position
define( 'SBP_FOOTER_LAST', 99999 );                                                  // Defining css last position

/*----------------------------------------------------------------------------------------------------------
	Main Plugin Class
-----------------------------------------------------------------------------------------------------------*/

if ( ! class_exists( 'Speed_Booster_Pack' ) ) {

	class Speed_Booster_Pack {

		/*----------------------------------------------------------------------------------------------------------
			Function Construct
		-----------------------------------------------------------------------------------------------------------*/

		public function __construct() {
			global $sbp_options;

			// Enqueue admin scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'sbp_admin_enqueue_scripts' ) );

			/**
			 * Should remain disabled until we release wpspeedbooster.com
			 *
			 * @since: 3.7
			 *
			 */
			//add_action( 'wp_dashboard_setup', [ $this, 'sbp_load_dashboard_widget' ] );


			// load plugin textdomain
			add_action( 'plugins_loaded', array( $this, 'sbp_load_translation' ) );

			add_action( 'admin_notices', array( &$this, 'sbp_display_notices' ) );
			add_action( 'wp_ajax_sbp_dismiss_notices', array( &$this, 'sbp_dismiss_notices' ) );

			// Load plugin settings page
			require_once( SPEED_BOOSTER_PACK_PATH . 'inc/settings.php' );
			$Speed_Booster_Pack_Options = new Speed_Booster_Pack_Options();

			// Load main plugin functions
			require_once( SPEED_BOOSTER_PACK_PATH . 'inc/core.php' );
			$Speed_Booster_Pack_Core = new Speed_Booster_Pack_Core();

			// Enqueue admin style
			add_action( 'admin_enqueue_scripts', array( $this, 'sbp_enqueue_styles' ) );


			// Filters
			$this->path = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$this->path", array( $this, 'sbp_settings_link' ) );


		}    // END public function __construct


		function sbp_load_dashboard_widget() {

			require_once plugin_dir_path( __FILE__ ) . 'widgets/dashboard-widget.php';

		}

		/*----------------------------------------------------------------------------------------------------------
			Load plugin textdomain
		-----------------------------------------------------------------------------------------------------------*/

		function sbp_load_translation() {
			load_plugin_textdomain( 'speed-booster-pack', false, SPEED_BOOSTER_PACK_PATH . '/lang/' );
		}


		/*----------------------------------------------------------------------------------------------------------
			Display/dismiss admin notices if needed
		-----------------------------------------------------------------------------------------------------------*/

		function sbp_display_notices() {
			if ( ! get_option( 'sbp_news' ) ) {
				global $sbp_settings_page;
				$screen = get_current_screen();
				if ( $screen->id != $sbp_settings_page ) {
					require_once( SPEED_BOOSTER_PACK_PATH . 'inc/template/notice.php' );
				}
			}
		}

		function sbp_dismiss_notices() {
			update_option( 'sbp_news', true );

			return json_encode( array( "Status" => 0 ) );
		}

		/*----------------------------------------------------------------------------------------------------------
			Activate the plugin
		-----------------------------------------------------------------------------------------------------------*/

		public static function sbp_activate() {

			$sbp_options     = get_option( 'sbp_settings', '' );
			$timer_stop      = timer_stop( 0, 2 );
			$get_num_queries = get_num_queries();

			$url      = get_site_url();
			$response = wp_remote_get( $url, array() );

			$get_enqueued_scripts_handle = get_option( 'all_theme_scripts_handle' );
			$get_enqueued_scripts_src    = get_option( 'all_theme_scripts_src' );
			$get_enqueued_styles_handle  = get_option( 'all_theme_styles_handle' );

			if ( get_option( 'sbp_page_time' ) == '' ) {
				update_option( 'sbp_page_time', $timer_stop );
			}

			if ( get_option( 'sbp_page_queries' ) == '' ) {
				update_option( 'sbp_page_queries', $get_num_queries );
			}

			if ( get_option( 'all_theme_scripts_handle' ) == '' ) {
				update_option( 'all_theme_scripts_handle', $get_enqueued_scripts_handle );
			}

			if ( get_option( 'all_theme_scripts_src' ) == '' ) {
				update_option( 'all_theme_scripts_src', $get_enqueued_scripts_src );
			}

			if ( get_option( 'all_theme_styles_handle' ) == '' ) {
				update_option( 'all_theme_styles_handle', $get_enqueued_styles_handle );
			}

		} // END public static function sb_activate


		/*----------------------------------------------------------------------------------------------------------
			Deactivate the plugin
		-----------------------------------------------------------------------------------------------------------*/

		public static function sbp_deactivate() {
		}


		/*----------------------------------------------------------------------------------------------------------
			CSS style of the plugin options page
		-----------------------------------------------------------------------------------------------------------*/

		function sbp_enqueue_styles( $hook ) {

			// load stylesheet only on plugin options page
			global $sbp_settings_page;
			if ( $hook != $sbp_settings_page ) {
				return;
			}
			wp_enqueue_style( 'sbp-styles', plugin_dir_url( __FILE__ ) . 'css/style.css' );
			wp_enqueue_style( 'jquery-ui', plugin_dir_url( __FILE__ ) . 'css/vendors/jquery-ui/jquery-ui.min.css' );

		}    //	End function sbp_enqueue_styles


		/*----------------------------------------------------------------------------------------------------------
			Enqueue admin scripts to plugin options page
		-----------------------------------------------------------------------------------------------------------*/

		public function sbp_admin_enqueue_scripts( $hook_sbp ) {
			// load scripts only on plugin options page
			global $sbp_settings_page;
			if ( $hook_sbp != $sbp_settings_page ) {
				return;
			}
			wp_enqueue_script( 'jquery-ui-slider' );
			wp_enqueue_script( 'postbox' );

			wp_enqueue_script( 'sbp-admin-scripts', plugins_url( 'inc/js/admin-scripts.js', __FILE__ ), array(
				'jquery',
				'postbox',
				'jquery-ui-slider',
			), SPEED_BOOSTER_PACK_VERSION, true );

			wp_enqueue_script( 'sbp-plugin-install', plugins_url( 'inc/js/plugin-install.js', __FILE__ ), array(
				'jquery',
				'updates',
			), SPEED_BOOSTER_PACK_VERSION, true );

		}


		/*----------------------------------------------------------------------------------------------------------
			Add settings link on plugins page
		-----------------------------------------------------------------------------------------------------------*/

		function sbp_settings_link( $links ) {

			$settings_link = ' <a href="admin.php?page=sbp-options">Settings</a > ';
			array_unshift( $links, $settings_link );

			return $links;

		}    //	End function sbp_settings_link
	}//	End class Speed_Booster_Pack
}    //	End if (!class_exists("Speed_Booster_Pack")) (1)

if ( class_exists( 'Speed_Booster_Pack' ) ) {

	// Installation and uninstallation hooks
	register_activation_hook( __FILE__, array( 'Speed_Booster_Pack', 'sbp_activate' ) );
	register_deactivation_hook( __FILE__, array( 'Speed_Booster_Pack', 'sbp_deactivate' ) );

	// instantiate the plugin class
	$speed_booster_pack = new Speed_Booster_Pack();

}    //	End if (!class_exists("Speed_Booster_Pack")) (2)

// make sure to update the path to where you cloned the projects to!

//review function
function sb_pack_check_for_review() {
    if ( ! is_admin() ) {
        return;
    }
    require_once SPEED_BOOSTER_PACK_PATH . 'inc/class-sb-pack-review.php';

    SB_Pack_Review::get_instance( array(
        'slug' => 'speed-booster-pack',
    ) );
}

sb_pack_check_for_review();