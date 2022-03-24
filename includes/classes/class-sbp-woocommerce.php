<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Z_TODO: Remove class in version 4.5
class SBP_Woocommerce extends SBP_Abstract_Module {
	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_woocommerce' ) ) {
			return;
		}

		add_action( 'set_current_user', [ $this, 'run_class' ] );
	}

	public function run_class() {
		if ( $this->should_sbp_run ) {
			$this->woocommerce_disable_cart_fragments();
			$this->optimize_nonwc_pages();
			$this->remove_wc_password_strength_meter();
			$this->set_action_scheduler_period();
//			$this->remove_marketing();
		}
	}

	/**
	 * Removes WooCommerce scripts from non-woocommerce pages
	 */
	// Z_TODO: Move this method to WooCommerce class in version 4.5
	private function optimize_nonwc_pages() {
		if ( function_exists( 'is_woocommerce' ) && sbp_get_option( 'woocommerce_optimize_nonwc_pages' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'optimize_nonwc_pages_handle' ] );
		}
	}

	// Z_TODO: Move this method to WooCommerce class in version 4.5
	public function optimize_nonwc_pages_handle() {
		if ( SBP_Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) && sbp_get_option( 'woocommerce_optimize_nonwc_pages' ) ) {
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
				wp_dequeue_script( 'wc-cart-fragments' );
				wp_dequeue_script( 'wc-checkout' );
				wp_dequeue_script( 'wc-chosen' );
				wp_dequeue_script( 'wc-single-product' );
				wp_dequeue_script( 'wc-single-product' );
				wp_dequeue_script( 'wc_price_slider' );
				wp_dequeue_script( 'woocommerce' );
			}
		}
	}

	// Z_TODO: Move this method to WooCommerce class in version 4.5
	private function woocommerce_disable_cart_fragments() {
		if ( sbp_get_option( 'woocommerce_disable_cart_fragments' ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'woocommerce_disable_cart_fragments_handle' ], 999 );
		}
	}

	/**
	 * Removes cart-fragments.js
	 */
	// Z_TODO: Move this method to WooCommerce class in version 4.5
	public function woocommerce_disable_cart_fragments_handle() {
		if ( SBP_Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			global $wp_scripts;
			$handle = 'wc-cart-fragments';
			if ( isset( $wp_scripts->registered[ $handle ] ) && $wp_scripts->registered[ $handle ]->src ) {
				$load_cart_fragments_path               = $wp_scripts->registered[ $handle ]->src;
				$wp_scripts->registered[ $handle ]->src = null;
				wp_add_inline_script(
					'jquery',
					'function sbp_getCookie(c){var e=document.cookie.match("(^|;) ?"+c+"=([^;]*)(;|$)");return e?e[2]:null}function sbp_check_wc_cart_script(){var c="sbp_loaded_wc_cart_fragments";if(null!==document.getElementById(c))return!1;if(sbp_getCookie("woocommerce_cart_hash")){var e=document.createElement("script");e.id=c,e.src="' . $load_cart_fragments_path . '",e.async=!0,document.head.appendChild(e)}}sbp_check_wc_cart_script(),document.addEventListener("click",function(){setTimeout(sbp_check_wc_cart_script,1e3)});'
				);
			}
		}
	}

	/**
	 * Removes password strength meter in WooCommerce checkout process
	 */
	// Z_TODO: Move this method to WooCommerce class in version 4.5
	private function remove_wc_password_strength_meter() {
		if ( function_exists( 'is_account_page' ) && sbp_get_option( 'woocommerce_disable_password_meter' ) ) {
			add_action( 'wp_print_scripts', [ $this, 'remove_wc_password_strength_meter_handle' ], 100 );
		}
	}

	// Z_TODO: Move this method to WooCommerce class in version 4.5
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

	// Z_TODO: Somehow it's not working. Will check
//	public function remove_marketing() {
//		if ( sbp_get_option( 'wc_disable_marketing' ) ) {
//			add_filter( 'woocommerce_marketing_menu_items', function() {
//				return [];
//			} );
//		}
//	}

	private function set_action_scheduler_period() {
		add_filter( 'action_scheduler_retention_period', function() {
			return DAY_IN_SECONDS * sbp_get_option( 'wc_action_scheduler_period', 7 );
		} );
	}

	public static function options_saved_listener( $saved_data ) {
		if ( isset( $saved_data['wc_disable_admin'] ) ) {
			$woocommerce_analytics_enabled = get_option( 'woocommerce_analytics_enabled' ) == 'yes' ? '1' : '0';

			if ( $woocommerce_analytics_enabled != $saved_data['wc_disable_admin'] ) {
				update_option( 'woocommerce_analytics_enabled', $saved_data['wc_disable_admin'] == '1' ? 'yes' : 'no' );
			}
		}

		if ( isset( $saved_data['wc_disable_tracking'] ) ) {
			$woocommerce_allow_tracking = get_option( 'woocommerce_allow_tracking' ) == 'yes' ? '0' : '1';

			if ( $woocommerce_allow_tracking != $saved_data['wc_disable_tracking'] ) {
				update_option( 'woocommerce_allow_tracking', $saved_data['wc_disable_tracking'] == '1' ? 'no' : 'yes' );
			}
		}
	}
}