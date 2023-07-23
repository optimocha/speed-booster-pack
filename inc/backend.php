<?php

//	TODO:
//		onboarding (admin_init)
//		append_to_plugin_description (plugin_row_meta)
//		add_settings_link (plugin_action_links_ . SBP_BASENAME)
//		enqueue_notices (admin_init)
//		enqueue_admin_assets (admin_enqueue_scripts) // tüm wp-admin için (options harici css&js)
//		generate_options_page (admin_init) // csf hook'larını da bir yerlere sokuştur
//		generate_meta_boxes (admin_init)
//		generate_admin_bar_menu (admin_bar_menu)
//		generate_dashboard_widget (wp_dashboard_setup)

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      4.0.0
 *
 * @package    Optimocha\SpeedBooster
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

use Optimocha\SpeedBooster\Utils;
use Optimocha\SpeedBooster\Backend\Notices;
use Optimocha\SpeedBooster\Frontend\AdvancedCacheGenerator;
use Optimocha\SpeedBooster\Frontend\Cache;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Optimocha\SpeedBooster
 * @author     Optimocha
 */
class Backend {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    4.0.0
	 * @access   protected
	 * @var      Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The main options array of the plugin.
	 *
	 * @since    5.0.0
	 * @access   protected
	 */
	protected $options;

	/**
	 * WooCommerce Tracking setting.
	 *
	 * @since    4.5.0
	 * @access   private
	 * @var      string $woocommerce_tracking WooCommerce Tracking setting.
	 */
	private $woocommerce_tracking;

	/**
	 * WooCommerce Analytics setting.
	 *
	 * @since    4.5.0
	 * @access   private
	 * @var      string $woocommerce_analytics WooCommerce Analytics setting.
	 */
	private $woocommerce_analytics;

	/**
	 * Defines the backend class constructor.
	 *
	 * @since    5.0.0
	 */
	public function __construct( $options, $loader ) {

		$this->options = $options;
		$this->loader = $loader;

		$this->woocommerce_analytics = 1;
		$this->woocommerce_tracking  = 1;

		add_action( 'woocommerce_loaded', [ $this, 'get_woocommerce_options' ] );

		add_filter( 'csf_sbp_options_saved', 'Optimocha\SpeedBooster\Frontend\Cache::options_saved_filter' );

		add_action( 'csf_sbp_options_save_before', 'Optimocha\SpeedBooster\Frontend\Cache::options_saved_listener' );

		add_action( 'csf_sbp_options_save_before', 'Optimocha\SpeedBooster\Frontend\Cloudflare::update_cloudflare_settings' );

		add_action( 'csf_sbp_options_saved', 'Optimocha\SpeedBooster\Frontend\WooCommerce::set_woocommerce_optimizations' );

		add_action( 'csf_sbp_options_saved', 'Optimocha\SpeedBooster\Frontend\Cache::clear_total_cache' );

		add_action( 'csf_sbp_options_saved', 'Optimocha\SpeedBooster\Frontend\Cache::generate_htaccess' );

		add_action( 'admin_enqueue_scripts', 'add_thickbox' );

		add_action( 'admin_print_footer_scripts', [ $this, 'modify_menu_title' ] );

	}

	/**
	 * Enqueues necessary CSS & JS for the admin area.
	 *
	 * @since    5.0.0
	 */
	public function enqueue_admin_assets() {

		wp_enqueue_style( SBP_SLUG, SBP_URL . 'assets/sbp-admin.css', [], SBP_VERSION );

		wp_enqueue_script( SBP_SLUG, SBP_URL . 'assets/sbp-admin.js', [ 'jquery' ], SBP_VERSION );

		wp_localize_script( SBP_SLUG, 'sbp_ajax_vars', [ 'nonce' => wp_create_nonce( 'sbp_ajax_nonce' ), ] );

	}

	// TODO: test this changed function
	public function get_woocommerce_options() {

		$this->woocommerce_analytics = ( get_option( 'woocommerce_analytics_enabled' ) === 'yes' ) ? 1 : 0;

		$this->woocommerce_tracking  = ( get_option( 'woocommerce_allow_tracking' ) === 'yes' ) ? 1 : 0;

	}

