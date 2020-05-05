<?php

class SBP_Compatibility_Checker {
	const PLUGINS = [
		'general'   => [
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
			'swift-performance-lite/performance.php',
			'swift-performance/performance.php',
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
			'wp-rocket/wp-rocket.php',
		],
		'lazy_load' => [
			'bj-lazy-load/bj-lazy-load.php',
			'lazy-load/lazy-load.php',
			'jquery-image-lazy-loading/jq_img_lazy_load.php',
			'advanced-lazy-load/advanced_lazyload.php',
			'crazy-lazy/crazy-lazy.php',
			'specify-image-dimensions/specify-image-dimensions.php',
			'lazy-load-for-videos/codeispoetry.php',
		],
		'minify'    => [
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
		],
		'heartbeat' => [
			'heartbeat-control/heartbeat-control.php',
		],
	];

	private $active_plugins = [];

	public function __construct() {
		add_action( 'admin_init', [ $this, 'check_plugins_active' ] );
		add_action( 'admin_notices', [ $this, 'compatibility_notices' ] );
	}

	public function compatibility_notices() {
		global $sbp_settings_page;
		$plugins = $this->active_plugins;
		if ( 0 === count( $plugins ) ) {
			return;
		}
		if ( $sbp_settings_page !== get_current_screen()->id ) {
			include_once( SPEED_BOOSTER_PACK_PATH . "inc/template/notices/compatibility.php" );
		}
	}

	public function check_plugins_active() {
		foreach ( self::PLUGINS as $category => $plugins ) {
			foreach ( $plugins as $plugin ) {
				if ( is_plugin_active( $plugin ) ) {
					$this->active_plugins[ $category ][] = $plugin;
				}
			}
		}
	}
}

new SBP_Compatibility_Checker();