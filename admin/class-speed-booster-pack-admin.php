<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://optimocha.com
 * @since      4.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/admin
 */

// If this file is called directly, abort.
use SpeedBooster\SBP_Cloudflare;
use SpeedBooster\SBP_Notice_Manager;
use SpeedBooster\SBP_Utils;

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Returns absolute value of a number. Returns 1 if value is zero.
 *
 * @param $value
 *
 * @return float|int
 * @since 4.0.0
 *
 */
function sbp_posabs( $value ) {
	if ( 0 == $value ) {
		return 1;
	}

	return absint( $value );
}

/**
 * Removes http(s?):// and trailing slash from the url
 *
 * @param $url
 *
 * @return string
 * @since 4.0.0
 *
 */
function sbp_clear_cdn_url( $url ) {
	return preg_replace( "#^[^:/.]*[:/]+#i", "", rtrim( $url, '/' ) );
}

/**
 * Removes http:// from the url
 *
 * @param $url
 *
 * @return string
 * @since 4.0.0
 *
 */
function sbp_clear_http( $url ) {
	return str_replace( "http://", "//", $url );
}

/**
 * Trims and strips the tags from given value. Takes one dimensional array or string as argument. Returns the modified value.
 *
 * @param $value array|string
 *
 * @return array|string
 */
function sbp_sanitize_strip_tags( $value ) {
	if ( is_array( $value ) ) {
		$value = array_map(
			function ( $item ) {
				return trim( strip_tags( $item ) );
			},
			$value
		);
	} else {
		$value = trim( strip_tags( $value ) );
	}

	return $value;
}

/**
 * Sanitizes excluded URLs for caching
 *
 * @param $urls
 *
 * @return string
 * @since 4.0.0
 *
 */
