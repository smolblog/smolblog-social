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
class SocialAccount extends BaseModel {
	public const TABLE_NAME = 'smolblog_social';

	/**
	 * Get the table name with the current WP prefix
	 *
	 * @return string
	 */
	protected function full_table_name() : string {
		global $wpdb;
		switch_to_blog( get_main_site_id() );
		$full_table_name = $wpdb->prefix . self::TABLENAME;
		restore_current_blog();
		return $full_table_name;
	}

	/**
	 * Get all social accounts owned by the given user.
	 *
	 * @param integer $user_id User ID of the desired user's accounts.
	 * @return array Database results.
	 */
	public static function get_accounts_for_user( $user_id = 0 ) {
		global $wpdb;

		if ( ! $user_id ) {
			return;
		}

		switch_to_blog( get_main_site_id() );

		$table_name = $wpdb->prefix . self::TABLE_NAME;
		$results    = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d", //phpcs:ignore
				$user_id,
			)
		);

		restore_current_blog();

		return $results;
	}

	/**
	 * Create a SocialAccount object. Pass an ID to retrieve one from the database.
	 *
	 * @param integer $id Database ID for the account.
	 */
	public function __construct( $id = 0 ) {
		$this->data = [
			'user_id'         => 0,
			'social_type'     => '',
			'social_username' => '',
			'oauth_token'     => '',
			'oauth_secret'    => '',
		];

		$this->data_formats = [
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
		];

		if ( $id ) {
			global $wpdb;
			$tablename = $this->full_table_name();

			$db_result = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT `id` FROM $tablename WHERE `id` = %d", //phpcs:ignore
					$id
				)
			);

			if ( $db_result ) {
				$this->db_id = $db_result;
				$this->load();
			}
		}
	}
}
