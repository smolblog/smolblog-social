<?php
/**
 * Handler for importing posts from Twitter
 *
 * @package Smolblog\Social
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 */

namespace Smolblog\Social\Import;

class CreatePost {
	/**
	 * Check to see if this tweet is already imported
	 *
	 * @param string $twid ID of tweet to check.
	 * @return bool True if tweet has been imported.
	 */
	public function has_been_imported( $import_id ) {
		$check_query = new \WP_Query( [
			'meta_key'   => 'smolblog_social_import_id',
			'meta_value' => $import_id,
		] );

		return $check_query->found_posts > 0;
	}

	/**
	 * Imports the media found at the given URL into the WP Media Library linked to the given post
	 *
	 * @param string $url Address of the remote media to import.
	 * @param int    $post_id ID of the WordPress post this media should be attached to.
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
		preg_match( '/[^\?]+\.(jpg|jpe|jpeg|gif|png|mp4|m4v)/i', $url, $matches );

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
