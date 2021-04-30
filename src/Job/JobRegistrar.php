<?php
/**
 * Endpoint Registrar for the plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Service;
use Smolblog\Social\Import\Twitter;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class EndpointRegistrar extends Service {
	/**
	 * Called by Plugin class; register the hooks for this plugin
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_hooks() {
		add_action( 'init', [ $this, 'register_jobs' ], 10 );
	}

	/**
	 * Register jobs. Put job registration hooks here.
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_jobs() {
		$queue = new JobQueue();

		$queue->register_job( 'smolblog_import_twitter', [ ( new Twitter() ), 'import_twitter' ], 2 );
	}
}
