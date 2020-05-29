<?php //phpcs:ignore Wordpress.Files.Filename
/**
 * Admin page Registrar for the plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\AdminPage;

use WebDevStudios\OopsWP\Structure\Service;
use WebDevStudios\OopsWP\Utility\Hookable;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class AdminPageRegistrar extends Service {

	/**
	 * List of Hookable classes that should be registered
	 * by this service
	 *
	 * @var Array $pages array of Hookable classes
	 * @since 0.1.0
	 */
	protected $pages = [
		SmolblogMain::class,
	];

	/**
	 * Called by Plugin class; register the hooks for this plugin
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_hooks() {
		add_action( 'init', [ $this, 'register_pages' ] );
	}

	/**
	 * Iterate through $pages and register them.
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	public function register_pages() {
		foreach ( $this->pages as $page_class ) {
			$page = new $page_class();
			$this->register_page( $page );
		}
	}

	/**
	 * Register the given instantiated content class. This function
	 * largely exists as a check to make sure we are passing the correct
	 * object class.
	 *
	 * @param Hookable $page Endpoint to register.
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 */
	private function register_page( Hookable $page ) {
		$page->register_hooks();
	}
}
