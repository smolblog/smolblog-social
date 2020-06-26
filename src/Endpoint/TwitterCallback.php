<?php
/**
 * Endpoint for the Twitter OAuth Callback
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use WebDevStudios\OopsWP\Structure\Content\ApiEndpoint;
use Abraham\TwitterOAuth\TwitterOAuth;
use \WP_REST_Request;

/**
 * Class to register our custom post types
 *
 * @since 0.1.0
 */
class TwitterCallback extends ApiEndpoint {
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
	protected $route = '/twitter/callback';

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
		global $wpdb;

		if ( ! $request ) {
			return;
		}
		$current_user  = get_current_user_id();
		$request_token = get_transient( 'smolblog_twitter_oauth_request_' . $current_user );

		if ( isset( $_REQUEST['oauth_token'] ) && $request_token['oauth_token'] !== $_REQUEST['oauth_token'] ) {
			wp_die( 'OAuth tokens did not match; <a href="' . get_rest_url( null, 'smolblog/v1/twitter/init' ) . '">try again</a>' );
		}

		$connection = new TwitterOAuth(
			SMOLBLOG_TWITTER_APPLICATION_KEY,
			SMOLBLOG_TWITTER_APPLICATION_SECRET,
			$request_token['oauth_token'],
			$request_token['oauth_token_secret']
		);

		$access_info = $connection->oauth( 'oauth/access_token', [ 'oauth_verifier' => $_REQUEST['oauth_verifier'] ] );

		$wpdb->insert(
			$wpdb->prefix . 'smolblog_social',
			[
				'user_id'         => $current_user,
				'social_id'       => $access_info['user_id'],
				'social_username' => $access_info['screen_name'],
				'oauth_token'     => $access_info['oauth_token'],
				'oauth_secret'    => $access_info['oauth_token_secret'],
			],
			[ '%d', '%s', '%s', '%s', '%s' ],
		);

		header( 'Location: /wp-admin/admin.php?page=smolblog', true, 302 );
		die;
	}
}
