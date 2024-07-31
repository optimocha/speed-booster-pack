<?php
/**
 * The core plugin class.
 *
 * This class is responsible for initializing the plugin.
 * It loads the plugin constants, registers the activation and deactivation hooks, and initializes the plugin services.
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SpeedBoosterPack\Booster\DatabaseOptimizer;
use SpeedBoosterPack\Configurations\Constants;
use SpeedBoosterPack\Configurations\DI;
use SpeedBoosterPack\Configurations\Filters;
use SpeedBoosterPack\Services\ActivationService;
use SpeedBoosterPack\Services\AdminService;
use SpeedBoosterPack\Services\AjaxService;
use SpeedBoosterPack\Services\AssetService;
use SpeedBoosterPack\Services\BoosterService;
use SpeedBoosterPack\Services\DeactivationService;
use SpeedBoosterPack\Services\i18nService;
use SpeedBoosterPack\Services\MetaboxService;
use SpeedBoosterPack\Services\OptionService;
use SpeedBoosterPack\Services\PostTypeService;
use SpeedBoosterPack\Services\PublicService;

defined('ABSPATH') || exit;

class App
{

    /**
     * List of services to be initialized
     * @var array
     * @since 5.0.0
     */
    private array $services = [
        AssetService::class,
        i18nService::class,
        AdminService::class,
        PublicService::class,
        PostTypeService::class,
        AjaxService::class,
        OptionService::class,
        MetaboxService::class,
	    BoosterService::class,
    ];

    /**
     * Run the plugin
     * @return void
     * @throws Exception
     * @since 5.0.0
     */
    public function run()
    {

        //Check if plugin should run, else return
        if (!$this->shouldPluginRun()) {
            return;
        }

        //Load plugin constants first
        DI::container()->get(Constants::class);
        //Activation
        register_activation_hook(SBP_PLUGIN_BASENAME, [$this, 'initActivation']);
        //Deactivation
        register_deactivation_hook(SBP_PLUGIN_BASENAME, [$this, 'initDeactivation']);
        //Services
        add_action('plugins_loaded', [$this, 'initPluginServices']);

    }

    /**
     * Check if the plugin should run
     * @return bool
     * @since 5.0.0
     */
    public function shouldPluginRun(): bool
    {

        //TODO is escape url affected the operation of the plugin?
        if (preg_match('/(_wp-|\.txt|\.pdf|\.xml|\.xsl|\.svg|\.ico|\/wp-json|\.gz|\/feed\/?)/', esc_url($_SERVER['REQUEST_URI']))) {
            return false;
        }

        $login_path = parse_url(wp_login_url(), PHP_URL_PATH);

        if (false !== stripos(esc_url($_SERVER['REQUEST_URI']), $login_path)) {
            return false;
        }

        $query_strings_to_exclude = Filters::queryStringsToExclude();

        foreach ($query_strings_to_exclude as $query_string => $value) {
            if (isset($_GET[$query_string]) && ($value == sanitize_text_field($_GET[$query_string]) || null == $value)) {
                return false;
            }
        }

        // Brizy Editor
        if (class_exists('Brizy_Editor')) {

            $brizyEditorEdit = sanitize_text_field($_GET[Brizy_Editor::prefix('-edit')]);
            $brizyEditorEditFrame = sanitize_text_field($_GET[Brizy_Editor::prefix('-edit-iframe')]);

            if ((isset($brizyEditorEdit) || isset($brizyEditorEditFrame))) {
                return false;
            }

        }

        return true;
    }

    /**
     * Initialize the plugin activation
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     * @since 5.0.0
     */
    public function initActivation()
    {
        DI::container()->get(ActivationService::class)->activate();
    }

    /**
     * Initialize the plugin deactivation
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     * @since 5.0.0
     */
    public function initDeactivation()
    {
        DI::container()->get(DeactivationService::class)->deactivate();
    }

    /**
     * Initialize the plugin services
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
     * @since 5.0.0
     */
    public function initPluginServices()
    {
        do_action('speed_booster_pack_before_init');

        foreach ($this->services as $service) {
            DI::container()->get($service);
        }

        do_action('speed_booster_pack_after_init');
    }
}
