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

register_activation_hook(
	__FILE__,
	function() {
		$db = new Database\Schema();
		$db->create_social_table();
	}
);
