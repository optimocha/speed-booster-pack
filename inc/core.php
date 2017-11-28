<?php

/*--------------------------------------------------------------------------------------------------------
    Plugin Core Functions
---------------------------------------------------------------------------------------------------------*/

if( !class_exists( 'Speed_Booster_Pack_Core' ) ) {

	class Speed_Booster_Pack_Core {

		public function __construct() {

			global $sbp_options;
            add_action( 'wp_enqueue_scripts',  array( $this, 'sbp_no_more_fontawesome'), 9999 );
			add_action( 'wp_enqueue_scripts', array( $this, 'sbp_move_scripts_to_footer' ) );
			if ( !is_admin() and isset( $sbp_options['jquery_to_footer'] ) ) {
				add_action( 'wp_head', array( $this, 'sbp_scripts_to_head' ) );
				add_action( 'wp_print_scripts', array( $this, 'sbp_exclude_scripts' ), 100  );
				add_action( 'wp_enqueue_scripts', array( $this, 'sbp_exclude_scripts' ), 100  );
			}
			add_action( 'wp_footer', array( $this, 'sbp_show_page_load_stats' ), 999 );
			add_action( 'after_setup_theme', array( $this, 'sbp_junk_header_tags' ) );
	    	add_action( 'init', array( $this, 'sbp_init') );

			$this->sbp_css_optimizer(); // CSS Optimizer functions

			if ( isset( $sbp_options['sbp_css_async'] ) ) {
				add_action( 'wp_head', array( $this, 'sbp_except_admin_bar_css' ) );
			}

			//	Use Google Libraries
			if ( !is_admin() and isset( $sbp_options['use_google_libs'] ) ) {
				$this->sbp_use_google_libraries();
			}

			// Lazy Load
			if ( !is_admin() and isset( $sbp_options['lazy_load'] ) ) {
				$this->sbp_lazy_load_for_images();
			}

			// Minifier
			if ( !is_admin() and isset( $sbp_options['minify_html_js'] ) ) {
				$this->sbp_minifier();
			}


			//	Defer parsing of JavaScript
			if ( !is_admin() and isset( $sbp_options['defer_parsing'] ) ) {
				add_filter( 'clean_url', array( $this, 'sbp_defer_parsing_of_js' ), 11, 1 );
			}

			//	Remove query strings from static resources
			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
			 add_filter( 'script_loader_src', array( $this, 'sbp_remove_query_strings_1' ), 15, 1 );
			}

			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
				add_filter( 'style_loader_src', array( $this, 'sbp_remove_query_strings_1' ), 15, 1 );
			}

			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
			 add_filter( 'script_loader_src', array( $this, 'sbp_remove_query_strings_2' ), 15, 1 );
			}

			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
				add_filter( 'style_loader_src', array( $this, 'sbp_remove_query_strings_2' ), 15, 1 );
			}

			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
				add_filter( 'script_loader_src', array( $this, 'sbp_remove_query_strings_3' ), 15, 1 );
			}

			if ( !is_admin() and isset( $sbp_options['query_strings'] ) ) {
				add_filter( 'style_loader_src', array( $this, 'sbp_remove_query_strings_3' ), 15, 1 );
			}

			// JPEG  Compression filter
			add_filter( 'jpeg_quality', array( $this, 'filter_image_quality' ) );
			add_filter( 'wp_editor_set_quality', array( $this, 'filter_image_quality' ) );


		}  //  END public public function __construct


/*--------------------------------------------------------------------------------------------------------
    Init the CSS Optimizer actions
---------------------------------------------------------------------------------------------------------*/

function sbp_init() {

	global $sbp_options;

	if ( wp_is_mobile() and isset ( $sbp_options['sbp_is_mobile'] ) ) {	// disable all CSS options on mobile devices
		return;
	}

	if ( !is_admin() and isset( $sbp_options['sbp_css_async'] ) ) {
		add_action( 'wp_print_styles', array( $this, 'sbp_print_styles' ), SBP_FOOTER );
		add_action( 'wp_footer', array( $this, 'sbp_print_delayed_styles' ), SBP_FOOTER+1 );
	}

}


