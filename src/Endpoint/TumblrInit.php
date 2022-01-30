<?php
/**
 * Endpoint for the tumblr OAuth Init
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;
use Tumblr\API\Client as TumblrClient;
use \WP_REST_Request;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class TumblrInit extends ApiEndpoint {
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
	protected $route = '/tumblr/init';

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
			'methods'             => [ 'POST', 'GET' ],
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

		$current_user = get_current_user_id();
		$current_blog = get_current_blog_id();

		// We want the callback to go to the root site.
		switch_to_blog( get_main_site_id() );

		$callback_url = get_rest_url( null, 'smolblog/v1/tumblr/callback' );

		$client          = new TumblrClient( SMOLBLOG_TUMBLR_APPLICATION_KEY, SMOLBLOG_TUMBLR_APPLICATION_SECRET );
		$request_handler = $client->getRequestHandler();
		$request_handler->setBaseUrl( 'https://www.tumblr.com/' );

		$resp = $request_handler->request( 'POST', 'oauth/request_token', [ 'oauth_callback' => $callback_url ] );

		$out  = $resp->body;
		$data = array();
		parse_str( $out, $data );

		$oauth_token = $data['oauth_token'] ?? '';

		set_transient(
			'smolblog_tumblr_' . $oauth_token,
			[
				'redirect_to'  => $current_blog,
				'user'         => $current_user,
				'oauth_token'  => $oauth_token,
				'oauth_secret' => $ata['oauth_token_secret'],
			],
			5 * MINUTE_IN_SECONDS
		);

		// Redirect to the login.
		header( 'Location: https://www.tumblr.com/oauth/authorize?oauth_token=' . $data['oauth_token'], true, 302 );

		die;
	}
}
