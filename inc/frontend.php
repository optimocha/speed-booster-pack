<?php

//	TODO:
//		disable features per user role HERE!
//		run (hook methods below, hooks in parantheses)
//		sbp_public (template_redirect) // do_action ( 'sbp_public' ); ayrıca wp-admin, feed, ajax, rest vb. kontrolünü unutma!
//		maybe_disable_sbp_frontend (sbp_public) // core'daki should_plugin_run() metodu buraya
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

	public function check_user_roles() {

		$sbp_disabled_roles = sbp_get_option( 'roles_to_disable_sbp' );
		if ( ! $sbp_disabled_roles ) { return false; }

		$user               = wp_get_current_user();
		$roles              = $user->roles;
		if ( ! $roles ) { return false; }

		foreach ( $roles as $role ) {
			if ( in_array( $role, $sbp_disabled_roles ) ) {
				return true;
				break;
			}
		}

	}

	public function disable_sbp_frontend() {

		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() ) { return true; }

		if ( true === $this->check_user_roles() ) { return true; }

	}
	
	/**
	 * Starts output buffering for the HTML, and hooks to the `template_redirect` action.
	 *
	 * @return void
	 */
	public function template_redirect() {
		
		if( true === $this->disable_sbp_frontend() ) { return; }
		
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

		$html = str_replace( '</head>', '<!-- Optimized by Speed Booster Pack v' . SBP_VERSION . ' -->' . PHP_EOL . '</head>', $html );

		return $html;
	}

}