	public function create_settings_page() {
		// Check core class for avoid errors
		if ( ! class_exists( 'CSF' ) ) { return; }

		// Set a unique slug-like ID
		$prefix = 'sbp_options';

		// Create options
		\CSF::createOptions( $prefix,
			[
				// framework title
				'framework_title' => 'Speed Booster Pack' . ' <small>by <a href="https://optimocha.com/" rel="external noopener" target="_blank">Optimocha</a></small>',
				'framework_class' => 'sbp-settings',

				// menu settings
				'menu_title'      => 'Speed Booster',
				'menu_icon'       => SBP_URL . 'assets/sbp-icon.svg?ver=' . SBP_VERSION,
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
				'footer_credit'           => sprintf( __( 'Thank you for using %1$s! If you like our plugin, be sure to %2$sleave a fair review%3$s.', 'speed-booster-pack' ), 'Speed Booster Pack', '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external noopener" target="_blank">', '</a>' ),
			] );

		/* BEGIN Section: Dashboard */
		\CSF::createSection(
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
						'content' => sprintf( __( 'Welcome to %s!', 'speed-booster-pack' ), 'Speed Booster Pack' ),
					],
					[
						'type'    => 'content',
						/* translators: %s = Speed Booster Pack  */
						'content' => sprintf( __( 'Thank you for installing %s! We really hope you\'ll like our plugin and greatly benefit from it. On this page, you\'ll find a small introduction to the plugin\'s features, and a few other things.', 'speed-booster-pack' ), 'Speed Booster Pack' ),
					],
					[
						'type'    => 'content',
						/* translators: %s = Speed Booster Pack  */
						'content' => '<p>' . __( 'Each module of this plugin has different sets of really cool features that can help speed up your website:', 'speed-booster-pack' ) . '</p>' . '<ul><li>' .
						 			'<strong>' . __( 'General', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module lets you tweak the WordPress core and your page sources by dequeueing core scripts/styles, decluttering &lt;head&gt;, optimizing revisions and the Heartbeat API and so on.', 'speed-booster-pack' ) . '</li><li>' .
						 			'<strong>' . __( 'Caching', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module caches your pages into static HTML files, greatly reducing database queries. It also helps browsers cache static assets more efficiently.', 'speed-booster-pack' ) . '</li><li>' .
						 			'<strong>' . __( 'Optimize CSS', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module allows you to delay loading of your CSS files while injecting "critical CSS" to your pages, which will definitely improve your PageSpeed metrics.', 'speed-booster-pack' ) . '</li><li>' .
						 			'<strong>' . __( 'Assets', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module helps you optimize the static assets in your pages by minifying HTML, lazy loading media (images, videos and iframes), deferring JavaScript, optimizing Google fonts and preloading any asset you want.', 'speed-booster-pack' ) . '</li><li>' .
						 			'<strong>' . __( 'WooCommerce', 'speed-booster-pack' ) . '</strong>: ' . __( 'This module has optimizations specific to WooCommerce, speeding up your users\' shopping experience.', 'speed-booster-pack' ) . '</li><li>' .
						 			'<strong>' . __( 'And many more', 'speed-booster-pack' ) . '</strong>: ' . __( 'Lots and lots of other features (like Cloudflare integration and database engine conversion) for you to get your website faster than ever!', 'speed-booster-pack' ) . '</li></ul>',
					],
					[
						'type'    => 'content',
						/* translators: 1. Speed Booster Pack 2. link to the speedboosterpack.com contact form 3. link to the GitHub page  */
						'content' => sprintf( __( 'Don\'t be afraid to tinker with the settings as you like, and disable any option or module if it causes any issues. After configuring %1$s, make sure you check your website as a visitor and confirm all\'s well. If you find a bug, you can let us know about it via our contact form on %2$s or create an issue on %3$s.', 'speed-booster-pack' ), 'Speed Booster Pack', '<a href="https://speedboosterpack.com/contact/" rel="external noopener" target="_blank">speedboosterpack.com</a>', '<a href="https://github.com/optimocha/speed-booster-pack/" rel="external noopener" target="_blank">GitHub</a>' ),
					],
					[
						'type'    => 'content',
						/* translators: 1. opening tag for the newsletter hyperlink 2. closing tag for the hyperlink  */
						'content' => sprintf( __( 'We\'re constantly adding new features to Speed Booster Pack, and improving existing ones. If you\'d like to be the first to know about improvements before they\'re released, plus more tips &amp; tricks about web performance optimization, %1$syou can sign up for our weekly newsletter here%2$s!', 'speed-booster-pack' ), '<a href="https://speedboosterpack.com/go/subscribe">', '</a>' ),
					],
					[
						'type'    => 'subheading',
						'content' => __( 'That\'s it, enjoy!', 'speed-booster-pack' ),
					],
					[
						'type'    => 'content',
						'content' => '<p>' . __( 'We really hope that you\'ll enjoy working with our plugin. Always remember that this is a powerful tool, and using powerful tools might hurt you if you\'re not careful. Have fun!', 'speed-booster-pack' ) . '</p>' .
						 			/* translators: %s = Optimocha */
						 			'<p style="font-style:italic;">' . sprintf( __( 'Your friends at %s', 'speed-booster-pack' ), 'Optimocha' ) . '</p>' .
						 			/* translators: 1. Speed Booster Pack 2. link to the plugin's reviews page on wp.org */
						 			'<p>' . sprintf( __( 'Almost forgot: If you like %1$s, it would mean a lot to us if you gave a fair rating on %2$s, because highly rated plugins are shown to more users on the WordPress plugin directory, meaning that we\'ll have no choice but to take better care of %1$s!', 'speed-booster-pack' ), 'Speed Booster Pack', '<a href="https://wordpress.org/support/plugin/speed-booster-pack/reviews/#new-post" rel="external noopener" target="_blank">wordpress.org</a>' ) . '</p>',
					],
					[
						'type'    => 'subheading',
						'content' => __( 'If you\'re looking for professional help...', 'speed-booster-pack' ),
					],
					[
						'type'    => 'content',
						/* translators: 1: plugin owner's name (Optimocha) 2: Speed Booster Pack (Speed Booster Pack) 3: hyperlink to the owner's website */
						'content' => sprintf( __( 'As %1$s, we like to brag about completing hundreds of tailored speed optimization jobs for different websites. (This experience is actually the source of the know-how that helps %2$s get better on every release!) If you\'re willing to invest in speeding up your website, not just with %2$s but as a whole, feel free to contact us on %3$s and benefit from our expertise on speed optimization!', 'speed-booster-pack' ), 'Optimocha', 'Speed Booster Pack', '<a href="https://optimocha.com/" rel="external noopener" target="_blank">optimocha.com</a>' ),
					],

				],
			]
		);
		/* END Section: Dashboard */

		/* BEGIN Section: General */
		\CSF::createSection(
			$prefix,
			[
				'title'  => __( 'General', 'speed-booster-pack' ),
				'id'     => 'tweaks',
				'icon'   => 'fa fa-sliders-h',
				'fields' => [
					[
						/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
						'title'    => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'General', 'speed-booster-pack' ),
						'id'       => 'module_tweaks',
						'class'    => 'module-tweaks',
						'type'     => 'switcher',
						'label'    => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
						'default'  => true,
						'sanitize' => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Enable instant.page', 'speed-booster-pack' ),
						'id'         => 'instant_page',
						'type'       => 'switcher',
						/* translators: %s = hyperlink to the instant.page website  */
						'desc'       => sprintf( __( 'Enqueues %s (locally), which basically boosts the speed of navigating through your whole website.', 'speed-booster-pack' ), '<a href="https://instant.page/" rel="external noopener" target="_blank">instant.page</a>' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Trim query strings', 'speed-booster-pack' ),
						'id'         => 'trim_query_strings',
						'type'       => 'switcher',
						'desc'       => __( 'Removes the query strings (characters that come after the question mark) at the end of enqueued asset URLs.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Disable self pingbacks', 'speed-booster-pack' ),
						'id'         => 'disable_self_pingbacks',
						'type'       => 'switcher',
						'desc'       => __( 'Disabling this will prevent pinging this website to ping itself (its other posts etc.) during publishing, which will improve the speed of publishing posts or pages.', 'speed-booster-pack' ),
						'default'    => true,
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Dequeue emoji scripts', 'speed-booster-pack' ),
						'id'         => 'dequeue_emoji_scripts',
						'type'       => 'switcher',
						'desc'       => __( 'Removes the unnecessary emoji scripts from your website front-end. Doesn\'t remove emojis, don\'t worry.', 'speed-booster-pack' ),
						'default'    => true,
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Dequeue the post embed script', 'speed-booster-pack' ),
						'id'         => 'disable_post_embeds',
						'type'       => 'switcher',
						'desc'       => __( 'Disables embedding posts from WordPress-based websites (including your own) which converts URLs into heavy iframes.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						/* translators: %s: <code>comment-reply.js</code>  */
						'title'      => sprintf( __( 'Dequeue %s', 'speed-booster-pack' ), '<code>comment-reply.js</code>' ),
						/* translators: %s: <code>comment-reply.js</code>  */
						'desc'       => sprintf( __( 'Disables the %s script.', 'speed-booster-pack' ), '<code>comment-reply.js</code>' ),
						'id'         => 'dequeue_comment_reply_script',
						'type'       => 'switcher',
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Dequeue Dashicons CSS', 'speed-booster-pack' ),
						'id'         => 'dequeue_dashicons',
						'type'       => 'switcher',
						/* translators: 1. <strong> 2. </strong>  */
						'desc'       => sprintf( __( 'Removes dashicons.css from your front-end for your visitors. Since Dashicons are required for the admin bar, %1$sdashicons.css will not be removed for logged-in users%2$s.', 'speed-booster-pack' ), '<strong>', '</strong>' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Dequeue Gutenberg CSS', 'speed-booster-pack' ),
						'id'         => 'dequeue_block_library',
						'type'       => 'switcher',
						'desc'       => __( 'If you\'re not using the block editor (Gutenberg) in your posts/pages, this is a safe setting to enable.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'title'      => __( 'Dequeue "global styles"', 'speed-booster-pack' ),
						'id'         => 'dequeue_global_styles',
						'type'       => 'switcher',
						'desc'       => __( 'With version 5.9, full site editing capabilities has been added to WordPress. However, the new structure has some "global styles" that isn\'t really needed for most themes and/or WordPress users. Enabling this will remove a big chunk of inline CSS that WordPress adds, plus some SVG definitions. If you don\'t know what this is, you can try enabling it and see if it affects your front-end.', 'speed-booster-pack' ),
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
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
								'title'    => __( 'Shortlinks', 'speed-booster-pack' ),
								'id'       => 'declutter_shortlinks',
								'type'     => 'switcher',
								'label'    => '<link rel=\'shortlink\' href=\'...\' />',
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'Next/previous posts links', 'speed-booster-pack' ),
								'id'       => 'declutter_adjacent_posts_links',
								'type'     => 'switcher',
								'label'    => "<link rel='next (or prev)' title='...' href='...' />",
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'WLW Manifest link', 'speed-booster-pack' ),
								'id'       => 'declutter_wlw',
								'type'     => 'switcher',
								'label'    => '<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="..." />',
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'Really Simple Discovery (RSD) link', 'speed-booster-pack' ),
								'id'       => 'declutter_rsd',
								'type'     => 'switcher',
								'label'    => '<link rel="EditURI" type="application/rsd+xml" title="RSD" href="..." />',
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'REST API links', 'speed-booster-pack' ),
								'id'       => 'declutter_rest_api_links',
								'type'     => 'switcher',
								'label'    => "<link rel='https://api.w.org/' href='...' />",
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'RSS feed links', 'speed-booster-pack' ),
								'id'       => 'declutter_feed_links',
								'type'     => 'switcher',
								'label'    => '<link rel="alternate" type="application/rss+xml" title="..." href="..." />',
								'sanitize' => 'sbp_sanitize_boolean',
							],
							[
								'title'    => __( 'WordPress version', 'speed-booster-pack' ),
								'id'       => 'declutter_wp_version',
								'type'     => 'switcher',
								'label'    => '<meta name="generator" content="WordPress X.X" />',
								'sanitize' => 'sbp_sanitize_boolean',
							],
						],
						'dependency' => [ 'module_tweaks', '==', '1', '', 'visible' ],
					],
					[
						'id'          => 'roles_to_disable_sbp',
						'type'        => 'select',
						'title'       => sprintf( __( 'Roles to disable %s features', 'speed-booster-pack' ), 'Speed Booster Pack' ),
						'chosen'      => true,
						'multiple'    => true,
						'placeholder' => 'Select user role',
						'options'     => 'roles',
					],
				],
			]
		);
		/* END Section: General */

		/* BEGIN Section: Caching */
		$cache_fields = [
			[
				'id'       => 'module_caching',
				'class'    => 'module-caching',
				'type'     => 'switcher',
				/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
				'title'    => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Caching', 'speed-booster-pack' ),
				'label'    => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
				'sanitize' => 'sbp_sanitize_boolean',
				// 'default'    => true,
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
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'id'         => 'caching_warmup_after_clear',
				'type'       => 'switcher',
				'title'      => __( 'Warm up cache on clear', 'speed-booster-pack' ),
				'desc'       => __( 'Creates cache files for the front page and all pages that are linked from the front page, each time the cache is cleared. Note that even though you don\'t turn this option on, you can manually warm up the cache from your admin bar.', 'speed-booster-pack' ),
				'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
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
				'id'         => 'caching_exclude_cookies',
				'class'      => 'caching-exclude-cookies',
				'type'       => 'code_editor',
				'title'      => __( 'Exclude Cookies', 'speed-booster-pack' ),
				'desc'       => __( 'Enter one cookie per line to exclude them from caching.', 'speed-booster-pack' ),
				'dependency' => [ 'module_caching', '==', '1', '', 'visible' ],
				'sanitize'   => 'esc_html',
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
				'sanitize'   => 'esc_url',
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

		if ( sbp_get_option( 'module_caching' ) && ! defined( 'SBP_ADVANCED_CACHE' ) ) {
			$cache_fields = array_merge( [
				[
					'type'    => 'submessage',
					'style'   => 'danger',
					'content' => sprintf( __( '%1$s cache is enabled but the advanced-cache.php file is created by another plugin. Saving your settings now will overwrite %1$s\'s advanced-cache.php file. To fix this, you can either disable the other plugin\'s caching feature or ours.', 'speed-booster-pack' ), 'Speed Booster Pack' ),
				]
			], $cache_fields );
		}

		\CSF::createSection(
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

		/* BEGIN Section: Optimize CSS */
		$critical_css_fields = [
			[
				'title'  => 'is_front_page',
				'fields' => [
					[
						'id'       => 'is_front_page',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into the front page of your website.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_front_page/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_front_page()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_home',
				'fields' => [
					[
						'id'       => 'is_home',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into the blog homepage of your website.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_home/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_home()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_single',
				'fields' => [
					[
						'id'       => 'is_single',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all single posts.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_single/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_single()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_page',
				'fields' => [
					[
						'id'       => 'is_page',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all static pages.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_page/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_page()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_category',
				'fields' => [
					[
						'id'       => 'is_category',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all category archive pages.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_category/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_category()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_tag',
				'fields' => [
					[
						'id'       => 'is_tag',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all tag archive pages.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_tag/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_tag()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
			[
				'title'  => 'is_archive',
				'fields' => [
					[
						'id'       => 'is_archive',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all archive pages.', 'speed-booster-pack' ) . ' <a href="https://developer.wordpress.org/reference/functions/is_archive/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_archive()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			],
		];

		// Check if WooCommerce active or not
		if ( Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$critical_css_fields[] = [
				'title'  => 'is_shop',
				'fields' => [
					[
						'id'       => 'is_shop',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into the shop page of your website.', 'speed-booster-pack' ) . ' <a href="https://woocommerce.com/document/conditional-tags/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_shop()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			];
			$critical_css_fields[] = [
				'title'  => 'is_product',
				'fields' => [
					[
						'id'       => 'is_product',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all single product pages.', 'speed-booster-pack' ) . ' <a href="https://woocommerce.com/document/conditional-tags/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_product()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			];
			$critical_css_fields[] = [
				'title'  => 'is_product_category',
				'fields' => [
					[
						'id'       => 'is_product_category',
						'type'     => 'code_editor',
						'desc'     => __( 'This CSS block will be injected into all product category pages.', 'speed-booster-pack' ) . ' <a href="https://woocommerce.com/document/conditional-tags/" rel="external noopener" target="_blank">' . sprintf( __( 'Learn more about %s.', 'speed-booster-pack' ), '<code>is_product_category()</code>' ) . '</a>',
						'settings' => [ 'lineWrapping' => true ],
						'sanitize' => 'sbp_sanitize_strip_tags',
					],
				],
			];
		}

		\CSF::createSection(
			$prefix,
			[
				'title'  => __( 'Optimize CSS', 'speed-booster-pack' ),
				'id'     => 'css',
				'icon'   => 'fa fa-palette',
				'fields' => [

					[
						/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
						'title'    => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Optimize CSS', 'speed-booster-pack' ),
						'id'       => 'module_css',
						'class'    => 'module-css',
						'type'     => 'switcher',
						'label'    => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
						'default'  => true,
						'sanitize' => 'sbp_sanitize_boolean',
					],
					[
						'type'  => 'subheading',
						'title' => 'Critical CSS',
					],
					[
						'id'         => 'enable_criticalcss',
						'title'      => __( 'Enable Critical CSS', 'speed-booster-pack' ),
						'type'       => 'switcher',
						'default'    => false,
						'desc'       => sprintf( __( 'Critical CSS is a method to optimize CSS delivery, %1$srecommended by Google%2$s. It allows you to defer all your CSS files and inline the styles of your content above the fold. You can generate critical CSS needed for your website %3$susing a tool like this%4$s and paste them below.', 'speed-booster-pack' ), '<a href="https://web.dev/extract-critical-css/" rel="external noopener" target="_blank">', '</a>', '<a href="https://speedboosterpack.com/go/criticalcss" rel="external noopener" target="_blank">', '</a>' ),
						'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
					[
						'id'         => 'criticalcss_default',
						'type'       => 'code_editor',
						'before'     => '<h3>' . __( 'Default Critical CSS', 'speed-booster-pack' ) . '</h3>',
						'sanitize'   => 'sbp_sanitize_strip_tags',
						'desc'       => sprintf( __( 'This CSS block will be injected into all pages if there\'s no critical CSS blocks with higher priority. %1$sLearn more about the template hierarchy of WordPress.%2$s', 'speed-booster-pack' ), '<a href="https://developer.wordpress.org/themes/basics/template-hierarchy/" rel="external noopener" target="_blank">', '</a>' ),
						'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						'settings'   => [ 'lineWrapping' => true ],
						'class'      => 'hide-if-disabled',
					],
					[
						'id'         => 'criticalcss_codes',
						'type'       => 'accordion',
						'title'      => '',
						'sanitize'   => 'sbp_sanitize_strip_tags',
						'accordions' => $critical_css_fields,
						'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						'class'      => 'hide-if-disabled',
					],
					[
						'title'      => __( 'Remove critical CSS after onload', 'speed-booster-pack' ),
						'id'         => 'remove_criticalcss',
						'desc'       => __( 'Remove critical CSS the onload event fires on browser. Enable this only if you\'re having styling issues when the pages finish loading.', 'speed-booster-pack' ),
						'type'       => 'switcher',
						'default'    => true,
						'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
						'class'      => 'hide-if-disabled',
					],
					[
						'id'         => 'criticalcss_excludes',
						'type'       => 'code_editor',
						'title'      => __( 'Critical CSS exclude rules', 'speed-booster-pack' ),
						'sanitize'   => 'sbp_sanitize_strip_tags',
						'desc'       => __( 'Enter CSS file names or URLs to exclude from critical CSS.', 'speed-booster-pack' ),
						'dependency' => [ 'module_css|enable_criticalcss', '==|==', '1|1', '', 'visible' ],
						'settings'   => [ 'lineWrapping' => true ],
						'class'      => 'hide-if-disabled',
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
						'sanitize'   => 'sbp_sanitize_boolean',
						'class'      => 'hide-if-disabled',
					],
					[
						'title'      => __( 'Minify all inlined CSS', 'speed-booster-pack' ),
						'id'         => 'css_minify',
						'type'       => 'switcher',
						'desc'       => __( 'Minifies the already inlined CSS.', 'speed-booster-pack' ),
						'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
						'class'      => 'hide-if-disabled',
					],
					[
						'title'      => __( 'CSS exclusions', 'speed-booster-pack' ),
						'id'         => 'css_exclude',
						'class'      => 'css-exclude',
						'type'       => 'code_editor',
						'desc'       => __( 'If your design breaks after enabling the CSS options above, you can exclude CSS file URLs here. One rule per line.', 'speed-booster-pack' ),
						'dependency' => [ 'module_css', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_strip_tags',
						'class'      => 'hide-if-disabled',
					],
				],
			]
		);
		/* END Section: Optimize CSS */

		/* BEGIN Section: Assets */
		$asset_fields = [
			[
				/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
				'title'    => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'Assets', 'speed-booster-pack' ),
				'id'       => 'module_assets',
				'type'     => 'switcher',
				'label'    => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
				'default'  => true,
				'sanitize' => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Minify HTML', 'speed-booster-pack' ),
				'id'         => 'minify_html',
				'type'       => 'switcher',
				'desc'       => __( 'Removes all whitespace characters from the HTML output, minimizing the HTML size.', 'speed-booster-pack' ),
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Optimize Google Fonts', 'speed-booster-pack' ),
				'id'         => 'optimize_gfonts',
				'type'       => 'switcher',
				'desc'       => __( 'Combines all Google Fonts URLs into a single URL and optimizes loading of that URL.', 'speed-booster-pack' ),
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Set missing image dimensions', 'speed-booster-pack' ),
				'id'         => 'missing_image_dimensions',
				'type'       => 'switcher',
				'desc'       => __( 'Automatically sets missing image width and height attributes to improve the Cumulative Layout Shift (CLS) and Largest Contentful Paint (LCP) metrics.', 'speed-booster-pack' ),
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Localize Google Analytics & Google Tag Manager', 'speed-booster-pack' ),
				'id'         => 'localize_tracking_scripts',
				'type'       => 'switcher',
				'desc'       => __( 'Searches for Google Analytics or Google Tag Manager scripts (analytics.js, gtag.js or gtm.js) in your page sources, and replaces them with a locally saved script.', 'speed-booster-pack' ),
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Lazy load media', 'speed-booster-pack' ),
				'id'         => 'lazyload',
				'type'       => 'switcher',
				'desc'       => __( 'Defers loading of images, videos and iframes to page onload.', 'speed-booster-pack' ) . ' <a href="https://web.dev/lazy-loading/" rel="external noopener" target="_blank">' . __( 'Learn more about lazy loading.', 'speed-booster-pack' ) . '</a>',
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'class'      => 'lazyload-media ',
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Lazy load exclusions', 'speed-booster-pack' ),
				'id'         => 'lazyload_exclude',
				'class'      => 'lazyload-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Excluding important images at the top of your pages (like your logo and such) is a good idea. One URL per line.', 'speed-booster-pack' ),
				'dependency' => [ 'module_assets|lazyload', '==|==', '1|1', '', 'visible|visible' ],
				'sanitize'   => 'sbp_clear_http',
			],
			[
				'title'      => __( 'Optimize JavaScript', 'speed-booster-pack' ),
				'id'         => 'js_optimize',
				'desc'       => __( 'Improves JavaScript loading by deferring all JS files and inline JS, avoiding render blocking issues. You can either defer everything and exclude some JS, or only defer some JS with the Custom option. Be sure what you\'re doing and use the exclude/include lists, or you might break your front-end JavaScript!', 'speed-booster-pack' ),
				'type'       => 'button_set',
				'options'    => [
					'off'        => __( 'Off', 'speed-booster-pack' ),
					'everything' => __( 'Everything', 'speed-booster-pack' ),
					'custom'     => __( 'Custom', 'speed-booster-pack' ),
				],
				'default'    => 'off',
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
			],
			[
				'title'      => __( 'JavaScript to exclude from deferring', 'speed-booster-pack' ),
				'id'         => 'js_exclude',
				'class'      => 'js-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from deferring.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'module_assets|js_optimize', '==|==', '1|everything', '', 'visible|visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			],
			[
				'title'      => __( 'JavaScript to defer', 'speed-booster-pack' ),
				'id'         => 'js_include',
				'class'      => 'js-include',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to defer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => '',
				'dependency' => [ 'module_assets|js_optimize', '==|==', '1|custom', '', 'visible|visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			],
			[
				'title'      => __( 'Move JavaScript to footer', 'speed-booster-pack' ),
				'id'         => 'js_footer',
				'class'      => 'js-footer',
				'desc'       => __( 'Moves all JS files and inline JS to the bottom of your page sources. Has a high chance to break your website, so be sure to exclude things! If you\'re using the defer setting, you probably don\'t need to enable this.', 'speed-booster-pack' ),
				'type'       => 'switcher',
				'default'    => '',
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'JavaScript to exclude from moving to footer', 'speed-booster-pack' ),
				'id'         => 'js_footer_exclude',
				'class'      => 'js-footer-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from moving to footer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'module_assets|js_footer', '==|==', '1|1', '', 'visible|visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			],
			[
				'title'      => __( 'Preload assets', 'speed-booster-pack' ),
				'id'         => 'preboost',
				'class'      => 'preboost',
				'type'       => 'fieldset',
				'sanitize'   => 'sbp_sanitize_strip_tags',
				'fields'     => [
					[
						'id'       => 'preboost_enable',
						'type'     => 'switcher',
						'label'    => __( 'Enable preloading of the assets specified below.', 'speed-booster-pack' ),
						'sanitize' => 'sbp_sanitize_boolean',
					],
					[
						'id'         => 'preboost_include',
						'type'       => 'code_editor',
						'desc'       => __( 'Enter full URLs of the assets you want to preload. One URL per line.', 'speed-booster-pack' ),
						'dependency' => [ 'preboost_enable', '==', '1', '', 'visible' ],
						'settings'   => [ 'lineWrapping' => true ],
						'sanitize'   => 'sbp_sanitize_strip_tags',
					],
					[
						'id'         => 'preboost_featured_image',
						'type'       => 'switcher',
						'label'      => __( 'Preload featured images.', 'speed-booster-pack' ),
						'desc'       => __( 'Enable this if you want featured images to be preloaded.', 'speed-booster-pack' ),
						'dependency' => [ 'preboost_enable', '==', '1', '', 'visible' ],
						'sanitize'   => 'sbp_sanitize_boolean',
					],
				],
				'dependency' => [ 'module_assets', '==', '1', '', 'visible' ],
			],
		];

		\CSF::createSection(
			$prefix,
			[
				'title'  => __( 'Assets', 'speed-booster-pack' ),
				'id'     => 'assets',
				'icon'   => 'fa fa-code',
				'fields' => $asset_fields,
			]
		);
		/* END Section: Assets */

		/* BEGIN Section: CDN & Proxy */

		/* Begin Cloudflare Fields */
		$cloudflare_fields = [
			[
				'title' => 'Cloudflare',
				'type'  => 'subheading',
			],
			[
				'title'    => __( 'Connect to Cloudflare', 'speed-booster-pack' ),
				'id'       => 'cloudflare_enable',
				'type'     => 'switcher',
				'sanitize' => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Cloudflare global API key', 'speed-booster-pack' ),
				'id'         => 'cloudflare_api',
				'type'       => 'text',
				'desc'       => '<a href="https://developers.cloudflare.com/api/keys/#view-your-api-key" rel="external noopener" target="_blank">' . __( 'You can find it using this tutorial.', 'speed-booster-pack' ) . '</a>',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
			],
			[
				'title'      => __( 'Cloudflare email address', 'speed-booster-pack' ),
				'id'         => 'cloudflare_email',
				'type'       => 'text',
				'desc'       => __( 'The email address you signed up for Cloudflare with.', 'speed-booster-pack' ),
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
			],
			[
				'title'      => __( 'Cloudflare zone ID', 'speed-booster-pack' ),
				'id'         => 'cloudflare_zone',
				'type'       => 'text',
				'desc'       => __( 'You can find your zone ID in the Overview tab on your Cloudflare panel.', 'speed-booster-pack' ),
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
			],
			[
				'title'      => 'Rocket Loader',
				'id'         => 'cf_rocket_loader_enable',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Development Mode',
				'id'         => 'cf_dev_mode_enable',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Minify CSS',
				'id'         => 'cf_css_minify_enable',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Minify HTML',
				'id'         => 'cf_html_minify_enable',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Minify JS',
				'id'         => 'cf_js_minify_enable',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Automatic Platform Optimization',
				'id'         => 'cf_apo_enable',
				'desc'       => __( 'You need to be a paying Cloudflare user to enable this setting, otherwise it will get disabled again.', 'speed-booster-pack' ),
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'APO: Cache By Device Type',
				'id'         => 'cf_apo_device_type',
				'class'      => 'with-preloader',
				'type'       => 'switcher',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
			],
			[
				'title'      => 'Browser Cache TTL',
				'id'         => 'cf_browser_cache_ttl',
				'class'      => 'with-preloader',
				'type'       => 'select',
				'options'    => [
					0        => 'Respect Existing Headers',
					1800     => '30 minutes',
					3600     => '1 hour',
					7200     => '2 hours',
					10800    => '3 hours',
					14400    => '4 hours',
					18000    => '5 hours',
					28800    => '8 hours',
					43200    => '12 hours',
					57600    => '16 hours',
					72000    => '20 hours',
					86400    => '1 day',
					172800   => '2 days',
					259200   => '3 days',
					345600   => '4 days',
					432000   => '5 days',
					691200   => '8 days',
					1382400  => '16 days',
					2073600  => '24 days',
					2678400  => '1 month',
					5356800  => '2 months',
					16070400 => '6 months',
					31536000 => '1 year',
				],
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
			],
			[
				'type'       => 'content',
				'content'    => '
				<span>
					<a href="#" class="button button-small sbp-cloudflare-test">' . __( 'Test Cloudflare connection', 'speed-booster-pack' ) . '<span class="sbp-cloudflare-spinner"></span></a>
					<span class="sbp-cloudflare-fetching">' . __( 'Fetching Cloudflare settings...', 'speed-booster-pack' ) . '</span>
				</span>
				<span class="sbp-cloudflare-info-text sbp-cloudflare-incorrect" style="color:red; vertical-align: middle;"><i class="fa fa-exclamation-triangle"></i> ' . __( 'Your Cloudflare credentials are incorrect.', 'speed-booster-pack' ) . '</span>
				<span class="sbp-cloudflare-info-text sbp-cloudflare-connection-issue" style="color:red; vertical-align: middle;"><i class="fa fa-exclamation-triangle"></i> ' . __( 'Error occured while connecting to Cloudflare.', 'speed-booster-pack' ) . '</span>
				<span class="sbp-cloudflare-info-text sbp-cloudflare-correct" style="color:green; vertical-align: middle;"><i class="fa fa-check-circle"></i> ' . __( 'Your Cloudflare credentials are correct.', 'speed-booster-pack' ) . '</span>
				<span class="sbp-cloudflare-info-text sbp-cloudflare-warning" style="color:orange; vertical-align: middle;"><i class="fa fa-exclamation-circle"></i> ' . __( 'Enter your Cloudflare credentials and save settings to see CloudFlare options.', 'speed-booster-pack' ) . '</span>
			  ',
				'dependency' => [ 'cloudflare_enable', '==', '1', '', 'visible' ],
			],
		];
		/* End Cloudflare Fields */

		/* Begin Sucuri Fields */
		$sucuri_fields = [
			[
				'title' => 'Sucuri',
				'type'  => 'subheading',
			],
			[
				'title'    => __( 'Connect to Sucuri', 'speed-booster-pack' ),
				'id'       => 'sucuri_enable',
				'type'     => 'switcher',
				'desc'     => sprintf( __( 'When you connect your Sucuri account, you\'ll be able to clear your Sucuri cache via your admin bar. Plus, every time %1$s\'s cache is cleared, Sucuri\'s cache will be cleared as well.', 'speed-booster-pack' ), 'Speed Booster Pack' ),
				'sanitize' => 'sbp_sanitize_boolean',
			],
			[
				'title'      => __( 'Sucuri API key', 'speed-booster-pack' ),
				'id'         => 'sucuri_api',
				'type'       => 'text',
				'dependency' => [ 'sucuri_enable', '==', '1', '', 'visible' ],
			],
			[
				'title'      => __( 'Sucuri API Secret', 'speed-booster-pack' ),
				'id'         => 'sucuri_secret',
				'type'       => 'text',
				'dependency' => [ 'sucuri_enable', '==', '1', '', 'visible' ],
			],
		];
		/* End Of Sucuri Fields */

		$proxy_fields = array_merge( [
			[
				'title' => __( 'CDN', 'speed-booster-pack' ),
				'type'  => 'subheading',
			],
			[
				'title' => __( 'Enable/Disable', 'speed-booster-pack' ) . ' ' . __( 'CDN', 'speed-booster-pack' ),
				'id' => 'cdn_enable',
				'type' => 'switcher',
				'sanitize' => 'sbp_sanitize_boolean',
			],
			[
				'title'    => __( 'CDN domain', 'speed-booster-pack' ),
				'id'       => 'cdn_url',
				'class'    => 'cdn-url',
				'type'     => 'text',
				'before'   => 'http(s)://&nbsp;',
				'after'    => '&nbsp;/',
				'desc'     => __( 'Rewrites all asset URLs with the specified CDN domain. Enter the CDN domain without a protocol or a trailing slash; a relative protocol will be automatically added to all changed asset URLs.', 'speed-booster-pack' ),
				'sanitize' => 'esc_url',
				'dependency' => [ 'cdn_enable', '!=', '', '', 'visible' ],
			],
			[
				'title'      => __( 'Included Directories', 'speed-booster-pack' ),
				'id'         => 'cdn_includes',
				'type'       => 'code_editor',
				'desc'       => __( 'Anything other than WordPress\'s existing directories should be entered here to be rewritten with the CDN domain. Separated by new lines.', 'speed-booster-pack' ),
				'sanitize'   => 'sbp_sanitize_strip_tags',
				'dependency' => [ 'cdn_enable', '!=', '', '', 'visible' ],
			],
			[
				'title'      => __( 'Excluded Extensions', 'speed-booster-pack' ),
				'id'         => 'cdn_excludes',
				'type'       => 'code_editor',
				'desc'       => __( 'If you want to exclude certain file types, enter the extensions here. Separated by new lines.', 'speed-booster-pack' ),
				'sanitize'   => 'sbp_sanitize_strip_tags',
				'dependency' => [ 'cdn_enable', '!=', '', '', 'visible' ],
			],
		],
			$cloudflare_fields,
			$sucuri_fields );
		\CSF::createSection(
			$prefix,
			array(
				'title'  => __( 'CDN & Proxy', 'speed-booster-pack' ),
				'id'     => 'cdn_proxy',
				'icon'   => 'fa fa-directions',
				'fields' => $proxy_fields,
			)
		);
		/* END Section: CDN & Proxy */

		/* BEGIN Section: WooCommerce */
    	$woocommerce_fields = [];

		if ( ! Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    		$woocommerce_fields = [
    			[
    				'id'    => 'sbp_csp_warning',
    				'type'  => 'submessage',
    				'style' => 'warning',
    				'content' => __( 'WooCommerce is not active right now, but you can still change the settings below.', 'speed-booster-pack' ),
    			]
    		];
    	}

		$woocommerce_fields = array_merge( $woocommerce_fields, [
    		[
				/* translators: used like "Enable/Disable XXX" where "XXX" is the module name. */
				'title'    => __( 'Enable/Disable', 'speed-booster-pack' ) . ' WooCommerce',
				'id'       => 'module_woocommerce',
				'class'    => 'module-woocommerce',
				'type'     => 'switcher',
				'label'    => __( 'Enables or disables the whole module without resetting its settings.', 'speed-booster-pack' ),
				'default'  => true,
				'sanitize' => 'sbp_sanitize_boolean',
    		],
    		[
				'title'      => __( 'Disable cart fragments', 'speed-booster-pack' ),
				'id'         => 'woocommerce_disable_cart_fragments',
				'type'       => 'switcher',
				/* translators: %s = cart-fragments.js  */
				'desc'       => sprintf( __( 'Dequeues the %s file if the visitor\'s cart is empty,  preventing an unnecessary and slow AJAX request.', 'speed-booster-pack' ), '<code>cart-fragments.js</code>' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
    		],
    		[
				'title'      => __( 'Optimize non-WooCommerce pages', 'speed-booster-pack' ),
				'id'         => 'woocommerce_optimize_nonwc_pages',
				'type'       => 'switcher',
				'desc'       => __( 'Prevents loading of WooCommerce-related scripts and styles on non-WooCommerce pages.', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
    		],
    		[
				'title'      => __( 'Disable password strength meter', 'speed-booster-pack' ),
				'id'         => 'woocommerce_disable_password_meter',
				'type'       => 'switcher',
				'desc'       => __( 'Disables the password strength meter for password inputs during a WooCommerce checkout.', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
    		],
    		[
				'title'      => __( 'Action Scheduler retention period', 'speed-booster-pack' ),
				'id'         => 'woocommerce_action_scheduler_period',
				'type'       => 'spinner',
				'default'    => 30,
				'desc'       => __( 'The "Action Scheduler" of WooCommerce has a default retention period of 30 days. Change it here, if you know what you are doing.', 'speed-booster-pack' ),
				'unit'       => __( 'days', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'absint',
    		],
    		[
				'title'      => __( 'WooCommerce Marketing', 'speed-booster-pack' ),
				'id'         => 'woocommerce_marketing',
				'type'       => 'switcher',
				'desc'       => __( 'Enable or disable WooCommerce marketing.', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
				'text_on'    => 'Enabled',
				'text_off'   => 'Disabled',
				'text_width' => 100,
    			'default'    => '1',
    		],
    		[
				'title'      => __( 'WooCommerce Analytics', 'speed-booster-pack' ),
				'id'         => 'woocommerce_analytics',
				'type'       => 'switcher',
				'desc'       => __( 'Enable or disable WooCommerce analytics.', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
				'text_on'    => 'Enabled',
				'text_off'   => 'Disabled',
				'text_width' => 100,
    			'default'    => $this->woocommerce_analytics,
    		],
    		[
				'title'      => __( 'WooCommerce Tracking', 'speed-booster-pack' ),
				'id'         => 'woocommerce_tracking',
				'type'       => 'switcher',
				'desc'       => __( 'Enable or disable WooCommerce tracking.', 'speed-booster-pack' ),
				'dependency' => [ 'module_woocommerce', '==', '1', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_boolean',
				'text_on'    => 'Enabled',
				'text_off'   => 'Disabled',
				'text_width' => 100,
				'default'    => $this->woocommerce_tracking,
    		],
    	] );

		\CSF::createSection(
			$prefix,
			[
				'title'  => 'WooCommerce',
				'id'     => 'woocommerce',
				'icon'   => 'fa fa-shopping-cart',
				'fields' => $woocommerce_fields,
			]
		);
		/* END Section: WooCommerce */

		/** BEGIN Section: Database Optimization */
		\CSF::createSection(
			$prefix,
			[
				'title'  => __( 'Optimize Database', 'speed-booster-pack' ),
				'id'     => 'database_optimization',
				'icon'   => 'fa fa-database',
				'fields' => [
					[
						'type'  => 'subheading',
						'title' => __( 'Database Engine Converter', 'speed-booster-pack' ),
					],
					[
						'id'      => 'button',
						'title'   => '',
						'type'    => 'content',
						'content' => '
							<p>' . __( 'This handy little tool converts your database tables from MyISAM to InnoDB. Click the button below to scan your tables. Tables with the MyISAM engine will then be listed so you can convert them one by one.', 'speed-booster-pack' ) . '</p>
							<button class="button button-primary sbp-scan-database-tables sbp-button-loading"><span>' . __( 'Scan database tables', 'speed-booster-pack' ) . '</span> <i class="dashicons dashicons-image-rotate"></i></button>
							<table class="widefat fixed sbp-database-tables" cellspacing="0" style="margin-top: 20px; display: none;">
								<thead>
									<tr>
										<th>' . __( 'Table name', 'speed-booster-pack' ) . '</th>
										<th>' . __( 'Actions', 'speed-booster-pack' ) . '</th>
									</tr>
								</thead>
								<tbody>
								</tbody>
							</table>
						',
					],
				],
			]
		);
		/** END Section: Database Optimization */

		/* BEGIN Section: Tools */
		\CSF::createSection(
			$prefix,
			array(
				'title'  => __( 'Tools', 'speed-booster-pack' ),
				'id'     => 'tools',
				'icon'   => 'fa fa-tools',
				'fields' => [
					[
						'type'    => 'subheading',
						/* translators: %s = Speed Booster Pack  */
						'content' => sprintf( __( 'Backup %s Settings', 'speed-booster-pack' ), 'Speed Booster Pack' ),
					],
					[
						'id'    => 'backup',
						'type'  => 'backup',
						'title' => '',
					],
				],
			)
		);
		/* END Section: Tools */

		/* BEGIN Section: About */
		\CSF::createSection(
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
						'content' => sprintf( __( 'About %s', 'speed-booster-pack' ), 'Optimocha' ),
					],
					[
						'type'    => 'content',
						/* translators: 1. Optimocha 2. Speed Booster Pack  */
						'content' => '<p>' . sprintf( __( 'We are %1$s, a small team of speed optimization experts. Along with hundreds of websites we finished optimizing, we acquired %2$s in 2019 and we\'re working hard to make this plugin the best speed optimization plugin for WordPress ever since!', 'speed-booster-pack' ), 'Optimocha', 'Speed Booster Pack' ) . '</p><ul><li><a href="https://optimocha.com/speed-optimization-for-wordpress/" rel="external noopener" target="_blank">' .
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
							<li><a href="https://codestarframework.com/" rel="external noopener" target="_blank">CodeStar Framework</a></li>
							<li><a href="http://sourceforge.net/projects/simplehtmldom/" rel="external noopener" target="_blank">PHP Simple HTML DOM Parser</a></li>
							<li><a href="https://github.com/verlok/vanilla-lazyload" rel="external noopener" target="_blank">LazyLoad by Andrea Verlicchi</a></li>
							<li><a href="https://github.com/deliciousbrains/wp-background-processing" rel="external noopener" target="_blank">WP Background Processing by Delicious Brains</a></li>
							<li><a href="https://instant.page/" rel="external noopener" target="_blank">instant.page</a></li>
						</ul>',
					],
				],
			]
		);
		/* END Section: About */

	}

	public function create_metaboxes() {
		if ( function_exists( 'current_user_can' ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
		}

		/* BEGIN Metaboxes */
		$metabox_prefix    = 'sbp_post_meta';
		$public_post_types = get_option( 'sbp_public_post_types' );
		if ( is_array( $public_post_types ) ) {
			\CSF::createMetabox( $metabox_prefix,
				[
					'title'     => 'Speed Booster Pack',
					'post_type' => $public_post_types,
				]
			);

			// BEGIN CONTENT SPECIFIC PRELOAD
			$meta_fields = [
				[
					'id'       => 'sbp_preload',
					'type'     => 'code_editor',
					'title'    => __( 'Preload rules for this content', 'speed-booster-pack' ),
					'desc'     => __( 'Enter full URLs of files to preload only for this content.', 'speed-booster-pack' ),
					'settings' => [ 'lineWrapping' => true ],
					'sanitize' => 'sbp_sanitize_strip_tags',
				],
			];

			if ( ! sbp_get_option( 'module_assets' ) || ! sbp_get_option( 'preboost' ) || ( sbp_get_option( 'preboost' ) && ! sbp_get_option( 'preboost' )['preboost_enable'] && ! sbp_get_option( 'preboost' )['preboost_enable'] ) ) {
				$meta_fields[] = [
					'id'    => 'sbp_csp_warning',
					'type'  => 'notice',
					'style' => 'warning',
					'title' => __( sprintf( 'Warning: Preloading isn\'t active in %1$s%2$s settings.%3$s', '<a href="admin.php?page=sbp-settings#tab=assets" target="_blank">', 'Speed Booster Pack', '</a>' ) ),
				];
			}
			// END CONTENT SPECIFIC PRELOAD

			// BEGIN CONTENT SPECIFIC CRITICALCSS
			$meta_fields[] = [
				'id'      => 'sbp_criticalcss_status',
				'type'    => 'button_set',
				'title'   => __( 'Critical CSS for this content', 'speed-booster-pack' ),
				'options' => array(
					'main_setting' => 'Main setting',
					'off'          => 'Off',
					'custom'       => 'Custom',
				),
				'default' => 'main_setting',
				'class'   => 'sbp-gap-top',
			];

			$meta_fields[] = [
				'id'         => 'sbp_criticalcss',
				'type'       => 'code_editor',
				'desc'       => __( 'Paste the critical CSS rules generated for this exact URL.', 'speed-booster-pack' ),
				'settings'   => [ 'lineWrapping' => true ],
				'dependency' => [ 'sbp_criticalcss_status', '==', 'custom', '', 'visible' ],
				'class'      => 'meta_box_critical_css_excludes',
			];

			if ( ! sbp_get_option( 'module_css' ) || ! sbp_get_option( 'enable_criticalcss' ) ) {
				$meta_fields[] = [
					'id'    => 'sbp_criticalcss_warning',
					'type'  => 'notice',
					'style' => 'warning',
					'title' => __( sprintf( 'Warning: Critical CSS isn\'t active in %1$s%2$s settings.%3$s', '<a href="admin.php?page=sbp-settings#tab=optimize-css" target="_blank">', 'Speed Booster Pack', '</a>' ) ),
				];
			}
			// END CONTENT SPECIFIC CRITICALCSS

			// BEGIN CONTENT SPECIFIC JS DEFER
			$meta_fields[] = [
				'title'   => __( 'Optimize JS for this content', 'speed-booster-pack' ),
				'id'      => 'js_optimize',
				'desc'    => __( 'Improves JavaScript loading by deferring all JS files and inline JS, avoiding render blocking issues. You can either defer everything and exclude some JS, or only defer some JS with the Custom option. Be sure what you\'re doing and use the exclude/include lists, or you might break your front-end JavaScript!', 'speed-booster-pack' ),
				'type'    => 'button_set',
				'options' => [
					'main_setting' => __( 'Main setting', 'speed-booster-pack' ),
					'off'          => __( 'Off', 'speed-booster-pack' ),
					'everything'   => __( 'Everything', 'speed-booster-pack' ),
					'custom'       => __( 'Custom', 'speed-booster-pack' ),
				],
				'default' => 'main_setting',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to exclude from deferring', 'speed-booster-pack' ),
				'id'         => 'js_exclude',
				'class'      => 'js-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from deferring.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'js_optimize', '==', 'everything', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to defer', 'speed-booster-pack' ),
				'id'         => 'js_include',
				'class'      => 'js-include',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to defer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => '',
				'dependency' => [ 'js_optimize', '==', 'custom', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];
			// END CONTENT SPECIFIC JS DEFER

			// BEGIN CONTENT SPECIFIC JS MOVE TO FOOTER
			$meta_fields[] = [
				'title'   => __( 'Move JS to footer for this content', 'speed-booster-pack' ),
				'id'      => 'js_footer_status',
				'class'   => 'js-footer',
				'type'    => 'button_set',
				'options' => [
					'main_setting' => __( 'Main setting', 'speed-booster-pack' ),
					'off'          => __( 'Off', 'speed-booster-pack' ),
					'on'           => __( 'On', 'speed-booster-pack' ),
				],
				'desc'    => __( 'Moves all JS files and inline JS to the bottom of your page sources. Has a high chance to break your website, so be sure to exclude things! If you\'re using the defer setting, you probably don\'t need to enable this.', 'speed-booster-pack' ),
				'default' => 'main_setting',
			];

			$meta_fields[] = [
				'title'      => __( 'JavaScript to exclude from moving to footer', 'speed-booster-pack' ),
				'id'         => 'js_footer_exclude',
				'class'      => 'js-footer-exclude',
				'type'       => 'code_editor',
				'desc'       => __( 'Enter JS filenames/URLs or parts of inline JS to exclude from moving to footer.', 'speed-booster-pack' ) . ' ' . __( 'One rule per line. Each line will be taken as a separate rule, so don\'t paste entire blocks of inline JS!', 'speed-booster-pack' ),
				'default'    => 'js/jquery/jquery.js' . PHP_EOL . 'js/jquery/jquery.min.js',
				'dependency' => [ 'js_footer_status', '==', 'on', '', 'visible' ],
				'sanitize'   => 'sbp_sanitize_strip_tags',
			];
			// END CONTENT SPECIFIC JS MOVE TO FOOTER

			\CSF::createSection( $metabox_prefix,
				array(
					'fields' => $meta_fields,
				) );
		}
	}

	public function modify_menu_title() {
		$count = Notices::get_notice_count();

		if ( $count ) {
			?>
    		<script type="text/javascript">
    			jQuery(document).ready(function ($) {
    				$('#toplevel_page_sbp-settings .wp-menu-name').append('&nbsp;<span class="update-plugins count-<?php echo $count; ?>"><?php echo $count; ?></span>');
    			});
    		</script>
			<?php

			return sprintf(
				' %1$s',
				esc_html( number_format_i18n( $count ) )
			);
		}
	}
}
