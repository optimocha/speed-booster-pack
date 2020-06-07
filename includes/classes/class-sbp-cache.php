<?php

namespace SpeedBooster;

class SBP_Cache extends SBP_Abstract_Module {
	/**
	 * Name of the cached file
	 *
	 * @var string $file_name
	 */
	private $file_name = 'index.html';

	public function __construct() {
		if ( ! parent::should_plugin_run() || ! sbp_get_option( 'module_caching' ) ) {
			return;
		}

		self::generate_htaccess();

		// Clear cache hook
		add_action( 'init', [ $this, 'clear_cache_request' ] );

		if ( sbp_get_option( 'enable-cache' ) ) {
			$this->set_wp_cache_constant( true );
		}

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

		if ( wp_is_mobile() && ! sbp_get_option( 'caching_separate_mobile' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Handles the HTTP request to catch cache clear action
	 */
	public function clear_cache_request() {
		if ( isset( $_GET['sbp_action'] ) && $_GET['sbp_action'] == 'sbp_clear_cache' && current_user_can( 'manage_options' ) ) {
			$redirect_url = remove_query_arg( 'sbp_action', ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );
			self::clear_total_cache();
			SBP_Cloudflare::clear_cache();
			set_transient( 'sbp_notice_cache', '1', 60 );
			wp_redirect( $redirect_url );
		}
	}

	/**
	 * Return WP_Filesystem instance
	 *
	 * @return mixed
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		require_once( ABSPATH . '/wp-admin/includes/file.php' );
		WP_Filesystem();

		return $wp_filesystem;
	}

	/**
	 * Clears all cache files and regenerates settings.json file
	 */
	public static function clear_total_cache() {
		self::delete_dir( SBP_CACHE_DIR );
		self::create_settings_json();
	}

	/**
	 * Deletes directories recursively
	 *
	 * @param $dir
	 */
	public static function delete_dir( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$dir_objects = @scandir( $dir );
		$objects     = array_filter( $dir_objects,
			function ( $object ) {
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
		if ( sbp_get_option( 'caching_exclude_urls' ) ) {
			$exclude_urls = array_map( 'trim', explode( PHP_EOL, sbp_get_option( 'caching_exclude_urls' ) ) );
			if ( count( $exclude_urls ) > 0 && in_array( $_SERVER['REQUEST_URI'], $exclude_urls ) ) {
				return false;
			}
		}

		$wp_filesystem = $this->get_filesystem();

		// Read cache file
		$cache_file_path = $this->get_cache_file_path() . $this->file_name;

		$caching_expiry = sbp_get_option( 'caching_expiry' ) * DAY_IN_SECONDS;

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
		$dir_path  = $this->get_cache_file_path();
		$file_path = $dir_path . $this->file_name;
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
			$cache_dir = SBP_CACHE_DIR . '/mobile';
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

	/**
	 * Generates advanced-cache.php and settings.json on CSF options save.
	 *
	 * @param $saved_data
	 */
	public static function options_saved_listener( $saved_data ) {
		// Delete or recreate advanced-cache.php
		$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
		if ( $saved_data['module_caching'] ) {
			$sbp_advanced_cache = SBP_PATH . '/advanced-cache.php';

			SBP_Cache::set_wp_cache_constant( true );

			file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', file_get_contents( $sbp_advanced_cache ) );

			self::create_settings_json( $saved_data );
		} else {
			SBP_Cache::set_wp_cache_constant( false );
			if ( file_exists( $advanced_cache_path ) ) {
				if ( ! unlink( $advanced_cache_path ) ) {
					return wp_send_json_error( [ 'notice' => esc_html__( 'advanced-cache.php can not be removed. Please remove it manually.', 'speed-booster-pack' ), 'errors' => [] ] );
				}
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

	// Delete homepage cache
	public function clear_homepage_cache() {
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
	}

	public function generate_htaccess() {
		$htaccess_file_content = '# BEGIN Speed Booster Pack
# SBP v4.0

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

# END Speed Booster Pack
';

		$wp_filesystem    = $this->get_filesystem();
		$current_htaccess = trim( $wp_filesystem->get_contents( get_home_path() . '/.htaccess' ) );
		if ( strpos( $current_htaccess, '# BEGIN Speed Booster Pack' ) !== 0 ) {
			$generated_htaccess = $htaccess_file_content . PHP_EOL . $current_htaccess;
			$wp_filesystem->put_contents( get_home_path() . '/.htaccess', $generated_htaccess );
		}
		/* LAHMACUNTODO: 
			1. DON'T search for our code block in the htaccess file & stop if it's found; but search & remove our block if it's found. That way we can remove our code block from earlier versions.
			2. Remove the whole block on a) cache module disable b) plugin deactivation
			3. Add the code block before "# BEGIN WordPress", not to the beginning of the htaccess file.
		*/
	}
}