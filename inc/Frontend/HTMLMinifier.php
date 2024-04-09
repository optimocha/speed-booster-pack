<?php

namespace Optimocha\SpeedBooster\Frontend;

use Optimocha\SpeedBooster\Utils\HTMLMinify;

defined('ABSPATH') || exit;

class HTMLMinifier
{
    public function __construct()
    {
        if (!sbp_get_option('module_assets') || !sbp_get_option('minify_html')) {
            return;
        }

        add_action('set_current_user', [$this, 'run_class']);
    }

    public function run_class()
    {
        add_filter('sbp_output_buffer', [$this, 'handle_html_minify'], 11);
    }

    public function handle_html_minify($html): string
    {
        return HTMLMinify::minify($html);
    }
}
