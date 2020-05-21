<?php

namespace SpeedBooster;

class SBP_Cache extends SBP_Abstract_Module {

	private $file_name = 'index.html';

	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_caching' ) ) {
			return;
		}

		// Set admin bar links
		add_action( 'admin_bar_menu', [ $this, 'admin_bar_links' ], 71 );

		// Clear cache hook
		add_action( 'admin_init', [ $this, 'clear_cache_request' ] );

		if ( sbp_get_option( 'enable-cache' ) ) {
			$this->set_wp_cache_constant( true );
		}

		// Handle The Cache
		add_filter( 'sbp_output_buffer', [ $this, 'handle_cache' ], 1000 );
	}

	public static function instantiate() {
		new Self();
	}

	private function should_bypass_cache() {
		// Do not cache for logged in users
		if ( is_user_logged_in() ) {
			return true;
		}

		// Do not cache administrator
		if ( user_can( get_current_user_id(), 'administrator' ) ) {
			return true;
		}

		// Check for several special pages
		if ( is_search() || is_404() || is_feed() || is_trackback() || is_robots() || is_preview() || post_password_required() ) {
			return true;
		}

		// DONOTCACHEPAGE
		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE === true ) {
			return true;
		}

		// Woocommerce checkout check
		if ( function_exists( 'is_checkout' ) ) {
			if ( is_checkout() ) {
				return true;
			}
		}

		// Woocommerce cart check
		if ( function_exists( 'is_cart' ) ) {
			if ( is_cart() ) {
				return true;
			}
		}

		// Check request method. Only cache get methods
		if ( $_SERVER['REQUEST_METHOD'] != 'GET' ) {
			return true;
		}

		// Check for UTM parameters for affiliates // TODO: Check UTM parameters again
