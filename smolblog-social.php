<?php
/**
 * Plugin Name: Smolblog Social
 * Plugin URI:  https://dev.smolblog.com/smolblog-wp-social
 * Description: Connect your WordPress install to your social media accounts. Supports PESOS and POSSE. Part of the Smolblog project.
 * Version:     0.1.0
 * Author:      Smolblog
 * Author URI:  https://dev.smolblog.com/
 * Text Domain: smolblog
 * Domain Path: /languages
 * License:     GPL2
 *
 * @package Smolblog\Social
 * @since 0.1.0
 */

namespace Smolblog\Social;

use Smolblog\Social\Database\Schema;

defined( 'ABSPATH' ) || die( 'Please do not.' );

// Load composer libraries.
$smolblog_autoload = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $smolblog_autoload ) ) {
	require_once $smolblog_autoload;
}

// Load Action Scheduler.
$smolblog_action_scheduler = __DIR__ . '/vendor/plugins/action-scheduler/action-scheduler.php';
if ( is_readable( $smolblog_action_scheduler ) ) {
	require_once $smolblog_action_scheduler;
}

add_action(
	'plugins_loaded',
	function() {
		try {
			update_database();
			( new SmolblogSocial( __FILE__ ) )->run();
		} catch ( Error $e ) {
			add_action(
				'admin_notices',
				function() {
					$message = __(
						'Could not locate OOPS-WP Demo class files. Did you remember to run composer install?',
						'smolblog'
					);

					echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
				}
			);
		}
	}
);

/**
 * Check the database version and update if needed.
 */
function update_database() {
	if ( is_main_site() && get_option( 'smolblog_social_db_version', 0 ) !== Schema::DATABASE_VERSION ) {
		$db = new Schema();
		$db->create_social_table();
	}
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\update_database' );

add_action(
	'admin_enqueue_scripts',
	function() {
		// Register our script for enqueuing.
		$smolblog_asset_info =
		file_exists( plugin_dir_path( __FILE__ ) . 'build/main.asset.php' ) ?
		require plugin_dir_path( __FILE__ ) . 'build/main.asset.php' :
		[
			'dependencies' => 'wp-element',
			'version'      => filemtime( 'js/main.js' ),
		];

		wp_register_script(
			'smolblog_admin',
			plugin_dir_url( __FILE__ ) . 'build/main.js',
			$smolblog_asset_info['dependencies'],
			$smolblog_asset_info['version'],
			true
		);
	},
	1,
	0
);