function sbp_sanitize_caching_urls( $urls ) {
	$urls = SBP_Utils::explode_lines( $urls );
	foreach ( $urls as &$url ) {
		$url = preg_replace( '#^https?://(.*?)#', '\1', $url );
		$url = preg_replace( '#(.*?)\#(.*?)$#', '\1', $url );
		$url = preg_replace( '#(.*?)\?(.*?)$#', '\1', $url );
		$url = preg_replace( '#(.*?)\/$#', '\1', $url );
	}

	$urls = array_unique( $urls );

	return implode( PHP_EOL, $urls );
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/admin
 * @author     Optimocha <info@speedboosterpack.com>
 */
class Speed_Booster_Pack_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();

		add_action( 'csf_sbp_options_save_before', '\SpeedBooster\SBP_Cloudflare::reset_transient' );

		add_action( 'csf_sbp_options_save_before', '\SpeedBooster\SBP_Cache::options_saved_listener' );

		add_action( 'csf_sbp_options_save_before', '\SpeedBooster\SBP_Cloudflare::update_cloudflare_settings' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_Cache::clear_total_cache' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_Cache::generate_htaccess' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_WP_Config_Injector::inject_wp_config' );

		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );

		add_action( 'init', [ $this, 'set_notices' ] );

		$this->initialize_announce4wp();

		$this->create_settings_page();

		add_action( 'admin_enqueue_scripts', 'add_thickbox' );
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, SBP_URL . 'admin/css/speed-booster-pack-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, SBP_URL . 'admin/js/speed-booster-pack-admin.js', array( 'jquery' ), $this->version, false );
	}

	public function load_dependencies() {
		require_once SBP_LIB_PATH . 'codestar-framework/codestar-framework.php';
		require_once SBP_LIB_PATH . 'announce4wp/announce4wp-client.php';
	}

	public function create_settings_page() {
		// Check core class for avoid errors
		if ( class_exists( 'CSF' ) ) {

			// Set a unique slug-like ID
			$prefix = 'sbp_options';

			// Create options
			CSF::createOptions( $prefix,
				[
					// framework title
					'framework_title' => SBP_PLUGIN_NAME . ' <small>by <a href="' . SBP_OWNER_HOME . '" rel="external noopener" target="_blank">' . SBP_OWNER_NAME . '</a></small>',
					'framework_class' => 'sbp-settings',

					// menu settings
					'menu_title'      => 'Speed Booster',
					'menu_icon'       => SBP_URL . 'admin/images/icon.svg',
					'menu_slug'       => 'sbp-settings',
					'menu_type'       => 'menu',
					'menu_capability' => 'manage_options',

					'theme'                   => 'light',
					'ajax_save'               => false,
					'show_search'             => false,
					'show_reset_section'      => false,
					'show_all_options'        => false,

					// menu extras
					'show_bar_menu'           => false,
					'show_sub_menu'           => true,
					'admin_bar_menu_icon'     => '',
					'admin_bar_menu_priority' => 80,

					/* translators: 1: plugin name 2: opening tag for the hyperlink 3: closing tag for the hyperlink  */
					'footer_credit'           => sprintf( __( 'Thank you for using %1$s! If you like our plugin, be sure to %2$sleave a fair review%3$s.', 'speed-booster-pack' ), SBP_PLUGIN_NAME, '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external noopener" target="_blank">', '</a>' ),
				] );

			/* BEGIN Section: Dashboard */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Dashboard', 'speed-booster-pack' ),
					'id'     => 'dashboard',
					'class'  => 'dashboard',
					'icon'   => 'fa fa-tachometer-alt',
					'fields' => [

						[
							'type'    => 'heading',
							/* translators: %s = Speed Booster Pack  */
							'content' => sprintf( __( 'Welcome to %s!', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							'type'    => 'content',
							/* translators: %s = Speed Booster Pack  */
							'content' => sprintf( __( 'Thank you for installing %s! We really hope you\'ll like our plugin and greatly benefit from it. On this page, you\'ll find a small introduction to the plugin\'s features, and a few other things. Let\'s begin!', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'Heads up: This plugin is ALWAYS in beta!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							/* translators: 1. Speed Booster Pack 2. link to the speedboosterpack.com contact form 3. link to the GitHub page  */
							'content' => sprintf( __( 'We\'re constantly adding new features to %1$s, and improving existing ones. While it\'s safe to use on live websites, there are a lot of moving parts and there\'s a chance that it might cause conflicts. After configuring %1$s, make sure you check your website as a visitor and confirm all\'s well. If you find a bug, you can let us know about it via our contact form on %2$s or create an issue on %3$s.', 'speed-booster-pack' ), SBP_PLUGIN_NAME, '<a href="https://speedboosterpack.com/contact/" rel="external noopener" target="_blank">speedboosterpack.com</a>', '<a href="https://github.com/optimocha/speed-booster-pack/" rel="external noopener" target="_blank">GitHub</a>' ),
						],
						[
							'type'    => 'subheading',
							/* translators: %s = Speed Booster Pack  */
							'content' => sprintf( __( 'Features and benefits of %s', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							'type'    => 'content',
							/* translators: %s = Speed Booster Pack  */
							'content' => '<p>' . __( 'Each module of this plugin has different sets of really cool features that can help speed up your website:', 'speed-booster-pack' ) . '</p>' . '<ul><li>' .
							             '<strong>' . __( 'Caching', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module caches your pages into static HTML files, greatly reducing database queries. It also helps browsers cache static assets more efficiently.', 'speed-booster-pack' ) . '</li><li>' .
							             '<strong>' . __( 'Assets', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module helps you optimize the static assets in your pages by minifying HTML and CSS, lazy loading media (images, videos and iframes), deferring JavaScript, optimizing Google fonts and preloading any asset you want.', 'speed-booster-pack' ) . '</li><li>' .
							             '<strong>' . __( 'Special', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module has features for specific cases like CDN usage, localizing tracker scripts, adding custom JavaScript code and optimizations for some popular plugins.', 'speed-booster-pack' ) . '</li><li>' .
							             '<strong>' . __( 'Tweaks', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module lets you tweak the WordPress core and your page sources by dequeueing core scripts/styles, decluttering &lt;head&gt;, optimizing revisions and the Heartbeat API and so on.', 'speed-booster-pack' ) . '</li></ul>' .
							             '<p>' . __( 'Feel free to experiment, and don\'t forget to create exclude rules when necessary!', 'speed-booster-pack' ) . '</p>',
						],
						[
							'type'    => 'subheading',
							'content' => __( 'Upcoming features', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							/* translators: 1. opening tag for the newsletter hyperlink 2. closing tag for the hyperlink  */
							'content' => sprintf( __( 'Like we mentioned above, we\'re constantly working on making our plugin better on every release. If you\'d like to be the first to know about improvements before they\'re released, plus more tips &amp; tricks about web performance optimization, %1$syou can sign up for our weekly newsletter here%2$s!', 'speed-booster-pack' ), '<a href="https://speedboosterpack.com/static/newsletter.php?KeepThis=true&TB_iframe=true&height=500&width=400" class="thickbox">', '</a>' ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'That\'s it, enjoy!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => '<p>' . __( 'We really hope that you\'ll enjoy working with our plugin. Always remember that this is a powerful tool, and using powerful tools might hurt you if you\'re not careful. Have fun!', 'speed-booster-pack' ) . '</p>' .
							             /* translators: %s = Optimocha */
							             '<p style="font-style:italic;">' . sprintf( __( 'Your friends at %s', 'speed-booster-pack' ), SBP_OWNER_NAME ) . '</p>' .
							             /* translators: 1. Speed Booster Pack 2. link to the plugin's reviews page on wp.org */
							             '<p>' . sprintf( __( 'Almost forgot: If you like %1$s, it would mean a lot to us if you gave a fair rating on %2$s, because highly rated plugins are shown to more users on the WordPress plugin directory, meaning that we\'ll have no choice but to take better care of %1$s!', 'speed-booster-pack' ), SBP_PLUGIN_NAME, '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external noopener" target="_blank">wordpress.org</a>' ) . '</p>',
						],
						[
							'type'    => 'subheading',
							'content' => __( 'If you\'re looking for professional help...', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							/* translators: 1: plugin owner's name (Optimocha) 2: Speed Booster Pack (Speed Booster Pack) 3: hyperlink to the owner's website */
							'content' => sprintf( __( 'As %1$s, we like to brag about completing hundreds of tailored speed optimization jobs for different websites. (This experience is actually the source of the know-how that helps %2$s get better on every release!) If you\'re willing to invest in speeding up your website, not just with %2$s but as a whole, feel free to contact us on %3$s and benefit from our expertise on speed optimization!', 'speed-booster-pack' ), SBP_OWNER_NAME, SBP_PLUGIN_NAME, '<a href="' . SBP_OWNER_HOME . '" rel="external noopener" target="_blank">' . strtolower( SBP_OWNER_NAME ) . '.com</a>' ),
						],

					],
				]
			);
			/* END Section: Dashboard */

			/* BEGIN Section: Caching */
			$cache_fields = [
				[
					'id'    => 'module_caching',
					'class' => 'module-caching',
					'type'  => 'switcher',
					/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
					'title' => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Caching', 'speed-booster-pack' ),
					'label' => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
				],
				[
					'title'      => __( 'Cache expiry time', 'speed-booster-pack' ),
					'id'         => 'caching_expiry',
					'type'       => 'spinner',
					'min'        => '1',
					'unit'       => __( 'hours', 'speed-booster-pack' ),
					'desc'       => __( 'How many hours to expire a cached page (1 or higher). Expired cache files are regenerated automatically.', 'speed-booster-pack' ),
					'default'    => '10',
					'sanitize'   => 'sbp_posabs',
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				],
				[
					'id'         => 'caching_separate_mobile',
					'type'       => 'switcher',
					'title'      => __( 'Separate mobile cache', 'speed-booster-pack' ),
					'desc'       => __( 'Creates separate cache files for mobile and desktop. Useful if you have mobile-specific plugins or themes. Not necessary if you have a responsive theme.', 'speed-booster-pack' ),
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				],
				[
					'id'         => 'caching_warmup_after_clear',
					'type'       => 'switcher',
					'title'      => __( 'Warm up cache on clear', 'speed-booster-pack' ),
					'desc'       => __( 'Creates cache files for the front page and all pages that are linked from the front page, each time the cache is cleared. Note that even though you don\'t turn this option on, you can manually warm up the cache from your admin bar.', 'speed-booster-pack' ),
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				],
				[
					'id'         => 'caching_exclude_urls',
					'class'      => 'caching-exclude-urls',
					'type'       => 'code_editor',
					'title'      => __( 'Exclude URLs', 'speed-booster-pack' ),
					'desc'       => __( 'Enter one URL per line to exclude them from caching. Cart and Checkout pages of WooCommerce are always excluded, so you don\'t have to set them in here.', 'speed-booster-pack' ),
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
					'sanitize'   => 'sbp_sanitize_caching_urls',
				],
				[
					'id'         => 'caching_include_query_strings',
					'class'      => 'caching-include-query-strings',
					'type'       => 'code_editor',
					'title'      => __( 'Cached query strings', 'speed-booster-pack' ),
					'desc'       => __( 'Enter one query string per line to cache URLs with those query strings.', 'speed-booster-pack' ) . '<br />' .
					                /* translators: 1. <code> 2. </code> */
					                sprintf( __( 'For example, after adding "foo" to the list, %1$sexample.com/blog-post/?foo=bar%2$s will be cached.', 'speed-booster-pack' ), '<code>', '</code>' ),
					'default'    => 'utm_source',
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				],
			];

			$should_disable_caching = sbp_should_disable_feature( 'caching' );
			if ( $should_disable_caching ) {
				$restricted_hosting_notice = [
					[
						'type'    => 'submessage',
						'style'   => 'success',
						'class'   => 'hosting-warning',
						'content' => $should_disable_caching['error_message'],
					],
				];
				$cache_fields              = array_merge( $restricted_hosting_notice, $cache_fields );
			}

			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Caching', 'speed-booster-pack' ),
					'id'     => 'caching',
					'icon'   => 'fa fa-server',
					'class'  => sbp_should_disable_feature( 'caching' ) ? 'inactive-section' : '',
					'fields' => $cache_fields,
				]
			);
			/* END Section: Caching */

			/* BEGIN Section: CDN & Proxy */
			/* Begin Of Cloudflare Fields */
			$cloudflare_fields    = [
				[
					'title' => __( 'Cloudflare', 'speed-booster-pack' ),
					'type'  => 'subheading',
				],
				[
					'title' => __( 'Connect to Cloudflare', 'speed-booster-pack' ),
					'id'    => 'cloudflare_enable',
					'type'  => 'switcher',
				],
				[
					'title' => __( 'Cloudflare global API key', 'speed-booster-pack' ),
					'id'    => 'cloudflare_api',
					'type'  => 'text',
					'desc'  => '<a href="https://support.cloudflare.com/hc/en-us/articles/200167836-Managing-API-Tokens-and-Keys#12345682" rel="external noopener" target="_blank">' . __( 'You can find it using this tutorial.', 'speed-booster-pack' ) . '</a>',
				],
				[
					'title' => __( 'Cloudflare email address', 'speed-booster-pack' ),
					'id'    => 'cloudflare_email',
					'type'  => 'text',
					'desc'  => __( 'The email address you signed up for Cloudflare with.', 'speed-booster-pack' ),
				],
				[
					'title' => __( 'Cloudflare zone ID', 'speed-booster-pack' ),
					'id'    => 'cloudflare_zone',
					'type'  => 'text',
					'desc'  => __( 'You can find your zone ID in the Overview tab on your Cloudflare panel.', 'speed-booster-pack' ),
				],
				[
					'title'      => __( 'Rocket Loader', 'speed-booster-pack' ),
					'id'         => 'cf_rocket_loader_enable',
					'class'      => 'with-preloader',
					'type'       => 'switcher',
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Development Mode', 'speed-booster-pack' ),
					'id'         => 'cf_dev_mode_enable',
					'class'      => 'with-preloader',
					'type'       => 'switcher',
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Minify CSS', 'speed-booster-pack' ),
					'id'         => 'cf_css_minify_enable',
					'class'      => 'with-preloader',
					'type'       => 'switcher',
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Minify HTML', 'speed-booster-pack' ),
					'id'         => 'cf_html_minify_enable',
					'class'      => 'with-preloader',
					'type'       => 'switcher',
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Minify JS', 'speed-booster-pack' ),
					'id'         => 'cf_js_minify_enable',
					'class'      => 'with-preloader',
					'type'       => 'switcher',
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Browser Cache TTL', 'speed-booster-pack' ),
					'id'         => 'cf_browser_cache_ttl',
					'class'      => 'with-preloader',
					'type'       => 'select',
					'options'    => [
						0        => __( 'Respect Existing Headers', 'speed-booster-pack' ),
						1800     => __( '30 minutes', 'speed-booster-pack' ),
						3600     => __( '1 hour', 'speed-booster-pack' ),
						7200     => __( '2 hours', 'speed-booster-pack' ),
						10800    => __( '3 hours', 'speed-booster-pack' ),
						14400    => __( '4 hours', 'speed-booster-pack' ),
						18000    => __( '5 hours', 'speed-booster-pack' ),
						28800    => __( '8 hours', 'speed-booster-pack' ),
						43200    => __( '12 hours', 'speed-booster-pack' ),
						57600    => __( '16 hours', 'speed-booster-pack' ),
						72000    => __( '20 hours', 'speed-booster-pack' ),
						86400    => __( '1 day', 'speed-booster-pack' ),
						172800   => __( '2 days', 'speed-booster-pack' ),
						259200   => __( '3 days', 'speed-booster-pack' ),
						345600   => __( '4 days', 'speed-booster-pack' ),
						432000   => __( '5 days', 'speed-booster-pack' ),
						691200   => __( '8 days', 'speed-booster-pack' ),
						1382400  => __( '16 days', 'speed-booster-pack' ),
						2073600  => __( '24 days', 'speed-booster-pack' ),
						2678400  => __( '1 month', 'speed-booster-pack' ),
						5356800  => __( '2 months', 'speed-booster-pack' ),
						16070400 => __( '6 months', 'speed-booster-pack' ),
						31536000 => __( '1 year', 'speed-booster-pack' ),
					],
					'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				],
				[
					'type'    => 'content',
					'content' => '
				    <span>
				    	<a href="#" class="button button-small sbp-cloudflare-test">' . __( 'Test Cloudflare connection', 'speed-booster-pack' ) . '<span class="sbp-cloudflare-spinner"></span></a>
				    	<span class="sbp-cloudflare-fetching">' . __( 'Fetching Cloudflare settings...', 'speed-booster-pack' ) . '</span>
			        </span>
				    <span class="sbp-cloudflare-info-text sbp-cloudflare-incorrect" style="color:red; vertical-align: middle;"><i class="fa fa-exclamation-triangle"></i> ' . __( 'Your Cloudflare credentials are incorrect.', 'speed-booster-pack' ) . '</span>
				    <span class="sbp-cloudflare-info-text sbp-cloudflare-connection-issue" style="color:red; vertical-align: middle;"><i class="fa fa-exclamation-triangle"></i> ' . __( 'Error occured while connecting to Cloudflare.', 'speed-booster-pack' ) . '</span>
				    <span class="sbp-cloudflare-info-text sbp-cloudflare-correct" style="color:green; vertical-align: middle;"><i class="fa fa-check-circle"></i> ' . __( 'Your Cloudflare credentials are correct.', 'speed-booster-pack' ) . '</span>
				    <span class="sbp-cloudflare-info-text sbp-cloudflare-warning" style="color:orange; vertical-align: middle;"><i class="fa fa-exclamation-circle"></i> ' . __( 'Enter your Cloudflare credentials and save settings to see CloudFlare options.', 'speed-booster-pack' ) . '</span>
				  ',
				],
			];
			$cloudflare_transient = get_transient( 'sbp_cloudflare_status' );

			if ( '0' === $cloudflare_transient ) {
				array_splice( $cloudflare_fields,
					1,
					0,
					[
						[
							'type'    => 'submessage',
							'style'   => 'danger',
							'content' => __( 'Your Cloudflare credentials are incorrect.', 'speed-booster-pack' ),
						],
					] );
			}
			/* End Of Cloudflare Fields */

			/* Begin Of Sucuri Fields */
			$sucuri_fields = [
				[
					'title' => __( 'Sucuri', 'speed-booster-pack' ),
					'type'  => 'subheading',
				],
				[
					'title' => __( 'Connect to Sucuri', 'speed-booster-pack' ),
					'id'    => 'sucuri_enable',
					'type'  => 'switcher',
					'desc'  => sprintf( __( 'When you connect your Sucuri account, you\'ll be able to clear your Sucuri cache via your admin bar. Plus, every time %1$s Cache\'s cache is cleared, Sucuri\'s cache will be cleared as well.', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
				],
				[
					'title' => __( 'Sucuri API key', 'speed-booster-pack' ),
					'id'    => 'sucuri_api',
					'type'  => 'text',
				],
				[
					'title' => __( 'Sucuri API Secret', 'speed-booster-pack' ),
					'id'    => 'sucuri_secret',
					'type'  => 'text',
				],
			];
			/* End Of Sucuri Fields */

			$proxy_fields = array_merge( [
				[
					'title' => __( 'CDN', 'speed-booster-pack' ),
					'type'  => 'subheading',
				],
				[
					'title'    => __( 'Enable CDN', 'speed-booster-pack' ),
					'id'       => 'cdn_url',
					'class'    => 'cdn-url',
					'type'     => 'text',
					'before'   => 'http(s)://&nbsp;',
					'after'    => '&nbsp;/',
					'desc'     => __( 'Rewrites all asset URLs with the specified CDN domain. Enter the CDN domain without a protocol or a trailing slash; a relative protocol will be automatically added to all changed asset URLs.', 'speed-booster-pack' ),
					'sanitize' => 'sbp_clear_cdn_url',
				],
				[
					'title' => __( 'Included Directories', 'speed-booster-pack' ),
					'id'    => 'cdn_includes',
					'type'  => 'code_editor',
					'desc'  => __( 'Anything other than WordPress\'s existing directories should be entered here to be rewritten with the CDN domain. Separated by new lines.', 'speed-booster-pack' ),
				],
				[
					'title' => __( 'Excluded Extensions', 'speed-booster-pack' ),
					'id'    => 'cdn_excludes',
					'type'  => 'code_editor',
					'desc'  => __( 'If you want to exclude certain file types, enter the extensions here. Separated by new lines.', 'speed-booster-pack' ),
				],
			],
				$cloudflare_fields,
				$sucuri_fields );
			CSF::createSection(
				$prefix,
				array(
					'title'  => __( 'CDN & Proxy', 'speed-booster-pack' ),
					'id'     => 'cdn_proxy',
					'icon'   => 'fa fa-directions',
					'fields' => $proxy_fields,
				)
			);
			/* END Section: CDN & Proxy */

			/* BEGIN Section: Optimize CSS */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Optimize CSS', 'speed-booster-pack' ),
					'id'     => 'css',
					'icon'   => 'fa fa-palette',
					'fields' => [

						[
							/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
							'title'   => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Optimize CSS', 'speed-booster-pack' ),
							'id'      => 'module_css',
							'class'   => 'module-css',
							'type'    => 'switcher',
							'label'   => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
							'default' => true,
						],
						[
							'type'  => 'subheading',
							'title' => 'Critical CSS',
						],
						[
							'id'         => 'enable_criticalcss',
							'title'      => __( 'Enable', 'speed-booster-pack' ) . ' ' . __( ' Critical CSS', 'speed-booster-pack' ),
							'type'       => 'switcher',
							'default'    => false,
							'desc'       => sprintf( __( 'Critical CSS is a method to optimize CSS delivery, %1$srecommended by Google%2$s. It allows you to defer all your CSS files and inline the styles of your content above the fold. You can generate critical CSS needed for your website %3$susing a tool like this%4$s and paste them below.', 'speed-booster-pack' ), '<a href="https://web.dev/extract-critical-css/" rel="external noopener" target="_blank">', '</a>', '<a href="https://www.sitelocity.com/critical-path-css-generator" rel="external noopener" target="_blank">', '</a>' ),
							'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						],
						[
							'id'         => 'criticalcss_default',
							'type'       => 'code_editor',
							'before'     => __( '<h3>Default Critical CSS</h3>', 'speed-booster-pack' ),
							'sanitize'   => 'sbp_sanitize_strip_tags',
							'desc'       => sprintf( __( 'This CSS block will be injected into all pages if there\'s no critical CSS blocks with higher priority. %1$sLearn more about the template hierarchy of WordPress.%2$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/themes/basics/template-hierarchy/" rel="external noopener" target="_blank">', '</a>' ),
							'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
							'settings'   => [ 'lineWrapping' => true ],
						],
						[
							'id'         => 'criticalcss_codes',
							'type'       => 'accordion',
							'title'      => '',
							'sanitize'   => 'sbp_sanitize_strip_tags',
							'accordions' => [
								[
									'title'  => 'is_front_page',
									'fields' => [
										[
											'id'       => 'is_front_page',
											'type'     => 'code_editor',
											// Z_TODO: Edit the following description.
											'desc'     => sprintf( __( 'This CSS block will be injected into the front page of your website. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_front_page/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_front_page()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_home',
									'fields' => [
										[
											'id'       => 'is_home',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into the blog homepage of your website. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_home/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_home()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_single',
									'fields' => [
										[
											'id'       => 'is_single',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into all single posts. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_single/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_single()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_page',
									'fields' => [
										[
											'id'       => 'is_page',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into all static pages. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_page/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_page()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_category',
									'fields' => [
										[
											'id'       => 'is_category',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into all category archive pages. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_category/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_category()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_tag',
									'fields' => [
										[
											'id'       => 'is_tag',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into all tag archive pages. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_tag/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_tag()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
								[
									'title'  => 'is_archive',
									'fields' => [
										[
											'id'       => 'is_archive',
											'type'     => 'code_editor',
											'desc'     => sprintf( __( 'This CSS block will be injected into all archive pages. %1$s%2$s%3$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/reference/functions/is_archive/" rel="external noopener" target="_blank">', sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_archive()</code>' ), '</a>' ),
											'settings' => [ 'lineWrapping' => true ],
										],
									],
								],
							],
							'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						],
						[
							'title'      => __( 'Remove critical CSS after onload', 'speed-booster-pack' ),
							'id'         => 'remove_criticalcss',
							'desc'       => __( 'Remove critical CSS the onload event fires on browser. Enable this only if you\'re having styling issues when the pages finish loading.', 'speed-booster-pack' ),
							'type'       => 'switcher',
							'default'    => true,
							'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						],
						[
							'type'  => 'subheading',
							'title' => __( 'Inline & Minify CSS', 'speed-booster-pack' ),
						],
						[
							'title'      => __( 'Inline all CSS', 'speed-booster-pack' ),
							'id'         => 'css_inline',
							'type'       => 'switcher',
							'desc'       => __( 'Inlines all CSS files into the HTML output. Useful for lightweight designs but might be harmful for websites with over 500KB of total CSS.', 'speed-booster-pack' ),
							'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Minify all inlined CSS', 'speed-booster-pack' ),
							'id'         => 'css_minify',
							'type'       => 'switcher',
							'desc'       => __( 'Minifies the already inlined CSS.', 'speed-booster-pack' ),
							'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'CSS exclusions', 'speed-booster-pack' ),
							'id'         => 'css_exclude',
							'class'      => 'css-exclude',
							'type'       => 'code_editor',
							'desc'       => __( 'If your design breaks after enabling the CSS options above, you can exclude CSS file URLs here. One rule per line.', 'speed-booster-pack' ),
							'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						],
					],
				]
			);
			/* END Section: Optimize CSS */

			/* BEGIN Section: Assets */
			$asset_fields = [
				[
					/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
					'title'   => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Assets', 'speed-booster-pack' ),
					'id'      => 'module_assets',
					'type'    => 'switcher',
					'label'   => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					'default' => true,
				],
				[
					'title'      => __( 'Minify HTML', 'speed-booster-pack' ),
					'id'         => 'minify_html',
					'type'       => 'switcher',
					'desc'       => __( 'Removes all whitespace characters from the HTML output, minimizing the HTML size.', 'speed-booster-pack' ),
					'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'Optimize Google Fonts', 'speed-booster-pack' ),
					'id'         => 'optimize_gfonts',
					'type'       => 'switcher',
					'desc'       => __( 'Combines all Google Fonts URLs into a single URL and optimizes loading of that URL.', 'speed-booster-pack' ),
					'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				]
			];

			$should_disable_lazyload = sbp_should_disable_feature( 'lazyload' );
			if ( $should_disable_lazyload ) {
				$asset_fields = array_merge( $asset_fields, [
					[
						'type'    => 'submessage',
						'style'   => 'success',
						'class'   => 'hosting-warning',
						'content' => sprintf( __( 'Since you\'re using %s, lazyload feature is completely disabled to ensure compatibility with internal lazyload system of %s.' ), $should_disable_lazyload, $should_disable_lazyload ),
					],
				] );
			}

			$asset_fields = array_merge( $asset_fields, [
				[
					'title'      => __( 'Lazy load media', 'speed-booster-pack' ),
					'id'         => 'lazyload',
					'type'       => 'switcher',
					'desc'       => __( 'Defers loading of images, videos and iframes to page onload.', 'speed-booster-pack' ),
					'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
					'class'      => $should_disable_lazyload ? ' inactive-section' : null,
				],
				[
					'title'      => __( 'Lazy load exclusions', 'speed-booster-pack' ),
					'id'         => 'lazyload_exclude',
					'class'      => 'lazyload-exclude' . ( $should_disable_lazyload ? ' inactive-section' : null ),
					'type'       => 'code_editor',
					'desc'       => __( 'Excluding important images at the top of your pages (like your logo and such) is a good idea. One URL per line.', 'speed-booster-pack' ),
					'dependency' => [ 'module_assets|lazyload', '==|==', '1|1', '', 'visible|visible' ],
					'sanitize'   => 'sbp_clear_http',
				],
				[
					'title'      => __( 'Optimize JavaScript', 'speed-booster-pack' ),
					'id'         => 'js_optimize',
					'desc'       => __( 'Loads JavaScript better, avoiding render blocking issues. Moving all tags to the footer (before the &lt;/body&gt; tag) causes less issues but if you know what you\'re doing, deferring JS tags makes your website work faster. Use the exclusions list to keep certain scripts from breaking your site!', 'speed-booster-pack' ),
					'type'       => 'button_set',
					'options'    => [
						'off'   => __( 'Off', 'speed-booster-pack' ),
						'defer' => __( 'Defer', 'speed-booster-pack' ),
						'move'  => __( 'Move to footer', 'speed-booster-pack' ),
					],
					'default'    => 'off',
					'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				],
				[
					'title'      => __( 'JavaScript exclusions', 'speed-booster-pack' ),
					'id'         => 'js_exclude',
					'class'      => 'js-exclude',
					'type'       => 'code_editor',
					'desc'       => __( 'If you encounter JavaScript errors on your error console, you can exclude JS file URLs or parts of inline JS here. One rule per line. Since each line will be taken as separate exclude rules, don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
					'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
					'dependency' => [ 'module_assets|js_optimize', '==|!=', '1|off', '', 'visible|visible' ],
				],
				[
					'title'      => __( 'Preload assets', 'speed-booster-pack' ),
					'id'         => 'preboost',
					'class'      => 'preboost',
					'type'       => 'fieldset',
					'fields'     => [
						[
							'id'    => 'preboost_enable',
							'type'  => 'switcher',
							'label' => __( 'Enable preloading of the assets specified below.', 'speed-booster-pack' ),
						],
						[
							'id'   => 'preboost_include',
							'type' => 'code_editor',
							'desc' => __( 'Enter full URLs of the assets you want to preload. One URL per line.', 'speed-booster-pack' ),
							'dependency' => [ 'preboost_enable', '==', '1', '', 'visible' ],
						],
					],
					'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				],
			] );

			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Assets', 'speed-booster-pack' ),
					'id'     => 'assets',
					'icon'   => 'fa fa-code',
					'fields' => $asset_fields,
				]
			);
			/* END Section: Assets */

			/* BEGIN Section: Special */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Special', 'speed-booster-pack' ),
					'id'     => 'special',
					'icon'   => 'fa fa-bolt',
					'fields' => [

						[
							/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
							'title'   => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Special', 'speed-booster-pack' ),
							'id'      => 'module_special',
							'class'   => 'module-special',
							'type'    => 'switcher',
							'label'   => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
							'default' => true,
						],
						[
							'title'      => __( 'Localize Google Analytics & Google Tag Manager', 'speed-booster-pack' ),
							'id'         => 'localize_tracking_scripts',
							'type'       => 'switcher',
							'desc'       => __( 'Searches for Google Analytics or Google Tag Manager scripts (analytics.js, gtag.js or gtm.js) in your page sources, and replaces them with a locally saved script.', 'speed-booster-pack' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'                  => __( 'Custom code manager', 'speed-booster-pack' ),
							'id'                     => 'custom_codes',
							'type'                   => 'group',
							'before'                 => '<p>' . __( 'Code blocks added with this tool can be loaded in the header, the footer and can even be delayed.', 'speed-booster-pack' ) . '</p>',
							'accordion_title_number' => true,
							'accordion_title_auto'   => false,
							'sanitize'               => false,
							'fields'                 => [
								[
									'id'       => 'custom_codes_item',
									'type'     => 'code_editor',
									'before'   => '&lt;script&gt;',
									'after'    => '&lt;/script&gt;',
									/* translators: %s = script tag  */
									'desc'     => sprintf( __( 'Paste the inline JavaScript here. DON\'T include the %s tags or else you might break it!', 'speed-booster-pack' ), '<code>&lt;script&gt;</code>' ),
									'settings' => [ 'lineWrapping' => true ],
								],
								[
									'title'   => __( 'Placement', 'speed-booster-pack' ),
									'id'      => 'custom_codes_place',
									'desc'    => __( 'Set this to "Footer" to place the code before &lt;/body&gt;, or "Header" to place it before &lt;/head&gt;.', 'speed-booster-pack' ),
									'type'    => 'button_set',
									'options' => [
										'footer' => __( 'Footer', 'speed-booster-pack' ),
										'header' => __( 'Header', 'speed-booster-pack' ),
									],
									'default' => 'footer',
								],
								[
									'title'   => __( 'Loading method', 'speed-booster-pack' ),
									'id'      => 'custom_codes_method',
									'desc'    => __( 'Set this to "onload" to defer the code to page onload, or "4-second delay" to defer it to four seconds after onload. When in doubt, set it to "Normal".', 'speed-booster-pack' ),
									'type'    => 'button_set',
									'options' => [
										'normal'  => __( 'Normal', 'speed-booster-pack' ),
										'onload'  => __( 'onload', 'speed-booster-pack' ),
										'delayed' => __( '4-second delay', 'speed-booster-pack' ),
									],
									'default' => 'normal',
								],
							],
							'dependency'             => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'Jetpack: ' . __( 'Dequeue the devicepx script', 'speed-booster-pack' ),
							'id'         => 'jetpack_dequeue_devicepx',
							'type'       => 'switcher',
							/* translators: %s = devicepx-jetpack.js  */
							'desc'       => sprintf( __( 'The %s file replaces images served via Jetpack\'s Photon CDN with their higher-quality equivalents. If you don\'t need this feature, you can dequeue the file and save an extra HTTP request and an extra DNS connection.', 'speed-booster-pack' ), '<code>devicepx-jetpack.js</code>' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'WooCommerce: ' . __( 'Disable cart fragments', 'speed-booster-pack' ),
							'id'         => 'woocommerce_disable_cart_fragments',
							'type'       => 'switcher',
							/* translators: %s = cart-fragments.js  */
							'desc'       => sprintf( __( 'Dequeues the %s file if the visitor\'s cart is empty,  preventing an unnecessary and slow AJAX request.', 'speed-booster-pack' ), '<code>cart-fragments.js</code>' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'WooCommerce: ' . __( 'Optimize non-WooCommerce pages', 'speed-booster-pack' ),
							'id'         => 'woocommerce_optimize_nonwc_pages',
							'type'       => 'switcher',
							'desc'       => __( 'Prevents loading of WooCommerce-related scripts and styles on non-WooCommerce pages.', 'speed-booster-pack' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'WooCommerce: ' . __( 'Disable password strength meter', 'speed-booster-pack' ),
							'id'         => 'woocommerce_disable_password_meter',
							'type'       => 'switcher',
							'desc'       => __( 'Disables the password strength meter for password inputs during a WooCommerce checkout.', 'speed-booster-pack' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'type'  => 'subheading',
							'title' => __( 'PageSpeed Tricker', 'speed-booster-pack' ),
						],
						[
							'type'    => 'submessage',
							'style'   => 'warning',
							/* translators: %s = Speed Booster Pack  */
							'content' => sprintf( __( 'This experimental feature is here to show you how easy it is to get top scores with Google PageSpeed (or Lighthouse to be exact), and how meaningless it is to obsess over these metrics. Google doesn\'t have a guideline about any penalties for websites manipulating Lighthouse metrics, but that doesn\'t mean they won\'t. Thus, take this feature as a joke and use only to experiment. By activating the feature, you acknowledge that you have sole responsibility for any kind of effects on your website.', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							/* translators: %s = PageSpeed Tricker  */
							'title' => sprintf( __( 'Enable %s', 'speed-booster-pack' ), 'PageSpeed Tricker' ),
							'id'    => 'pagespeed_tricker',
							'type'  => 'switcher',
						],
					],
				]
			);
			/* END Section: Special */

			/* BEGIN Section: Tweaks */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Tweaks', 'speed-booster-pack' ),
					'id'     => 'tweaks',
					'icon'   => 'fa fa-sliders-h',
					'fields' => [


						[
							/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
							'title'   => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Tweaks', 'speed-booster-pack' ),
							'id'      => 'module_tweaks',
							'class'   => 'module-tweaks',
							'type'    => 'switcher',
							'label'   => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
							'default' => true,
						],
						[
							'title'      => __( 'Enable instant.page', 'speed-booster-pack' ),
							'id'         => 'instant_page',
							'type'       => 'switcher',
							/* translators: %s = hyperlink to the instant.page website  */
							'desc'       => sprintf( __( 'Enqueues %s (locally), which basically boosts the speed of navigating through your whole website.', 'speed-booster-pack' ), '<a href="https://instant.page/" rel="external noopener" target="_blank">instant.page</a>' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Trim query strings', 'speed-booster-pack' ),
							'id'         => 'trim_query_strings',
							'type'       => 'switcher',
							'desc'       => __( 'Removes the query strings (characters that come after the question mark) at the end of enqueued asset URLs.', 'speed-booster-pack' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Disable self pingbacks', 'speed-booster-pack' ),
							'id'         => 'disable_self_pingbacks',
							'type'       => 'switcher',
							'desc'       => __( 'Disabling this will prevent pinging this website to ping itself (its other posts etc.) during publishing, which will improve the speed of publishing posts or pages.', 'speed-booster-pack' ),
							'default'    => true,
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue emoji scripts', 'speed-booster-pack' ),
							'id'         => 'dequeue_emoji_scripts',
							'type'       => 'switcher',
							'desc'       => __( 'Removes the unnecessary emoji scripts from your website front-end. Doesn\'t remove emojis, don\'t worry.', 'speed-booster-pack' ),
							'default'    => true,
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue the post embed script', 'speed-booster-pack' ),
							'id'         => 'disable_post_embeds',
							'type'       => 'switcher',
							'desc'       => __( 'Disables embedding posts from WordPress-based websites (including your own) which converts URLs into heavy iframes.', 'speed-booster-pack' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue Dashicons CSS', 'speed-booster-pack' ),
							'id'         => 'dequeue_dashicons',
							'type'       => 'switcher',
							/* translators: 1. <strong> 2. </strong>  */
							'desc'       => sprintf( __( 'Removes dashicons.css from your front-end for your visitors. Since Dashicons are required for the admin bar, %1$sdashicons.css will not be removed for logged-in users%2$s.', 'speed-booster-pack' ), '<strong>', '</strong>' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue Gutenberg CSS', 'speed-booster-pack' ),
							'id'         => 'dequeue_block_library',
							'type'       => 'switcher',
							'desc'       => __( 'If you\'re not using the block editor (Gutenberg) in your posts/pages, this is a safe setting to enable.', 'speed-booster-pack' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Heartbeat settings', 'speed-booster-pack' ),
							'id'         => 'heartbeat_settings',
							/* translators: 1. opening tag for the hyperlink to the Heartbeat API 2. closing tag for the hyperlink  */
							'desc'       => sprintf( __( 'Controls the %1$sHeartbeat API%2$s, which checks if the user is still logged-in or not every 15 to 60 seconds.', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/plugins/javascript/heartbeat-api/" rel="external noopener" target="_blank">', '</a>' ) . '<br />' . __( '"Enabled" lets it run like usual, "Optimized" sets both intervals to 120 seconds, and "Disabled" disables the Heartbeat API completely.', 'speed-booster-pack' ),
							'type'       => 'button_set',
							'options'    => [
								'enabled'   => __( 'Enabled', 'speed-booster-pack' ),
								'optimized' => __( 'Optimized', 'speed-booster-pack' ),
								'disabled'  => __( 'Disabled', 'speed-booster-pack' ),
							],
							'default'    => 'enabled',
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Limit post revisions', 'speed-booster-pack' ),
							'id'         => 'post_revisions',
							'type'       => 'spinner',
							'unit'       => __( 'revisions', 'speed-booster-pack' ),
							/* translators: 1. opening tag for the hyperlink to the support article for revisions 2. closing tag for the hyperlink  */
							'desc'       => sprintf( __( 'Limits the number of %1$spost revisions%2$s saved for each post. Keeping 3 or 5 revisions for each post should be enough for most sites. Set it to 0 to disable post revisions completely.', 'speed-booster-pack' ), '<a href="https://wordpress.org/support/article/revisions/" rel="external noopener" target="_blank">', '</a>' ) . '<br />'
							                /* translators: 1. WP_POST_REVISIONS 2. wp-config.php  */
							                . sprintf( __( 'Note: If the %1$s constant is set in your %2$s file, it will override this setting.', 'speed-booster-pack' ), '<code>WP_POST_REVISIONS</code>', '<code>wp-config.php</code>' ),
							'sanitize'   => 'absint',
							'default'    => '99',
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Autosave interval', 'speed-booster-pack' ),
							'id'         => 'autosave_interval',
							'type'       => 'spinner',
							'min'        => '1',
							'unit'       => __( 'minutes', 'speed-booster-pack' ),
							'desc'       => __( 'Sets how frequent the content is saved automatically while editing. WordPress sets it to 1 minute by default, and you can\'t set it to a shorter interval.', 'speed-booster-pack' ) . '<br />'
							                /* translators: 1. AUTOSAVE_INTERVAL 2. wp-config.php  */
							                . sprintf( __( 'Note: If the %1$s constant is set in your %2$s file, it will override this setting.', 'speed-booster-pack' ), '<code>AUTOSAVE_INTERVAL</code>', '<code>wp-config.php</code>' ),
							'sanitize'   => 'sbp_posabs',
							'default'    => '1',
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							/* translators: %s = <head>  */
							'title'      => sprintf( __( 'Declutter %s', 'speed-booster-pack' ), '<code>&lt;head&gt;</code>' ),
							'id'         => 'declutter_head',
							'class'      => 'declutter-head',
							'type'       => 'fieldset',
							'before'     => '<p>' . __( 'Enabling these options removes corresponding elements from your HTML source code. If you don\'t know what they are, it\'s probably safer for you to keep them disabled.', 'speed-booster-pack' ) . '</p>',
							'fields'     => [

								[
									'title' => __( 'Shortlinks', 'speed-booster-pack' ),
									'id'    => 'declutter_shortlinks',
									'type'  => 'switcher',
									'label' => '<link rel=\'shortlink\' href=\'...\' />',
								],
								[
									'title' => __( 'Next/previous posts links', 'speed-booster-pack' ),
									'id'    => 'declutter_adjacent_posts_links',
									'type'  => 'switcher',
									'label' => "<link rel='next (or prev)' title='...' href='...' />",
								],
								[
									'title' => __( 'WLW Manifest link', 'speed-booster-pack' ),
									'id'    => 'declutter_wlw',
									'type'  => 'switcher',
									'label' => '<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="..." />',
								],
								[
									'title' => __( 'Really Simple Discovery (RSD) link', 'speed-booster-pack' ),
									'id'    => 'declutter_rsd',
									'type'  => 'switcher',
									'label' => '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="..." />',
								],
								[
									'title' => __( 'REST API links', 'speed-booster-pack' ),
									'id'    => 'declutter_rest_api_links',
									'type'  => 'switcher',
									'label' => "<link rel='https://api.w.org/' href='...' />",
								],
								[
									'title' => __( 'RSS feed links', 'speed-booster-pack' ),
									'id'    => 'declutter_feed_links',
									'type'  => 'switcher',
									'label' => '<link rel="alternate" type="application/rss+xml" title="..." href="..." />',
								],
								[
									'title' => __( 'WordPress version', 'speed-booster-pack' ),
									'id'    => 'declutter_wp_version',
									'type'  => 'switcher',
									'label' => '<meta name="generator" content="WordPress X.X" />',
								],
							],
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],

					],
				]
			);
			/* END Section: Tweaks */

			/* BEGIN Section: Tools */
			CSF::createSection(
				$prefix,
				array(
					'title'  => __( 'Tools', 'speed-booster-pack' ),
					'id'     => 'tools',
					'icon'   => 'fa fa-tools',
					'fields' => array(
						array(
							'type'    => 'subheading',
							/* translators: %s = Speed Booster Pack  */
							'content' => sprintf( __( 'Backup %s Settings', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						),
						array(
							'id'    => 'backup',
							'type'  => 'backup',
							'title' => '',
						),

					),
				)
			);
			/* END Section: Tools */

			/* BEGIN Section: About */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'About', 'speed-booster-pack' ),
					'id'     => 'about',
					'class'  => 'about',
					'icon'   => 'fa fa-info-circle',
					'fields' => [

						[
							'type'    => 'heading',
							/* translators: %s = Optimocha  */
							'content' => sprintf( __( 'About %s', 'speed-booster-pack' ), SBP_OWNER_NAME ),
						],
						[
							'type'    => 'content',
							/* translators: 1. Optimocha 2. Speed Booster Pack  */
							'content' => '<p>' . sprintf( __( 'We are %1$s, a small team of speed optimization experts. Along with hundreds of websites we finished optimizing, we acquired %2$s in 2019 and we\'re working hard to make this plugin the best speed optimization plugin for WordPress ever since!', 'speed-booster-pack' ), SBP_OWNER_NAME, SBP_PLUGIN_NAME ) . '</p><ul><li><a href="https://optimocha.com/speed-optimization-for-wordpress/" rel="external noopener" target="_blank">' .
							             __( 'Visit our website', 'speed-booster-pack' ) . '</a></li><li><a href="https://optimocha.com/" rel="external noopener" target="_blank">' .
							             __( 'Learn more about our tailored Complete Speed Optimization services', 'speed-booster-pack' ) . '</a></li><li><a href="https://optimocha.com/contact/" rel="external noopener" target="_blank">' .
							             __( 'Contact us', 'speed-booster-pack' ) . '</a></li></ul>',
						],
						[
							'type'    => 'subheading',
							'content' => __( 'Special thanks', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							/* translators: 1. Speed Booster Pack 2. link to the speedboosterpack.com contact form 3. link to the GitHub page  */
							'content' => __( 'We made use of the following libraries and frameworks in Speed Booster Pack, so we\'d like to give them a shout out and thank them:', 'speed-booster-pack' ) .
							             '<ul>
											<li><a href="https://instant.page/" rel="external noopener" target="_blank">instant.page</a></li>
											<li><a href="https://github.com/verlok/vanilla-lazyload" rel="external noopener" target="_blank">LazyLoad by Andrea Verlicchi</a></li>
											<li><a href="https://codestarframework.com/" rel="external noopener" target="_blank">CodeStar Framework</a></li>
										 </ul>',
						],
						[
							'title'   => __( 'Allow external notices', 'speed-booster-pack' ),
							'id'      => 'enable_external_notices',
							'type'    => 'switcher',
							'label'   => __( '', 'speed-booster-pack' ),
							/* translators: %s = hyperlink to speedboosterpack.com  */
							'desc'    => sprintf( __( 'Fetches notices from %s, and shows them in a non-obtrusive manner. We intend to send essential notices only, and we hate spam as much as you do, but if you don\'t want to get them, you can disable this setting.', 'speed-booster-pack' ), '<a href="https://speedboosterpack.com/" rel="external noopener" target="_blank">speedboosterpack.com</a>' ),
							'default' => true,
						],
					],
				]
			);
			/* END Section: About */
		}
	}

	public function add_admin_bar_links( WP_Admin_Bar $admin_bar ) {

		if ( current_user_can( 'manage_options' ) ) {

			$admin_bar->add_menu( [
				'id'    => 'speed_booster_pack',
				'title' => 'Speed Booster',
				'href'  => admin_url( 'admin.php?page=sbp-settings' ),
				'meta'  => [
					'target' => '_self',
					'html'   => '<style>#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item{background:url("' . SBP_URL . 'admin/images/icon.svg") no-repeat 5px center;padding-left:25px;filter: brightness(0.7) sepia(1) hue-rotate(50deg) saturate(1.5);}#wpadminbar #wp-admin-bar-speed_booster_pack .ab-item:hover{color:white;}</style>',
				],
			] );

			if ( sbp_get_option( 'module_caching' ) && ! sbp_should_disable_feature( 'caching' ) ) {
				// Cache clear
				$clear_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cache' ), 'sbp_clear_total_cache', 'sbp_nonce' );
				$sbp_admin_menu  = [
					'id'     => 'sbp_clear_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cache', 'speed-booster-pack' ),
					'href'   => $clear_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );

				// Cache warmup
				$warmup_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_warmup_cache' ), 'sbp_warmup_cache', 'sbp_nonce' );
				$sbp_admin_menu   = [
					'id'     => 'sbp_warmup_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Warmup Cache', 'speed-booster-pack' ),
					'href'   => $warmup_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'localize_tracking_scripts' ) ) {
				$clear_tracking_scripts_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_refresh_localized_analytics' ), 'sbp_refresh_localized_analytics', 'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_localized_scripts',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Localized Scripts', 'speed-booster-pack' ),
					'href'   => $clear_tracking_scripts_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( SBP_Cloudflare::is_cloudflare_active() ) {
				$clear_cloudflare_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cloudflare_cache' ), 'sbp_clear_cloudflare_cache', 'sbp_nonce' );
				$sbp_admin_menu             = [
					'id'     => 'sbp_clear_cloudflare_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Cloudflare Cache', 'speed-booster-pack' ),
					'href'   => $clear_cloudflare_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}

			if ( sbp_get_option( 'sucuri_enable' ) ) {
				$clear_sucuri_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_sucuri_cache' ), 'sbp_clear_sucuri_cache', 'sbp_nonce' );
				$sbp_admin_menu         = [
					'id'     => 'sbp_clear_sucuri_cache',
					'parent' => 'speed_booster_pack',
					'title'  => __( 'Clear Sucuri Cache', 'speed-booster-pack' ),
					'href'   => $clear_sucuri_cache_url,
				];

				$admin_bar->add_node( $sbp_admin_menu );
			}
		}
	}

	public function set_notices() {
		if ( SBP_Utils::is_plugin_active( 'autoptimize/autoptimize.php' ) && sbp_get_option( 'enable_criticalcss' ) && get_option( 'autoptimize_css_defer' ) && sbp_get_option( 'module_css' ) ) {
			SBP_Notice_Manager::display_notice( 'autoptimize_inline_defer_css', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . sprintf( __( 'It looks like Autoptimize is active on your site. Autoptimize\'s "defer and inline css" feature may cause conflict with %s.', 'speed-booster-pack' ), SBP_PLUGIN_NAME ) . '</p>', 'warning' );
		}

		// Set Sucuri Notice
		if ( $transient_value = get_transient( 'sbp_clear_sucuri_cache' ) ) {
			$notice_message = $transient_value == '1' ? __( 'Sucuri cache cleared.', 'speed-booster-pack' ) : __( 'Error occured while clearing Sucuri cache. ', 'speed-booster-pack' ) . get_transient( 'sbp_sucuri_error' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_clear_sucuri_cache', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>', $notice_type, true, 'flash' );
		}

		// Set Cloudflare Notice
		if ( $transient_value = get_transient( 'sbp_notice_cloudflare' ) ) {
			$notice_message = $transient_value == '1' ? __( 'Cloudflare cache cleared.', 'speed-booster-pack' ) : __( 'Error occured while clearing Cloudflare cache. Possible reason: Credentials invalid.', 'speed-booster-pack' );
			$notice_type    = $transient_value == '1' ? 'success' : 'error';
			SBP_Notice_Manager::display_notice( 'sbp_notice_cloudflare', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( $notice_message, 'speed-booster-pack' ) . '</p>', $notice_type, true, 'flash' );
		}

		// Set Cache Clear Notice
		if ( get_transient( 'sbp_notice_cache' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_notice_cache', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache cleared.', 'speed-booster-pack' ) . '</p>', 'success', true, 'flash' );
		}

		// Set Localizer Cache Clear Notice
		if ( get_transient( 'sbp_notice_tracker_localizer' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_notice_tracker_localizer', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Localized scripts are cleared.', 'speed-booster-pack' ) . '</p>', 'success', true, 'flash' );
		}

		// Warmup Notice
		if ( get_transient( 'sbp_warmup_started' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_warmup_started', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Cache warmup started.', 'speed-booster-pack' ) . '</p>', 'success', true, 'recurrent' );
		}

		// Warmup Notice
		if ( get_transient( 'sbp_warmup_complete' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_warmup_complete', '<p><strong>' . SBP_PLUGIN_NAME . ':</strong> ' . __( 'Static cache files created.', 'speed-booster-pack' ) . '</p>', 'info', true, 'recurrent' );
		}

		// WP-Config Inject File Error
		if ( get_transient( 'sbp_wp_config_inject_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_wp_config_inject_error', '<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Can not write plugins/speed-booster-pack/includes/wp-config-options/wp-config-inject.php file. Some ' . SBP_PLUGIN_NAME . ' features may not work. Please check your file permissions.', 'speed-booster-pack' ) . '</p>', 'error', true, 'recurrent' );
		}

		// WP-Config File Error
		if ( get_transient( 'sbp_wp_config_error' ) ) {
			SBP_Notice_Manager::display_notice( 'sbp_wp_config_error', '<p><strong>' . SBP_PLUGIN_NAME . '</strong> ' . __( 'Can not write wp-config.php file. Some ' . SBP_PLUGIN_NAME . ' features may not work. Please check your file permissions.', 'speed-booster-pack' ) . '</p>', 'error', true, 'recurrent' );
		}
	}

	private function initialize_announce4wp() {
		if ( sbp_get_option( 'enable_external_notices' ) ) {
			new Announce4WP_Client( 'speed-booster-pack.php', SBP_PLUGIN_NAME, "sbp", "https://speedboosterpack.com/wp-json/a4wp/v1/" . SBP_VERSION . "/news.json", "toplevel_page_sbp-settings" );
		}
	}
}