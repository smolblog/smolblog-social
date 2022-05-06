<?php
/**
 * General utilities that don't seem to fit elsewhere.
 *
 * @package Smolblog\Social
 */

namespace Smolblog\Social;

use Smolblog\Social\Model\SocialAccount;
use Smolblog\Social\Model\AccountBlogLink;

/**
 * Utility functions that don't really fit elsewhere.
 */
class Utilities {

	/**
	 * Get all accounts for the current user and blog.
	 *
	 * @param integer $user_id ID of the user.
	 * @param integer $blog_id ID of the blog.
	 * @return array Results of the query.
	 */
	public static function get_accounts_for_user_and_blog( int $user_id, int $blog_id ) : array {
		global $wpdb;

		if ( ! is_numeric( $user_id ) || ! is_numeric( $blog_id ) ) {
			return [];
		}

		switch_to_blog( get_main_site_id() );

		$account_table = $wpdb->prefix . SocialAccount::TABLE_NAME;
		$link_table    = $wpdb->prefix . AccountBlogLink::TABLE_NAME;

		$results = $wpdb->get_results(
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"SELECT
					account.id,
					account.user_id,
					account.social_type,
					account.social_username,
					link.id AS `link_id`,
					link.additional_info,
					link.can_push,
					link.can_pull
				FROM $account_table AS account
					LEFT JOIN (
						SELECT *
						FROM $link_table
						WHERE blog_id = %d
					) AS link ON account.id = link.social_id
				WHERE
					account.user_id = %d OR
					link.id IS NOT NULL",
				$blog_id,
				$user_id,
			),
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		restore_current_blog();

		return array_map(function($line) {
			$line['can_push'] = $line['can_push'] ? true : false;
			$line['can_pull'] = $line['can_pull'] ? true : false;
			return $line;
		}, $results);
	}

	public static function get_social_links_for_import() : array {
		global $wpdb;

		switch_to_blog( get_main_site_id() );

		$link_table = $wpdb->prefix . AccountBlogLink::TABLE_NAME;
		$link_ids   = $wpdb->get_col( "SELECT `id` FROM $link_table WHERE `can_pull` = 1" );

		restore_current_blog();

		return array_map( function( $id ) { return AccountBlogLink::find_by_id( $id ); }, $link_ids );
	}
}
