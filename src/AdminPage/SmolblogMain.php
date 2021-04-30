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
use Smolblog\Social\Import\Twitter;

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
			'read',
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
		global $wpdb;

		$table_name   = $wpdb->prefix . 'smolblog_social';
		$all_accounts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d", //phpcs:ignore
				get_current_user_id(),
			)
		);
		?>
		<h1>Smolblog</h1>

		<?php if ( ! empty( $_GET['smolblog_action'] ) && 'import_twitter' === $_GET['smolblog_action'] ) : ?>
			<h2>Twitter import</h2>
			<pre>
			<?php
				$importer = new Twitter();
				$importer->import_twitter( $_GET['social_id'] );
			?>
			</pre>
		<?php endif; ?>

		<h2>Connected social accounts:</h2>

		<ul>
		<?php foreach ( $all_accounts as $account ) : ?>
			<li>
				<strong>Twitter:</strong> <?php echo esc_html( $account->social_username ); ?>
				<a href="?page=smolblog&amp;smolblog_action=import_twitter&amp;social_id=<?php echo esc_attr( $account->id ); ?>" class="button">Import</a>
			</li>
		<?php endforeach; ?>
		</ul>

		<p>Add new account: <a href="<?php echo esc_attr( get_rest_url( null, 'smolblog/v1/twitter/init' ) ); ?>?_wpnonce=<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" class="button">Sign in with Twitter</a></p>

		<p>Twitter callback: <code><?php echo esc_html( get_rest_url( null, 'smolblog/v1/twitter/callback' ) ); ?></code></p>
		<?php
	}
}
