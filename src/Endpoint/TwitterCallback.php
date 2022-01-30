<?php
/**
 * Endpoint for the Twitter OAuth Callback
 *
 * @since 0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Endpoint;

use Smolblog\Social\Model\SocialAccount;
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
		// No security check at this level;
		// this needs to work outside of authenticated requests.
		return [
			'methods' => [ 'POST', 'GET' ],
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
		if ( ! $request || ! isset( $request['oauth_token'] ) ) {
			return;
		}
		$request_info = get_transient( 'smolblog_twitter_' . $request['oauth_token'] );

		if ( $request_info === false ) {
			wp_die( 'OAuth tokens did not match; <a href="' . esc_attr( get_rest_url( null, 'smolblog/v1/twitter/init' ) ) . '">try again</a>' );
		}

		$connection = new TwitterOAuth(
			SMOLBLOG_TWITTER_APPLICATION_KEY,
			SMOLBLOG_TWITTER_APPLICATION_SECRET,
			$request_info['oauth_token'],
			$request_info['oauth_token_secret']
		);

		$access_info = $connection->oauth( 'oauth/access_token', [ 'oauth_verifier' => $request['oauth_verifier'] ] );

		$account = new SocialAccount();

		$account->user_id         = $request_info['user'];
		$account->social_type     = 'twitter';
		$account->social_username = $access_info['screen_name'];
		$account->oauth_token     = $access_info['oauth_token'];
		$account->oauth_secret    = $access_info['oauth_token_secret'];

		$account->save();

		header( 'Location: ' . get_admin_url( $request_info['redirect_to'], 'admin.php?page=smolblog' ), true, 302 );
		die;
	}
}
