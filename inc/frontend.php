<?php

//	TODO:
//		run (hook methods below, hooks in parantheses)
//		sbp_public (template_redirect) // do_action ( 'sbp_public' ); ayrıca wp-admin, feed, ajax, rest vb. kontrolünü unutma!
//		maybe_disable_sbp_frontend (sbp_public)
//		http_headers (send_headers)

/**
 * The public-facing functionality of the plugin.
 *
 * @since      4.0.0
 *
 * @package    Optimocha\SpeedBooster
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Optimocha\SpeedBooster
 * @author     Optimocha
 */
class Frontend {
	
	/**
	 * Starts output buffering for the HTML, and hooks to the `template_redirect` action.
	 *
	 * @return void
	 */
	public function template_redirect() {
		
		// TODO: probably delete the condition below, because it already exists in Core::should_plugin_run()
		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) { return; }
		ob_start( [ $this, 'output_buffer' ] );

	}
	
	/**
	 * Gets the HTML output of a page and applies a filter hook so Speed Booster Pack features can work with the HTML.
	 *
	 * @param  string $html
	 * @return void
	 */
	public function output_buffer( $html ) {

		if( is_embed() || $_SERVER[ 'REQUEST_METHOD' ] != 'GET' || ! preg_match( '/<\/html>/i', $html ) ) {
			return $html;
		}

		$html = apply_filters( 'sbp_output_buffer', $html );

		$html = str_replace( '</head>', '<!-- Optimized by Speed Booster Pack v' . SPEED_BOOSTER_PACK['version'] . ' -->' . PHP_EOL . '</head>', $html );

		return $html;
	}

}
