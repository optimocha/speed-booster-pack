<?php

namespace Optimocha\SpeedBooster;

defined('ABSPATH') || exit;

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Optimocha\SpeedBooster
 * @author     Optimocha
 */
class Loader
{
    /**
     * The array of actions registered with WordPress.
     *
     * @since    4.0.0
     * @access   protected
     * @var      array $actions The actions registered with WordPress to fire when the plugin loads.
     */
    private $actions = [];

    /**
     * The array of filters registered with WordPress.
     *
     * @since    4.0.0
     * @access   protected
     * @var      array $filters The filters registered with WordPress to fire when the plugin loads.
     */
    private $filters = [];

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    4.0.0
     */
    public function __construct()
    {}

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @param string $hook The name of the WordPress action that is being registered.
     * @param object $component A reference to the instance of the object on which the action is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     *
     * @since    4.0.0
     */
    public function add_action(string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1)
    {
        $this->add('actions', $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @param string $hook The name of the WordPress filter that is being registered.
     * @param object $component A reference to the instance of the object on which the filter is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority Optional. The priority at which the function should be fired. Default is 10.
     * @param int $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1
     *
     * @since    4.0.0
     */
    public function add_filter(string $hook, $component, string $callback, int $priority = 10, int $accepted_args = 1)
    {
        $this->add('filters', $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @param string $type Type of the hook (actions or filters)
     * @param string $hook The name of the WordPress filter that is being registered.
     * @param object $component A reference to the instance of the object on which the filter is defined.
     * @param string $callback The name of the function definition on the $component.
     * @param int $priority The priority at which the function should be fired.
     * @param int $accepted_args The number of arguments that should be passed to the $callback.
     *
     * @return   void
     * @since    4.0.0
     * @access   private
     */
    private function add(string $type, string $hook, $component, string $callback, int $priority, int $accepted_args)
    {
        $this->{$type}[] = [
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        ];
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    4.0.0
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], [$hook['component'], $hook['callback']], $hook['priority'], $hook['accepted_args']);
        }
    }

}
