<?php
/**
 * This class is responsible for registration public hooks and other public functionality.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Services
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Services;

defined('ABSPATH') || exit;

class PublicService
{

    public function __construct()
    {
        if (is_admin() || wp_doing_cron() || wp_doing_ajax()) {
            return;
        }

        //TODO add descriptions
        add_action('template_redirect', [$this, 'templateRedirect'], 2);

        //add_action( 'shutdown', [ $this, 'shutdown' ], PHP_INT_MAX );

        // add_filter( 'wp_headers', [ $this, 'sbpHeaders' ] );

        add_filter('aioseo_flush_output_buffer', '__return_false');
    }

    /**
     * Basically a hook for functions which use output buffer
     * @return void
     * @since ??? //TODO add version
     */
    public function templateRedirect()
    {
        if (is_admin() || wp_doing_cron() || wp_doing_ajax()) {
            return;
        }
        ob_start([$this, 'outputBuffer']);
    }

    /**
     * Output buffer function
     * @param $html
     * @return mixed|string
     * @since ??? //TODO add version
     */
    public function outputBuffer($html)
    {

        if (is_embed() || $_SERVER['REQUEST_METHOD'] != 'GET' || !preg_match('/<\/html>/i', $html)) {
            return $html;
        }

        $html = apply_filters('sbp_output_buffer', $html);

        $html .= PHP_EOL . '<!-- Optimized by Speed Booster Pack v' . SBP_VERSION . ' -->';

        return $html;
    }


    /**
     * Shutdown function //TODO add description
     *
     * @return void
     * @since ??? //TODO add version
     */
    public function shutdown()
    {
        if (ob_get_length() != false) {
            ob_end_flush();
        }
    }

    /**
     * Add custom headers
     *
     * @param $headers
     * @return mixed
     * @since ??? //TODO add version
     */
    public function sbpHeaders($headers)
    {
        $headers['X-Powered-By'] = SBP_PLUGIN_NAME . ' v' . SBP_VERSION;

        return $headers;
    }
}