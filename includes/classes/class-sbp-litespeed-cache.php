<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_LiteSpeed_Cache extends SBP_Base_Cache {
	const ROOT_MARKER = 'SBP_LS_CACHE';

	public function __construct() {
		parent::__construct();
		if ( SBP_Utils::is_litespeed() ) {
			add_action( 'init', [ $this, 'clear_lscache_request' ] );
			add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );
			add_filter( 'sbp_output_buffer', [ $this, 'set_headers' ] );
			add_action( 'csf_sbp_options_saved', [ $this, 'send_clear_cache_header' ] );
			$this->clear_cache_hooks();
		}
	}

	public function add_admin_bar_links( \WP_Admin_Bar $admin_bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			// Cache clear
			$clear_lscache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_lscache' ),
				'sbp_clear_total_lscache',
				'sbp_nonce' );
			$sbp_admin_menu    = [
				'id'     => 'sbp_clear_lscache',
				'parent' => 'speed_booster_pack',
				'title'  => __( 'Clear LiteSpeed Cache', 'speed-booster-pack' ),
				'href'   => $clear_lscache_url,
			];

			$admin_bar->add_node( $sbp_admin_menu );
		}
	}

	/**
	 * Handles the HTTP request to catch cache clear action
	 */
	public function clear_lscache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_lscache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_total_lscache' ) ) {
			$this->send_clear_cache_header();
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	public static function insert_htaccess_rules() {
		if ( ! SBP_Utils::is_litespeed() ) {
			return;
		}

		$lines = [];

		if ( sbp_get_option( 'module_caching_ls' ) ) {
			$lines[] = '<IfModule LiteSpeed>';
			$lines[] = 'RewriteEngine On';
			$lines[] = 'CacheLookup On' . PHP_EOL;

			// Add vary, so the logged in users won't see public cache or other users' caches
			$lines[] = '## BEGIN Cache vary for logged in users';
			$lines[] = 'RewriteRule .? - [E="Cache-Vary:,wordpress_logged_in_' . COOKIEHASH . '"]';
			$lines[] = '## END Cache vary for logged in users' . PHP_EOL;

			if ( sbp_get_option( 'caching_ls_separate_mobile' ) ) {
				$lines[] = '## BEGIN Cache vary for mobile browsers';
				$lines[] = 'RewriteCond %{HTTP_USER_AGENT} "Mobile|Android|Silk/|Kindle|BlackBerry|Opera Mini|Opera Mobi"';
				$lines[] = 'RewriteRule .* - [E=Cache-Control:vary=ismobile]';
				$lines[] = '## END Cache vary for mobile browsers' . PHP_EOL;
			}

			// Z_TODO: Exclude cookie rules must be in htaccess

			if ( $query_strings = sbp_get_option( 'caching_ls_include_query_strings' ) ) {
				$keys = explode( PHP_EOL, $query_strings );
				if ( $keys ) {
					$lines[] = '## BEGIN Dropped Query Strings';
					foreach ( $keys as $key ) {
						$lines[] = 'CacheKeyModify -qs:' . $key;
					}
					$lines[] = '## END Dropped Query Strings';
				}
			}
			$lines[] = '</IfModule>';
		}

		SBP_Utils::insert_to_htaccess( self::ROOT_MARKER, implode( PHP_EOL, $lines ) );
	}

	public static function remove_htaccess_rules() {
		SBP_Utils::insert_to_htaccess( self::ROOT_MARKER, '' );
	}

	private function add_tags() {
		$tags = [];

		$template_functions = [
			'is_front_page',
			'is_home',
			'is_single',
			'is_page',
			'is_category',
			'is_tag',
			'is_archive',
			'is_shop',
			'is_product',
			'is_product_category',
		];

		foreach ( $template_functions as $function ) {
			if ( function_exists( $function ) && call_user_func( $function ) ) {
				$tags[] = $function;
			}
		}

		if ( $tags ) {
			header( 'X-LiteSpeed-Tag: ' . implode( ',', $tags ) );
		}
	}

	public function set_headers( $html ) {
		if ( ! sbp_get_option( 'module_caching_ls' ) ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
		} else {
			// Multiply by 3600 because we store this value in hours but this value should be converted to seconds here
			$cache_expire_time = sbp_get_option( 'caching_ls_expiry', 10 ) * HOUR_IN_SECONDS;

			// Check for all exclusions
			if ( true === $this->should_bypass_cache( [ 'is_logged_in', 'include_query_strings', 'check_cookies' ] ) ) {
				header( 'X-LiteSpeed-Cache-Control: no-cache' );
			} else {
				if ( ! sbp_get_option( 'caching_ls_cache_logged_in_users' ) && is_user_logged_in() ) {
					header( 'X-LiteSpeed-Cache-Control: no-cache' );
				} else {
					$this->add_tags();

					if (is_user_logged_in()) {
						header( 'X-LiteSpeed-Cache-Control: private,max-age=' . $cache_expire_time );
						header( 'X-LiteSpeed-Vary: cookie=wordpress_logged_in_' . COOKIEHASH );
					} else {
						header( 'X-LiteSpeed-Cache-Control: public,max-age=' . $cache_expire_time );
						$html .= '<!-- LiteSpeed cache controlled by ' . SBP_PLUGIN_NAME . ' -->';
					}
				}

			}
		}

		return $html;
	}

	private function clear_cache_hooks() {
		add_action( '_core_updated_successfully', [ $this, 'send_clear_cache_header' ] );
		add_action( 'switch_theme', [ $this, 'send_clear_cache_header' ] );
		add_action( 'save_post', [ $this, 'send_clear_cache_header' ] );
		add_action( 'autoptimize_action_cachepurged', [ $this, 'send_clear_cache_header' ] );
		add_action( 'upgrader_process_complete', [ $this, 'send_clear_cache_header' ] );
		add_action( 'woocommerce_thankyou', [ $this, 'send_clear_cache_header' ] );
		add_action( 'woocommerce_product_set_stock', [ $this, 'send_clear_cache_header' ] );
		add_action( 'woocommerce_product_set_stock_status', [ $this, 'send_clear_cache_header' ] );
		add_action( 'woocommerce_variation_set_stock', [ $this, 'send_clear_cache_header' ] );
		add_action( 'woocommerce_variation_set_stock_status', [ $this, 'send_clear_cache_header' ] );
		add_action( 'wp_update_nav_menu', [ $this, 'send_clear_cache_header' ] );  // When a custom menu is update.
		add_action( 'update_option_sidebars_widgets', [ $this, 'send_clear_cache_header' ] );  // When you change the order of widgets.
		add_action( 'update_option_category_base', [ $this, 'send_clear_cache_header' ] );  // When category permalink prefix is update.
		add_action( 'update_option_tag_base', [ $this, 'send_clear_cache_header' ] );  // When tag permalink prefix is update.
		add_action( 'permalink_structure_changed', [ $this, 'send_clear_cache_header' ] );  // When permalink structure is update.
		add_action( 'edited_terms', [ $this, 'send_clear_cache_header' ] );  // When a term is updated.
		add_action( 'customize_save', [ $this, 'send_clear_cache_header' ] );  // When customizer is saved.
//		add_action( 'comment_post', [ $this, 'clear_post_by_comment' ] );
		add_action(
			'wp_trash_post',
			function ( $post_id ) {
				if ( get_post_status( $post_id ) == 'publish' ) {
					$this->send_clear_cache_header();
				}
			}
		);

		if ( is_admin() ) {
			add_action( 'wpmu_new_blog', [ $this, 'send_clear_cache_header' ] );
			add_action( 'delete_blog', [ $this, 'send_clear_cache_header' ] );
			add_action( 'transition_comment_status', [ $this, 'send_clear_cache_header' ], 10, 3 );
//			add_action( 'edit_comment', [ $this, 'clear_post_by_comment' ] );
		}
	}

	public function send_clear_cache_header() {
		@header( 'X-LiteSpeed-Purge:*' );
		if ( sbp_get_option( 'caching_ls_warmup_after_clear' ) && sbp_get_option( 'module_caching_ls' ) ) {
			// Start Warmup
			$warmup = new SBP_Cache_Warmup();
			$warmup->start_process();
			unset( $warmup );
		}
	}

	// Z_TODO: We are currently not supporting this feature on LiteSpeed Cache
//	public function clear_post_by_comment( $comment_id ) {
//		$comment = get_comment( $comment_id );
//
//		if ( $comment->comment_approved ) {
//			self::clear_post_by_id( $comment->comment_post_ID );
//		}
//	}
}