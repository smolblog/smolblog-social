<?php
/**
 * Model class to handle getting account data from the DB.
 *
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Model;

/**
 * Model class to handle getting account data from the DB.
 */
class AccountBlogLink extends BaseModel {
	public const TABLE_NAME = 'smolblog_social_blog_link';

	/**
	 * Returns an AccountBlogLink instance of the specified ID
	 * or null if it does not exist.
	 *
	 * @param int $id ID of the AccountBlogLink row in the DB
	 * @return AccountBlogLink|null AccountBlogLink object or null if not found
	 */
	public static function find_by_id( $id ) {
		global $wpdb;
		switch_to_blog( get_main_site_id() );
		$tablename = $wpdb->prefix . self::TABLE_NAME;
		restore_current_blog();

		$db_result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					`blog_id`,
					`social_id`,
					`additional_info`
				FROM $tablename
				WHERE `id` = %d", //phpcs:ignore
				$id
			), ARRAY_A
		);

		if ( $db_result ) {
			return new AccountBlogLink(
				$db_result['blog_id'],
				$db_result['social_id'],
				$db_result['additional_info']
			);
		}

		return null;
	}

	/**
	 * Get the table name with the current WP prefix
	 *
	 * @return string
	 */
	protected function full_table_name() : string {
		global $wpdb;
		switch_to_blog( get_main_site_id() );
		$full_table_name = $wpdb->prefix . self::TABLE_NAME;
		restore_current_blog();
		return $full_table_name;
	}

	/**
	 * Create a SocialAccount object. Pass an ID to retrieve one from the database.
	 *
	 * @param integer $id Database ID for the account.
	 */
	public function __construct( $blog_id = 0, $social_id = 0, $additional_info = null ) {
		$this->data = [
			'blog_id' => $blog_id,
			'social_id' => $social_id,
			'additional_info' => $additional_info ?? '',
			'can_push' => false,
			'can_pull' => false,
			'pull_frequency' => 0,
		];

		$this->data_formats = [
			'%d',
			'%d',
			'%s',
			'%d',
			'%d',
			'%d',
		];

		if ( $blog_id && $social_id ) {
			global $wpdb;
			$tablename = $this->full_table_name();

			$db_result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `id` FROM $tablename WHERE `blog_id` = %d AND `social_id` = %d", //phpcs:ignore
					$blog_id,
					$social_id
				) . ( empty( $additional_info ) ?  '' : $wpdb->prepare(
					' AND `additional_info` = %s',
					$additional_info
				))
			);

			if ( $db_result ) {
				$this->db_id = $db_result;
				$this->load();
			}
		}
	}
}