//		if ( isset( $_GET['utm_source'] ) || isset( $_GET['utm_medium'] ) || isset( $_GET['utm_campaign'] ) || isset( $_GET['utm_term'] ) || isset( $_GET['utm_content'] ) ) {
//			return true;
//		}

		if ( wp_is_mobile() && ! sbp_get_option( 'caching_separate_mobile' ) ) {
			return true;
		}

		return false;
	}

	public function clear_cache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_cache' && current_user_can( 'manage_options' ) ) {
			self::clear_total_cache();
			SBP_Cloudflare::clear_cache();
			wp_redirect( admin_url( 'admin.php?page=sbp-settings#tab=3' ) );
		}
	}

	private function get_filesystem() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		return $wp_filesystem;
	}

	public function admin_bar_links( $admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$sbp_admin_menu = [
			'id'    => 'speed_booster_pack',
			'title' => SBP_PLUGIN_NAME,
		];

		$admin_bar->add_menu( $sbp_admin_menu );

		$cache_items = [
			'id'     => 'sbp_clear_cache',
			'parent' => 'speed_booster_pack',
			'title'  => __( 'Clear Cache', 'speed-booster-pack' ),
			'href'   => admin_url( 'admin.php?page=sbp-settings&sbp_action=sbp_clear_cache' )
		];

		$admin_bar->add_menu( $cache_items );
	}

	public static function clear_total_cache() {
		self::delete_dir( SBP_CACHE_DIR );
	}

	public static function delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$dir_objects = @scandir( $dir );
		$objects     = array_filter( $dir_objects, function ( $object ) {
			return $object != '.' && $object != '..';
		} );

		if ( empty( $objects ) ) {
			return;
		}

		foreach ( $objects as $object ) {
			$object = $dir . DIRECTORY_SEPARATOR . $object;

			if ( is_dir( $object ) ) {
				self::delete_dir( $object );
			} else {
				@unlink( $object );
			}
		}

		@rmdir( $dir );

		clearstatcache();
	}

	public function start_buffer() {
		if ( $this->should_bypass_cache() ) {
			return;
		}

		ob_start( [ $this, 'handle_cache' ] );
	}

	public function handle_cache( $html ) {
		if ( $this->should_bypass_cache() ) {
			return $html;
		}

		// Check for query strings
		if ( ! empty( $_GET ) ) {
			// Get included rules
			$include_query_strings = SBP_Utils::explode_lines( sbp_get_option( 'caching_include_query_strings', '' ) );

			$query_string_file_name = '';
			// Order get parameters alphabetically (to get same filename for every order of query parameters)
			ksort( $_GET );
			foreach ( $_GET as $key => $value ) {
				if ( in_array( $key, $include_query_strings ) ) {
					$query_string_file_name .= "$key-$value-";
				}
			}
			if ( '' !== $query_string_file_name ) {
				$query_string_file_name .= '.html';
				$this->file_name        = $query_string_file_name;
			}
		}

		// Check for exclude URL's
		if ( sbp_get_option('caching_exclude_urls') ) {
			$exclude_urls = array_map( 'trim', explode( PHP_EOL, sbp_get_option('caching_exclude_urls') ) );
			if ( count( $exclude_urls ) > 0 && in_array( $_SERVER['REQUEST_URI'], $exclude_urls ) ) {
				return false;
			}
		}

		$wp_filesystem = $this->get_filesystem();

		// Read cache file
		$cache_file_path = $this->get_cache_file_path() . $this->file_name;

		$caching_expiry = sbp_get_option('caching_expiry') * DAY_IN_SECONDS;

		$has_file_expired = $wp_filesystem->mtime( $cache_file_path ) + $caching_expiry < time();

		if ( $wp_filesystem->exists( $cache_file_path ) && ! $has_file_expired ) {
			return $wp_filesystem->get_contents( $cache_file_path );
		}

		// Apply filters
		$html = apply_filters( 'sbp_cache_before_create', $html );
		$this->create_cache_file( $html );

		return $html;
	}

	private function create_cache_file( $html ) {
		$dir_path  = $this->get_cache_file_path();
		$file_path = $dir_path . $this->file_name;

		wp_mkdir_p( $dir_path );
		$file = @fopen( $file_path, 'w+' );
		fwrite( $file, $html );
		fclose( $file );
	}

	private function get_cache_file_path() {
		$cache_dir = SBP_CACHE_DIR;
		if ( wp_is_mobile() && sbp_get_option( 'caching_separate_mobile' ) ) {
			$cache_dir = SBP_CACHE_DIR . '/mobile';
		}

		$path = sprintf(
			'%s%s%s%s',
			$cache_dir,
			DIRECTORY_SEPARATOR,
			parse_url(
				'http://' . strtolower( $_SERVER['HTTP_HOST'] ),
				PHP_URL_HOST
			),
			parse_url(
				$_SERVER['REQUEST_URI'],
				PHP_URL_PATH
			)
		);

		if ( is_file( $path ) > 0 ) {
			wp_die( 'Error occured on SBP cache. Please contact you webmaster.' );
		}

		return rtrim( $path, "/" ) . "/";
	}

	/**
	 * Parts of this class was inspired from Cache Enabler's codebase.
	 *
	 * @param bool $wp_cache
	 */
	public static function set_wp_cache_constant( $wp_cache = true ) {
		$wp_config_file = ABSPATH . 'wp-config.php';

		if ( file_exists( $wp_config_file ) && is_writable( $wp_config_file ) ) {
			// get wp config as array
			$wp_config = file( $wp_config_file );

			if ( $wp_cache ) {
				$append_line = "define('WP_CACHE', true); // Added by Speed Booster Pack" . "\r\n";
			} else {
				$append_line = '';
			}

			$found_wp_cache = false;

			foreach ( $wp_config as &$line ) {
				if ( preg_match( '/^\s*define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,\s*(.*)\s*\)/', $line ) ) {
					$line           = $append_line;
					$found_wp_cache = true;
					break;
				}
			}

			// add wp cache ce line if not found yet
			if ( ! $found_wp_cache ) {
				array_shift( $wp_config );
				array_unshift( $wp_config, "<?php\r\n", $append_line );
			}

			// write wp-config.php file
			$fh = @fopen( $wp_config_file, 'w' );
			foreach ( $wp_config as $ln ) {
				@fwrite( $fh, $ln );
			}

			@fclose( $fh );
		}
	}

	public static function options_saved_listener($saved_data) {
		global $wp_filesystem;

		// Delete or recreate advanced-cache.php
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( sbp_get_option( 'module_caching' ) ) {
			$sbp_advanced_cache = SBP_PATH . '/advanced-cache.php';

			SBP_Cache::set_wp_cache_constant( true );

			file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', file_get_contents( $sbp_advanced_cache ) );

		} else {
			SBP_Cache::set_wp_cache_constant( false );
			if ( file_exists( $advanced_cache_path ) ) {
				unlink( $advanced_cache_path );
			}
		}

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		wp_mkdir_p(WP_CONTENT_DIR . '/cache/speed-booster');

		$settings = [
			'caching_include_query_strings' => sbp_get_option('caching_include_query_strings'),
			'caching_expiry' => sbp_get_option('caching_expiry'),
			'caching_exclude_urls' => sbp_get_option('caching_exclude_urls'),
		];

		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/cache/speed-booster/settings.json', json_encode( $settings ) );
	}
}