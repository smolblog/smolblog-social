<?php
/**
 * Handler for importing posts from Twitter
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Import;

class Twitter {
	/**
	 * Import the twitter timeline of the currently authorized account.
	 *
	 * @return void
	 */
	public function import_twitter( $account_id ) {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'smolblog_social';
		$account_info = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d", //phpcs:ignore
				$account_id,
			)
		);

		echo "Loading Twitter...\n";

		$twitter_api_settings = [
			'consumer_key'              => SMOLBLOG_TWITTER_APPLICATION_KEY,
			'consumer_secret'           => SMOLBLOG_TWITTER_APPLICATION_SECRET,
			'oauth_access_token'        => $account_info[0]->oauth_token,
			'oauth_access_token_secret' => $account_info[0]->oauth_secret,
		];

		$twitter = new \TwitterAPIExchange( $twitter_api_settings );

		$url      = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
		$getfield = '?count=10&trim_user=true&exclude_replies=false&include_rts=true&tweet_mode=extended';

		$twitter_json     = $twitter->setGetfield( $getfield )->buildOauth( $url, 'GET' )->performRequest();
		$twitter_response = json_decode( $twitter_json );

		if ( ! is_array( $twitter_response ) ) {
			print_r( $twitter_response );
			return;
		}

		$posts_to_import = [];
		foreach ( $twitter_response as $tweet ) {
			if ( ! $this->has_been_imported( $tweet->id ) ) {
				$posts_to_import[] = $this->import_tweet( $tweet );
			} else {
				echo "Tweet {$tweet->id} already imported.\n";
			}
		}

		echo esc_html( print_r( $posts_to_import, true ) );


		echo 'Done!';
	}

	/**
	 * Check to see if this tweet is already imported
	 *
	 * @param string $twid ID of tweet to check.
	 * @return bool True if tweet has been imported.
	 */
	public function has_been_imported( $twid ) {
		$check_query = new \WP_Query( [
			'meta_key'   => 'smolblog_social_import_id',
			'meta_value' => 'twitter_' . $twid,
		] );

		return $check_query->found_posts > 0;
	}

	/**
	 * Import the given tweet.
	 *
	 * @param Object $tweet parsed API response from Twitter representing a single tweet.
	 */
	private function import_tweet( $tweet ) {
		$new_post = [
			'import_id' => 'twitter_' . $tweet->id,
			'content'   => $body,
			'date'      => $this->parse_date( $tweet->created_at ),
			'status'    => 'publish',
			'slug'      => $tweet->id,
			'author'    => get_current_user_id(),
			'media'     => [],
			'tags'      => [],
			'meta'      => [],
			'reblog'    => false,
		];

		if ( ! empty( $tweet->retweeted_status ) ) {
			$new_post['reblog'] = $this->getfinalurl( 'https://twitter.com/statuses/' . $tweet->retweeted_status->id );
			return $new_post;
		}

		$body = mb_substr(
			$tweet->full_text,
			$tweet->display_text_range[0],
			( $tweet->display_text_range[1] - $tweet->display_text_range[0] )
		);

		if ( $tweet->in_reply_to_status_id ) {
			if ( $tweet->in_reply_to_user_id !== $tweet->user->id ) {
				$new_post['reblog'] = $this->getfinalurl( 'https://twitter.com/statuses/' . $tweet->in_reply_to_status_id );
				$new_post['meta']['smolblog_twitter_replyid'] = $tweet->in_reply_to_status_id;
			} else {
				$new_post['meta']['smolblog_twitter_threadprevid'] = $tweet->in_reply_to_status_id;
			}
		} elseif ( $tweet->is_quote_status && isset( $tweet->quoted_status_id ) ) {
			$new_post['reblog'] = $this->getfinalurl( 'https://twitter.com/statuses/' . $tweet->quoted_status_id );
		}

		foreach ( $tweet->entities->urls as $tacolink ) {
			if ( $tweet->is_quote_status && isset( $tweet->quoted_status_id ) ) {
				$ind = strrpos( $tacolink->expanded_url, '/' );
				if ( substr( $tacolink->expanded_url, $ind + 1 ) === $tweet->quoted_status_id_str ) {
					$body = str_replace( $tacolink->url, '', $body );
				}
			}

			$body = str_replace(
				$tacolink->url,
				"<a href=\"{$tacolink->expanded_url}\">{$tacolink->display_url}</a>",
				$body
			);
		}

		$already_mentioned = array();
		foreach ( $tweet->entities->user_mentions as $atmention ) {
			if ( ! in_array( $atmention->screen_name, $already_mentioned, true ) ) {
				$body = str_replace(
					'@' . $atmention->screen_name,
					"<a href=\"https://twitter.com/{$atmention->screen_name}\">@{$atmention->screen_name}</a>",
					$body
				);

				$already_mentioned[] = $atmention->screen_name;
			}
		}

		if ( ! empty( $tweet->entities->hashtags ) ) {
			foreach ( $tweet->entities->hashtags as $hashtag ) {
				$body = str_replace(
					'#' . $hashtag->text,
					"<a href=\"https://twitter.com/hashtag/{$hashtag->text}\">#{$hashtag->text}</a>",
					$body
				);

				$new_post['tags'][] = $hashtag->text;
			}
		}

		$body = $this->process_markdown( $body );

		$new_post['content'] = $body;

		if ( empty( $tweet->retweeted_status ) && ! empty( $tweet->extended_entities->media ) ) {
			foreach ( $tweet->extended_entities->media as $media ) {
				$local_id = count( $new_post['media'] );
				if ( 'photo' === $media->type ) {
					$new_post['media'][] = [
						'type' => 'image',
						'url'  => $media->media_url_https,
						'alt'  => 'Image from Twitter',
					];

					$new_post['content'] .= "\n\#SMOLBLOG_MEDIA_IMPORT#{$local_id}#\n";
				} elseif ( 'video' === $media->type || 'animated_gif' === $media->type ) {
					$video_url     = '#';
					$video_bitrate = -1;
					foreach ( $media->video_info->variants as $vidinfo ) {
						if ( 'video/mp4' === $vidinfo->content_type && $vidinfo->bitrate > $video_bitrate ) {
							$video_bitrate = $vidinfo->bitrate;
							$video_url     = $vidinfo->url;
						}
					}

					$new_post['media'][] = [
						'type' => 'video',
						'url'  => $video_url,
						'alt'  => 'Video from Twitter',
						'atts' => ( 'animated_gif' === $media->type ) ? 'autoplay loop ' : null,
					];

					$new_post['content'] .= "\n#SMOLBLOG_MEDIA_IMPORT#{$local_id}#\n";
				}
			}
		}

		return $new_post;
	}

	/**
	 * Use cURL to follow all the redirects to get the final URL. Twitter will redirect
	 * `twitter.com/statuses/[tweet id]` to its proper place, and WordPress needs this
	 * final URL for its oEmbed to work.
	 *
	 * @param string $url URL to search.
	 * @return string URL at the end of all redirects
	 */
	private function getfinalurl( $url ) {
		// via https://stackoverflow.com/questions/17472329/php-get-url-of-redirect-from-source-url .
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // Must be set to true so that PHP follows any "Location:" header.
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		curl_exec( $ch );
		$newurl = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

		return $newurl;
	}

	/**
	 * Given a tweet ID number, return the Gutenberg block to embed the tweet.
	 *
	 * @param string $twid ID number of tweet.
	 * @return string Embed code for given tweet.
	 */
	private function get_tweet_embed( $twid ) {
		$twurl = $this->getfinalurl( 'https://twitter.com/statuses/' . $twid );

		return '<!-- wp:embed {"url":"' . $twurl . '","type":"rich","providerNameSlug":"twitter","responsive":true} -->
		<figure class="wp-block-embed is-type-rich is-provider-twitter wp-block-embed-twitter"><div class="wp-block-embed__wrapper">
		' . $twurl . '
		</div></figure>
		<!-- /wp:embed -->';
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
				return date( 'Y-m-d H:i:s', $timestamp );
		}
	}

	/**
	 * Instance of League/CommonMark.
	 *
	 * @var CommonMarkConverter $markdown_processor
	 */
	private $markdown_processor = false;

	/**
	 * Process text through a Markdown processor
	 *
	 * @param string $md Markdown-formatted text to convert.
	 * @return string HTML-formatted text.
	 */
	private function process_markdown( $md ) {
		if ( ! $this->markdown_processor ) {
			$this->markdown_processor = new \League\CommonMark\CommonMarkConverter();
		}

		return $this->markdown_processor->convertToHtml( $md );
	}
}
