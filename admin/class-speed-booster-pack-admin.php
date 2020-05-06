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
		add_action( 'csf_speed_booster_saved', function () {
			$settings = [
				'cache_expire_time'       => 604800, // Expire time in seconds
				'exclude_urls'            => '',
				'include_query_strings'   => '',
				'show_mobile_cache' => false,
				'separate_mobile_cache' => false,
			];

			foreach ( $settings as $option => $default_value ) {
				$settings[ $option ] = sbp_get_option( $option, $default_value );
			}

			global $wp_filesystem;

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
			$prefix = 'speed_booster';

			//
			// Create options
			CSF::createOptions( $prefix, array(
				'framework_title' => 'Speed Booster Pack <small>by Optimocha</small>',
				'menu_title'      => 'Speed Booster',
				'menu_slug'       => 'speed-booster',
				'menu_icon'       => SBP_URL . 'admin/images/icon-16x16.png',
				'show_reset_all'  => false,
				'theme'           => 'light',
			) );

			//
			// Create Analytics Tab
			CSF::createSection( $prefix, array(
				'id'    => 'special_tab', // Set a unique slug-like ID
				'title' => 'Specials',
			) );

			//
			// Create a sub-tab
			CSF::createSection( $prefix, [
				'parent' => 'special_tab', // The slug id of the parent section
				'title'  => 'Localize Trackers',
				'fields' => [
					[
						'type'  => 'heading',
						'title' => 'Localize Analytics',
					],
					[
						'id'    => 'localize-analytics',
						'type'  => 'switcher',
						'title' => 'Localize Analytics',
					],
					[
						'id'         => 'use-minimal-analytics',
						'type'       => 'switcher',
						'title'      => 'Use Minimal Analytics',
						'dependency' => [ 'localize-analytics', '==', 'true' ],
					],
					[
						'id'         => 'tracking-id',
						'type'       => 'text',
						'title'      => 'Tracking ID',
						'dependency' => [ 'use-minimal-analytics', '==', 'true' ],
					],
					[
						'id'         => 'tracking-position',
						'type'       => 'radio',
						'title'      => 'Tracking Position',
						'options'    => [
							'footer' => 'Footer',
							'header' => 'Header',
						],
						'dependency' => [ 'use-minimal-analytics', '==', 'true' ],
					],
				],
			] );

			//
			// Create a top-tab
			CSF::createSection( $prefix, array(
				'id'    => 'cache_tab', // Set a unique slug-like ID
				'title' => 'Cache',
			) );


			//
			// Create a sub-tab
			CSF::createSection( $prefix, array(
				'parent' => 'cache_tab', // The slug id of the parent section
				'title'  => 'General',
				'fields' => array(

					// A switcher field
					array(
						'id'    => 'enable_cache',
						'type'  => 'switcher',
						'title' => 'Enable/Disable Caching',
					),

					// A switcher field
					array(
						'id'    => 'show_mobile_cache',
						'type'  => 'switcher',
						'title' => 'Show Cache For Mobile Devices',
					),

					// A switcher field
					array(
						'id'    => 'separate_mobile_cache',
						'type'  => 'switcher',
						'title' => 'Separate Mobile Cache',
						'dependency' => ['show_mobile_cache', '==', 'true'],
					),

					[
						'id'      => 'cache_expire_time',
						'type'    => 'number',
						'default' => '604800',
						'title'   => 'Cache expire time in seconds'
					],

					[
						'id'          => 'exclude_urls',
						'type'        => 'code_editor',
						'default'     => '',
						'title'       => 'Exclude url\'s from caching',
						'subtitle'    => 'Write url each line',
						'placeholder' => '/some/relative/path',
					],

					[
						'id'          => 'include_query_strings',
						'type'        => 'code_editor',
						'default'     => '',
						'title'       => 'Include Query Strings',
						'subtitle'    => 'Write url each line',
						'placeholder' => '/some/relative/path',
					],

				)
			) );

			CSF::createSection( $prefix, [
				'id'    => 'assets_tab',
				'title' => 'Assets',
			] );

			CSF::createSection( $prefix, [
				'parent' => 'assets_tab',
				'id'     => 'font-optimizer',
				'title'  => 'Font Optimizer',
				'fields' => [
					[
						'id'    => 'optimize-fonts',
						'title' => 'Optimize Google Fonts',
						'type'  => 'switcher',
					]
				],
			] );
		}
	}

}
