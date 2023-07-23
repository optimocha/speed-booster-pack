<?php

namespace Optimocha\SpeedBooster\Frontend;

defined( 'ABSPATH' ) || exit;

use Optimocha\SpeedBooster\Utils;

class WooCommerce {
	public function __construct() {

		add_action( 'set_current_user', [ $this, 'run_class' ] );

	}

	public function run_class() {

		if ( ! sbp_get_option( 'module_woocommerce' ) ) { return; }

		if ( ! Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) { return; }

		add_action( 'wp_enqueue_scripts', [ $this, 'optimize_nonwc_pages_handle' ] );

		add_action( 'wp_enqueue_scripts', [ $this, 'woocommerce_disable_cart_fragments_handle' ], 999 );

		add_action( 'wp_print_scripts', [ $this, 'remove_wc_password_strength_meter_handle' ], 100 );

		add_filter( 'action_scheduler_retention_period', [ $this, 'set_action_scheduler_period' ] );

		$this->remove_marketing();

	}

	/**
	 * Removes WooCommerce scripts from non-woocommerce pages
	 */
	public function optimize_nonwc_pages_handle() {

		if ( ! sbp_get_option( 'woocommerce_optimize_nonwc_pages' ) ) { return; }

		if ( is_woocommerce() || is_cart() || is_checkout() ) { return; }

		// dequeue WooCommerce styles
		wp_dequeue_style( 'woocommerce_chosen_styles' );
		wp_dequeue_style( 'woocommerce_fancybox_styles' );
		wp_dequeue_style( 'woocommerce_frontend_styles' );
		wp_dequeue_style( 'woocommerce_prettyPhoto_css' );

		// dequeue WooCommerce scripts
		wp_dequeue_script( 'wc-add-to-cart' );
		wp_dequeue_script( 'wc-add-to-cart-variation' );
		wp_dequeue_script( 'wc-cart' );
		wp_dequeue_script( 'wc-checkout' );
		wp_dequeue_script( 'wc-chosen' );
		wp_dequeue_script( 'wc-single-product' );
		wp_dequeue_script( 'wc_price_slider' );
		wp_dequeue_script( 'woocommerce' );

	}

	/**
	 * Removes cart-fragments.js
	 */
	public function woocommerce_disable_cart_fragments_handle() {

		if ( ! sbp_get_option( 'woocommerce_disable_cart_fragments' ) ) { return; }

		global $wp_scripts;
		$handle = 'wc-cart-fragments';

		if ( ! isset( $wp_scripts->registered[ $handle ] ) || ! $wp_scripts->registered[ $handle ]->src ) { return; }

		$load_cart_fragments_path = $wp_scripts->registered[ $handle ]->src;
		$wp_scripts->registered[ $handle ]->src = null;
		wp_add_inline_script(
			'jquery',
			'function sbp_getCookie(c){var e=document.cookie.match("(^|;) ?"+c+"=([^;]*)(;|$)");return e?e[2]:null}function sbp_check_wc_cart_script(){var c="sbp_loaded_wc_cart_fragments";if(null!==document.getElementById(c))return!1;if(sbp_getCookie("woocommerce_cart_hash")){var e=document.createElement("script");e.id=c,e.src="' . $load_cart_fragments_path . '",e.async=!0,document.head.appendChild(e)}}sbp_check_wc_cart_script(),document.addEventListener("click",function(){setTimeout(sbp_check_wc_cart_script,1e3)});'
		);

	}

	/**
	 * Removes password strength meter in WooCommerce checkout process
	 * TODO: test with a plugin that changes login/lostpassword urls
	 */
	public function remove_wc_password_strength_meter_handle() {

		if ( ! sbp_get_option( 'woocommerce_disable_password_meter' ) ) { return; }

		if ( ! function_exists( 'is_account_page' ) ) { return; }

		global $wp;

		$wp_check = isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword';

		$wc_check = is_account_page() || is_checkout();

		if ( $wp_check || $wc_check ) { return; }

		wp_dequeue_script( 'zxcvbn-async' );
		wp_dequeue_script( 'password-strength-meter' );
		wp_dequeue_script( 'wc-password-strength-meter' );

	}

	public function remove_marketing() {

		add_filter( 'woocommerce_marketing_menu_items', '__return_empty_array' );

		add_filter( 'woocommerce_admin_features', function ( $features ) {
			return array_values(
				array_filter( $features, function ( $feature ) {
					return $feature !== 'marketing';
				} )
			);
		} );

	}

	private function set_action_scheduler_period() {

		return DAY_IN_SECONDS * sbp_get_option( 'woocommerce_action_scheduler_period', 7 );

	}

	public static function set_woocommerce_optimizations( $saved_data ) {

		$woocommerce_analytics = ( $saved_data[ 'woocommerce_analytics' ] == '1' ) ? 'yes' : 'no';
		update_option( 'woocommerce_analytics_enabled', $woocommerce_analytics );

		$woocommerce_tracking = ( $saved_data[ 'woocommerce_tracking' ] == '1' ) ? 'yes' : 'no';
		update_option( 'woocommerce_allow_tracking', $woocommerce_tracking );

	}

}
