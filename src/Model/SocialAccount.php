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
class SocialAccount {
	public const TABLE_NAME = 'smolblog_social';

	/**
	 * Database ID for this account
	 *
	 * @var int
	 */
	private $db_id;

	/**
	 * User ID of the owner of this account
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * Key of the handler for this account. (Usually corresponds to social network.)
	 *
	 * @var string
	 */
	public $social_type;

	/**
	 * Human-readable name for the account for user verification.
	 *
	 * @var string
	 */
	public $social_username;

	/**
	 * User public token for accessing the service.
	 *
	 * @var string
	 */
	public $oauth_token;

	/**
	 * User private token for accessing the service.
	 *
	 * @var string
	 */
	public $oauth_secret;


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

		$table_name   = $wpdb->prefix . self::TABLE_NAME;
		$account_info = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE id = %d", //phpcs:ignore
				$id
			)
		);

		restore_current_blog();

		if ( ! is_array( $account_info ) || empty( $account_info ) ) {
			return;
		}

		$this->user_id         = $account_info[0]->user_id;
		$this->social_type     = $account_info[0]->social_type;
		$this->social_username = $account_info[0]->social_username;
		$this->oauth_token     = $account_info[0]->oauth_token;
		$this->oauth_secret    = $account_info[0]->oauth_secret;
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
		global $wpdb;

		switch_to_blog( get_main_site_id() );

		$success = $wpdb->insert(
			$wpdb->prefix . self::TABLE_NAME,
			[
				'user_id'         => $this->user_id,
				'social_type'     => $this->social_type,
				'social_username' => $this->social_username,
				'oauth_token'     => $this->oauth_token,
				'oauth_secret'    => $this->oauth_secret,
			],
			[ '%d', '%s', '%s', '%s', '%s' ],
		);

		if ( $success ) {
			$this->db_id = $wpdb->insert_id;
		}

		restore_current_blog();
	}

	/**
	 * Perform a database update.
	 */
	private function update() {
		global $wpdb;

		switch_to_blog( get_main_site_id() );

		$wpdb->update(
			$wpdb->prefix . self::TABLE_NAME,
			[
				'user_id'         => $this->user_id,
				'social_type'     => $this->social_type,
				'social_username' => $this->social_username,
				'oauth_token'     => $this->oauth_token,
				'oauth_secret'    => $this->oauth_secret,
			],
			[ 'id' => $this->db_id ],
			[ '%d', '%s', '%s', '%s', '%s' ],
			[ '%d' ],
		);

		restore_current_blog();
	}
}
