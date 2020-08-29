<?php
/**
 * Endpoint Registrar for the plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Hook;

use WebDevStudios\OopsWP\Structure\Service;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class HookRegistrar extends Service {

	/**
	 * List of Service classes that should be registered
	 * by this service
	 *
	 * @var Array $hooks array of Service classes
	 * @since 0.1.0
	 */
	protected $hooks = [
		PostPublish::class,
	];

	/**
	 * Called by Plugin class; Iterate through $hooks and register them.
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_hooks() {
		foreach ( $this->hooks as $hooks_class ) {
			$hook = new $hooks_class();
			$this->register_hook( $hook );
		}
	}

	/**
	 * Register the given instantiated content class. This function
	 * largely exists as a check to make sure we are passing the correct
	 * object class.
	 *
	 * @param Service $hook Hook to register.
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	private function register_hook( Service $hook ) {
		$hook->register_hooks();
	}
}
