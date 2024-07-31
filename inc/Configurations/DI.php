<?php
/**
 * DI Container
 *
 * Uses PHP-DI to create a DI container
 *
 * @since      5.0.0
 * @package    SpeedBoosterPack
 * @subpackage SpeedBoosterPack/Configurations
 * @author     Optimocha <info@speedboosterpack.com>
 * @link       https://optimocha.com
 */

namespace SpeedBoosterPack\Configurations;

defined('ABSPATH') || exit;

use DI\Container;
use DI\ContainerBuilder;
use Exception;

class DI
{

    /**
     * Get the DI container
     * @return Container
     * @throws Exception
     * @since 5.0.0
     */
    public static function container(): Container
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);

        return $containerBuilder->build();
    }
}
