<?php

namespace SpeedBooster;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class SBP_Cache extends SBP_Abstract_Module {
	/**
	 * Name of the cached file
	 *
	 * @var string $file_name
	 */
	private $file_name = 'index.html';

	public function __construct() {
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
	 * Decides to run cache or not.
	 *
	 * @return bool
	 */
	private function should_bypass_cache() {

		// Do not cache for logged in users
		if ( is_user_logged_in() ) {
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

		if ( ! empty( $_GET ) ) {
			$include_query_strings = SBP_Utils::explode_lines( sbp_get_option( 'caching_include_query_strings' ) );

			foreach ( $_GET as $key => $value ) {
				if ( ! in_array( $key, $include_query_strings ) ) {
					return true;
				}
			}
		}

		// Check for exclude URLs
		if ( sbp_get_option( 'caching_exclude_urls' ) ) {
			$exclude_urls   = array_map( 'trim', explode( PHP_EOL, sbp_get_option( 'caching_exclude_urls' ) ) );
			$exclude_urls[] = '/favicon.ico';
			$current_url    = rtrim( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '/' );
			if ( count( $exclude_urls ) > 0 && in_array( $current_url, $exclude_urls ) ) {
				return true;
			}
		}

		return false;

	}

	/**
	 * Handles the HTTP request to catch cache clear action
	 */
	public function clear_cache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_cache' && current_user_can( 'manage_options' ) && isset( $_GET['sbp_nonce'] ) && wp_verify_nonce( $_GET['sbp_nonce'], 'sbp_clear_total_cache' ) ) {
			$redirect_url = remove_query_arg( [ 'sbp_action', 'sbp_nonce' ] );
			self::clear_total_cache();
			set_transient( 'sbp_notice_cache', '1', 60 );
			wp_redirect( $redirect_url );
		}
	}

