<?php

/*--------------------------------------------------------------------------------------------------------
    Plugin Core Functions
---------------------------------------------------------------------------------------------------------*/

if ( ! class_exists( 'Speed_Booster_Pack_Core' ) ) {

	class Speed_Booster_Pack_Core {

		public function __construct() {

			global $sbp_options;

			add_action( 'wp_enqueue_scripts', array( $this, 'sbp_no_more_fontawesome' ), 9999 );
			add_action( 'wp_enqueue_scripts', array( $this, 'sbp_move_scripts_to_footer' ) );
			if ( ! is_admin() and isset( $sbp_options['jquery_to_footer'] ) ) {
				add_action( 'wp_head', array( $this, 'sbp_scripts_to_head' ) );
			}
			add_action( 'init', array( $this, 'sbp_show_page_load_stats' ), 999 );
			add_action( 'after_setup_theme', array( $this, 'sbp_junk_header_tags' ) );
			add_action( 'init', array( $this, 'sbp_init' ) );


			$this->sbp_css_optimizer(); // CSS Optimizer functions


			//	Use Google Libraries
			if ( ! is_admin() and isset( $sbp_options['use_google_libs'] ) ) {
				$this->sbp_use_google_libraries();
			}



			// Minifier
			if ( ! is_admin() and isset( $sbp_options['minify_html_js'] ) ) {
				$this->sbp_minifier();
			}

			//	Defer parsing of JavaScript
			if ( ! is_admin() and isset( $sbp_options['defer_parsing'] ) ) {
				add_filter( 'script_loader_tag', array( $this, 'sbp_defer_parsing_of_js' ), 10, 3 );
			}

			//	Remove query strings from static resources
			if ( ! is_admin() and isset( $sbp_options['query_strings'] ) ) {
				add_filter( 'script_loader_src', array( $this, 'sbp_remove_query_strings' ), 15, 1 );
				add_filter( 'style_loader_src', array( $this, 'sbp_remove_query_strings' ), 15, 1 );
			}


			// JPEG  Compression filter
			add_filter( 'jpeg_quality', array( $this, 'filter_image_quality' ) );
			add_filter( 'wp_editor_set_quality', array( $this, 'filter_image_quality' ) );


			/**
			 * @since 3.7
			 */
			// Disable emojis
			if ( ! is_admin() && isset( $sbp_options['remove_emojis'] ) ) {
				add_action( 'init', array( $this, 'sbp_disable_emojis' ) );
			}

			/**
			 * @since 3.7
			 */
			// Disable XML-RPC
			if ( ! isset( $sbp_options['disable_xmlrpc'] ) ) {
				add_filter( 'xmlrpc_enabled', '__return_false' );
				add_filter( 'wp_headers', array( $this, 'sbp_remove_x_pingback' ) );
				add_filter( 'pings_open', '__return_false', 9999 );
			}

		}  //  END public public function __construct


		/*--------------------------------------------------------------------------------------------------------
			Init the CSS Optimizer actions
		---------------------------------------------------------------------------------------------------------*/

		function sbp_init() {

			global $sbp_options;

			if ( wp_is_mobile() and isset ( $sbp_options['sbp_is_mobile'] ) ) {    // disable all CSS options on mobile devices
				return;
			}

			if ( ! is_admin() and isset( $sbp_options['sbp_css_async'] ) ) {
				add_action( 'wp_print_styles', array( $this, 'sbp_print_styles' ), SBP_FOOTER );
				add_action( 'wp_footer', array( $this, 'sbp_print_delayed_styles' ), SBP_FOOTER + 1 );
			}

		}


		/*--------------------------------------------------------------------------------------------------------
			Get image quality value if it's set. Otherwise it's set to 90
		---------------------------------------------------------------------------------------------------------*/

		function filter_image_quality() {

			if ( get_option( 'sbp_integer' ) ) {
				$sbp_compression = get_option( 'sbp_integer' );
			} else {
				$sbp_compression = 75; //@since v3.7
			}

			return $sbp_compression;
		}


		/*--------------------------------------------------------------------------------------------------------
			ACTION wp_print_styles
		---------------------------------------------------------------------------------------------------------*/

		function sbp_print_styles() {
			global $sbp_styles_are_async;
			global $sbp_styles;
			global $sbp_options;

			if ( isset( $sbp_options['sbp_css_minify'] ) ) {
				$minify = true;
			} else {
				$minify = false;
			}

			$sbp_styles_are_async = true;

			$sbp_styles = sbp_generate_styles_list();

			if ( ! isset( $sbp_options['sbp_footer_css'] ) ) {

				$not_inlined = array();

				foreach ( $sbp_styles as $style ) {
					echo "<style type=\"text/css\" " . ( $style['media'] ? "media=\"{$style['media']}\"" : '' ) . ">";
					if ( ! sbp_inline_css( $style['src'], $minify ) ) {
						$not_inlined[] = $style;
					}
					echo "</style>";
				}
				if ( ! empty( $not_inlined ) ) {
					foreach ( $not_inlined as $style ) {
						?>
						<link rel="stylesheet" href="<?php echo $style['src'] ?>" type="text/css" <?php echo $style['media'] ? "media=\"{$style['media']}\"" : '' ?> /><?php
					}
				}
			}

			sbp_unregister_styles();
		}


		/*--------------------------------------------------------------------------------------------------------
			ACTION wp_footer
		---------------------------------------------------------------------------------------------------------*/

		function sbp_print_delayed_styles() {
			global $sbp_styles;
			global $sbp_options;

			if ( isset( $sbp_options['sbp_css_minify'] ) ) {
				$minify = true;
			} else {
				$minify = false;
			}

			if ( isset( $sbp_options['sbp_footer_css'] ) ) {

				$not_inlined = array();
				foreach ( $sbp_styles as $style ) {
					echo "<style type=\"text/css\" " . ( $style['media'] ? "media=\"{$style['media']}\"" : '' ) . ">";
					if ( ! sbp_inline_css( $style['src'], $minify ) ) {
						$not_inlined[] = $style;
					}
					echo "</style>";
				}
				if ( ! empty( $not_inlined ) ) {
					foreach ( $not_inlined as $style ) {
						?>
						<link rel="stylesheet" href="<?php echo $style['src'] ?>" type="text/css" <?php echo $style['media'] ? "media=\"{$style['media']}\"" : '' ?> /><?php
					}
				}
			}
		}


		/*--------------------------------------------------------------------------------------------------------
			Moves scripts to the footer to decrease page load times, while keeping stylesheets in the header
		---------------------------------------------------------------------------------------------------------*/

		function sbp_move_scripts_to_footer() {

			global $sbp_options;

			if ( ! is_admin() and isset( $sbp_options['jquery_to_footer'] ) ) {

				remove_action( 'wp_head', 'wp_print_scripts' );
				remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
				remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );

			}

		}    //  END function sbp_move_scripts_to_footer


		/*--------------------------------------------------------------------------------------------------------
			Put scripts back to the head
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_scripts_to_head() {


			if ( get_option( 'sbp_head_html_script1' ) ) {
				echo get_option( 'sbp_head_html_script1' ) . "\n";
			}

			if ( get_option( 'sbp_head_html_script2' ) ) {
				echo get_option( 'sbp_head_html_script2' ) . "\n";
			}

			if ( get_option( 'sbp_head_html_script3' ) ) {
				echo get_option( 'sbp_head_html_script3' ) . "\n";
			}

			if ( get_option( 'sbp_head_html_script4' ) ) {
				echo get_option( 'sbp_head_html_script4' ) . "\n";
			}

			/**
			 * Default: add jQuery to header always
			 *
			 * @since 3.7
			 */
			global $wp_scripts;
			$js_footer_exceptions1 = '';
			$js_footer_exceptions2 = '';
			$js_footer_exceptions3 = '';
			$js_footer_exceptions4 = '';

			if ( get_option( 'sbp_js_footer_exceptions1' ) ) {
				$js_footer_exceptions1 = get_option( 'sbp_js_footer_exceptions1' );
			}

			if ( get_option( 'sbp_js_footer_exceptions2' ) ) {
				$js_footer_exceptions2 = get_option( 'sbp_js_footer_exceptions2' );
			}

			if ( get_option( 'sbp_js_footer_exceptions3' ) ) {
				$js_footer_exceptions3 = get_option( 'sbp_js_footer_exceptions3' );
			}

			if ( get_option( 'sbp_js_footer_exceptions4' ) ) {
				$js_footer_exceptions4 = get_option( 'sbp_js_footer_exceptions4' );
			}

			$sbp_enq  = 'enqueued';
			$sbp_reg  = 'registered';
			$sbp_done = 'done';

			/**
			 * Echo jQuery in header all the time, if none of the other options contain in
			 *
			 * @since 3.7
			 *
			 * New solution, going forward so not to crash so many sites anymore
			 *
			 *        This should come BEFORE the fallback function, since jQuery should be ALWAYS
			 *        the first loaded script.
			 *
			 */
			if ( $js_footer_exceptions1 !== 'jquery-core' || $js_footer_exceptions2 !== 'jquery-core' || $js_footer_exceptions3 !== 'jquery-core' || $js_footer_exceptions4 !== 'jquery-core' ) {

				// if the script actually exists, dequeue it and re-add it for header inclusion
				$script_src = $wp_scripts->registered['jquery-core']->src;

				if ( strpos( $script_src, 'wp-includes' ) == true ) { // it's a local resource, append wordpress installation URL
					echo '<script type="text/javascript" src="' . get_site_url() . esc_attr( $script_src ) . '"></script>';
				} else {
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				}

				// deregister & dequeue the script
				wp_deregister_script( 'jquery-core' );
				wp_dequeue_script( 'jquery-core' );
			}


			/**
			 * Echo the scripts in the header
			 *
			 * @since 3.7
			 *
			 * Fallback for previous plugin users
			 *
			 */
			if ( array_key_exists( $js_footer_exceptions1, $wp_scripts->registered ) ) {
				$script_src = '';
				// if the script actually exists, dequeue it and re-add it for header inclusion
				$script_src = $wp_scripts->registered[ $js_footer_exceptions1 ]->src;

				if ( strpos( $script_src, 'wp-includes' ) == true ) { // it's a local resource, append wordpress installation URL
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				} else {
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				}
			}

			if ( array_key_exists( $js_footer_exceptions2, $wp_scripts->registered ) ) {
				$script_src = '';
				// if the script actually exists, dequeue it and re-add it for header inclusion
				$script_src = $wp_scripts->registered[ $js_footer_exceptions2 ]->src;

				if ( strpos( $script_src, 'wp-includes' ) == true ) {
					echo '<script type="text/javascript" src="' . get_site_url() . esc_attr( $script_src ) . '"></script>';
				} else {
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				}
			}

			if ( array_key_exists( $js_footer_exceptions3, $wp_scripts->registered ) ) {
				$script_src = '';
				// if the script actually exists, dequeue it and re-add it for header inclusion
				$script_src = $wp_scripts->registered[ $js_footer_exceptions3 ]->src;

				if ( strpos( $script_src, 'wp-includes' ) == true ) {
					echo '<script type="text/javascript" src="' . get_site_url() . esc_attr( $script_src ) . '"></script>';
				} else {
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				}

			}

			if ( array_key_exists( $js_footer_exceptions4, $wp_scripts->registered ) ) {
				$script_src = '';
				// if the script actually exists, dequeue it and re-add it for header inclusion
				$script_src = $wp_scripts->registered[ $js_footer_exceptions4 ]->src;

				if ( strpos( $script_src, 'wp-includes' ) == true ) { // it's a local resource, append wordpress installation URL
					echo '<script type="text/javascript" src="' . get_site_url() . esc_attr( $script_src ) . '"></script>';
				} else {
					echo '<script type="text/javascript" src="' . esc_attr( $script_src ) . '"></script>';
				}
			}


			/**
			 * De-register the scripts from other parts of the site since they're already echo-ed in the header
			 */
			/*--------------------------------------------------------------------------------------------------------*/
			if ( ! empty( $sbp_js_footer_exceptions1 ) and wp_script_is( $js_footer_exceptions1, $sbp_enq ) ) {
				wp_dequeue_script( $js_footer_exceptions1 );
			}
			if ( ! empty( $sbp_js_footer_exceptions2 ) and wp_script_is( $js_footer_exceptions2, $sbp_enq ) ) {
				wp_dequeue_script( $js_footer_exceptions2 );
			}
			if ( ! empty( $sbp_js_footer_exceptions3 ) and wp_script_is( $js_footer_exceptions3, $sbp_enq ) ) {
				wp_dequeue_script( $sbp_js_footer_exceptions3 );
			}
			if ( ! empty( $sbp_js_footer_exceptions4 ) and wp_script_is( $js_footer_exceptions4, $sbp_enq ) ) {
				wp_dequeue_script( $sbp_js_footer_exceptions4 );
			}
			/*--------------------------------------------------------------------------------------------------------*/
			if ( ! empty( $js_footer_exceptions1 ) and wp_script_is( $js_footer_exceptions1, $sbp_reg ) ) {
				wp_deregister_script( $js_footer_exceptions1 );
			}
			if ( ! empty( $js_footer_exceptions2 ) and wp_script_is( $js_footer_exceptions2, $sbp_reg ) ) {
				wp_deregister_script( $js_footer_exceptions2 );
			}
			if ( ! empty( $js_footer_exceptions3 ) and wp_script_is( $js_footer_exceptions3, $sbp_reg ) ) {
				wp_deregister_script( $js_footer_exceptions3 );
			}
			if ( ! empty( $js_footer_exceptions4 ) and wp_script_is( $js_footer_exceptions4, $sbp_reg ) ) {
				wp_deregister_script( $js_footer_exceptions4 );
			}
			/*--------------------------------------------------------------------------------------------------------*/
			if ( ! empty( $js_footer_exceptions1 ) and wp_script_is( $js_footer_exceptions1, $sbp_done ) ) {
				wp_deregister_script( $js_footer_exceptions1 );
			}
			if ( ! empty( $js_footer_exceptions2 ) and wp_script_is( $js_footer_exceptions2, $sbp_done ) ) {
				wp_deregister_script( $js_footer_exceptions2 );
			}
			if ( ! empty( $js_footer_exceptions3 ) and wp_script_is( $js_footer_exceptions3, $sbp_done ) ) {
				wp_deregister_script( $js_footer_exceptions3 );
			}
			if ( ! empty( $js_footer_exceptions4 ) and wp_script_is( $js_footer_exceptions4, $sbp_done ) ) {
				wp_deregister_script( $js_footer_exceptions4 );
			}

		}


		/*--------------------------------------------------------------------------------------------------------
			Show Number of Queries and Page Load Time
		---------------------------------------------------------------------------------------------------------*/

		function sbp_show_page_load_stats() {
			$timer_stop      = timer_stop( 0, 2 );    //	to display milliseconds instead of seconds usethe following:	$timer_stop = 1000 * ( float ) timer_stop( 0, 4 );
			$get_num_queries = get_num_queries();
			update_option( 'sbp_page_time', $timer_stop );
			update_option( 'sbp_page_queries', $get_num_queries );
		}


		/*--------------------------------------------------------------------------------------------------------
			Use Google Libraries
		---------------------------------------------------------------------------------------------------------*/

		function sbp_use_google_libraries() {

			require_once( SPEED_BOOSTER_PACK_PATH . 'inc/use-google-libraries.php' );

			if ( class_exists( 'SBP_GoogleLibraries' ) ) {
				SBP_GoogleLibraries::configure_plugin();

			}

		}    //	End function sbp_use_google_libraries()




		/*--------------------------------------------------------------------------------------------------------
			Minify HTML and Javascripts
		---------------------------------------------------------------------------------------------------------*/

		function sbp_minifier() {

			require_once( SPEED_BOOSTER_PACK_PATH . 'inc/sbp-minifier.php' );
		}    //	End function sbp_minifier()


		/*--------------------------------------------------------------------------------------------------------
			CSS Optimizer
		---------------------------------------------------------------------------------------------------------*/

		function sbp_css_optimizer() {

			require_once( SPEED_BOOSTER_PACK_PATH . 'inc/css-optimizer.php' );

		}    //	End function sbp_css_optimizer()

		/*--------------------------------------------------------------------------------------------------------
			Defer parsing of JavaScript and exclusion files
		---------------------------------------------------------------------------------------------------------*/

		function sbp_defer_parsing_of_js( $tag, $handle, $src ) {

			$defer_exclude1 = '';
			$defer_exclude2 = '';
			$defer_exclude3 = '';
			$defer_exclude4 = '';

			if ( get_option( 'sbp_defer_exceptions1' ) ) {
				$defer_exclude1 = get_option( 'sbp_defer_exceptions1' );
			}

			if ( get_option( 'sbp_defer_exceptions2' ) ) {
				$defer_exclude2 = get_option( 'sbp_defer_exceptions2' );
			}

			if ( get_option( 'sbp_defer_exceptions3' ) ) {
				$defer_exclude3 = get_option( 'sbp_defer_exceptions3' );
			}

			if ( get_option( 'sbp_defer_exceptions4' ) ) {
				$defer_exclude4 = get_option( 'sbp_defer_exceptions4' );
			}

			$array_with_values[] = $defer_exclude1;
			$array_with_values[] = $defer_exclude2;
			$array_with_values[] = $defer_exclude3;
			$array_with_values[] = $defer_exclude4;

			$array_with_values = apply_filters( 'sbp_exclude_defer_scripts', $array_with_values ); // possibility of extending this via filters
			$array_with_values = array_filter( $array_with_values ); // remove empty entries


			if ( ! in_array( $handle, $array_with_values ) ) {
				return '<script src="' . $src . '" defer="defer" type="text/javascript"></script>' . "\n";
			}

			return $tag;

		}    //	END function sbp_defer_parsing_of_js


		/*--------------------------------------------------------------------------------------------------------
			Remove query strings from static resources
		---------------------------------------------------------------------------------------------------------*/

		function sbp_remove_query_strings( $src ) {    //	remove "?ver" string

			$output = preg_split( "/(\?rev|&ver|\?ver)/", $src );

			return $output[0];

		}

		/*--------------------------------------------------------------------------------------------------------
			Disable Emoji
		---------------------------------------------------------------------------------------------------------*/
		function sbp_disable_emojis() {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

			add_filter( 'tiny_mce_plugins', array( $this, 'sbp_disable_emojis_tinymce' ) );
			add_filter( 'wp_resource_hints', array( $this, 'sbp_disable_emojis_dns_prefetch' ), 10, 2 );
		}

		function sbp_disable_emojis_tinymce( $plugins ) {
			if ( is_array( $plugins ) ) {
				return array_diff( $plugins, array( 'wpemoji' ) );
			} else {
				return array();
			}
		}

		function sbp_disable_emojis_dns_prefetch( $urls, $relation_type ) {
			if ( 'dns-prefetch' == $relation_type ) {
				$emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2.2.1/svg/' );
				$urls          = array_diff( $urls, array( $emoji_svg_url ) );
			}

			return $urls;
		}

		/*--------------------------------------------------------------------------------------------------------
			Disable XML-RPC
		---------------------------------------------------------------------------------------------------------*/

		function sbp_remove_x_pingback( $headers ) {
			unset( $headers['X-Pingback'] );

			return $headers;
		}

		/*--------------------------------------------------------------------------------------------------------
			Dequeue extra Font Awesome stylesheet
		---------------------------------------------------------------------------------------------------------*/

		function sbp_no_more_fontawesome() {
			global $wp_styles;
			global $sbp_options;

			// we'll use preg_match to find only the following patterns as exact matches, to prevent other plugin stylesheets that contain font-awesome expression to be also dequeued
			$patterns = array(
				'font-awesome.css',
				'font-awesome.min.css',
			);
			//	multiple patterns hook
			$regex = '/(' . implode( '|', $patterns ) . ')/i';
			foreach ( $wp_styles->registered as $registered ) {
				if ( ! is_admin() and preg_match( $regex, $registered->src ) and isset( $sbp_options['font_awesome'] ) ) {
					wp_dequeue_style( $registered->handle );
					// FA was dequeued, so here we need to enqueue it again from CDN
					wp_enqueue_style( 'font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
				}    //	END if( preg_match...
			}    //	END foreach
		}    //	End function dfa_no_more_fontawesome


		/*--------------------------------------------------------------------------------------------------------
			Remove junk header tags
		---------------------------------------------------------------------------------------------------------*/

		public function sbp_junk_header_tags() {

			global $sbp_options;

			//	Remove Adjacent Posts links PREV/NEXT
			if ( isset( $sbp_options['remove_adjacent'] ) ) {
				remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
			}

			//	Remove Windows Live Writer Manifest Link
			if ( isset( $sbp_options['wml_link'] ) ) {
				remove_action( 'wp_head', 'wlwmanifest_link' );
			}

			// Remove RSD (Really Simple Discovery) Link
			if ( isset( $sbp_options['rsd_link'] ) ) {
				remove_action( 'wp_head', 'rsd_link' );
			}

			//	Remove WordPress Shortlinks from WP Head
			if ( isset( $sbp_options['remove_wsl'] ) ) {
				remove_action( 'wp_head', 'wp_shortlink_wp_head' );
			}

			//	Remove WP Generator/Version - for security reasons and cleaning the header
			if ( isset( $sbp_options['wp_generator'] ) ) {
				remove_action( 'wp_head', 'wp_generator' );
			}

			//	Remove all feeds
			if ( isset( $sbp_options['remove_all_feeds'] ) ) {
				remove_action( 'wp_head', 'feed_links_extra', 3 );    // remove the feed links from the extra feeds such as category feeds
				remove_action( 'wp_head', 'feed_links', 2 );        // remove the feed links from the general feeds: Post and Comment Feed
			}

		}    //	END public function sbp_junk_header_tags
	}   //  END class Speed_Booster_Pack_Core
}   //  END if(!class_exists('Speed_Booster_Pack_Core'))