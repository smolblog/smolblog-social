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
	 * @param int    $before Unix timestamp to start at.
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
				'smolblog_import_tumblr',
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

	/**
	 * Parse a tumblr post object for use by a CreatePost object
	 *
	 * @param object $post Parsed object from the Tumblr API.
	 * @return array Associative array for CreatePost
	 */
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
			'meta'      => [],
			'content'   => $parsed_blocks['content'],
			'reblog'    => $post->reblogged_from_url ?? null,
			'media'     => $parsed_blocks['media'],
		];

		return array_filter( $new_post );
	}

	/**
	 * Translate a Tumblr state into a WordPress state
	 *
	 * @param string $state State from the Tumblr API.
	 * @return string State for WordPress
	 */
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

	/**
	 * Translate a given Tumblr format into HTML tags.
	 *
	 * @param object $format Format object from the Tumblr API.
	 * @return array Array of two strings containing the opening and closing HTML tags.
	 */
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

	/**
	 * Translate Tumblr NPF blocks into WordPress blocks
	 *
	 * @param Array $blocks Array of blocks from the Tumblr API.
	 * @return array Three variables: title, content, and media array.
	 */
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

	/**
	 * Translate a Tumblr text block into a WordPress block
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return string WordPress block for a Block Editor (Gutenberg) post.
	 */
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

	/**
	 * Apply any formatting objects to the block's text as HTML tags
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return string HTML-formatted text from the block.
	 */
	private function parse_text_formatting( $block ) : string {
		if ( ! isset( $block->formatting ) || ! is_array( $block->formatting ) ) {
			return $block->text;
		}

		$inserts = [];
		foreach ( $block->formatting as $format ) {
			[$open_tag, $close_tag] = $this->parse_format_tags( $format );

			$existing_open_tag         = $inserts[ $format->start ] ?? '';
			$inserts[ $format->start ] = $existing_open_tag . $open_tag;

			$existing_close_tag      = $inserts[ $format->end ] ?? '';
			$inserts[ $format->end ] = $close_tag . $existing_close_tag;
		}

		$formatted_text = '';
		$cursor         = 0;
		$stops          = array_keys( $inserts );

		foreach ( $stops as $stop ) {
			$formatted_text .= mb_substr( $block->text, $cursor, ( $stop - $cursor ) );
			$formatted_text .= $inserts[ $stop ];
			$cursor          = $stop;
		}
		if ( $cursor < mb_strlen( $block->text ) ) {
			$formatted_text .= mb_substr( $block->text, $cursor );
		}

		return $formatted_text;
	}

	/**
	 * Translate an image block into a WordPress block.
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return array Image data to be given to CreatePost
	 */
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

	/**
	 * Translate a link block into a WordPress block
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return string Block for a WordPress post.
	 */
	private function parse_link( $block ) : string {
		return "<!-- wp:heading -->\n<h2><a href=\"$block->url\" data-type=\"URL\" data-id=\"$block->url\">$block->title</a></h2>\n<!-- /wp:heading -->";
	}

	/**
	 * Translate an audio block into a WordPress block. Native Tumblr audio is marked for sideloading.
	 * Off-site audio (soundcloud, spotify) is embedded with oEmbed.
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return string|array String for embedded audio, array for sideloaded audio
	 */
	private function parse_audio( $block ) {
		if ( isset( $block->provider ) && $block->provider === 'tumblr' ) {
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

	/**
	 * Translate an video block into a WordPress block. Native Tumblr video is marked for sideloading.
	 * Off-site video (YouTube, Vimeo) is embedded with oEmbed.
	 *
	 * @param object $block Block object from the Tumblr API.
	 * @return string|array String for embedded video, array for sideloaded video
	 */
	private function parse_video( $block ) {
		if ( isset( $block->provider ) && $block->provider === 'tumblr' ) {
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
