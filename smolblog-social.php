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

register_activation_hook( __FILE__, function() {
	$db = new Database\Schema();
	$db->create_social_table();
} );

/**
 * Register the block with WordPress.
 *
 * @author Smolblog
 * @since 0.0.1
 */
function register_block() {

	// Define our assets.
	$editor_script   = 'build/index.js';
	$editor_style    = 'build/editor.css';
	$frontend_style  = 'build/style.css';
	$frontend_script = 'build/frontend.js';

	// Verify we have an editor script.
	if ( ! file_exists( plugin_dir_path( __FILE__ ) . $editor_script ) ) {
		wp_die( esc_html__( 'Whoops! You need to run `npm run build` for the Smolblog Social first.', 'social' ) );
	}

	// Autoload dependencies and version.
	$asset_file = require plugin_dir_path( __FILE__ ) . 'build/index.asset.php';

	// Register editor script.
	wp_register_script(
		'social-editor-script',
		plugins_url( $editor_script, __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);

	// Register editor style.
	if ( file_exists( plugin_dir_path( __FILE__ ) . $editor_style ) ) {
		wp_register_style(
			'social-editor-style',
			plugins_url( $editor_style, __FILE__ ),
			[ 'wp-edit-blocks' ],
			filemtime( plugin_dir_path( __FILE__ ) . $editor_style )
		);
	}

	// Register frontend style.
	if ( file_exists( plugin_dir_path( __FILE__ ) . $frontend_style ) ) {
		wp_register_style(
			'social-style',
			plugins_url( $frontend_style, __FILE__ ),
			[],
			filemtime( plugin_dir_path( __FILE__ ) . $frontend_style )
		);
	}

	// Register block with WordPress.
	register_block_type( 'smolblog/social', array(
		'editor_script' => 'social-editor-script',
		'editor_style'  => 'social-editor-style',
		'style'         => 'social-style',
	) );

	// Register frontend script.
	if ( file_exists( plugin_dir_path( __FILE__ ) . $frontend_script ) ) {
		wp_enqueue_script(
			'social-frontend-script',
			plugins_url( $frontend_script, __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\register_block' );
