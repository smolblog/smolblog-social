<?php
/**
 * Endpoint for the Twitter OAuth Init
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class BlogSocialAccounts extends ApiEndpoint {
	/**
	 * Namespace for this endpoint
	 *
	 * @since 2019-05-01
	 * @var   string
	 */
	protected $namespace = 'smolblog/v1';

	/**
	 * Route for this endpoint
	 *
	 * @since 2019-05-01
	 * @var   string
	 */
	protected $route = '/social/accounts';

	/**
	 * Set up the arguments for this REST endpoint
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 *
	 * @return array Arguments for the endpoint
	 */
	protected function get_args() : array {
		return [
			'methods' => [ 'GET' ],
			'permission_callback' => [ $this, 'can_user_write' ],
		];
	}

	/**
	 * Check if user is able to use the post editor.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 *
	 * @return bool If current user has 'edit_post' permissions.
	 */
	public function can_user_write() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Execute code for the endpoint
	 *
	 * @since 0.1.0
	 * @author Evan Hildreth <me@eph.me>
	 *
	 * @param WP_REST_Request $request Current post object.
	 * @return void used as control structure only.
	 */
	public function run( WP_REST_Request $request = null ) {
		if ( ! $request ) {
			return;
		}

		$block_setting = get_option( 'smolblog_social_accounts' );
		// $num_accounts  = count( $block_setting );
		// for ( $index = 0; $index < $num_accounts; $index++ ) {
		// 	$block_setting[ $index ]['account_id'] = $index + 1;
		// }

		// error_log( 'Hello from BlogSocialAccounts' );
		// error_log( print_r( $block_setting, true ) );

		$response = new WP_REST_Response( $block_setting );
		$response->set_status( 200 );

		return $response;
	}
}
