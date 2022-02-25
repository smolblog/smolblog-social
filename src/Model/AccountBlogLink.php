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
class AccountBlogLink {
	public const TABLE_NAME = 'smolblog_social_blog_link';

	/**
	 * Database ID for this account
	 *
	 * @var int
	 */
	private $db_id;

	/**
	 * Create a SocialAccount object. Pass an ID to retrieve one from the database.
	 *
	 * @param integer $id Database ID for the account.
	 */
	public function __construct( $id = 0 ) {
		global $wpdb;
		$this->db_id = $id;

		if ( ! $id ) {
			return;
		}

		switch_to_blog( get_main_site_id() );

		// do db stuff.

		restore_current_blog();
	}

	/**
	 * Save this account's state to the database.
	 */
	public function save() {
		if ( $this->db_id ) {
			$this->update();
			return;
		}

		$this->insert();
	}

	/**
	 * Perform a database insert.
	 */
	private function insert() {

	}

	/**
	 * Perform a database update.
	 */
	private function update() {

	}
}
