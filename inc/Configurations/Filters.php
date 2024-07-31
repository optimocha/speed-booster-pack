<?php
/**
 * This class is responsible for defining plugin filters.
 *
 * It defines all the plugin filters as array or string.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Configurations
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Configurations;

defined('ABSPATH') || exit;

class Filters
{

    /**
     * List of query strings to exclude from cache.
     *
     * @return array
     * @since 5.0.0
     */
    public static function queryStringsToExclude(): array
    {
        return [
            "sbp_disable" => "1", // speed booster pack
            "elementor-preview" => "elementor", // elementor
            "ai-debug-blocks" => "1", // ad inserter
            "ao_noptimize" => "1", // autoptimize
            "ao_noptirocket" => "1", // autoptimize & wp rocket
            "bt-beaverbuildertheme" => "show", // beaver builder 2
            "ct_builder" => "true", // oxygen builder
            "customize_changeset_uuid" => null, // wordpress core customizer
            "et_fb" => "1", // divi builder
            "fb-edit" => "1", // fusion builder
            "fl_builder" => null, // beaver builder 1
            "PageSpeed" => "off", // mod_pagespeed
            "preview" => "true", // wordpress core preview
            "siteorigin_panels_live_editor" => null, // siteorigin page builder
            "tb_preview" => "1", // themify builder
            "tipi_builder" => "1", // tipi builder
            "tve" => "true", // thrive architect
            "vc_action" => "vc_inline", // wpbakery page builder
        ];
    }

}