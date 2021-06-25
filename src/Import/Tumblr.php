<?php
/**
 * Handler for importing posts from Tumblr
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Import;

use Smolblog\Social\Job\JobQueue;
use Tumblr\API\Client as TumblrClient;

/**
 * Handle importing tweets from Twitter
 */
class Tumblr {
	/**
	 * Import the tumblr blog of the given authorized account.
	 *
	 * @param int    $account_id Database ID of the account to import.
	 * @param string $blog_name Name of blog to import.
	 * @return void
	 */
	public function import_tumblr( $account_id, $blog_name ) {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'smolblog_social';
		$account_info = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d", //phpcs:ignore
				$account_id
			)
		);

		echo "Loading Tumblr...\n";

		$client = new TumblrClient(
			SMOLBLOG_TUMBLR_APPLICATION_KEY,
			SMOLBLOG_TUMBLR_APPLICATION_SECRET,
			$access_info['oauth_token'],
			$access_info['oauth_token_secret']
		);

		$response = $client->getBlogPosts(
			$blog_name,
			[
				'reblog_info' => true,
				'npf'         => true,
			]
		);

		$posts_to_import = [];
		$max_twid        = -1;
		$all_empty       = true;

		foreach ( $response->posts as $post ) {
			if ( ! $this->has_been_imported( $post->id ) ) {
				$posts_to_import[] = $this->import_post( $post );
				$all_empty         = false;
			}
		}

		print_r( $posts_to_import );
		die;
	}

	/**
	 * Check to see if this tweet is already imported
	 *
	 * @param string $tumblr_id ID of tweet to check.
	 * @return bool True if tweet has been imported.
	 */
	public function has_been_imported( $tumblr_id ) {
		$check_query = new \WP_Query(
			[
				'meta_key'   => 'smolblog_social_import_id',
				'meta_value' => 'twitter_' . $tumblr_id,
			]
		);

		return $check_query->found_posts > 0;
	}

	private function import_post( $post ) {
		$new_post = [
			'date'      => $post->date,
			'tags'      => $post->tags,
			'slug'      => $post->slug,
			'excerpt'   => $post->summary,
			'import_id' => $post->id_string,
			'meta'      => [
				'tumblr_blocks' => $post->content,
			],
		];

		$new_post['status'] = $post->state;

		/*
			'post_title'   => $new_post['title'] ?? '',
			'post_content' => $new_post['content'],
			'post_date'    => $new_post['date'] ?? null,
			'post_excerpt' => $new_post['excerpt'] ?? null,
			'post_name'    => $new_post['slug'] ?? null,
			'post_author'  => $new_post['author'] ?? get_current_user_id(),
			'tags_input'   => $new_post['tags'],
			'meta_input'   => $new_post['meta'],
			$new_post['import_id']
			$new_post['reblog']
		*/

		return $new_post;
	}

	/**
	 * Convert date in CSV file to 1999-12-31 23:52:00 format
	 *
	 * @param string $data Date to convert.
	 * @return string Formatted date.
	 */
	private function parse_date( $data ) {
		$timestamp = strtotime( $data );
		if ( false === $timestamp ) {
				return '';
		} else {
				return gmdate( 'Y-m-d H:i:s', $timestamp );
		}
	}
}
