<?php //phpcs:ignore Wordpress.Files.Filename
/**
 * Endpoint Registrar for the plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social;

use WebDevStudios\OopsWP\Structure\Service;
use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class EndpointRegistrar extends Service {

	/**
	 * List of ApiEndpoint classes that should be registered
	 * by this service
	 *
	 * @var Array $endpoints array of ApiEndpoint classes
	 * @since 0.1.0
	 */
	protected $endpoints = [
		TwitterCallback::class,
	];

	/**
	 * Called by Plugin class; register the hooks for this plugin
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_hooks() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	/**
	 * Iterate through $endpoints and register them.
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_endpoints() {
		foreach ( $this->endpoints as $endpoint_class ) {
			$endpoint = new $endpoint_class();
			$this->register_endpoint( $endpoint );
		}
	}

	/**
	 * Register the given instantiated content class. This function
	 * largely exists as a check to make sure we are passing the correct
	 * object class.
	 *
	 * @param ApiEndpoint $endpoint Endpoint to register.
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	private function register_endpoint( ApiEndpoint $endpoint ) {
		$endpoint->register();
	}
}
