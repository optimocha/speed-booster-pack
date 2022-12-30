<?php

namespace SpeedBooster;

use SpeedBooster\SBP_Advanced_Cache_Generator;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Cache extends SBP_Base_Cache {
	/**
	 * Name of the cached file
	 *
	 * @var string $file_name
	 */
	private $file_name = 'index.html';

	public function __construct() {
		parent::__construct();

		if ( ! sbp_get_option( 'module_caching' ) || sbp_should_disable_feature( 'caching' ) ) {
			return;
		}

		$this->clear_cache_hooks();

		// Clear cache hook
		add_action( 'init', [ $this, 'clear_cache_request' ] );

		// Handle The Cache
		add_filter( 'sbp_output_buffer', [ $this, 'handle_cache' ], 1000 );
	}

	/**
	 * Handles the HTTP request to catch cache clear action
	 */
	public function clear_cache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_total_cache' ) ) {
			self::clear_total_cache();
			set_transient( 'sbp_notice_cache', '1', 60 );
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Clears all cache files
	 */
	public static function clear_total_cache() {
		do_action( 'sbp_before_cache_clear' );
		sbp_delete_dir_recursively( SBP_CACHE_DIR );
		if ( sbp_get_option( 'caching_warmup_after_clear' ) && sbp_get_option( 'module_caching' ) ) {
			$warmup = new SBP_Cache_Warmup();
			$warmup->start_process();
			unset( $warmup );
		}
		SBP_Cloudflare::clear_cache();
		do_action( 'sbp_after_cache_clear' );
	}

	/**
	 * Do all the dirty work about cache.
	 * First, checks query strings if they're in included query string rules or not.
	 * Second, checks if current url is excluded or not.
	 * Third, reads cache files, checks the expire time of file
	 * Fourth, if file doesn't exists, creates the cache file
	 *
	 * @param $html
	 *
	 * @return bool|mixed|void
	 */
	public function handle_cache( $html ) {
		if ( true === $this->should_bypass_cache() ) {
			return $html;
		}

		$wp_filesystem = sbp_get_filesystem();

		$this->check_query_strings();

		// Read cache file
		$cache_file_path = $this->get_cache_file_path() . $this->file_name;

		$caching_expiry = sbp_get_option( 'caching_expiry' ) * HOUR_IN_SECONDS;

		$has_file_expired = $wp_filesystem->mtime( $cache_file_path ) + $caching_expiry < time();

		if ( $wp_filesystem->exists( $cache_file_path ) && ! $has_file_expired ) {
			return $wp_filesystem->get_contents( $cache_file_path );
		}

		// Apply filters
		$html = apply_filters( 'sbp_cache_before_create', $html );
		$this->create_cache_file( $html );

		return $html;
	}

	/**
	 * Generates a cache file for visited page
	 *
	 * @param $html
	 */
	private function create_cache_file( $html ) {
    	if( empty( $html ) ) { return; }
		$dir_path            = $this->get_cache_file_path();
		$file_path           = $dir_path . $this->file_name;
		$sbp_cache_signature = PHP_EOL . '<!-- Cached by Speed Booster Pack -->';

		wp_mkdir_p( $dir_path );
		$file = @fopen( $file_path, 'w+' );
		fwrite( $file, $html . $sbp_cache_signature );
		fclose( $file );
	}

	/**
	 * Returns cache file path according to current URL
	 *
	 * @param null $post_url
	 * @param bool $is_mobile
	 *
	 * @return string
	 */
	private function get_cache_file_path( $post_url = null, $is_mobile = false ) {
		$cache_dir = SBP_CACHE_DIR;
		if ( ( wp_is_mobile() && sbp_get_option( 'caching_separate_mobile' ) ) || true === $is_mobile ) {
			$cache_dir = SBP_CACHE_DIR . 'mobile';
		}

		$path = sprintf(
			'%s%s%s%s',
			$cache_dir,
			DIRECTORY_SEPARATOR,
			parse_url(
				$post_url ? $post_url : 'http://' . strtolower( $_SERVER['HTTP_HOST'] ),
				PHP_URL_HOST
			),
			parse_url(
				$post_url ? $post_url : $_SERVER['REQUEST_URI'],
				PHP_URL_PATH
			)
		);

		if ( is_file( $path ) > 0 ) {
			wp_die( 'Error occured on SBP cache. Please contact you webmaster.' );
		}

		return rtrim( $path, "/" ) . "/";
	}

	/**
	 * Parts of this function was inspired from Cache Enabler's codebase.
	 *
	 * @param bool $wp_cache
	 */
	public static function set_wp_cache_constant( $wp_cache = true ) {
		if ( $wp_cache === true && defined( 'WP_CACHE' ) && WP_CACHE === true ) {
			return;
		}

		if ( sbp_is_wp_config_writable() ) {
			// get wp config as array
			$wp_config_file = sbp_get_wp_config_path();

			// Get content of the config file.
			$config_file = file( $wp_config_file );

			if ( $wp_cache ) {
				$append_line = PHP_EOL . "define( 'WP_CACHE', true ); // Added by Speed Booster Pack";
			} else {
				$append_line = '';
			}

			$config_file_content = '';
			foreach ( $config_file as &$line ) {
				preg_match( '/^define\(\s*\'([A-Z_]+)\',(.*)\)/', trim($line), $match );

				if ( isset( $match[1] ) && 'WP_CACHE' === $match[1] ) {
					$line = '';
				}

				$config_file_content .= $line;
			}
			unset( $line );

			$pos = strpos($config_file_content, '<?php');
			if ($pos !== false) {
				$config_file_content = substr_replace($config_file_content, '<?php' . $append_line, $pos, strlen('<?php'));
			}

			file_put_contents( $wp_config_file, $config_file_content );
		}
	}

	/**
	 * Generates advanced-cache.php
	 *
	 * @param $saved_data
	 */
	public static function options_saved_listener( $saved_data ) {
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( sbp_should_disable_feature( 'caching' ) != false ) {
			return;
		}

		$module_caching_option = sbp_get_option( 'module_caching' );

		if ( $saved_data['module_caching'] != $module_caching_option ) {
			if ( ! sbp_is_wp_config_writable() ) {
				set_transient( 'sbp_wp_config_error', 1 );

				return;
			}

			if ( ! sbp_check_file_permissions( WP_CONTENT_DIR ) ) {
				set_transient( 'sbp_advanced_cache_error', 1 );

				return;
			}

			if ( file_exists( $advanced_cache_path ) && ! sbp_check_file_permissions( $advanced_cache_path ) ) {
				set_transient( 'sbp_advanced_cache_error', 1 );

				return;
			}
		}

		// Delete or recreate advanced-cache.php
		if ( $saved_data['module_caching'] ) {
			$advanced_cache_file_content = SBP_Advanced_Cache_Generator::generate_advanced_cache_file( $saved_data );
			if ( $advanced_cache_file_content ) {
				SBP_Cache::set_wp_cache_constant( true );

				if ( ! @file_put_contents( $advanced_cache_path, $advanced_cache_file_content ) ) {
					set_transient( 'sbp_advanced_cache_error', 1 );
				}
			}
		}

		if ( ! $saved_data['module_caching'] && $module_caching_option ) {
			SBP_Cache::set_wp_cache_constant( false );
			if ( file_exists( $advanced_cache_path ) ) {
				if ( ! unlink( $advanced_cache_path ) ) {
					wp_send_json_error( [
						'notice' => esc_html__( 'advanced-cache.php can not be removed. Please remove it manually.', 'speed-booster-pack' ),
						'errors' => [],
					] );
				}
			}
		}
	}

	public static function options_saved_filter( $data ) {
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';

		$module_caching_option = sbp_get_option( 'module_caching', '0' );

		$do_not_change_cache = false;

		if ( $data['module_caching'] != $module_caching_option ) {
			if ( ! sbp_is_wp_config_writable() ) {
				$do_not_change_cache = true;
			}

			if ( ! sbp_check_file_permissions( WP_CONTENT_DIR ) ) {
				$do_not_change_cache = true;
			}

			if ( file_exists( $advanced_cache_path ) && ! sbp_check_file_permissions( $advanced_cache_path ) ) {
				$do_not_change_cache = true;
			}
		}

		if ( $do_not_change_cache == true ) {
			$data['module_caching'] = $module_caching_option;
		}

		return $data;
	}

	public function clear_homepage_cache() {
		do_action( 'sbp_before_homepage_cache_clear' );

		global $wp_filesystem;
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$home_cache        = $this->get_cache_file_path( get_home_url() ) . 'index.html';
		$mobile_home_cache = $this->get_cache_file_path( get_home_url(), true ) . 'index.html';

		// Find index.html files
		if ( $wp_filesystem->exists( $home_cache ) ) {
			@unlink( $home_cache );
		}

		if ( $wp_filesystem->exists( $mobile_home_cache ) ) {
			@unlink( $mobile_home_cache );
		}
		do_action( 'sbp_after_homepage_cache_clear' );
	}

	public function clear_post_by_id( $post_id ) {
		do_action( 'sbp_before_post_cache_clear' );

		global $wp_filesystem;
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$home_cache        = $this->get_cache_file_path( get_permalink( $post_id ) ) . 'index.html';
		$mobile_home_cache = $this->get_cache_file_path( get_permalink( $post_id ), true ) . 'index.html';

		// Find index.html files
		if ( $wp_filesystem->exists( $home_cache ) ) {
			@unlink( $home_cache );
		}

		if ( $wp_filesystem->exists( $mobile_home_cache ) ) {
			@unlink( $mobile_home_cache );
		}

		do_action( 'sbp_after_post_cache_clear' );
	}

	public static function generate_htaccess( $saved_data = [] ) {
		global $is_apache;

		if ( ! $is_apache ) {
			return;
		}

		$sbp_htaccess_block = '# BEGIN Speed Booster Pack
# SBP ' . SBP_VERSION . '

# Character encodings
AddDefaultCharset utf-8

# ETags
<IfModule mod_headers.c>
    Header unset ETag
</IfModule>
FileETag None

# Compression
<IfModule mod_deflate.c>
	<IfModule mod_setenvif.c>
		<IfModule mod_headers.c>
			SetEnvIfNoCase ^(Accept-EncodXng|X-cept-Encoding|X{15}|~{15}|-{15})$ ^((gzip|deflate)\s*,?\s*)+|[X~-]{4,13}$ HAVE_Accept-Encoding
			RequestHeader append Accept-Encoding "gzip,deflate" env=HAVE_Accept-Encoding
		</IfModule>
	</IfModule>
	<IfModule mod_filter.c>
		AddOutputFilterByType DEFLATE "application/atom+xml" \
									  "application/javascript" \
									  "application/json" \
									  "application/ld+json" \
									  "application/manifest+json" \
									  "application/rdf+xml" \
									  "application/rss+xml" \
									  "application/schema+json" \
									  "application/geo+json" \
									  "application/vnd.ms-fontobject" \
									  "application/wasm" \
									  "application/x-font-ttf" \
									  "application/x-javascript" \
									  "application/x-web-app-manifest+json" \
									  "application/xhtml+xml" \
									  "application/xml" \
									  "font/eot" \
									  "font/opentype" \
									  "font/otf" \
									  "font/ttf" \
									  "image/bmp" \
									  "image/svg+xml" \
									  "image/vnd.microsoft.icon" \
									  "image/x-icon" \
									  "text/cache-manifest" \
									  "text/calendar" \
									  "text/css" \
									  "text/html" \
									  "text/javascript" \
									  "text/plain" \
									  "text/markdown" \
									  "text/vcard" \
									  "text/vnd.rim.location.xloc" \
									  "text/vtt" \
									  "text/x-component" \
									  "text/x-cross-domain-policy" \
									  "text/xml"
	</IfModule>
	<IfModule mod_mime.c>
		AddEncoding gzip			  svgz
	</IfModule>
</IfModule>

# Cache expiration
<IfModule mod_expires.c>

	ExpiresActive on

	# Default: Fallback
	ExpiresDefault									  "access plus 1 year"

	# Specific: Assets
	ExpiresByType image/vnd.microsoft.icon			  "access plus 1 week"
	ExpiresByType image/x-icon						  "access plus 1 week"

	# Specific: Manifests
	ExpiresByType application/manifest+json			 "access plus 1 week"
	ExpiresByType application/x-web-app-manifest+json   "access"
	ExpiresByType text/cache-manifest				   "access"

	# Specific: Data interchange
	ExpiresByType application/atom+xml				  "access plus 1 hour"
	ExpiresByType application/rdf+xml				   "access plus 1 hour"
	ExpiresByType application/rss+xml				   "access plus 1 hour"

	# Specific: Documents
	ExpiresByType text/html							 "access"
	ExpiresByType text/markdown						 "access"
	ExpiresByType text/calendar						 "access"

	# Specific: Other
	ExpiresByType text/x-cross-domain-policy			"access plus 1 week"

	# Generic: Data
	ExpiresByType application/json					  "access"
	ExpiresByType application/ld+json				   "access"
	ExpiresByType application/schema+json			   "access"
	ExpiresByType application/geo+json				  "access"
	ExpiresByType application/xml					   "access"
	ExpiresByType text/xml							  "access"

	# Generic: Assets
	ExpiresByType application/javascript			  "access plus 1 year"
	ExpiresByType application/x-javascript			"access plus 1 year"
	ExpiresByType text/javascript					 "access plus 1 year"
	ExpiresByType text/css							"access plus 1 year"

	# Generic: Medias
	ExpiresByType audio/*							 "access plus 1 year"
	ExpiresByType image/*							 "access plus 1 year"
	ExpiresByType video/*							 "access plus 1 year"
	ExpiresByType font/*							  "access plus 1 year"

</IfModule>

# Ported from: https://github.com/h5bp/server-configs-apache

# END Speed Booster Pack';

		$wp_filesystem = sbp_get_filesystem();

		$htaccess_file_path = get_home_path() . '/.htaccess';

		if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
			$current_htaccess = trim( self::get_default_htaccess() );

			if ( ( isset( $saved_data['module_caching'] ) && $saved_data['module_caching'] ) || ( $saved_data == [] && sbp_get_option( 'module_caching' ) ) ) {
				$current_htaccess = str_replace( "# BEGIN WordPress", $sbp_htaccess_block . PHP_EOL . PHP_EOL . "# BEGIN WordPress", $current_htaccess );
			}

			$wp_filesystem->put_contents( $htaccess_file_path, $current_htaccess );
		}
	}

	public static function get_default_htaccess() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$htaccess_file_path = get_home_path() . '/.htaccess';

		if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
			$current_htaccess = trim( $wp_filesystem->get_contents( $htaccess_file_path ) );
			$current_htaccess = preg_replace( '/(# BEGIN Speed Booster Pack.*?# END Speed Booster Pack' . PHP_EOL . PHP_EOL . ')/msi', '', $current_htaccess );

			return $current_htaccess;
		}

		return false;
	}

	/**
	 * Removes Speed Booster Pack's htaccess content and returns that modified htaccess code.
	 * Returns false if htaccess file doesn't exists
	 */
	public static function clean_htaccess() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		$htaccess_file_path = get_home_path() . '/.htaccess';

		if ( $wp_filesystem->exists( $htaccess_file_path ) ) {
			$current_htaccess = self::get_default_htaccess();
			$wp_filesystem->put_contents( get_home_path() . '/.htaccess', $current_htaccess );
		}
	}

	private function clear_cache_hooks() {
		add_action( '_core_updated_successfully', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'switch_theme', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'save_post', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'autoptimize_action_cachepurged', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'upgrader_process_complete', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'woocommerce_thankyou', [ $this, 'woocommerce_cache_clean' ] );
		add_action( 'woocommerce_product_set_stock', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'woocommerce_product_set_stock_status', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'woocommerce_variation_set_stock', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'woocommerce_variation_set_stock_status', 'SpeedBooster\SBP_Cache::clear_total_cache' );
		add_action( 'wp_update_nav_menu', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a custom menu is update.
		add_action( 'update_option_sidebars_widgets', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When you change the order of widgets.
		add_action( 'update_option_category_base', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When category permalink prefix is update.
		add_action( 'update_option_tag_base', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When tag permalink prefix is update.
		add_action( 'permalink_structure_changed', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When permalink structure is update.
		add_action( 'edited_terms', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a term is updated.
		add_action( 'customize_save', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When customizer is saved.
		add_action( 'comment_post', [ $this, 'comment_action' ] );
		add_action(
			'wp_trash_post',
			function ( $post_id ) {
				if ( get_post_status( $post_id ) == 'publish' ) {
					self::clear_total_cache();
				}
			}
		);

//		add_action( 'user_register', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a user is added.
//		add_action( 'profile_update', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a user is updated.
//		add_action( 'deleted_user', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a user is deleted.
//		add_action( 'create_term', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a term is created.
//		add_action( 'delete_term', 'SpeedBooster\SBP_Cache::clear_total_cache' );  // When a term is deleted.

		if ( is_admin() ) {
			add_action( 'wpmu_new_blog', 'SpeedBooster\SBP_Cache::clear_total_cache' );
			add_action( 'delete_blog', 'SpeedBooster\SBP_Cache::clear_total_cache' );
			add_action( 'transition_comment_status', [ $this, 'comment_transition' ], 10, 3 );
			add_action( 'edit_comment', [ $this, 'comment_action' ] );
		}

	}

	public function comment_transition( $new_status, $old_status, $comment ) {
		self::clear_post_by_id( $comment->comment_post_ID );
	}

	public function comment_action( $comment_id ) {
		$comment = get_comment( $comment_id );

		if ( $comment->comment_approved ) {
			self::clear_post_by_id( $comment->comment_post_ID );
		}
	}

	public function woocommerce_cache_clean( $order_id ) {
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		$items = $order->get_items();
		foreach ( $items as $item_id => $item ) {
			$product_id = $item['product_id'];
			self::clear_post_by_id( $product_id );

			if ( $item['variation_id'] > 0 ) {
				$variation_id = $item['variation_id'];
				self::clear_post_by_id( $variation_id );
			}
		}
	}

	private function check_query_strings() {
		// Check for query strings
		if ( ! empty( $_GET ) ) {
			// Get included rules
			$include_query_strings = SBP_Utils::explode_lines( sbp_get_option( 'caching_include_query_strings' ) );

			$query_string_file_name = '';
			// Order get parameters alphabetically (to get same filename for every order of query parameters)
			ksort( $_GET );
			foreach ( $_GET as $key => $value ) {
				if ( in_array( $key, $include_query_strings ) ) {
					$query_string_file_name .= "$key-$value-";
				}
			}
			if ( '' !== $query_string_file_name ) {
				$this->file_name = md5( $query_string_file_name ) . '.html';
			}
		}
	}
}