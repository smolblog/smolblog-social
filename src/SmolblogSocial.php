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
		Endpoint\EndpointRegistrar::class,
		AdminPage\AdminPageRegistrar::class,
		Metadata\SocialMeta::class,
	];
}
