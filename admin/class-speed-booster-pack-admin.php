<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://optimocha.com
 * @since      1.0.0
 *
 * @package    Speed_Booster_Pack
 * @subpackage Speed_Booster_Pack/admin
 */

use SpeedBooster\SBP_Cache;

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

		add_action( 'csf_sbp_options_saved', function () {
			$settings = [
				'cache_expire_time'             => 604800, // Expire time in seconds
				'caching_exclude_urls'          => '',
				'caching_include_query_strings' => '',
				'show_mobile_cache'             => true,
				'caching_mobile'                => false,
			];

			foreach ( $settings as $option => $default_value ) {
				$settings[ $option ] = sbp_get_option( $option, $default_value );
			}

			global $wp_filesystem;

			// Delete or recreate advanced-cache.php
			$advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
			if ( sbp_get_option( 'module_caching' ) ) {
				$sbp_advanced_cache = SBP_PATH . '/advanced-cache.php';

				SBP_Cache::set_wp_cache_constant( true );

				if ( ! file_exists( $advanced_cache_path ) ) {
					file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', file_get_contents( $sbp_advanced_cache ) );
				} else {
					// Compare file contents
					if ( file_get_contents( $advanced_cache_path ) != file_get_contents( $sbp_advanced_cache ) ) {
						wp_die( 'advanced-cache.php file is already exists and created by other plugin. Delete wp-content/advanced-cache.php file to continue.' );
					}
				}
			} else {
				SBP_Cache::set_wp_cache_constant( false );
				if ( file_exists( $advanced_cache_path ) ) {
					unlink( $advanced_cache_path );
				}
			}

			require_once( ABSPATH . '/wp-admin/includes/file.php' );
			WP_Filesystem();

			$wp_filesystem->put_contents( WP_CONTENT_DIR . '/cache/speed-booster/settings.json', json_encode( $settings ) );
		} );

		$this->create_settings_page();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, SBP_URL . 'admin/css/speed-booster-pack-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, SBP_URL . 'admin/js/speed-booster-pack-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function load_dependencies() {
		require_once SBP_LIB_PATH . 'codestar-framework/codestar-framework.php';
	}

	public function create_settings_page() {
		// Check core class for avoid errors
		if ( class_exists( 'CSF' ) ) {

			// Set a unique slug-like ID
			$prefix = 'sbp_options';

			// Create options
			CSF::createOptions( $prefix, array(

				// framework title
				'framework_title' => SBP_PLUGIN_NAME . ' <small>by <a href="' . SBP_OWNER_HOME . '" rel="external nofollow noopener">' . SBP_OWNER_NAME . '</a></small>' . '
					<style>
					.sbp-settings [data-controller^="module_"].hidden {display:block;opacity:.5;filter:grayscale(1);}
					.sbp-settings .csf-field-code_editor .CodeMirror {height:190px;}
					.sbp-settings .declutter-head .csf-fieldset-content > .csf-field {padding:10px;}
					.sbp-settings .csf-field-spinner .csf--unit {padding:0 10px;}
					.sbp-settings .text-input-before, .sbp-settings .text-input-after {line-height:2;}
					.sbp-settings .font-monospace {font-family:monospace;}
					.sbp-settings .csf-field-group .csf-cloneable-header-icon {vertical-align:middle;}
					.sbp-settings .csf-sticky .csf-header-inner {z-index:99;}
					.sbp-settings {max-width:75rem;}
					</style>',
				// LAHMACUNTODO: Üst satıra eklediğim stili başka bir yöntemle ekleyelim. Bu dosyanın sonundaki sbp_settings_custom_css fonksiyonunu inceleyebilirsin.
				'framework_class' => 'sbp-settings',

				// menu settings
				'menu_title'      => 'Speed Booster',
				'menu_icon'       => SBP_URL . 'admin/images/icon-16x16.png',
				'menu_slug'       => 'sbp-settings',
				'menu_type'       => 'menu',
				'menu_capability' => 'manage_options',

				'theme'                   => 'light',
				'show_search'             => false,
				'show_reset_section'      => false,
				'show_all_options'        => false,

				// menu extras
				'show_bar_menu'           => false,
				'show_sub_menu'           => true,
				'admin_bar_menu_icon'     => '',
				'admin_bar_menu_priority' => 80,
				// LAHMACUNTODO: bu dört ayar admin bar'a menü ekliyor ama o menüye "clear cache" gibi düğmeler eklememize izin vermiyor. şimdilik kendi admin bar menümüzü yazalım, birinci menü maddesi SBP Settings, ikincisi Clear Cache, üçüncüsü About SBP olsun.

				// footer
				'footer_text'             => sprintf( __( 'Thank you for using %1$s! Be sure to %2$sleave a fair review%3$s if you liked our plugin.', 'speed-booster-pack' ), SBP_PLUGIN_NAME, '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external nofollow noopener">', '</a>' ),

			) );

			/* Section: Dashboard */
			CSF::createSection( $prefix, [
				'title'  => __( 'Dashboard', 'speed-booster-pack' ),
				'id'     => 'dashboard',
				'icon'   => 'fa fa-tachometer-alt',
				'fields' => [

					// [
					//	 'title'	=> __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'TODO', 'speed-booster-pack' ),
					//	 'id'	=> 'module_TODO',
					//	 'class'	=> 'module-TODO',
					//	 'type'	=> 'switcher',
					//	 'label'	=> __( 'Disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					// ],

				]
			] );
			/* END Section: Dashboard */

			/* Section: Tweaks */
			CSF::createSection( $prefix, [
				'title'  => __( 'Tweaks', 'speed-booster-pack' ),
				'id'     => 'tweaks',
				'icon'   => 'fa fa-sliders-h',
				'fields' => [


					[
						'title' => __( 'Enable/Disable Tweaks', 'speed-booster-pack' ),
						'id'    => 'module_tweaks',
						'class' => 'module-tweaks',
						'type'  => 'switcher',
						'label' => __( 'Disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					],
					[
						'title'      => __( 'Enable instant.page', 'speed-booster-pack' ),
						'id'         => 'instant_page',
						'type'       => 'switcher',
						'desc'       => sprintf( __( 'Enqueues %s (locally), which basically boosts the speed of navigating through your whole website.', 'speed-booster-pack' ), '<a href="https://instant.page/" rel="external nofollow noopener">instant.page</a>' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Trim query strings', 'speed-booster-pack' ),
						'id'         => 'trim_query_strings',
						'type'       => 'switcher',
						'desc'       => __( 'Removes the query strings (characters that come after the question mark) at the end of enqueued asset URLs.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Disable self pingbacks', 'speed-booster-pack' ),
						'id'         => 'disable_self_pingbacks',
						'type'       => 'switcher',
						'desc'       => __( 'Disabling this will prevent pinging this website to ping itself (its other posts etc.) during publishing, which will improve the speed of publishing posts or pages.', 'speed-booster-pack' ),
						'default'    => true,
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Dequeue emoji scripts', 'speed-booster-pack' ),
						'id'         => 'dequeue_emoji_scripts',
						'type'       => 'switcher',
						'desc'       => __( 'Removes the unnecessary emoji scripts from your website front-end. Doesn\'t remove emojis, no worries there.', 'speed-booster-pack' ),
						'default'    => true,
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Dequeue post embed script', 'speed-booster-pack' ),
						'id'         => 'disable_post_embeds',
						'type'       => 'switcher',
						'desc'       => __( 'Disables embedding posts from WordPress-based websites (including your own) which converts URLs into heavy iframes.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Dequeue jQuery Migrate', 'speed-booster-pack' ),
						'id'         => 'dequeue_jquery_migrate',
						'type'       => 'switcher',
						'desc'       => __( 'If you\'re sure that the jQuery plugins used in your website work with jQuery 1.9 and above, this is totally safe to enable.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Dequeue Dashicons CSS', 'speed-booster-pack' ),
						'id'         => 'dequeue_dashicons',
						'type'       => 'switcher',
						'desc'       => sprintf( __( 'Removes dashicons.css from your front-end for your visitors. Since Dashicons are required for the admin bar, %1$sdashicons.css will not be removed for logged-in users%2$s.', 'speed-booster-pack' ), '<strong>', '</strong>' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Dequeue Gutenberg CSS', 'speed-booster-pack' ),
						'id'         => 'dequeue_block_library',
						'type'       => 'switcher',
						'desc'       => __( 'If you\'re not using the block editor (Gutenberg) in your posts/pages, this is a safe setting to enable.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Heartbeat settings', 'speed-booster-pack' ),
						'id'         => 'heartbeat_settings',
						'desc'       => sprintf( __( 'Controls the %1$sHeartbeat API%2$s, which checks if the user is still logged-in or not every 15 to 60 seconds.', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/plugins/javascript/heartbeat-api/" rel="external nofollow noopener">', '</a>' ) . '<br />' . __( '"Enabled" lets it run like usual, "Optimized" sets both intervals to 120 seconds, and "Disabled" disables the Heartbeat API completely.', 'speed-booster-pack' ),
						'type'       => 'button_set',
						'options'    => [
							'enabled'   => __( 'Enabled', 'speed-booster-pack' ),
							'optimized' => __( 'Optimized', 'speed-booster-pack' ),
							'disabled'  => __( 'Disabled', 'speed-booster-pack' ),
						],
						'default'    => 'enabled',
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
						'title'      => __( 'Limit post revisions', 'speed-booster-pack' ),
						'id'         => 'post_revisions',
						'type'       => 'spinner',
						'unit'       => __( 'revisions', 'speed-booster-pack' ),
						'desc'       => sprintf( __( 'Limits the number of %1$spost revisions%2$s saved for each post.', 'speed-booster-pack' ), '<a href="https://wordpress.org/support/article/revisions/" rel="external nofollow noopener">', '</a>' ) . '<br />' . __( 'Keeping 3 or 5 revisions for each post should be enough for most sites. Setting this to 0 disables post revisions.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1' ],
						'sanitize'   => 'absint',
					],
					[
						'title'      => __( 'Autosave interval', 'speed-booster-pack' ),
						'id'         => 'autosave_interval',
						'type'       => 'spinner',
						'min'        => '1',
						'unit'       => __( 'minutes', 'speed-booster-pack' ),
						'desc'       => __( 'Sets how frequent the content is saved automatically while editing. WordPress sets it to 1 minute by default, and you can\'t set it to a shorter interval.', 'speed-booster-pack' ) . '<br />' . sprintf( __( 'If the %1$sAUTOSAVE_INTERVAL%2$s constant is set in your %1$swp-config.php%2$s file, it will override this setting.', 'speed-booster-pack' ), '<code>', '</code>' ),
						/* LAHMACUNTODO: hayır absint yeterli değil; onu yaparken aynı zamanda 0 olmasını da engellemek lazım!
						http://codestarframework.com/documentation/#/faq?id=how-to-use-sanitize-
						*/
						'sanitize'   => 'absint',
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],
					[
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
						'dependency' => [ 'module_tweaks', '==', '1' ]
					],

				]
			] );
			/* END Section: Tweaks */

			/* Section: Caching */
			CSF::createSection( $prefix, [
				'title'  => __( 'Caching', 'speed-booster-pack' ),
				'id'     => 'caching',
				'icon'   => 'fa fa-server',
				'fields' => [

					[
						'id'    => 'module_caching',
						'class' => 'module-caching',
						'type'  => 'switcher',
						'title' => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Caching', 'speed-booster-pack' ),
						'label' => __( 'Disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					],
					[
						'id'         => 'caching_mobile',
						'type'       => 'switcher',
						'title'      => __( 'Mobile Caching', 'speed-booster-pack' ),
						'label'      => __( 'Enable or disable separate caches for mobile and desktop.', 'speed-booster-pack' ),
						'desc'       => __( 'Useful if you have mobile-specific plugins or themes. Not necessary if you have a responsive theme.', 'speed-booster-pack' ),
						'dependency' => [ 'module_caching', '==', '1' ]
					],
					[
						'id'         => 'caching_exclude_urls',
						'class'      => 'caching-exclude-urls',
						'type'       => 'code_editor',
						'title'      => __( 'Exclude URLs', 'speed-booster-pack' ),
						'desc'       => __( 'Enter one URL per line to exclude them from caching.', 'speed-booster-pack' ),
						'dependency' => [ 'module_caching', '==', '1' ]
					],
					[
						'id'         => 'caching_include_query_strings',
						'class'      => 'caching-include-query-strings',
						'type'       => 'code_editor',
						'title'      => __( 'Include query strings', 'speed-booster-pack' ),
						'desc'       => __( 'Enter one query string per line to cache URLs with those query strings.', 'speed-booster-pack' ) . '<br />' . sprintf( __( 'For example, after adding "foo" to the list, %1$sexample.com/blog-post/?foo=bar%2$s will be cached.', 'speed-booster-pack' ), '<code>', '</code>' ),
						'default'    => 'utm_source',
						'dependency' => [ 'module_caching', '==', '1' ]
					],
					[
						'title'      => __( 'Cloudflare integration', 'speed-booster-pack' ),
						'id'         => 'cloudflare',
						'class'      => 'cloudflare',
						'type'       => 'fieldset',
						'fields'     => [

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
						],
						'dependency' => [ 'module_caching', '==', '1' ]
					],

				]
			] );
			/* END Section: Caching */

			/* Section: Assets */
			CSF::createSection( $prefix, [
				'title'  => __( 'Assets', 'speed-booster-pack' ),
				'id'     => 'assets',
				'icon'   => 'fa fa-code',
				'fields' => [

					[
						'title' => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Assets', 'speed-booster-pack' ),
						'id'    => 'module_assets',
						'type'  => 'switcher',
						'label' => __( 'Disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					],
					[
						'title'      => __( 'Minify HTML', 'speed-booster-pack' ),
						'id'         => 'minify_html',
						'type'       => 'switcher',
						'desc'       => __( 'Removes all whitespace characters from the HTML output, minimizing the HTML size.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Optimize Google Fonts', 'speed-booster-pack' ),
						'id'         => 'optimize_gfonts',
						'type'       => 'switcher',
						'desc'       => __( 'Combines all Google Fonts URLs into a single URL.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Lazy load images, videos &amp; iframes', 'speed-booster-pack' ),
						'id'         => 'lazyload',
						'type'       => 'switcher',
						'desc'       => __( 'Defers loading of images, videos and iframes to page onload.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Lazy load exclusions', 'speed-booster-pack' ),
						'id'         => 'lazyload_exclude',
						'class'      => 'lazyload_exclude',
						'type'       => 'code_editor',
						'desc'       => __( 'Excluding important images at the top of your pages (like your logo and such) is a good idea. One URL per line.', 'speed-booster-pack' ),
						'dependency' => [ [ 'module_assets', '==', '1' ], [ 'lazyload', '==', '1' ] ]
					],
					[
						'title'      => __( 'Move JavaScript to footer', 'speed-booster-pack' ),
						'id'         => 'js_move',
						'type'       => 'switcher',
						'desc'       => __( 'Moves all JavaScript files and inline JS to the end of the HTML, making the rest of the code load faster.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Defer all JavaScript', 'speed-booster-pack' ),
						'id'         => 'js_defer',
						'type'       => 'switcher',
						'desc'       => __( 'Defers loading of all JavaScript to page onload.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'JavaScript exclusions', 'speed-booster-pack' ),
						'id'         => 'js_exclude',
						'class'      => 'js_exclude',
						'type'       => 'code_editor',
						'desc'       => __( 'If you encounter JavaScript errors on your error console, you can exclude JS file URLs or parts of inline JS here. One rule per line. Since each line will be taken as separate exclude rules, don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Inline all CSS', 'speed-booster-pack' ),
						'id'         => 'css_inline',
						'type'       => 'switcher',
						'desc'       => __( 'Inlines all of your CSS files into your HTML output. Useful for lightweight designs but might be harmful for heavy websites with over 500KB of CSS.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'Minify all inlined CSS', 'speed-booster-pack' ),
						'id'         => 'css_minify',
						'type'       => 'switcher',
						'desc'       => __( 'Minifies the already inlined CSS.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
					],
					[
						'title'      => __( 'CSS exclusions', 'speed-booster-pack' ),
						'id'         => 'css_exclude',
						'class'      => 'CSS exclusions',
						'type'       => 'code_editor',
						'desc'       => __( 'If your design breaks after enabling the options above, you can exclude CSS file URLs here. One rule per line.', 'speed-booster-pack' ),
						'dependency' => [ 'module_assets', '==', '1' ]
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
						'dependency' => [ 'module_assets', '==', '1' ]
					],

				]
			] );
			/* END Section: Assets */

			/* Section: Special */
			CSF::createSection( $prefix, [
				'title'  => __( 'Special', 'speed-booster-pack' ),
				'id'     => 'special',
				'icon'   => 'fa fa-bolt',
				'fields' => [

					[
						'title' => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Special', 'speed-booster-pack' ),
						'id'    => 'module_special',
						'class' => 'module-special',
						'type'  => 'switcher',
						'label' => __( 'Disables the whole module without resetting its settings.', 'speed-booster-pack' ),
					],

					[
						'title'      => __( 'Enable CDN', 'speed-booster-pack' ),
						'id'         => 'cdn_enable',
						'type'       => 'text',
						'before'     => '<span class="text-input-before font-monospace">http(s)://</span> ',
						'after'      => ' <span class="text-input-after font-monospace">/</span>',
						'desc'       => __( 'Rewrites all asset URLs with the specified CDN URL.', 'speed-booster-pack' ),
						'dependency' => [ 'module_special', '==', '1' ]
					],
					[
						'title'      => __( 'Localize Google Analytics & Google Tag Manager', 'speed-booster-pack' ),
						'id'         => 'localize_tracking_scripts',
						'type'       => 'switcher',
						'desc'       => __( 'Searches for Google Analytics or Google Tag Manager scripts found in your pages, and replaces them with a locally saved script.', 'speed-booster-pack' ),
						'dependency' => [ 'module_special', '==', '1' ]
					],
					[
						'title'      => 'WooCommerce: ' . __( 'Disable cart fragments', 'speed-booster-pack' ),
						'id'         => 'woocommerce_disable_cart_fragments',
						'type'       => 'switcher',
						'desc'       => sprintf( __( 'Dequeues the %s file but only when the visitor\'s cart is empty.', 'speed-booster-pack' ), '<code>cart-fragments.js</code>' ),
						'dependency' => [ 'module_special', '==', '1' ]
					],
					[
						'title'      => 'WooCommerce: ' . __( 'Optimize non-WooCommerce pages', 'speed-booster-pack' ),
						'id'         => 'woocommerce_optimize_nonwc_pages',
						'type'       => 'switcher',
						'desc'       => __( 'Prevents loading of WooCommerce-related scripts and styles on non-WooCommerce pages.', 'speed-booster-pack' ),
						'dependency' => [ 'module_special', '==', '1' ]
					],
					[
						'title'      => 'WooCommerce: ' . __( 'Disable password strength meter', 'speed-booster-pack' ),
						'id'         => 'woocommerce_disable_password_meter',
						'type'       => 'switcher',
						'desc'       => __( 'Disables the password strength meter for password inputs during a WooCommerce checkout.', 'speed-booster-pack' ),
						'dependency' => [ 'module_special', '==', '1' ]
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
						'dependency'             => [ 'module_special', '==', '1' ]
					],

				]
			] );
			/* END Section: Special */

			/* Section: Tools */
			CSF::createSection( $prefix, array(
				'title'  => __( 'Tools', 'speed-booster-pack' ),
				'id'     => 'tools',
				'icon'   => 'fa fa-tools',
				'fields' => array(
					array(
						'type'    => 'subheading',
						'content' => __( 'Backup Settings', 'speed-booster-pack' ),
					),
					// A text field
					array(
						'id'    => 'backup',
						'type'  => 'backup',
						'title' => '',
					),

				)
			) );
			/* END Section: Tools */

			/* Section: About */
			CSF::createSection( $prefix, array(
				'title'  => __( 'About', 'speed-booster-pack' ),
				'id'     => 'about',
				'icon'   => 'fa fa-info-circle',
				'fields' => array(

					// // A text field
					// array(
					// 	'id'	=> 'opt_text',
					// 	'type'	=> 'text',
					// 	'title'	=> 'Simple Text',
					// ),

				)
			) );
			/* END Section: About */

			if ( ! function_exists( 'sbp_settings_custom_css' ) ) {
				function sbp_settings_custom_css() {

					// Style
					// wp_enqueue_style(	);

					/*
					<style>
					.sbp-settings [data-controller^="module-"].hidden {display:block;opacity:.5;filter:grayscale(1);}
					.sbp-settings .csf-field-code_editor .CodeMirror {height:190px;}
					.sbp-settings {max-width:75rem;}
					</style>
					*/

					// // Script
					// wp_enqueue_script(	);

				}

				add_action( 'csf/enqueue', 'sbp_settings_custom_css' );
			}
		}
	}
}