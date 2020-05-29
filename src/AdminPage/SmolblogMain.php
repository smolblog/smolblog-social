<?php //phpcs:ignore Wordpress.Files.Filename
/**
 * Main admin page for this plugin
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\AdminPage;

use WebDevStudios\OopsWP\Utility\Hookable;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class SmolblogMain implements Hookable {
	/**
	 * All the hooks my object sets up, right in one place!
	 */
	public function register_hooks() {
			// Put your hooks here!
			add_action( 'admin_menu', [ $this, 'add_smolblog_dashboard_page' ] );
	}

	/**
	 * My init callback.
	 */
	public function add_smolblog_dashboard_page() {
		add_menu_page(
			'Smolblog Dashboard',
			'Smolblog',
			'manage_options',
			'smolblog',
			[ $this, 'smolblog_dashboard' ],
			'dashicons-controls-repeat',
			3
		);
	}

	/**
	 * Output the Smolblog dashboard page
	 */
	public function smolblog_dashboard() {
?>
		<h1>Smolblog</h1>

		<?php if ( ! empty ( $_GET['smolblog_action'] ) && 'import_twitter' === $_GET['smolblog_action'] ) : ?>
			<h2>Twitter import</h2>
			<pre>
			Not yet!
			</pre>
		<?php elseif ( get_option( 'smolblog_twitter_username' ) ) : ?>
			<p>Authenticated with Twitter as <?php echo esc_html( get_option( 'smolblog_twitter_username' ) ); ?></p>

			<p><a href="?page=smolblog&amp;smolblog_action=import_twitter" class="button">Import latest 10 tweets</a>
		<?php else : ?>
			<p><a href="<?php echo get_rest_url( null, 'smolblog/v1/twitter/init' ); ?>" class="button">Sign in with Twitter</a></p>
		<?php endif; ?>

		<p>Twitter callback: <code><?php echo get_rest_url( null, 'smolblog/v1/twitter/callback' ); ?></code></p>
<?php
	}
}
