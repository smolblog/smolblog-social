<?php //phpcs:ignore Wordpress.Files.Filename
/**
 * Endpoint for the Twitter OAuth Init
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class TwitterInit extends ApiEndpoint {
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
	protected $route = '/twitter/init';

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
		];
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
		$callback_url = get_rest_url( null, 'smolblog/v1/twitter/callback' );
		$connection   = new TwitterOAuth( SMOLBLOG_TWITTER_APPLICATION_KEY, SMOLBLOG_TWITTER_APPLICATION_SECRET );

		$request_token = $connection->oauth( 'oauth/request_token', array( 'oauth_callback' => $callback_url ) );

		set_transient( 'smolblog_twitter_oauth_request_' . $current_user, $request_token, 5 * MINUTE_IN_SECONDS );

		$url = $connection->url( 'oauth/authorize', array( 'oauth_token' => $request_token['oauth_token'] ) );

		header( 'Location: ' . $url, true, 302 );
		die;
	}
}
