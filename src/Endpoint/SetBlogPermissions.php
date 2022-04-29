<?php
/**
 * Endpoint for getting the accounts for this user and blog
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;
use Smolblog\Social\Model\AccountBlogLink;
use Smolblog\Social\Model\SocialAccount;
use \WP_REST_Request;
use \WP_REST_Response;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class SetBlogPermissions extends ApiEndpoint {
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
	protected $route = '/accounts/blogs/setpermissions';

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
			'methods'             => [ 'POST' ],
			'permission_callback' => [ $this, 'is_admin_on_blog' ],
		];
	}

	/**
	 * Check if user is logged in; 'manage_options' permissions are given
	 * to Admins.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 *
	 * @return bool If current user has 'manage_options' permissions.
	 */
	public function is_admin_on_blog() {
		return current_user_can( 'manage_options' );
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

		$current_user = get_current_user_id();
		$current_blog = get_current_blog_id();
		$account = new SocialAccount($request['social_id']);

		if ( $account->needs_save() || $account->user_id !== $current_user ) {
			return new WP_Error(
				'not_found',
				'The indicated social account was not found.',
				[ 'status' => 404 ]
			);
		}

		$link = new AccountBlogLink($current_blog, $request['social_id']);
		$link->can_push = $request['push'];
		$link->can_pull = $request['pull'];

		if ( $request['additional_info'] && ! $link->additional_info ) {
			$link->additional_info = $request['additional_info'];
		}

		$link->save;

		return new WP_REST_Response( [ 'success' => true ] );
	}
}
