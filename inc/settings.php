<?php

if ( ! class_exists( 'Speed_Booster_Pack_Options' ) ) {

	class Speed_Booster_Pack_Options {

		private $sbp_options;

		/*--------------------------------------------------------------------------------------------------------
			Construct the plugin object
		---------------------------------------------------------------------------------------------------------*/

		public function __construct() {

			add_action( 'admin_init', array( $this, 'sbp_admin_init' ) );
			add_action( 'admin_menu', array( $this, 'sbp_add_menu' ) );
			add_action( 'wp_footer', array( $this, 'sbp_detected_scripts_handle' ), 999 );
			add_action( 'wp_footer', array( $this, 'sbp_detected_scripts_src' ), 999 );
			add_action( 'wp_footer', array( $this, 'sbp_detected_styles_handle' ), 999 );

		}   //  END public function __construct


		public function sbp_admin_init() {

			register_setting( 'speed_booster_settings_group', 'sbp_settings' );
			register_setting( 'speed_booster_settings_group', 'sbp_integer' );
			register_setting( 'speed_booster_settings_group', 'sbp_css_exceptions' );
			register_setting( 'speed_booster_settings_group', 'sbp_sanitize' );

			register_setting( 'speed_booster_settings_group', 'sbp_js_footer_exceptions1' );
			register_setting( 'speed_booster_settings_group', 'sbp_js_footer_exceptions2' );
			register_setting( 'speed_booster_settings_group', 'sbp_js_footer_exceptions3' );
			register_setting( 'speed_booster_settings_group', 'sbp_js_footer_exceptions4' );

			register_setting( 'speed_booster_settings_group', 'sbp_defer_exceptions1' );
			register_setting( 'speed_booster_settings_group', 'sbp_defer_exceptions2' );
			register_setting( 'speed_booster_settings_group', 'sbp_defer_exceptions3' );
			register_setting( 'speed_booster_settings_group', 'sbp_defer_exceptions4' );

		}  //  END public function admin_init


		/*--------------------------------------------------------------------------------------------------------
			Get enqueued scripts handles
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_detected_scripts_handle( $handles = array() ) {

			global $wp_scripts;


			// scripts
			foreach ( $wp_scripts->registered as $registered ) {
				$script_urls[ $registered->handle ] = $registered->src;
			}

			// if empty
			if ( empty( $handles ) ) {
				$handles = array_merge( $wp_scripts->done );
				array_values( $handles );
			}
			// output of values
			$get_enqueued_scripts_handle = '';
			foreach ( $handles as $handle ) {
				if ( ! empty( $script_urls[ $handle ] ) ) {
					$get_enqueued_scripts_handle .= $handle . '<br />';
				}

			}

			update_option( 'all_theme_scripts_handle', $get_enqueued_scripts_handle );

		}

		/*--------------------------------------------------------------------------------------------------------
			Get enqueued scripts src path
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_detected_scripts_src( $handles = array() ) {

			global $wp_scripts;

			// scripts
			foreach ( $wp_scripts->registered as $registered ) {
				$script_urls[ $registered->handle ] = $registered->src;
			}

			// if empty
			if ( empty( $handles ) ) {
				$handles = array_merge( $wp_scripts->done );
				array_values( $handles );
			}
			// output of values
			$get_enqueued_scripts_src = '';
			foreach ( $handles as $handle ) {
				if ( ! empty( $script_urls[ $handle ] ) ) {
					$get_enqueued_scripts_src .= $script_urls[ $handle ] . '<br />';
				}

			}

			update_option( 'all_theme_scripts_src', $get_enqueued_scripts_src );

		}


		/*--------------------------------------------------------------------------------------------------------
			Get enqueued style handles
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_detected_styles_handle( $handles = array() ) {

			global $wp_styles;


			// scripts
			foreach ( $wp_styles->registered as $registered ) {
				$style_urls[ $registered->handle ] = $registered->src;
			}

			// if empty
			if ( empty( $handles ) ) {
				$handles = array_merge( $wp_styles->queue );
				array_values( $handles );
			}
			// output of values
			$get_enqueued_styles_handle = '';
			foreach ( $handles as $handle ) {
				if ( ! empty( $style_urls[ $handle ] ) ) {
					$get_enqueued_styles_handle .= $handle . '<br />';
				}

			}

			update_option( 'all_theme_styles_handle', $get_enqueued_styles_handle );

		}


		/*--------------------------------------------------------------------------------------------------------
			Sanitize Options
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_sanitize( $input ) {

			$output = array();

			foreach ( $input as $key => $tigu ) {

				switch ( $key ) {
					case 'sbp_js_footer_exceptions1':
						$output[ $key ] = esc_html( $tigu );
						break;
					case 'sbp_js_footer_exceptions2':
						$output[ $key ] = esc_html( $tigu );
						break;
					case 'sbp_js_footer_exceptions3':
						$output[ $key ] = esc_html( $tigu );
						break;
					case 'sbp_js_footer_exceptions4':
						$output[ $key ] = esc_html( $tigu );
						break;
				}

			}

			return $output;
		}


		/*--------------------------------------------------------------------------------------------------------
			// Add a page to manage the plugin's settings
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_add_menu() {

			global $sbp_settings_page;
			$sbp_settings_page = add_menu_page( __( 'Speed Booster Options', 'sb-pack' ), __( 'Speed Booster', 'sb-pack' ), 'manage_options', 'sbp-options', array(
				$this,
				'sbp_plugin_settings_page',
			), plugin_dir_url( __FILE__ ) . 'images/icon-16x16.png' );

		}   //  END public function add_menu()


		public function sbp_plugin_settings_page() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}


			/*--------------------------------------------------------------------------------------------------------
				Global Variables used on options HTML page
			---------------------------------------------------------------------------------------------------------*/

			global $sbp_options;

			//  Global variables used in plugin options page
			$url                         = get_site_url();
			$response                    = wp_remote_get( $url, array() );
			$page_time                   = get_option( 'sbp_page_time' );
			$page_queries                = get_option( 'sbp_page_queries' );
			$get_enqueued_scripts_handle = get_option( 'all_theme_scripts_handle' );
			$get_enqueued_scripts_src    = get_option( 'all_theme_scripts_src' );
			$get_enqueued_styles_handle  = get_option( 'all_theme_styles_handle' );

			// fallback for image compression integer
			if ( get_option( 'sbp_integer' ) ) {
				$this->image_compression = get_option( 'sbp_integer' );
			} else {
				$this->image_compression = 75;
			}
			$this->plugin_url = plugin_dir_url( dirname( __FILE__ ) );

			// fallback for stylesheets exception handle
			if ( get_option( 'sbp_css_exceptions' ) ) {
				$css_exceptions = get_option( 'sbp_css_exceptions' );
			} else {
				$css_exceptions = '';
			}

			/*--------------------------------------------------------------------------------------------------------*/

			if ( get_option( 'sbp_js_footer_exceptions1' ) ) {
				$js_footer_exceptions1 = get_option( 'sbp_js_footer_exceptions1' );
			} else {
				$js_footer_exceptions1 = '';
			}

			if ( get_option( 'sbp_js_footer_exceptions2' ) ) {
				$js_footer_exceptions2 = get_option( 'sbp_js_footer_exceptions2' );
			} else {
				$js_footer_exceptions2 = '';
			}

			if ( get_option( 'sbp_js_footer_exceptions3' ) ) {
				$js_footer_exceptions3 = get_option( 'sbp_js_footer_exceptions3' );
			} else {
				$js_footer_exceptions3 = '';
			}

			if ( get_option( 'sbp_js_footer_exceptions4' ) ) {
				$js_footer_exceptions4 = get_option( 'sbp_js_footer_exceptions4' );
			} else {
				$js_footer_exceptions4 = '';
			}

			/*--------------------------------------------------------------------------------------------------------*/



			if ( get_option( 'sbp_defer_exceptions1' ) ) {
				$defer_exceptions1 = get_option( 'sbp_defer_exceptions1' );
			} else {
				$defer_exceptions1 = '';
			}

			if ( get_option( 'sbp_defer_exceptions2' ) ) {
				$defer_exceptions2 = get_option( 'sbp_defer_exceptions2' );
			} else {
				$defer_exceptions2 = '';
			}

			if ( get_option( 'sbp_defer_exceptions3' ) ) {
				$defer_exceptions3 = get_option( 'sbp_defer_exceptions3' );
			} else {
				$defer_exceptions3 = '';
			}

			if ( get_option( 'sbp_defer_exceptions4' ) ) {
				$defer_exceptions4 = get_option( 'sbp_defer_exceptions4' );
			} else {
				$defer_exceptions4 = '';
			}

			/*--------------------------------------------------------------------------------------------------------*/



			// Render the plugin options page HTML
			include( SPEED_BOOSTER_PACK_PATH . 'inc/template/options.php' );

		} // END public function sbp_plugin_settings_page()


	}   //  END class Speed_Booster_Pack_Options

}   //  END if(!class_exists('Speed_Booster_Pack_Options'))
