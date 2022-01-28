<?php
/**
 * Class Schema for Smolblog Social
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\Database;

/**
 * Create and maintain the custom database table for Smolblog
 *
 * @since 0.1.0
 */
class Schema {
	/**
	 * Version of the database defined in this file. Will be
	 * checked against option `smolblog_social_db_version` to determine
	 * if upgrade is needed.
	 *
	 * @since 0.1.0
	 *
	 * @var int $db_version
	 */
	private $db_version = 2;

	/**
	 * Create table for storing social account information.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 */
	public function create_social_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'smolblog_social';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			social_type varchar(50) NOT NULL,
			social_username varchar(50) NOT NULL,
			oauth_token varchar(255) NOT NULL,
			oauth_secret varchar(255) NOT NULL, 
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'smolblog_social_db_version', $this->db_version );
	}
}
