<?php
/**
 * Main admin page for this plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Metadata;

use WebDevStudios\OopsWP\Structure\Service;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class SocialMeta extends Service {
	/**
	 * Register the hook to actiavate this metadata
	 */
	public function register_hooks() {
		add_action( 'init', [ $this, 'init_social_metadata' ] );
	}

	/**
	 * My init callback.
	 */
	public function init_social_metadata() {
		register_meta(
			'post',
			'smolblog_social_meta',
			[
				'type'         => 'array',
				'description'  => 'The data (to be) sent to different social platforms for this post',
				'single'       => true,
				'show_in_rest' => true,
			]
		);
	}
}
