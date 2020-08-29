<?php
/**
 * Main admin page for this plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Hook;

use WebDevStudios\OopsWP\Structure\Service;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class PostPublish extends Service {
	/**
	 * Register the hook to actiavate this metadata.
	 *
	 * @see https://stackoverflow.com/questions/20087203/wordpress-hook-after-adding-updating-post-and-after-insertion-of-post-meta
	 */
	public function register_hooks() {
		add_action( 'added_post_meta', [ $this, 'added_post_meta' ], 100, 4 );
		add_action( 'updated_post_meta', [ $this, 'updated_post_meta' ], 100, 4 );
		add_action( 'save_post_post', [ $this, 'save_post_post' ], 100, 2 );
		add_action( 'publish_post', [ $this, 'publish_post' ], 100, 2 );
	}

	public function added_post_meta( $meta_id, $post_id, $key, $value ) {
		error_log( 'Hello from added_post_meta' );
		error_log( print_r( [
			'meta_id' => $meta_id,
			'post_id' => $post_id,
			'key'     => $key,
			'value'   => $value,
		], true) );
	}

	public function updated_post_meta( $meta_id, $post_id, $key, $value ) {
		error_log( 'Hello from updated_post_meta' );
		error_log( print_r( [
			'meta_id' => $meta_id,
			'post_id' => $post_id,
			'key'     => $key,
			'value'   => $value,
		], true) );
	}

	public function save_post_post( $post_id, $post ) {
		error_log( 'Hello from save_post_post' );
		error_log( print_r( [
			'post_id' => $post_id,
			'post'    => $post,
			'meta'    => get_post_meta( $post_id, 'smolblog_social_meta', true ),
		], true) );
	}

	public function publish_post( $post_id, $post ) {
		error_log( 'Hello from publish_post' );
		error_log( print_r( [
			'post_id' => $post_id,
			'post'    => $post,
			'meta'    => get_post_meta( $post_id, 'smolblog_social_meta', true ),
		], true) );
	}

	/**
	 * Post the indicated tweets to Twitter
	 *
	 * @param int $post_id Post being published.
	 * @return void
	 */
	public function post_tweet_meta( $meta_id, $post_id, $key, $value ) {
		$post_status = get_post_status( $post_id );
		error_log( 'Hello from PostPublish' );
		error_log( print_r( [
			'meta_id' => $meta_id,
			'post_id' => $post_id,
			'key'     => $key,
			'value'   => $value,
			'status'  => $post_status,
		], true) );
		if ( 'publish' !== $post_status || 'smolblog_social_meta' !== $key ) {
			return;
		}

		$payload = json_decode( $value );
		error_log( print_r( $payload, true ) );
	}

	public function post_tweet_publish( $post_id, $post ) {
		$post_status = get_post_status( $post_id );
		error_log( 'Hello from PostPublish' );
		error_log( print_r( [
			'REQUEST' => $_REQUEST,
			'post_id' => $post_id,
			'post'    => $post,
			'update'  => $update,
			'status'  => $post_status,
			'meta'    => get_post_meta( $post_id, 'smolblog_social_meta', true ),
		], true) );
		// if ( 'publish' !== $post_status || 'smolblog_social_meta' !== $key ) {
		// 	return;
		// }

		// $payload = json_decode( $value );
		// error_log( print_r( $payload, true ) );
	}
}
