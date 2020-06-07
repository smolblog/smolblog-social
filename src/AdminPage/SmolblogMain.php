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

		$current_user = get_current_user_id();
		$table_name   = $wpdb->prefix . 'smolblog_social';
		$all_accounts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name WHERE user_id = %d", //phpcs:ignore
				$current_user,
			)
		);

		$blog_social = get_option( 'smolblog_social_accounts' );
		if ( ! is_array( $blog_social ) ) {
			$blog_social = [];
		}
?>
		<h1>Smolblog</h1>

		<h2>Connected social accounts:</h2>

		<ul>
		<?php foreach ( $all_accounts as $account ) : ?>
			<li><strong>Twitter:</strong> <?php echo $account->social_username; ?></li>
		<?php endforeach; ?>
		</ul>

		<p>Add new account: <a href="<?php echo get_rest_url( null, 'smolblog/v1/twitter/init' ); ?>?_wpnonce=<?php echo wp_create_nonce( 'wp_rest' ); ?>" class="button">Sign in with Twitter</a></p>

		<p>Twitter callback: <code><?php echo get_rest_url( null, 'smolblog/v1/twitter/callback' ); ?></code></p>

		<h2>This Blog:</h2>

		<table class="widefat">
			<thead>
				<tr>
					<th class="row-title">Account</th>
					<th>Owner</th>
					<th>Post</th>
					<th>Import</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $blog_social as $account ) : ?>
				<tr>
					<td class="row-title"><?php echo $account['name']; ?></td>
					<td><?php echo $account['owner'] ?></td>
					<td><?php echo $account['push'] ? 'Yes' : '&mdash;'; ?></td>
					<td><?php echo $account['pull'] ? 'Yes' : '&mdash;'; ?></td>
					<td><?php submit_button( 'Remove', 'delete' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

<?php
	}
}