/*--------------------------------------------------------------------------------------------------------
    Add except for the admin toolbar css since the Async CSS removes the dashicons from the toolbar.
---------------------------------------------------------------------------------------------------------*/

function sbp_except_admin_bar_css() {

	if ( is_admin_bar_showing() ) { // enqueue the admin tolbar styles only if active
		wp_enqueue_style( 'open-sans' );
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'admin-bar' );
	}

}


/*--------------------------------------------------------------------------------------------------------
    Get image quality value if it's set. Otherwise it's set to 90
---------------------------------------------------------------------------------------------------------*/

function filter_image_quality() {

	if ( get_option( 'sbp_integer' ) ) {
		$sbp_compression = get_option( 'sbp_integer' );
	} else {
		$sbp_compression = 90;
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

    if ( is_admin() || !empty( $sbp_styles_are_async ) ) {
        return;
    }

	if ( isset( $sbp_options['sbp_css_minify'] ) ) {
		$minify = true;
	}else{
		$minify = false;
	}

    $sbp_styles_are_async = true;

    $sbp_styles = sbp_generate_styles_list();

if ( !isset( $sbp_options['sbp_footer_css'] ) ) {

	$not_inlined = array();

        foreach ( $sbp_styles as $style ) {
            echo "<style type=\"text/css\" ".($style['media'] ? "media=\"{$style['media']}\"" : '' ).">";
            if (!sbp_inline_css($style['src'],$minify)){
                $not_inlined[] = $style;
            }
            echo "</style>";
        }
        if ( !empty( $not_inlined) ) {
            foreach ( $not_inlined as $style ){
                ?><link rel="stylesheet" href="<?php echo $style['src']?>" type="text/css" <?php echo $style['media'] ? "media=\"{$style['media']}\"" : ''?> /><?php
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
	}else{
		$minify = false;
	}

if ( isset( $sbp_options['sbp_footer_css'] ) ) {

            $not_inlined = array();
            foreach ( $sbp_styles as $style ) {
                echo "<style type=\"text/css\" ".($style['media'] ? "media=\"{$style['media']}\"" : '' ).">";
                if ( !sbp_inline_css($style['src'],$minify) ) {
                    $not_inlined[] = $style;
                }
                echo "</style>";
            }
            if ( !empty( $not_inlined ) ) {
                foreach ( $not_inlined as $style ) {
                    ?><link rel="stylesheet" href="<?php echo $style['src']?>" type="text/css" <?php echo $style['media'] ? "media=\"{$style['media']}\"" : ''?> /><?php
                }
            }
        }
}


/*--------------------------------------------------------------------------------------------------------
    Moves scripts to the footer to decrease page load times, while keeping stylesheets in the header
---------------------------------------------------------------------------------------------------------*/

function sbp_move_scripts_to_footer() {

	global $sbp_options;

	if ( !is_admin() and isset( $sbp_options['jquery_to_footer'] ) ) {

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );

	}

}	//  END function sbp_move_scripts_to_footer


/*--------------------------------------------------------------------------------------------------------
    Exclude scripts from "Move scripts to footer" option
---------------------------------------------------------------------------------------------------------*/

public function sbp_exclude_scripts() {


	 if ( get_option( 'sbp_js_footer_exceptions1' ) ) {
		$sbp_handle1 = esc_html( get_option( 'sbp_js_footer_exceptions1' ) );
	}

	if ( get_option( 'sbp_js_footer_exceptions2' ) ) {
		$sbp_handle2 = esc_html( get_option( 'sbp_js_footer_exceptions2' ) );
	}

	if ( get_option( 'sbp_js_footer_exceptions3' ) ) {
		$sbp_handle3 = esc_html( get_option( 'sbp_js_footer_exceptions3' ) );
	}

	if ( get_option( 'sbp_js_footer_exceptions4' ) ) {
		$sbp_handle4 = esc_html( get_option( 'sbp_js_footer_exceptions4' ) );
	}

	$sbp_enq 	= 'enqueued';
	$sbp_reg 	= 'registered';
	$sbp_done	= 'done';

/*--------------------------------------------------------------------------------------------------------*/

	if ( get_option( 'sbp_js_footer_exceptions1' ) and wp_script_is( $sbp_handle1 , $sbp_enq ) ) {
		wp_dequeue_script( $sbp_handle1  );
	}

	if ( get_option( 'sbp_js_footer_exceptions2' ) and wp_script_is( $sbp_handle2 , $sbp_enq ) ) {
		wp_dequeue_script( $sbp_handle2  );
	}

	if ( get_option( 'sbp_js_footer_exceptions3' ) and wp_script_is( $sbp_handle3 , $sbp_enq ) ) {
		wp_dequeue_script( $sbp_handle3  );
	}

	if ( get_option( 'sbp_js_footer_exceptions4' ) and wp_script_is( $sbp_handle4 , $sbp_enq ) ) {
		wp_dequeue_script( $sbp_handle4  );
	}

/*--------------------------------------------------------------------------------------------------------*/

	if ( get_option( 'sbp_js_footer_exceptions1' ) and  wp_script_is( $sbp_handle1 , $sbp_reg ) ) {
		wp_deregister_script( $sbp_handle1  );
	}

	if ( get_option( 'sbp_js_footer_exceptions2' ) and wp_script_is( $sbp_handle2 , $sbp_reg ) ) {
		wp_deregister_script( $sbp_handle2  );
	}

	if ( get_option( 'sbp_js_footer_exceptions3' ) and wp_script_is( $sbp_handle3 , $sbp_reg ) ) {
		wp_deregister_script( $sbp_handle3  );
	}

	if ( get_option( 'sbp_js_footer_exceptions4' ) and wp_script_is( $sbp_handle4 , $sbp_reg ) ) {
		wp_deregister_script( $sbp_handle4  );
	}

/*--------------------------------------------------------------------------------------------------------*/

	if ( get_option( 'sbp_js_footer_exceptions1' ) and wp_script_is( $sbp_handle1 , $sbp_done ) ) {
		wp_deregister_script( $sbp_handle1  );
	}

	if ( get_option( 'sbp_js_footer_exceptions2' ) and wp_script_is( $sbp_handle2 , $sbp_done ) ) {
		wp_deregister_script( $sbp_handle2  );
	}

	if ( get_option( 'sbp_js_footer_exceptions3' ) and wp_script_is( $sbp_handle3 , $sbp_done ) ) {
		wp_deregister_script( $sbp_handle3  );
	}

	if ( get_option( 'sbp_js_footer_exceptions4' ) and wp_script_is( $sbp_handle4 , $sbp_done ) ) {
		wp_deregister_script( $sbp_handle4  );
	}

}


/*--------------------------------------------------------------------------------------------------------
    Put scripts back to the head
---------------------------------------------------------------------------------------------------------*/

public function sbp_scripts_to_head() {

	if ( get_option( 'sbp_head_html_script1' ) ) {
		echo  get_option( 'sbp_head_html_script1' ) . "\n";

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

}


/*--------------------------------------------------------------------------------------------------------
    Show Number of Queries and Page Load Time
---------------------------------------------------------------------------------------------------------*/

function sbp_show_page_load_stats() {
	$timer_stop = timer_stop( 0, 2 );	//	to display milliseconds instead of seconds usethe following:	$timer_stop = 1000 * ( float ) timer_stop( 0, 4 );
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

}	//	End function sbp_use_google_libraries()


/*--------------------------------------------------------------------------------------------------------
    Lazy Load for images
---------------------------------------------------------------------------------------------------------*/

function sbp_lazy_load_for_images() {

	if ( !class_exists( 'CrazyLazy' ) ) {
		require_once(SPEED_BOOSTER_PACK_PATH . 'inc/crazy-lazy.php');
	}
}	//	End function sbp_lazy_load_for_images()



/*--------------------------------------------------------------------------------------------------------
    Minify HTML and Javascripts
---------------------------------------------------------------------------------------------------------*/

function sbp_minifier() {

	require_once( SPEED_BOOSTER_PACK_PATH . 'inc/sbp-minifier.php' );
} 	//	End function sbp_minifier()


/*--------------------------------------------------------------------------------------------------------
    CSS Optimizer
---------------------------------------------------------------------------------------------------------*/

function sbp_css_optimizer() {

	require_once( SPEED_BOOSTER_PACK_PATH . 'inc/css-optimizer.php' );

}	//	End function sbp_css_optimizer()


/*--------------------------------------------------------------------------------------------------------
    Defer parsing of JavaScript and exclusion files
---------------------------------------------------------------------------------------------------------*/

function sbp_defer_parsing_of_js ( $url ) {

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


	if ( FALSE === strpos( $url, '.js' ) ) {
		return $url;
	}


	if ( get_option( 'sbp_defer_exceptions1' ) and strpos( $url, $defer_exclude1 ) ) {
		return $url;
	}

	if ( get_option( 'sbp_defer_exceptions2' ) and strpos( $url, $defer_exclude2 ) ) {
		return $url;
	}

	if ( get_option( 'sbp_defer_exceptions3' ) and strpos( $url, $defer_exclude3 ) ) {
		return $url;
	}

	if ( get_option( 'sbp_defer_exceptions4' ) and strpos( $url, $defer_exclude4 ) ) {
		return $url;
	}

	return "$url' defer='defer";

}	//	END function sbp_defer_parsing_of_js


/*--------------------------------------------------------------------------------------------------------
    Remove query strings from static resources
---------------------------------------------------------------------------------------------------------*/

function sbp_remove_query_strings_1( $src ) {	//	remove "?ver" string
$rqsfsr = explode( '?ver', $src );
return $rqsfsr[0];
}

function sbp_remove_query_strings_2( $src ) {	//	remove "&ver" string
$rqsfsr = explode( '&ver', $src );
return $rqsfsr[0];
}

function sbp_remove_query_strings_3( $src ) {	//	remove "?rev" string from Revolution Slider plugin
$rqsfsr = explode( '?rev', $src );
return $rqsfsr[0];
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
		'font-awesome.min.css'
		);
	//	multiple patterns hook
	$regex = '/(' .implode('|', $patterns) .')/i';
	foreach( $wp_styles -> registered as $registered ) {
		if( !is_admin() and preg_match( $regex, $registered->src) and isset( $sbp_options['font_awesome'] ) ) {
			wp_dequeue_style( $registered->handle );
			// FA was dequeued, so here we need to enqueue it again from CDN
			wp_enqueue_style( 'font-awesome', '//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' );
		}	//	END if( preg_match...
	}	//	END foreach
}	//	End function dfa_no_more_fontawesome


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

	//	Remove WordPress Shortlinks from WP Head
	if ( isset( $sbp_options['remove_wsl'] ) ) {
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}

	//	Remove WP Generator/Version - for security reasons and cleaning the header
	if ( isset( $sbp_options['wp_generator'] ) ) {
	remove_action('wp_head', 'wp_generator');
	}

	//	Remove all feeds
	if ( isset( $sbp_options['remove_all_feeds'] ) ) {
	remove_action( 'wp_head', 'feed_links_extra', 3 );	// remove the feed links from the extra feeds such as category feeds
	remove_action( 'wp_head', 'feed_links', 2 );		// remove the feed links from the general feeds: Post and Comment Feed
	}

}	//	END public function sbp_junk_header_tags

	}   //  END class Speed_Booster_Pack_Core

}   //  END if(!class_exists('Speed_Booster_Pack_Core'))