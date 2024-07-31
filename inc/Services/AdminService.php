<?php
/**
 * This class is responsible for registration admin hooks and other admin functionality.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

use CSF;
use SpeedBoosterPack\Booster\AdvancedCacheGenerator;
use SpeedBoosterPack\Booster\Cache;
use SpeedBoosterPack\Booster\Cloudflare;
use SpeedBoosterPack\Booster\LiteSpeedCache;
use SpeedBoosterPack\Booster\NoticeManager;
use SpeedBoosterPack\Booster\Woocommerce;
use SpeedBoosterPack\Booster\WpConfigInjector;
use SpeedBoosterPack\Common\Helper;

defined('ABSPATH') || exit;

class AdminService
{
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

    public function __construct()
    {

        $this->plugin_name = SBP_PLUGIN_SLUG;
        $this->version = SBP_VERSION;
        $this->woocommerce_analytics = SBP_WOOCOMMERCE_ANALYTICS;
        $this->woocommerce_tracking = SBP_WOOCOMMERCE_TRACKING;

        if (!is_admin() || wp_doing_cron() || wp_doing_ajax()) {
            return;
        }

        //Include plugin framework
        require_once SBP_LIB_PATH . 'codestar-framework/codestar-framework.php';

        add_filter('rocket_plugins_to_deactivate', '__return_empty_array');
        add_filter('csf_sbp_options_saved', [Cache::class, 'options_saved_filter']);

        add_action('woocommerce_loaded', [$this, 'getWoocommerceOptions']);
        add_action('csf_sbp_options_save_before', [Cache::class, 'options_saved_listener']);
        add_action('csf_sbp_options_save_before', [Cloudflare::class, 'update_cloudflare_settings']);
        add_action('csf_sbp_options_saved', [Woocommerce::class, 'set_woocommerce_option_tracking']);
        add_action('csf_sbp_options_saved', [Woocommerce::class, 'set_woocommerce_option_analytics']);
        add_action('csf_sbp_options_saved', [Cache::class, 'clear_total_cache']);
        add_action('csf_sbp_options_saved', [Cache::class, 'generate_htaccess']);
        add_action('csf_sbp_options_saved', [LiteSpeedCache::class, 'insert_htaccess_rules']);
        add_action('csf_sbp_options_saved', [WpConfigInjector::class, 'remove_wp_config_lines']);
        add_action('admin_enqueue_scripts', 'add_thickbox');
        add_action('admin_print_footer_scripts', [$this, 'modifyMenuTitle']);
        add_action('admin_init', [$this, 'setUpDefaults']);
        add_action('admin_init', [$this, 'redirect']);
    }

    public static function setUpDefaults()
    {

        if (!get_option('sbp_activation_defaults')) {
            return;
        }

        if (Helper::getOption('module_caching') && !Helper::sbpShouldDisableFeature('caching')) {

            Cache::clear_total_cache();
            Cache::set_wp_cache_constant(true);
            Cache::generate_htaccess();

            $advanced_cache_file_content = AdvancedCacheGenerator::generate_advanced_cache_file();
            $advanced_cache_path = WP_CONTENT_DIR . '/advanced-cache.php';
            if ($advanced_cache_file_content) {
                file_put_contents($advanced_cache_path, $advanced_cache_file_content);
            }

        }

        if (Helper::getOption('module_caching_ls') && !Helper::sbpShouldDisableFeature('caching')) {
            LiteSpeedCache::insert_htaccess_rules();
        }

        delete_option('sbp_activation_defaults');

    }

    public static function redirect()
    {

        if (!get_option('sbp_activation_redirect') || !current_user_can('manage_options')) {
            return;
        }

        // Make sure it's the correct user
        if (intval(get_option('sbp_activation_redirect', false)) === wp_get_current_user()->ID) {
            // Make sure we don't redirect again after this one
            delete_option('sbp_activation_redirect');
            wp_safe_redirect(admin_url('admin.php?page=sbp-settings'));
            exit;
        }

    }


    public function getWoocommerceOptions()
    {
        $this->woocommerce_analytics = Woocommerce::get_woocommerce_option('woocommerce_analytics_enabled');
        $this->woocommerce_tracking = Woocommerce::get_woocommerce_option('woocommerce_allow_tracking');
    }

    public function modifyMenuTitle()
    {

        $count = NoticeManager::get_notice_count();

        if ($count) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $('#toplevel_page_sbp-settings .wp-menu-name').append('&nbsp;<span class="update-plugins count-<?php echo $count; ?>"><?php echo $count; ?></span>');
                });
            </script>
            <?php

            return sprintf(
                ' %1$s',
                esc_html(number_format_i18n($count))
            );
        }
    }

}