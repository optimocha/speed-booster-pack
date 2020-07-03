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
function posabs( $value ) {
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
 * @param $urls
 */
function sanitize_caching_urls( $urls ) {
	$urls = \SpeedBooster\SBP_Utils::explode_lines( $urls );
	foreach ( $urls as &$url ) {
		$url = ltrim( $url, 'https://' );
		$url = ltrim( $url, 'http://' );
		$url = ltrim( $url, '//' );
		$url = rtrim( $url, '/' );
	}

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

	private $cloudflare_warning = false;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    4.0.0
	 */
	public function __construct( $plugin_name, $version ) {

// 		$eben = [];
// add_action( 'csf_sbp_options_save_before', function() use (&$eben) { $eben[] = 'csf_sbp_options_save_before'; } );
// add_action( 'csf_sbp_options_save_after', function() use (&$eben) { $eben[] = 'csf_sbp_options_save_after'; } );
// add_action( 'csf_sbp_options_saved', function() use (&$eben) { $eben[] = 'csf_sbp_options_saved'; } );
// add_action( 'csf_options_before', function()  use (&$eben){ $eben[] = 'csf_options_before'; } );
// add_action( 'csf_options_after', function() use (&$eben) { $eben[] = 'csf_options_after'; } );
// add_action( 'csf_init', function() use (&$eben) { $eben[] = 'csf_init'; } );
// add_action( 'csf_loaded', function() use (&$eben) { $eben[] = 'csf_loaded'; } );
// add_action( 'csf_enqueue', function() use (&$eben) { $eben[] = 'csf_enqueue'; } );
// add_action('shutdown', function( ) use (&$eben) {die(var_dump($eben));});

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_dependencies();
		


		// LAHMACUNTODO: bunu düzeltek
		add_action( 'csf_loaded', '\SpeedBooster\SBP_Cloudflare::check_credentials' );


		add_action( 'csf_sbp_options_save_before', '\SpeedBooster\SBP_Cloudflare::reset_transient' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_Cache::clear_total_cache' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_Cache::options_saved_listener' );

		add_action( 'csf_sbp_options_saved', '\SpeedBooster\SBP_Cache::generate_htaccess' );

		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_links' ], 90 );

		$this->set_flash_notices();

		$this->initialize_announce4wp();

		$this->create_settings_page();
		
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
					'framework_title' => SBP_PLUGIN_NAME . ' <small>by <a href="' . SBP_OWNER_HOME . '" rel="external nofollow noopener">' . SBP_OWNER_NAME . '</a></small>',
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
					'footer_text'             => sprintf( __( 'Thank you for using %1$s! Be sure to %2$sleave a fair review%3$s if you liked our plugin.', 'speed-booster-pack' ), SBP_PLUGIN_NAME, '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external nofollow noopener">', '</a>' ),
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

						/* BEYNTODO: İçeriği yaz!  */

						[
							'type'    => 'heading',
							/* translators: %s = plugin's name  */
							'content' => sprintf( __( 'Welcome to %s!', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							'type'    => 'content',
							'content' => __( 'BEYNTODO', 'speed-booster-pack' ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'Heads up: This plugin is ALWAYS in beta!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => __( 'BEYNTODO', 'speed-booster-pack' ),
						],
						[
							'type'    => 'subheading',
							/* translators: %s = plugin's name  */
							'content' => sprintf( __( 'Benefits of %s', 'speed-booster-pack' ), SBP_PLUGIN_NAME ),
						],
						[
							'type'    => 'content',
							'content' => __( 'BEYNTODO', 'speed-booster-pack' ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'How the features in each tab work', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => __( 'BEYNTODO', 'speed-booster-pack' ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'Upcoming features', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => __( 'BEYNTODO', 'speed-booster-pack' ),
						],
						[
							'type'    => 'callback',
							'function' => 'sbp_newsletter_form',
						],
						[
							'type'    => 'subheading',
							'content' => __( 'That\'s it, enjoy!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => __( 'We really hope that you\'ll enjoy working with our plugin. Always remember that this is a powerful tool, and using powerful tools might hurt you if you\'re not careful. Have fun!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							'content' => __( 'Almost forgot: If you like %1$s, it would mean a lot to us if you gave a fair rating on %2$s, because higher rated plugins show up on more users, meaning that we\'ll have to take better care of %1$s!', 'speed-booster-pack' ),
						],
						[
							'type'    => 'subheading',
							'content' => __( 'If you\'re looking for professional help...', 'speed-booster-pack' ),
						],
						[
							'type'    => 'content',
							/* translators: 1: plugin owner's name (Optimocha) 2: plugin's name (Speed Booster Pack) 3: hyperlink to the owner's website */
							'content' => sprintf( __( 'As %1$s, we like to brag about completing hundreds of tailored speed optimization jobs for different WordPress websites. (Our tailored speed optimization service is actually the source of the know-how that helps %2$s get better and better on every release!) If you\'re willing to invest in speeding up your website, not just with %2$s but as a whole, feel free to contact us on %3$s and benefit from our expertise on speed optimization!', 'speed-booster-pack' ), SBP_OWNER_NAME, SBP_OWNER_NAME, '<a href="' .  SBP_OWNER_HOME . '" rel="external noopener" target="_blank">' . strtolower( SBP_OWNER_NAME ) . '.com</a>' ),
						],


					],
				]
			);
			/* END Section: Dashboard */

			/* BEGIN Section: Caching */
			$cloudflare_fields = [];

			// Cloudflare fields
			// die(var_dump(get_transient( 'sbp_cloudflare_status' )));
			if ( 0 == get_transient( 'sbp_cloudflare_status' ) ) {
				$cloudflare_fields[] = [
					'type'    => 'submessage',
					'style'   => 'danger',
					'content' => __( 'asd', 'speed-booster-pack' ),
				];
			}

			$cloudflare_fields = array_merge( $cloudflare_fields,
				[
					[
						'title' => __( 'Connect to Cloudflare', 'speed-booster-pack' ),
						'id'    => 'cloudflare_enable',
						'type'  => 'switcher',
					],
					[
						'title' => __( 'Cloudflare global API key', 'speed-booster-pack' ),
						'id'    => 'cloudflare_api',
						'type'  => 'text',
					],
					[
						'title' => __( 'Cloudflare email address', 'speed-booster-pack' ),
						'id'    => 'cloudflare_email',
						'type'  => 'text',
					],
					[
						'title' => __( 'Cloudflare zone ID', 'speed-booster-pack' ),
						'id'    => 'cloudflare_zone',
						'type'  => 'text',
					],
				] );

			$cache_fields = [
				[
					'id'    => 'module_caching',
					'class' => 'module-caching',
					'type'  => 'switcher',
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
					'sanitize'   => 'posabs',
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
					'id'         => 'caching_exclude_urls',
					'class'      => 'caching-exclude-urls',
					'type'       => 'code_editor',
					'title'      => __( 'Exclude URLs', 'speed-booster-pack' ),
					'desc'       => __( 'Enter one URL per line to exclude them from caching. Cart and Checkout pages of WooCommerce are always excluded, so you don\'t have to set them in here.', 'speed-booster-pack' ),
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
					'sanitize'   => 'sanitize_caching_urls',
				],
				[
					'id'         => 'caching_include_query_strings',
					'class'      => 'caching-include-query-strings',
					'type'       => 'code_editor',
					'title'      => __( 'Include query strings', 'speed-booster-pack' ),
					'desc'       => __( 'Enter one query string per line to cache URLs with those query strings.', 'speed-booster-pack' ) . '<br />'
					                /* translators: BEYNTODO  */
					                . sprintf( __( 'For example, after adding "foo" to the list, %1$sexample.com/blog-post/?foo=bar%2$s will be cached.', 'speed-booster-pack' ), '<code>', '</code>' ),
					'default'    => 'utm_source',
					'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				],
				[
					'title'  => __( 'Cloudflare integration', 'speed-booster-pack' ),
					'id'     => 'cloudflare',
					'class'  => 'cloudflare',
					'type'   => 'fieldset',
					'fields' => $cloudflare_fields,
				],
			];

			$is_kinsta_active = false;
			if ( isset( $_SERVER['KINSTA_CACHE_ZONE'] ) ) {
				$kinsta_notice    = [
					[
						'type'    => 'submessage',
						'style'   => 'success',
						'class'   => 'kinsta-warning',
						'content' => __( 'Since you\'re using Kinsta, cache feature is completely disabled.', 'speed-booster-pack' ),
					],
				];
				$is_kinsta_active = true;
				$cache_fields     = array_merge( $kinsta_notice, $cache_fields );
			}

			if ( ! $is_kinsta_active && is_multisite() ) {
				$multisite_warning = [
					'type'    => 'submessage',
					'style'   => 'warning',
					'content' => sprintf( __( 'Caching in Speed Booster Pack isn\'t tested with WordPress Multisite, proceed with caution! We\'d appreciate getting feedback from you if you find any bugs: %1$sReport a bug%2$s', 'speed-booster-pack' ), '<a href="https://speedboosterpack.com/contact/?sbp_version=' . SBP_VERSION . '" target="_blank" rel="external noopener">', '</a>' ),
				];

				array_unshift( $cache_fields, $multisite_warning );
			}

			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Caching', 'speed-booster-pack' ),
					'id'     => 'caching',
					'icon'   => 'fa fa-server',
					'class'  => $is_kinsta_active ? 'inactive-section' : '',
					'fields' => $cache_fields,
				]
			);
			/* END Section: Caching */

			/* BEGIN Section: Assets */
			CSF::createSection(
				$prefix,
				[
					'title'  => __( 'Assets', 'speed-booster-pack' ),
					'id'     => 'assets',
					'icon'   => 'fa fa-code',
					'fields' => [

						[
							/* translators: used like "Enable/Disable Caching" where "Caching" is the module name. */
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
							'desc'       => __( 'Combines all Google Fonts URLs into a single URL.', 'speed-booster-pack' ),
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Lazy load images, videos &amp; iframes', 'speed-booster-pack' ),
							'id'         => 'lazyload',
							'type'       => 'switcher',
							'desc'       => __( 'Defers loading of images, videos and iframes to page onload.', 'speed-booster-pack' ),
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Lazy load exclusions', 'speed-booster-pack' ),
							'id'         => 'lazyload_exclude',
							'class'      => 'lazyload-exclude',
							'type'       => 'code_editor',
							'desc'       => __( 'Excluding important images at the top of your pages (like your logo and such) is a good idea. One URL per line.', 'speed-booster-pack' ),
							'dependency' => [ [ 'module_assets', '==', '1' ], [ 'lazyload', '==', '1' ] ],
						],
						[
							'title'      => __( 'Optimize JavaScript', 'speed-booster-pack' ),
							'id'         => 'js_optimize',
							'desc'       => __( 'Handles JavaScript tags to avoid render blocking issues. Moving all tags to the footer (before the &lt;/body&gt; tag) causes less issues but if you know what you\'re doing, deferring JS tags makes your website work faster. Use the exclusions list to keep certain scripts from breaking your site!', 'speed-booster-pack' ),
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
							'default'    => 'js/jquery/jquery.js',
							'dependency' => [ [ 'module_assets', '==', '1' ], [ 'js_optimize', '!=', 'off' ] ],
						],
						[
							'title'      => __( 'Inline all CSS', 'speed-booster-pack' ),
							'id'         => 'css_inline',
							'type'       => 'switcher',
							'desc'       => __( 'Inlines all of your CSS files into the HTML output. Useful for lightweight designs but might be harmful for heavy websites with over 500KB of total CSS.', 'speed-booster-pack' ),
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Minify all inlined CSS', 'speed-booster-pack' ),
							'id'         => 'css_minify',
							'type'       => 'switcher',
							'desc'       => __( 'Minifies the already inlined CSS.', 'speed-booster-pack' ),
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'CSS exclusions', 'speed-booster-pack' ),
							'id'         => 'css_exclude',
							'class'      => 'css-exclude',
							'type'       => 'code_editor',
							'desc'       => __( 'If your design breaks after enabling the options above, you can exclude CSS file URLs here. One rule per line.', 'speed-booster-pack' ),
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
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
								],
							],
							'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
						],

					],
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
							'title'   => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Special', 'speed-booster-pack' ),
							'id'      => 'module_special',
							'class'   => 'module-special',
							'type'    => 'switcher',
							'label'   => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
							'default' => true,
						],

						[
							'title'      => __( 'Enable CDN', 'speed-booster-pack' ),
							'id'         => 'cdn_url',
							'class'      => 'cdn-url',
							'type'       => 'text',
							'before'     => 'http(s)://&nbsp;',
							'after'      => '&nbsp;/',
							'desc'       => __( 'Rewrites all asset URLs with the specified CDN domain. Enter the CDN domain without a protocol or a trailing slash; a relative protocol will be automatically added to all changed asset URLs.', 'speed-booster-pack' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
							'sanitize'   => 'sbp_clear_cdn_url',
						],
						[
							'title'      => __( 'Localize Google Analytics & Google Tag Manager', 'speed-booster-pack' ),
							'id'         => 'localize_tracking_scripts',
							'type'       => 'switcher',
							'desc'       => __( 'Searches for Google Analytics or Google Tag Manager scripts found in your pages, and replaces them with a locally saved script.', 'speed-booster-pack' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'Jetpack: ' . __( 'Dequeue devicepx-jetpack.js', 'speed-booster-pack' ),
							'id'         => 'jetpack_dequeue_devicepx',
							'type'       => 'switcher',
							/* translators: BEYNTODO  */
							'desc'       => sprintf( __( 'The %s file replaces images served via Jetpack\'s Photon CDN with their higher-quality equivalents. If you don\'t need this feature, you can dequeue the file and save an extra HTTP request and an extra DNS connection.', 'speed-booster-pack' ), '<code>devicepx-jetpack.js</code>' ),
							'dependency' => [ 'module_special', '==', '1', '', 'visible' ],
						],
						[
							'title'      => 'WooCommerce: ' . __( 'Disable cart fragments', 'speed-booster-pack' ),
							'id'         => 'woocommerce_disable_cart_fragments',
							'type'       => 'switcher',
							/* translators: BEYNTODO  */
							'desc'       => sprintf( __( 'Dequeues the %s file but only when the visitor\'s cart is empty.', 'speed-booster-pack' ), '<code>cart-fragments.js</code>' ),
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
							'title'                  => __( 'Custom code manager', 'speed-booster-pack' ),
							'id'                     => 'custom_codes',
							'type'                   => 'group',
							'before'                 => '<p>' . __( 'Code blocks added with this tool can be loaded in the header, the footer and can even be delayed.', 'speed-booster-pack' ) . '</p>',
							'accordion_title_number' => true,
							'accordion_title_auto'   => false,
							'fields'                 => [
								[
									'id'     => 'custom_codes_item',
									'type'   => 'code_editor',
									'before' => '&lt;script&gt;',
									'after'  => '&lt;/script&gt;',
									/* translators: BEYNTODO  */
									'desc'   => sprintf( __( 'Paste the inline JavaScript here. DON\'T include the %s tags or else you might break it!', 'speed-booster-pack' ), '<code>&lt;script&gt;</code>' ),
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
							/* translators: BEYNTODO  */
							'desc'       => sprintf( __( 'Enqueues %s (locally), which basically boosts the speed of navigating through your whole website.', 'speed-booster-pack' ), '<a href="https://instant.page/" rel="external nofollow noopener">instant.page</a>' ),
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
							'desc'       => __( 'Removes the unnecessary emoji scripts from your website front-end. Doesn\'t remove emojis, no worries there.', 'speed-booster-pack' ),
							'default'    => true,
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue post embed script', 'speed-booster-pack' ),
							'id'         => 'disable_post_embeds',
							'type'       => 'switcher',
							'desc'       => __( 'Disables embedding posts from WordPress-based websites (including your own) which converts URLs into heavy iframes.', 'speed-booster-pack' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue jQuery Migrate', 'speed-booster-pack' ),
							'id'         => 'dequeue_jquery_migrate',
							'type'       => 'switcher',
							'desc'       => __( 'If you\'re sure that the jQuery plugins used in your website work with jQuery 1.9 and above, this is totally safe to enable.', 'speed-booster-pack' ),
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							'title'      => __( 'Dequeue Dashicons CSS', 'speed-booster-pack' ),
							'id'         => 'dequeue_dashicons',
							'type'       => 'switcher',
							/* translators: BEYNTODO  */
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
							/* translators: BEYNTODO  */
							'desc'       => sprintf( __( 'Controls the %1$sHeartbeat API%2$s, which checks if the user is still logged-in or not every 15 to 60 seconds.', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/plugins/javascript/heartbeat-api/" rel="external nofollow noopener">', '</a>' ) . '<br />' . __( '"Enabled" lets it run like usual, "Optimized" sets both intervals to 120 seconds, and "Disabled" disables the Heartbeat API completely.', 'speed-booster-pack' ),
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
							/* translators: BEYNTODO  */
							'desc'       => sprintf( __( 'Limits the number of %1$spost revisions%2$s saved for each post. Keeping 3 or 5 revisions for each post should be enough for most sites. Set it to 0 to disable post revisions completely.', 'speed-booster-pack' ), '<a href="https://wordpress.org/support/article/revisions/" rel="external nofollow noopener">', '</a>' ) . '<br />'
							                /* translators: BEYNTODO  */
							                . sprintf( __( 'Note: If the %1$s constant is set in your %2$swp-config.php%3$s file, it will override this setting.', 'speed-booster-pack' ), '<code>WP_POST_REVISIONS</code>', '<code>', '</code>' ),
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
							                /* translators: BEYNTODO  */
							                . sprintf( __( 'Note: If the %1$s constant is set in your %2$swp-config.php%3$s file, it will override this setting.', 'speed-booster-pack' ), '<code>AUTOSAVE_INTERVAL</code>', '<code>', '</code>' ),
							'sanitize'   => 'posabs',
							'default'    => '1',
							'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						],
						[
							/* translators: BEYNTODO  */
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
							/* translators: BEYNTODO  */
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
				array(
					'title'  => __( 'About', 'speed-booster-pack' ),
					'id'     => 'about',
					'icon'   => 'fa fa-info-circle',
					'fields' => array(/* BEYNTODO: İçeriği yaz!  */
						[
							'title'   => __( 'Allow external notices', 'speed-booster-pack' ),
							'id'      => 'enable_external_notices',
							'type'    => 'switcher',
							'label'   => __( '', 'speed-booster-pack' ),
							'desc'    => sprintf( __( 'Fetches daily notices from %s daily (all of which are dismissible), and shows them in a non-obtrusive manner. We only intend to send essential notices and we hate spam as much as you do, but if you don\'t want to get them, you can disable this setting.', 'speed-booster-pack' ), '<a href="https://speedboosterpack.com/" rel="external noopener">speedboosterpack.com</a>' ),
							'default' => true,
						],
					),
				)
			);
			/* END Section: About */
		}
	}

	public function add_admin_bar_links( WP_Admin_Bar $admin_bar ) {

		if ( current_user_can( 'manage_options' ) && sbp_get_option( 'module_caching' ) && ! isset( $_SERVER['KINSTA_CACHE_ZONE'] ) ) {
			$clear_cache_url = wp_nonce_url( add_query_arg( 'sbp_action', 'sbp_clear_cache' ), 'sbp_clear_total_cache', 'sbp_nonce' );
			$sbp_admin_menu  = [
				'id'    => 'speed_booster_pack',
				'title' => __( 'Clear Cache', 'speed-booster-pack' ),
				'href'  => $clear_cache_url,
			];

			$admin_bar->add_menu( $sbp_admin_menu );
		}

	}

	public function set_flash_notices() {
		$notice = get_transient( 'sbp_notice_cache' );
		if ( $notice ) {
			add_action( 'admin_notices', [ $this, 'show_cache_notice' ] );
			delete_transient( 'sbp_notice_cache' );
		}
	}

	public function show_cache_notice() {
		echo '<div class="notice notice-success is-dismissible">
                <p><strong>' . SBP_PLUGIN_NAME . ':</strong>' . __( 'Cache cleared.', 'speed-booster-pack' ) . '</p>
        </div>';
	}

	private function initialize_announce4wp() {
		if ( sbp_get_option( 'enable_external_notices' ) ) {
			new Announce4WP_Client( 'speed-booster-pack.php', SBP_PLUGIN_NAME, "sbp", "https://speedboosterpack.com/wp-json/a4wp/v1/" . SBP_VERSION . "/news.json", "toplevel_page_sbp-settings" );
		}
	}
}