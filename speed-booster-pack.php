<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:     Speed Booster Pack
 * Plugin URI:      https://speedboosterpack.com
 * Description:     PageSpeed optimization is vital for SEO: A faster website equals better conversions. Optimize & cache your site with this smart plugin!
 * Version:         5.0.0
 * Author:          Optimocha
 * Author URI:      https://optimocha.com
 * License:         GPLv3 or later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:     speed-booster-pack
 *
 */

namespace Optimocha\SpeedBooster;

defined( 'ABSPATH' ) || exit;

/**
 * Defines plugin constants.
 *
 * @since   5.0.0
 */
define( 'SPEED_BOOSTER_PACK', [
    'version'       => '5.0.0',
    'slug'          => 'speed-booster-pack',
    'path'          => __DIR__,
    'basename'      => plugin_basename( __FILE__ ),
    'url'           => plugin_dir_url( __FILE__ ),
] );

/*

TODO: replace the following old constants with the new ones above (or remove unused constants) in the codebase.
define( 'SBP_VERSION', '4.5.6' ); // plugin version
define( 'SBP_PLUGIN_NAME', 'Speed Booster Pack' ); // plugin name
define( 'SBP_OWNER_NAME', 'Optimocha' ); // plugin owner name
define( 'SBP_OWNER_HOME', 'https://optimocha.com/' ); // plugin owner home
define( 'SBP_URL', plugin_dir_url( __FILE__ ) ); // plugin root URL
define( 'SBP_PATH', realpath( dirname( __FILE__ ) ) . '/' ); // plugin root directory path
define( 'SBP_INC_PATH', SBP_PATH . 'includes/' ); // plugin includes directory path
define( 'SBP_LIB_PATH', SBP_PATH . 'vendor/' ); // plugin 3rd party directory path
define( 'SBP_CACHE_DIR', WP_CONTENT_DIR . '/cache/speed-booster/' ); // plugin cache directory path
define( 'SBP_UPLOADS_DIR', WP_CONTENT_DIR . '/uploads/speed-booster/' ); // plugin uploads path
define( 'SBP_UPLOADS_URL', WP_CONTENT_URL . '/uploads/speed-booster/' ); // plugin uploads URL
define( 'SBP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // plugin basename
define( 'SBP_MIGRATOR_VERSION', '45000' ); // plugin migrator version

 */

/**
 * Hooks to the `plugins_loaded` action.
 *
 * @since   5.0.0
 */
add_action( 'plugins_loaded', function() {

    /**
     * Requires the main plugin class.
     *
     * @since   5.0.0
     */
    // TODO: maybe use __DIR__ ?
    // TODO: we might not this line at all, as the autoloader might load the file
    //       when the Speed_Booster_Pack class is called.
    require_once SPEED_BOOSTER_PACK['path'] . 'inc/class-speed-booster-pack.php';

    /**
     * Registers the autoloader.
     *
     * @since   5.0.0
     */
    spl_autoload_register( function ( $class ) {

        $prefix = 'Optimocha\\SpeedBooster\\';
        $len = strlen( $prefix );

        if ( strncmp( $prefix, $class, $len ) !== 0) {
            return;
        }

        $relative_class = substr( $class, $len );

        // TODO: maybe use __DIR__ ?
        $file = SPEED_BOOSTER_PACK['path'] . 'inc/' . str_replace('\\', '/', $relative_class) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    });

    /**
     * Registers the activation hook.
     *
     * @since   5.0.0
     */
    register_activation_hook( __FILE__, function() {
        add_option( 'sbp_activated', true );
    } );

    /**
     * Registers the deactivation hook.
     *
     * @since   5.0.0
     */
    register_deactivation_hook( __FILE__, [ 'Core', 'deactivate' ] );

    /**
     * Begins execution of the plugin.
     *
     * @since   5.0.0
     */
    $plugin = new Speed_Booster_Pack();
    $plugin->run();

} );

//  CLASSES & METHODS:
//      
//  Core
//      run (hook methods below, hooks in parantheses)
//      activate (admin_init) // set defaults & redirect & delete option: sbp_activated
//      upgrade_process (upgrader_process_complete) // add_option( 'sbp_upgraded', [ 'from' => 'x.y.z', 'to' => 'a.b.c' ] )
//      upgrade (plugins_loaded) // otomatik de olabilir, çıkartılacak notice'teki linke tıklayarak da olabilir
//      deactivate (register_deactivation_hook)
//      load_plugin_textdomain (plugins_loaded)
//      meta_links (plugin_row_meta)
//      settings_links (plugin_action_links_ . SPEED_BOOSTER_PACK['basename'])
//      enqueue_notices (admin_init)
//      generate_options_page (admin_init) // csf hook'larını da bir yerlere sokuştur
//      generate_meta_boxes (admin_init)
//      generate_admin_bar_menu (admin_bar_menu)
//      generate_dashboard_widget (wp_dashboard_setup)
//      onboarding (admin_init)
//      deactivation_survey (admin_init) // freemius varken gerekmeyecek
//      enqueue_admin_css (admin_enqueue_scripts) // tüm wp-admin için (options harici)
//      enqueue_admin_js (admin_enqueue_scripts) // tüm wp-admin için (options harici)
//      simple_cron (admin_init) // option olarak kaydet, option içine timestamp kaydet, timestamp'e bakarak güncelle
//          update_sitedata (simple_cron)
//          update_server_tech (simple_cron)
//          update_sbp_license (simple_cron)
//          update_mothership_data (simple_cron)
//      init_freemius (plugins_loaded) // sbp.php'den direkt çalıştırman gerekebilir. wpdirectory.net'te başka pluginleri incele
//      check_debug_mode (???)
//          
//  Compatibility
//      run (hook methods below, hooks in parantheses)
//          add_filter( 'rocket_plugins_to_deactivate', '__return_empty_array' );
//          add_action( 'woocommerce_loaded', [ $this, 'get_woocommerce_options' ] );
//      check_plugin_compatibility (admin_init)
//      check_theme_compatibility (admin_init)
//      check_software_compatibility (admin_init)
//      check_hosting_compatibility (admin_init)
//      check_file_permissions (admin_init)
//      
//  Frontend
//      run (hook methods below, hooks in parantheses)
//      sbp_public (template_redirect) // do_action ( 'sbp_public' ); ayrıca wp-admin, feed, ajax, rest vb. kontrolünü unutma!
//      maybe_disable_sbp_frontend (sbp_public)
//      http_headers (send_headers)
//      
//  UTILITIES
//      notice manager
//      background worker
//      file handler (crud)
//      validate_option();
//      sanitize_option();
//      deactivation survey