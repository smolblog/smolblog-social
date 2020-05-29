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

$autoload = __DIR__ . '/vendor/autoload.php';

if ( is_readable( $autoload ) ) {
	require_once $autoload;
}

add_action( 'plugins_loaded', function() {
	try {
		( new SmolblogSocial( __FILE__ ) )->run();
	} catch ( Error $e ) {
		add_action( 'admin_notices', function() {
			$message = __(
				'Could not locate OOPS-WP Demo class files. Did you remember to run composer install?',
				'oops-wp-demo'
			);

			echo wp_kses_post( '<div class="notice notice-error"><p>' . $message . '</p></div>' );
		} );
	}
} );
