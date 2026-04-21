<?php
/**
 * Loader Class
 *
 * Manages all hooks (actions and filters) for the plugin.
 *
 * @package Notice_Tracker
 * @subpackage Core
 */

namespace Notice_Tracker\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loader Class
 *
 * Registers all actions and filters for the plugin.
 *
 * @since 1.0.0
 */
class Loader {

	/**
	 * Array of actions to register.
	 *
	 * @var array
	 */
	protected $actions = array();

	/**
	 * Array of filters to register.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Add a new action to the collection.
	 *
	 * @since 1.0.0
	 * @param string $hook          The name of the WordPress action.
	 * @param object $component     The object instance.
	 * @param string $callback      The callback method name.
	 * @param int    $priority      Optional. Priority. Default 10.
	 * @param int    $accepted_args Optional. Number of arguments. Default 1.
	 * @return void
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection.
	 *
	 * @since 1.0.0
	 * @param string $hook          The name of the WordPress filter.
	 * @param object $component     The object instance.
	 * @param string $callback      The callback method name.
	 * @param int    $priority      Optional. Priority. Default 10.
	 * @param int    $accepted_args Optional. Number of arguments. Default 1.
	 * @return void
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add hook to collection.
	 *
	 * @since 1.0.0
	 * @param array  $hooks         The collection of hooks.
	 * @param string $hook          The name of the WordPress hook.
	 * @param object $component     The object instance.
	 * @param string $callback      The callback method name.
	 * @param int    $priority      Priority.
	 * @param int    $accepted_args Number of arguments.
	 * @return array Updated collection of hooks.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all hooks with WordPress.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run() {
		// Register all actions.
		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		// Register all filters.
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}

