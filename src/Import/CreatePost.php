<?php
/**
 * Handler for importing posts from Twitter
 *
 * @package Smolblog\Social
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 */

namespace Smolblog\Social\Import;

/**
 * Create a WordPress post from the import information
 */
class CreatePost {
	/**
	 * Check to see if this tweet is already imported
	 *
	 * @param string $import_id ID of tweet to check.
	 * @return bool True if tweet has been imported.
	 */
	public function has_been_imported( $import_id ) {
		$check_query = new \WP_Query(
			[
				'meta_key'   => 'smolblog_social_import_id',
				'meta_value' => $import_id,
			]
		);

		return $check_query->found_posts > 0;
	}

	/**
	 * Create a post from the given array. Will handle adding reblog and image markup.
	 *
	 * @param array $new_post Array of information for the post to insert.
	 * @return int WordPress ID of the inserted post
	 * @throws \Exception When errors are encountered.
	 */
	public function create_post( $new_post ) {
		$post_content = $new_post['content'] ?? '';

		if ( ! empty( $new_post['reblog'] ) ) {
			$new_post['meta']['smolblog_is_reblog']  = true;
			$new_post['meta']['smolblog_reblog_url'] = $new_post['reblog'];

			$post_content = '<!-- wp:smolblog/reblog {"showEmbed":true,"title":""} -->
			<div class="wp-block-smolblog-reblog">
			<!-- wp:embed {"url":"' . $new_post['reblog'] . '","type":"rich","className":""} -->
			<figure class="wp-block-embed is-type-rich"><div class="wp-block-embed__wrapper">
			' . $new_post['reblog'] . '
			</div></figure>
			<!-- /wp:embed --></div>
			<!-- /wp:smolblog/reblog -->' . "\n\n" . $post_content;
		}

		$args = [
			'post_title'   => $new_post['title'] ?? '',
			'post_content' => $post_content,
			'post_date'    => $new_post['date'] ?? null,
			'post_excerpt' => $new_post['excerpt'] ?? null,
			'post_status'  => 'draft',
			'post_name'    => $new_post['slug'] ?? null,
			'post_author'  => $new_post['author'] ?? get_current_user_id(),
			'tags_input'   => $new_post['tags'] ?? null,
			'meta_input'   => $new_post['meta'] ?? null,
		];

		$args['meta_input']['smolblog_social_import_id'] = $new_post['import_id'];

		$post_id = wp_insert_post( array_filter( $args ), true );

		if ( is_wp_error( $post_id ) ) {
			throw new \Exception( 'Error creating post: ' . $post_id->get_error_message(), 1 );
		}

		if ( isset( $new_post['media'] ) ) {
			foreach ( $new_post['media'] as $local_id => $media ) {
				$alt   = isset( $media['alt'] ) ? $media['alt'] : '';
				$wp_id = $this->sideload_media( $media['url'], $post_id, $alt );
				$html  = '';

				switch ( $media['type'] ) {
					case 'image':
						$html = '<!-- wp:image {"id":' . $wp_id . '} -->
						<figure class="wp-block-image"><img src="' . wp_get_attachment_url( $wp_id ) . '" alt="" class="wp-image-' . $wp_id . '"/></figure>
						<!-- /wp:image -->';
						break;
					case 'video':
						$html = '<!-- wp:video {"id":' . $wp_id . '} -->
						<figure class="wp-block-video"><video controls ' . ( $media['atts'] ?? '' ) . 'preload="auto" src="' . wp_get_attachment_url( $wp_id ) . '"></video></figure>
						<!-- /wp:video -->';
						break;
					case 'audio':
						$html = '<!-- wp:audio {"id":' . $wp_id . '} -->
						<figure class="wp-block-audio"><audio controls ' . ( $media['atts'] ?? '' ) . 'preload="auto" src="' . wp_get_attachment_url( $wp_id ) . '"></audio></figure>
						<!-- /wp:audio -->';
						break;
				}

				$post_content = str_replace( "#SMOLBLOG_MEDIA_IMPORT#{$local_id}#", $html, $post_content );
			}
		}

		wp_insert_post(
			[
				'ID'           => $post_id,
				'post_title'   => $args['post_title'],
				'post_content' => $post_content,
				'post_status'  => $new_post['status'],
				'post_date'    => $new_post['date'] ?? null,
			]
		);

		return $post_id;
	}

	/**
	 * Imports the media found at the given URL into the WP Media Library linked to the given post
	 *
	 * @param string $url Address of the remote media to import.
	 * @param int    $post_id ID of the WordPress post this media should be attached to.
	 * @param string $desc Description of the image.
	 * @return int WordPress ID of imported media.
	 */
	private function sideload_media( $url, $post_id, $desc ) {
		$tmp = download_url( $url );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}
		$file_array = array();

		// Set variables for storage
		// fix file filename for query strings.
		preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png|mp4|m4v|mp3)/i', $url, $matches );

		$file_array['name']     = basename( $matches[0] );
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink.
		if ( is_wp_error( $tmp ) ) {
			unlink( $file_array['tmp_name'] );
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $post_id, $desc );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			unlink( $file_array['tmp_name'] );
			return $id;
		}

		return $id;
	}
}
