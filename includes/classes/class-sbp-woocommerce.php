<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Woocommerce extends SBP_Abstract_Module {
	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_woocommerce' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {

		if ( ! $this->should_sbp_run || ! SBP_Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			return;
		}

		$this->woocommerce_disable_cart_fragments();
		$this->optimize_nonwc_pages();
		$this->remove_wc_password_strength_meter();
		$this->set_action_scheduler_period();
		$this->remove_marketing();

	}

	/**
	 * Removes WooCommerce scripts from non-woocommerce pages
	 */
	private function optimize_nonwc_pages() {
		add_action( 'wp_enqueue_scripts', [ $this, 'optimize_nonwc_pages_handle' ] );
	}

	public function optimize_nonwc_pages_handle() {
		if ( sbp_get_option( 'woocommerce_optimize_nonwc_pages' ) ) {
			if ( ! is_woocommerce() && ! is_cart() && ! is_checkout() ) {
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
		}
	}

	private function woocommerce_disable_cart_fragments() {
		if ( sbp_get_option( 'woocommerce_disable_cart_fragments' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'woocommerce_disable_cart_fragments_handle' ], 999 );
		}
	}

	/**
	 * Removes cart-fragments.js
	 */
	public function woocommerce_disable_cart_fragments_handle() {

		global $wp_scripts;
		$handle = 'wc-cart-fragments';
		if ( isset( $wp_scripts->registered[ $handle ] ) && $wp_scripts->registered[ $handle ]->src ) {
			$load_cart_fragments_path = $wp_scripts->registered[ $handle ]->src;
			$wp_scripts->registered[ $handle ]->src = null;
			wp_add_inline_script(
				'jquery',
				'function sbp_getCookie(c){var e=document.cookie.match("(^|;) ?"+c+"=([^;]*)(;|$)");return e?e[2]:null}function sbp_check_wc_cart_script(){var c="sbp_loaded_wc_cart_fragments";if(null!==document.getElementById(c))return!1;if(sbp_getCookie("woocommerce_cart_hash")){var e=document.createElement("script");e.id=c,e.src="' . $load_cart_fragments_path . '",e.async=!0,document.head.appendChild(e)}}sbp_check_wc_cart_script(),document.addEventListener("click",function(){setTimeout(sbp_check_wc_cart_script,1e3)});'
			);
		}

	}

	/**
	 * Removes password strength meter in WooCommerce checkout process
	 */
	private function remove_wc_password_strength_meter() {
		if ( function_exists( 'is_account_page' ) && sbp_get_option( 'woocommerce_disable_password_meter' ) ) {
			add_action( 'wp_print_scripts', [ $this, 'remove_wc_password_strength_meter_handle' ], 100 );
		}
	}

	public function remove_wc_password_strength_meter_handle() {
		global $wp;

		$wp_check = isset( $wp->query_vars['lost-password'] ) || ( isset( $_GET['action'] ) && $_GET['action'] === 'lostpassword' ) || is_page( 'lost_password' );

		$wc_check = ( ( is_account_page() || is_checkout() ) );

		if ( ! $wp_check && ! $wc_check ) {
			if ( wp_script_is( 'zxcvbn-async', 'enqueued' ) ) {
				wp_dequeue_script( 'zxcvbn-async' );
			}

			if ( wp_script_is( 'password-strength-meter', 'enqueued' ) ) {
				wp_dequeue_script( 'password-strength-meter' );
			}

			if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
				wp_dequeue_script( 'wc-password-strength-meter' );
			}
		}
	}

	public function remove_marketing() {
		if ( ! sbp_get_option( 'woocommerce_marketing' ) ) {
			add_filter( 'woocommerce_marketing_menu_items', '__return_empty_array' );

			add_filter( 'woocommerce_admin_features', function ( $features ) {
				return array_values(
					array_filter( $features, function ( $feature ) {
						return $feature !== 'marketing';
					} )
				);
			} );
		}
	}

	private function set_action_scheduler_period() {
		add_filter( 'action_scheduler_retention_period', function () {
			return DAY_IN_SECONDS * sbp_get_option( 'woocommerce_action_scheduler_period', 7 );
		} );
	}

	public static function get_woocommerce_option( $option_name ) {

		if ( get_option( $option_name ) === 'yes' ) {
			return 1;
		}

		return 0;

	}
	public static function set_woocommerce_option_analytics( $saved_data ) {

		if ( ! sbp_get_option( 'module_woocommerce' ) ) {
			return;
		}

		$woocommerce_analytics = $saved_data[ 'woocommerce_analytics' ] == '1' ? 'yes' : 'no';
		update_option( 'woocommerce_analytics_enabled', $woocommerce_analytics );

	}

	public static function set_woocommerce_option_tracking( $saved_data ) {

		if ( ! sbp_get_option( 'module_woocommerce' ) ) {
			return;
		}

		$woocommerce_tracking = $saved_data[ 'woocommerce_tracking' ] == '1' ? 'yes' : 'no';
		update_option( 'woocommerce_allow_tracking', $woocommerce_tracking );

	}

}