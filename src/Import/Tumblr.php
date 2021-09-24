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
	public function import_tumblr( $account_id, $blog_name, $before = null ) {
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
			$account_info[0]->oauth_token,
			$account_info[0]->oauth_secret
		);

		$response = $client->getBlogPosts(
			$blog_name,
			[
				'reblog_info' => true,
				'npf'         => true,
				'before'      => $before ?? time(),
			]
		);

		$posts_to_import = [];
		$last_timestamp  = -1;
		$all_empty       = true;

		foreach ( $response->posts as $post ) {
			if ( ! $this->has_been_imported( $post->id ) ) {
				$posts_to_import[] = $this->import_post( $post );
				$all_empty         = false;
				$last_timestamp    = $post->timestamp;
			}
		}

		$loader = new CreatePost();
		foreach ( $posts_to_import as $post ) {
			$post_id = $loader->create_post( $post );
		}

		if ( ! $all_empty ) {
			( new JobQueue() )->enqueue_single_job(
				'smolblog_import_twitter',
				[
					$account_id,
					$blog_name,
					( $last_timestamp - 1 ),
				]
			);
		}
	}

	/**
	 * Check to see if this post is already imported
	 *
	 * @param string $tumblr_id ID of post to check.
	 * @return bool True if post has been imported.
	 */
	public function has_been_imported( $tumblr_id ) {
		$check_query = new \WP_Query(
			[
				'meta_key'   => 'smolblog_social_import_id',
				'meta_value' => 'tumblr_' . $tumblr_id,
			]
		);

		return $check_query->found_posts > 0;
	}

	private function import_post( $post ) {
		$new_post = [
			'date'      => wp_date( DATE_RFC3339, $post->timestamp ),
			'tags'      => $post->tags,
			'slug'      => $post->slug,
			'status'    => $this->parse_state( $post->state ),
			'excerpt'   => $post->summary,
			'import_id' => "tumblr_$post->id_string",
			'meta'      => [
				// 'tumblr_trail' => $post->trail,
			],
			'content'   => $this->parse_blocks( $post->content ),
			'reblog'    => $post->reblogged_from_url ?? null,
		];

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

	private function parse_state( $state ) {
		switch ( strtolower( $state ) ) {
			case 'queued':
				return 'future';
			case 'draft':
				return 'draft';
			case 'private':
				return 'private';
			case 'published':
				return 'publish';
		}
		return 'draft';
	}

	private function parse_blocks( $blocks ) : string {
		$parsed = '';

		foreach ( $blocks as $block_index => $block ) {
			switch ( strtolower( $block->type ) ) {
				case 'text':
					$parsed .= $this->parse_text_block( $block ) . "\n\n";
					break;
				// case 'image':
				// $parsed .= $this->parse_image( $block ) . "\n\n";
				// break;
				// case 'link':
				// $parsed .= $this->parse_link( $block ) . "\n\n";
				// break;
				// case 'audio':
				// $parsed .= $this->parse_audio( $block ) . "\n\n";
				// break;
				// case 'video':
				// $parsed .= $this->parse_video( $block ) . "\n\n";
				// break;
			}
		}

		return $parsed;
	}

	private function parse_text_block( $block ) : string {
		if ( ! isset( $block->subtype ) ) {
			return "<!-- wp:paragraph -->\n<p>$block->text</p>\n<!-- /wp:paragraph -->";
		}
		switch ( strtolower( $block->subtype ) ) {
			case 'heading1':
				return "<!-- wp:heading {\"level\":1} -->\n<h1>$block->text</h1>\n<!-- /wp:heading -->";
			case 'heading2':
				return "<!-- wp:heading -->\n<h2>$block->text</h2>\n<!-- /wp:heading -->";
			case 'quirky':
				return "<!-- wp:paragraph -->\n<p>$block->text</p>\n<!-- /wp:paragraph -->";
			case 'quote':
				return "<!-- wp:pullquote -->\n<figure class=\"wp-block-pullquote\"><blockquote><p>$block->text</p></blockquote></figure>\n<!-- /wp:pullquote -->";
			case 'indented':
				return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>$block->text</p></blockquote>\n<!-- /wp:quote -->";
			case 'ordered-list-item':
				return "<!-- wp:list {\"ordered\":true} -->\n<ol>\n<li>$block->text</li>\n</ol>\n<!-- /wp:list -->\n\n";
			case 'unordered-list-item':
				return "<!-- wp:list -->\n<ul>\n<li>$block->text</li>\n</ul>\n<!-- /wp:list -->\n\n";
			case 'chat':
				return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>$block->text</code></pre>\n<!-- /wp:code -->";
		}
		return "<!-- wp:paragraph -->\n<p>$block->text</p>\n<!-- /wp:paragraph -->";
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
