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
		$parsed_blocks = $this->parse_blocks( $post->content );
		$new_post      = [
			'title'     => $parsed_blocks['title'] ?? null,
			'date'      => wp_date( DATE_RFC3339, $post->timestamp ),
			'tags'      => $post->tags,
			'slug'      => $post->slug,
			'status'    => $this->parse_state( $post->state ),
			'excerpt'   => $post->summary,
			'import_id' => "tumblr_$post->id_string",
			'meta'      => [
				// 'tumblr_trail' => $post->trail,
			],
			'content'   => $parsed_blocks['content'],
			'reblog'    => $post->reblogged_from_url ?? null,
			'media'     => $parsed_blocks['media'],
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

		return array_filter( $new_post );
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

	private function parse_format_tags( $format ) : array {
		// Using the non-semantic tags here because we do not know the semantic meanting.
		switch ( strtolower( $format->type ) ) {
			case 'bold':
				return [ '<b>', '</b>' ];
			case 'italic':
				return [ '<i>', '</i>' ];
			case 'strikethrough':
				return [ '<s>', '</s>' ];
			case 'small':
				return [ '<small>', '</small>' ];
			case 'link':
				return [ "<a href=\"$format->url\">", '</a>' ];
			case 'mention':
				return [ "<a href=\"{$format->blog->url}\">", '</a>' ];
			case 'color':
				return [ "<span style=\"color:$format->hex\">", '</span>' ];
		}
		return [ '<span>', '</span>' ];
	}

	private function parse_blocks( $blocks ) : array {
		$title  = null;
		$parsed = '';
		$media  = [];

		foreach ( $blocks as $block_index => $block ) {
			switch ( strtolower( $block->type ) ) {
				case 'text':
					if ( ! $title && isset( $block->subtype ) && 'heading1' === strtolower( $block->subtype ) ) {
						// If this is the first H1, it's the post's title.
						$title = $block->text;
						break;
					}
					$parsed .= $this->parse_text_block( $block ) . "\n\n";
					break;
				case 'image':
					$local_id = count( $media );
					$parsed  .= "#SMOLBLOG_MEDIA_IMPORT#{$local_id}#\n\n";
					$media[]  = $this->parse_image( $block );
					break;
				case 'link':
					$parsed .= $this->parse_link( $block ) . "\n\n";
					break;
				case 'audio':
					$block = $this->parse_audio( $block );
					if ( is_array( $block ) ) {
						$local_id = count( $media );
						$parsed  .= "#SMOLBLOG_MEDIA_IMPORT#{$local_id}#\n\n";
						$media[]  = $block;
						break;
					}
					$parsed .= $block . "\n\n";
					break;
				case 'video':
					$block = $this->parse_video( $block );
					if ( is_array( $block ) ) {
						$local_id = count( $media );
						$parsed  .= "#SMOLBLOG_MEDIA_IMPORT#{$local_id}#\n\n";
						$media[]  = $block;
						break;
					}
					$parsed .= $block . "\n\n";
					break;
			}
		}

		return [
			'title'   => $title,
			'content' => $parsed,
			'media'   => $media,
		];
	}

	private function parse_text_block( $block ) : string {
		$block_text = $this->parse_text_formatting( $block );

		if ( ! isset( $block->subtype ) ) {
			return "<!-- wp:paragraph -->\n<p>$block_text</p>\n<!-- /wp:paragraph -->";
		}
		switch ( strtolower( $block->subtype ) ) {
			case 'heading1':
				return "<!-- wp:heading {\"level\":1} -->\n<h1>$block_text</h1>\n<!-- /wp:heading -->";
			case 'heading2':
				return "<!-- wp:heading -->\n<h2>$block_text</h2>\n<!-- /wp:heading -->";
			case 'quirky':
				return "<!-- wp:paragraph -->\n<p>$block_text</p>\n<!-- /wp:paragraph -->";
			case 'quote':
				return "<!-- wp:pullquote -->\n<figure class=\"wp-block-pullquote\"><blockquote><p>$block_text</p></blockquote></figure>\n<!-- /wp:pullquote -->";
			case 'indented':
				return "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>$block_text</p></blockquote>\n<!-- /wp:quote -->";
			case 'ordered-list-item':
				return "<!-- wp:list {\"ordered\":true} -->\n<ol>\n<li>$block_text</li>\n</ol>\n<!-- /wp:list -->\n\n";
			case 'unordered-list-item':
				return "<!-- wp:list -->\n<ul>\n<li>$block_text</li>\n</ul>\n<!-- /wp:list -->\n\n";
			case 'chat':
				return "<!-- wp:code -->\n<pre class=\"wp-block-code\"><code>$block_text</code></pre>\n<!-- /wp:code -->";
		}
		return "<!-- wp:paragraph -->\n<p>$block_text</p>\n<!-- /wp:paragraph -->";
	}

	private function parse_text_formatting( $block ) : string {
		if ( ! isset( $block->formatting ) || ! is_array( $block->formatting ) ) {
			return $block->text;
		}

		$block_text = $block->text;

		foreach ( $block->formatting as $format ) {
			$substring              = substr( $block->text, $format->start, $format->end - $format->start );
			[$open_tag, $close_tag] = $this->parse_format_tags( $format );

			$block_text = str_replace( $substring, $open_tag . $substring . $close_tag, $block_text );
		}

		return $block_text;
	}

	private function parse_image( $block ) : array {
		$img_size = -1;
		$img_url  = '#';

		foreach ( $block->media as $img_info ) {
			if ( $img_size < $img_info->width || $img_size < $img_info->height ) {
				$img_url  = $img_info->url;
				$img_size = ( $img_info->width > $img_info->height ) ? $img_info->width : $img_info->height;
			}
		}

		return [
			'type'    => 'image',
			'url'     => $img_url,
			'alt'     => $block->alt_text ?? 'Image from tumblr',
			'caption' => $block->caption ?? null,
		];
	}

	private function parse_link( $block ) : string {
		return "<!-- wp:heading -->\n<h2><a href=\"$block->url\" data-type=\"URL\" data-id=\"$block->url\">$block->title</a></h2>\n<!-- /wp:heading -->";
	}

	private function parse_audio( $block ) {
		if ( $block->provider === 'tumblr' ) {
			return [
				'type' => 'audio',
				'url'  => $block->media->url,
			];
		}

		return '<!-- wp:embed {"url":"' . $block->url . '","type":"rich","className":""} -->
		<figure class="wp-block-embed is-type-rich"><div class="wp-block-embed__wrapper">
		' . $block->url . '
		</div></figure>
		<!-- /wp:embed -->';
	}

	private function parse_video( $block ) {
		if ( $block->provider === 'tumblr' ) {
			return [
				'type' => 'video',
				'url'  => $block->media->url,
			];
		}

		return '<!-- wp:embed {"url":"' . $block->url . '","type":"rich","className":""} -->
		<figure class="wp-block-embed is-type-rich"><div class="wp-block-embed__wrapper">
		' . $block->url . '
		</div></figure>
		<!-- /wp:embed -->';
	}
}
