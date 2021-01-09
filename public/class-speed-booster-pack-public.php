<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/public
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Basically a hook for functions which use output buffer
	 */
	public function template_redirect() {
		ob_start( [ $this, 'output_buffer' ] );
	}

	public function output_buffer( $html ) {
		$html  = apply_filters( 'sbp_output_buffer', $html );
		$html .= PHP_EOL . '<!-- Optimized by Speed Booster Pack v' . SBP_VERSION . ' -->';

		return $html;
	}

}