	/**
	 * Clears all cache files and regenerates settings.json file
	 */
	public static function clear_total_cache() {
		do_action( 'sbp_before_cache_clear' );
		sbp_delete_dir_recursively( SBP_CACHE_DIR );
		self::create_settings_json();
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

		if ( ! $wp_filesystem->exists( SBP_CACHE_DIR . 'settings.json' ) ) {
			self::create_settings_json();
		}

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
				$this->file_name        = md5( $query_string_file_name ) . '.html';
			}
		}

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
		if ( file_exists( ABSPATH . 'wp-config.php' ) ) {
			$wp_config_file = ABSPATH . 'wp-config.php';
		} else {
			$wp_config_file = dirname( ABSPATH ) . '/wp-config.php';
		}

		if ( file_exists( $wp_config_file ) && is_writable( $wp_config_file ) ) {
			// get wp config as array
			$wp_config = file( $wp_config_file );

			if ( $wp_cache ) {
				$append_line = PHP_EOL . PHP_EOL . "define('WP_CACHE', true); // Added by Speed Booster Pack" . PHP_EOL;
			} else {
				$append_line = '';
			}

			$found_wp_cache = false;

			foreach ( $wp_config as &$line ) {
				if ( preg_match( '/^\s*define\s*\(\s*[\'\"]WP_CACHE[\'\"]\s*,.*\)\s*;/', $line ) ) {
					$line           = $append_line;
					$found_wp_cache = true;
					break;
				}
			}

			// append wp cache constant if not found yet
			if ( ! $found_wp_cache && $wp_cache ) {
				array_shift( $wp_config );
				array_unshift( $wp_config, "<?php", $append_line );
			}

			// write wp-config.php file
			$fh = @fopen( $wp_config_file, 'w' );
			foreach ( $wp_config as $ln ) {
				@fwrite( $fh, $ln );
			}

			@fclose( $fh );
		}
	}

	/**
	 * Generates advanced-cache.php and settings.json on CSF options save.
	 *
	 * @param $saved_data
	 */
	public static function options_saved_listener( $saved_data ) {
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( ! isset( $_SERVER['KINSTA_CACHE_ZONE'] ) && ( ! defined( 'IS_PRESSABLE' ) || ! IS_PRESSABLE ) ) {

			if ( sbp_get_option( 'module_caching' ) !== $saved_data['module_caching'] ) {

				// Delete or recreate advanced-cache.php
				if ( $saved_data['module_caching'] ) {
					$sbp_advanced_cache = SBP_PATH . '/advanced-cache.php';

					SBP_Cache::set_wp_cache_constant( true );

					file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', file_get_contents( $sbp_advanced_cache ) );

					self::create_settings_json( $saved_data );
				} else {
					SBP_Cache::set_wp_cache_constant( false );
					if ( file_exists( $advanced_cache_path ) ) {
						if ( ! unlink( $advanced_cache_path ) ) {
							return wp_send_json_error( [
								'notice' => esc_html__( 'advanced-cache.php can not be removed. Please remove it manually.', 'speed-booster-pack' ),
								'errors' => []
							] );
						}
					}
				}
			}
		} else {
			if ( file_exists( $advanced_cache_path ) ) {
				@unlink( $advanced_cache_path );
			}
		}
	}

	/**
	 * Generates settings.json file from current options
	 *
	 * @param null $saved_data
	 */
	public static function create_settings_json( $options = null ) {
		global $wp_filesystem;
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		wp_mkdir_p( WP_CONTENT_DIR . '/cache/speed-booster' );
		$settings = [
			'caching_include_query_strings' => $options !== null ? $options['caching_include_query_strings'] : sbp_get_option( 'caching_include_query_strings' ),
			'caching_expiry'                => $options !== null ? $options['caching_expiry'] : sbp_get_option( 'caching_expiry' ),
			'caching_exclude_urls'          => $options !== null ? $options['caching_exclude_urls'] : sbp_get_option( 'caching_exclude_urls' ),
			'caching_separate_mobile'       => $options !== null ? $options['caching_separate_mobile'] : sbp_get_option( 'caching_separate_mobile' ),
		];

		$wp_filesystem->put_contents( WP_CONTENT_DIR . '/cache/speed-booster/settings.json', json_encode( $settings ) );
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

## SECTION: General stuff

# UTF-8 config
  AddDefaultCharset UTF-8
  <IfModule mod_mime.c>
  AddCharset UTF-8 .appcache .bbaw .css .htc .ics .js .json .manifest .map .markdown .md .mjs .topojson .vtt .vcard .vcf .webmanifest .xloc
  </IfModule>
# FileETag config
  <IfModule mod_headers.c>
  Header unset ETag
  </IfModule>
  FileETag None
# Cache-Control config
  <FilesMatch "\.(html|htm|rtf|rtx|txt|xsd|xsl|xml|css|htc|js|asf|asx|wax|wmv|wmx|avi|bmp|class|divx|doc|docx|eot|exe|gif|gz|gzip|ico|jpg|jpeg|jpe|json|mdb|mid|midi|mov|qt|mp3|m4a|mp4|m4v|mpeg|mpg|mpe|mpp|otf|odb|odc|odf|odg|odp|ods|odt|ogg|pdf|png|pot|pps|ppt|pptx|ra|ram|svg|svgz|swf|tar|tif|tiff|ttf|ttc|wav|wma|wri|xla|xls|xlsx|xlt|xlw|zip)$">
  <IfModule mod_headers.c>
  Header unset Pragma
  Header append Cache-Control "public"
  </IfModule>
  </FilesMatch>

## SECTION: Compression (DEFLATE)

<IfModule mod_deflate.c>
<IfModule mod_setenvif.c>
<IfModule mod_headers.c>
SetEnvIfNoCase ^(Accept-EncodXng|X-cept-Encoding|X{15}|~{15}|-{15})$ ^((gzip|deflate)\s*,?\s*)+|[X~-]{4,13}$ HAVE_Accept-Encoding
RequestHeader append Accept-Encoding "gzip,deflate" env=HAVE_Accept-Encoding
SetEnvIfNoCase Request_URI \
\.(?:gif|jpe?g|png|rar|zip|exe|flv|mov|wma|mp3|avi|swf|mp?g|mp4|webm|webp|pdf)$ no-gzip dont-vary
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
AddEncoding gzip              svgz
</IfModule>
</IfModule>

## SECTION: Cache expiration

<IfModule mod_expires.c>
  ExpiresActive on
  ExpiresDefault                                      "access plus 1 month"
# HTML
  ExpiresByType text/html                             "access plus 0 seconds"
# CSS
  ExpiresByType text/css                              "access plus 1 year"
# JavaScript
  ExpiresByType application/javascript                "access plus 1 year"
  ExpiresByType application/x-javascript              "access plus 1 year"
  ExpiresByType text/javascript                       "access plus 1 year"
# Media files
  ExpiresByType audio/ogg                             "access plus 1 month"
  ExpiresByType image/bmp                             "access plus 1 month"
  ExpiresByType image/gif                             "access plus 1 month"
  ExpiresByType image/jpeg                            "access plus 1 month"
  ExpiresByType image/png                             "access plus 1 month"
  ExpiresByType image/apng                            "access plus 1 month"
  ExpiresByType image/avif                            "access plus 1 month"
  ExpiresByType image/avif-sequence                   "access plus 1 month"
  ExpiresByType image/svg+xml                         "access plus 1 month"
  ExpiresByType image/webp                            "access plus 1 month"
  ExpiresByType video/mp4                             "access plus 1 month"
  ExpiresByType video/ogg                             "access plus 1 month"
  ExpiresByType video/webm                            "access plus 1 month"
# Web fonts
  ExpiresByType font/collection                       "access plus 1 month"
  ExpiresByType application/vnd.ms-fontobject         "access plus 1 month"
  ExpiresByType font/eot                              "access plus 1 month"
  ExpiresByType font/opentype                         "access plus 1 month"
  ExpiresByType font/otf                              "access plus 1 month"
  ExpiresByType application/x-font-ttf                "access plus 1 month"
  ExpiresByType font/ttf                              "access plus 1 month"
  ExpiresByType application/font-woff                 "access plus 1 month"
  ExpiresByType application/x-font-woff               "access plus 1 month"
  ExpiresByType font/woff                             "access plus 1 month"
  ExpiresByType application/font-woff2                "access plus 1 month"
  ExpiresByType font/woff2                            "access plus 1 month"
# Data interchange
  ExpiresByType application/atom+xml                  "access plus 1 hour"
  ExpiresByType application/rdf+xml                   "access plus 1 hour"
  ExpiresByType application/rss+xml                   "access plus 1 hour"
  ExpiresByType application/json                      "access plus 0 seconds"
  ExpiresByType application/ld+json                   "access plus 0 seconds"
  ExpiresByType application/schema+json               "access plus 0 seconds"
  ExpiresByType application/geo+json                  "access plus 0 seconds"
  ExpiresByType application/xml                       "access plus 0 seconds"
  ExpiresByType text/calendar                         "access plus 0 seconds"
  ExpiresByType text/xml                              "access plus 0 seconds"
# Other
  ExpiresByType image/vnd.microsoft.icon              "access plus 1 week"
  ExpiresByType image/x-icon                          "access plus 1 week"
  ExpiresByType text/x-cross-domain-policy            "access plus 1 week"
  ExpiresByType application/manifest+json             "access plus 1 week"
  ExpiresByType application/x-web-app-manifest+json   "access plus 0 seconds"
  ExpiresByType text/cache-manifest                   "access plus 0 seconds"
</IfModule>

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
}