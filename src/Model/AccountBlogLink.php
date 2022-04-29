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
	public function __construct( $blog_id = 0, $social_id = 0 ) {
		$this->data = [
			'blog_id' => $blog_id,
			'social_id' => $social_id,
			'additional_info' => '',
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
				)
			);

			if ( $db_result ) {
				$this->db_id = $db_result;
				$this->load();
			}
		}
	}
}
