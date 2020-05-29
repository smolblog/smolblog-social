<?php
/**
 * The main class file.
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social;

use WebDevStudios\OopsWP\Structure\Plugin\Plugin;

/**
 * Class SmolblogSocial
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */
class SmolblogSocial extends Plugin {
	/**
	 * Class constructor.
	 *
	 * @param string $file_path The plugin file path.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since  0.1.0
	 */
	public function __construct( $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Services that this plugin registers.
	 *
	 * @var array
	 * @since 0.1.0
	 */
	protected $services = [
	];

	/**
	 * Register this plugin's services.
	 *
	 * Note: in standard implementations, there's no need to declare this function inside of your main plugin file,
	 * as it gets called automatically by the parent ServiceRegistrar class.
	 *
	 * That said, in your custom implementation, it's possible you might be using something like a Dependency Injection
	 * container in order to create your Service classes. Those Services might have parameters in their constructors,
	 * or there may just be something else necessary for you to process at the time the services get registered. As
	 * such, the `register_services` method has protected visibility so you can define that customization here. We've
	 * extended the method here primarily for demonstration purposes to let you know that it is possible.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since  0.1.0
	 */
	protected function register_services() { // phpcs:ignore
		parent::register_services();
	}
}
