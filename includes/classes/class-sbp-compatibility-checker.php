<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Compatibility_Checker extends SBP_Abstract_Module {
	private $plugins_list = [
		'wp-http-compression/wp-http-compression.php',
		'wordpress-gzip-compression/ezgz.php',
		'gzip-ninja-speed-compression/gzip-ninja-speed.php',
		'wp-performance-score-booster/wp-performance-score-booster.php',
		'remove-query-strings-from-static-resources/remove-query-strings.php',
		'query-strings-remover/query-strings-remover.php',
		'wp-ffpc/wp-ffpc.php',
		'far-future-expiry-header/far-future-expiration.php',
		'combine-css/combine-css.php',
		'super-static-cache/super-static-cache.php',
		'wpcompressor/wpcompressor.php',
		'check-and-enable-gzip-compression/richards-toolbox.php',
		'leverage-browser-caching-ninjas/leverage-browser-caching-ninja.php',
		'force-gzip/force-gzip.php',
		'enable-gzip-compression/enable-gzip-compression.php',
		'leverage-browser-caching/leverage-browser-caching.php',
		'add-expires-headers/add-expires-headers.php',
		'swift-performance-lite/performance.php',
		'swift-performance/performance.php',
		'w3-total-cache/w3-total-cache.php',
		'wp-super-cache/wp-cache.php',
		'litespeed-cache/litespeed-cache.php',
		'quick-cache/quick-cache.php',
		'hyper-cache/plugin.php',
		'hyper-cache-extended/plugin.php',
		'wp-fast-cache/wp-fast-cache.php',
		'flexicache/wp-plugin.php',
		'wp-fastest-cache/wpFastestCache.php',
		'lite-cache/plugin.php',
		'gator-cache/gator-cache.php',
		'cache-enabler/cache-enabler.php',
		'wp-rocket/wp-rocket.php',
		'bj-lazy-load/bj-lazy-load.php',
		'lazy-load/lazy-load.php',
		'jquery-image-lazy-loading/jq_img_lazy_load.php',
		'advanced-lazy-load/advanced_lazyload.php',
		'crazy-lazy/crazy-lazy.php',
		'specify-image-dimensions/specify-image-dimensions.php',
		'lazy-load-for-videos/codeispoetry.php',
		'wp-rocket/wp-rocket.php',
		'wp-super-minify/wp-super-minify.php',
		'bwp-minify/bwp-minify.php',
		'wp-minify/wp-minify.php',
		'scripts-gzip/scripts_gzip.php',
		'minqueue/plugin.php',
		'dependency-minification/dependency-minification.php',
		'fast-velocity-minify/fvm.php',
		'async-js-and-css/asyncJSandCSS.php',
		'merge-minify-refresh/merge-minify-refresh.php',
		'wp-html-compression/wp-html-compression.php',
		'wp-compress-html/wp_compress_html.php',
		'wp-js/wp-js.php',
		'combine-js/combine-js.php',
		'footer-javascript/footer-javascript.php',
		'scripts-to-footerphp/scripts-to-footer.php',
	];

	private $active_plugins = [];

	public function __construct() {
		$extra_plugins_list = [];
		$extra_plugins_list = apply_filters( 'sbp_incompatible_plugins', $extra_plugins_list );
		$this->plugins_list = array_merge( $this->plugins_list, $extra_plugins_list );

		add_action( 'admin_enqueue_scripts', [ $this, 'add_dismiss_notice_script' ] );
		add_action( 'wp_ajax_sbp_dismiss_compat_notice', [ $this, 'dismiss_notice' ] );

		add_action( 'admin_init', [ $this, 'check_plugins_active' ] );
		add_action( 'admin_notices', [ $this, 'compatibility_notices' ] );
	}

	public function compatibility_notices() {
		if ( get_current_screen()->id !== 'plugins' && get_current_screen()->id != 'toplevel_page_sbp-settings' ) {
			return;
		}

		$plugins = $this->active_plugins;
		if ( 0 === count( $plugins ) ) {
			return;
		}

		foreach ( $this->active_plugins as $plugin ) {
			$slash_position = strpos( $plugin, '/' );
			$notice_id      = $slash_position ? substr( $plugin, 0, $slash_position ) : $plugin;

			$dismissed_notices = get_user_meta( get_current_user_id(), 'sbp_dismissed_compat_notices', true );
			$dismissed_notices = $dismissed_notices == '' ? [] : $dismissed_notices;
			if ( $dismissed_notices && is_array( $dismissed_notices ) && in_array( $notice_id, $dismissed_notices ) ) {
				continue;
			}

			$plugin_name = get_plugin_data( WP_CONTENT_DIR . '/plugins/' . $plugin )['Name'];
			echo '<div class="notice notice-warning is-dismissible sbp-compatibility-notice" data-notice-id="' . $notice_id . '">
					<p>
					' . sprintf( __( 'The "%1$s" plugin has similar features to %2$s\'s, which might cause overlaps or even conflicts. Make sure you\'re not using the similar features of each plugin at the same time, test thoroughly and deactivate %1$s if necessary.', 'speed-booster-pack' ), "<strong>$plugin_name</strong>", SBP_PLUGIN_NAME ) . '
					</p>
				</div>';
		}
	}

	public function check_plugins_active() {
		foreach ( $this->plugins_list as $plugin ) {
			if ( is_plugin_active( $plugin ) ) {
				$this->active_plugins[] = $plugin;
			}
		}

		$this->active_plugins = array_unique( $this->active_plugins );
	}

	public function add_dismiss_notice_script() {
		$script = '
		jQuery(function() {
			jQuery(document).on("click", ".sbp-compatibility-notice .notice-dismiss", function() {
				jQuery.ajax({
					url: ajaxurl,
		            type: "POST",
		            data: {
		              action: "sbp_dismiss_compat_notice",
		              notice_id: jQuery(this).parent().attr("data-notice-id")
		            }
				});
			});
		})
		';
		wp_add_inline_script( 'jquery', $script );
	}

	public function dismiss_notice() {
		if ( current_user_can( 'manage_options' ) && isset( $_POST['notice_id'] ) && isset( $_POST['action'] ) && $_POST['action'] == 'sbp_dismiss_compat_notice' ) {
			$id              = $_POST['notice_id'];
			$dismiss_options = get_user_meta( get_current_user_id(), 'sbp_dismissed_compat_notices', true );
			$dismiss_options = $dismiss_options == '' ? [] : $dismiss_options;
			$dismiss_options[] = $id;
			$dismiss_options   = array_unique( $dismiss_options );
			update_user_meta( get_current_user_id(), 'sbp_dismissed_compat_notices', $dismiss_options );
		}
	}
}