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
	public const DATABASE_VERSION = 3;

	/**
	 * Create table for storing social account information.
	 *
	 * @author Evan Hildreth <me@eph.me>
	 * @since 0.1.0
	 */
	public function create_social_table() {
		global $wpdb;

		$account_table_name = $wpdb->prefix . 'smolblog_social';
		$link_table_name    = $wpdb->prefix . 'smolblog_social_blog_link';
		$charset_collate    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $account_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			social_type varchar(50) NOT NULL,
			social_username varchar(50) NOT NULL,
			oauth_token varchar(255) NOT NULL,
			oauth_secret varchar(255) NOT NULL, 
			PRIMARY KEY  (id)
		) $charset_collate;
		
		CREATE TABLE $link_table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) NOT NULL,
			social_id bigint(20) NOT NULL,
			additional_info varchar(255) NULL,
			can_push boolean DEFAULT false,
			can_pull boolean DEFAULT false,
			pull_frequency bigint(20) DEFAULT 0,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'smolblog_social_db_version', self::DATABASE_VERSION );
	}
}
