<?php
/**
 * Endpoint for getting the accounts for this user and blog
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;
use Smolblog\Social\Model\SocialAccount;
use Tumblr\API\Client as TumblrClient;
use \WP_Error;
use \WP_REST_Request;
use \WP_REST_Response;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class SubAccounts extends ApiEndpoint {
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
	protected $route = '/accounts/(?P<id>[0-9]+)/subaccounts';

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
			'methods'             => [ 'GET' ],
			'permission_callback' => [ $this, 'is_user_logged_in' ],
		];
	}

	/**
	 * Check if user is logged in; 'read' permissions are given
	 * to Subscribers.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 *
	 * @return bool If current user has 'read' permissions.
	 */
	public function is_user_logged_in() {
		return current_user_can( 'read' );
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

		$account = new SocialAccount($request['id']);
		$current_user = get_current_user_id();

		if ( $account->needs_save() || $account->user_id != $current_user ) {
			return new WP_Error(
				'not_found',
				'The indicated social account was not found.',
				[ 'status' => 404 ]
			);
		}

		switch ( $account->social_type ) {
			case 'tumblr':
				$client = new TumblrClient(
					SMOLBLOG_TUMBLR_APPLICATION_KEY,
					SMOLBLOG_TUMBLR_APPLICATION_SECRET,
					$account->oauth_token,
					$account->oauth_secret
				);
				$blogs  = $client->getUserInfo()->user->blogs;
				return new WP_REST_Response( array_map( function( $blog ) {
					return [
						'title' => $blog->title,
						'name'  => $blog->name,
						'url'   => $blog->url,
					];
				}, $blogs ) );
		}

		return new WP_Error(
			'not_found',
			'This social account does not belong to a servie with subaccounts.',
			[ 'status' => 404 ]
		);
	}
}
