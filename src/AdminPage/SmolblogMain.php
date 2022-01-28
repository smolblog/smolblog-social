<?php
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
use Smolblog\Social\Import\Tumblr;
use Tumblr\API\Client as TumblrClient;

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

		<?php if ( ! empty( $_GET['smolblog_action'] ) && 'import_tumblr' === $_GET['smolblog_action'] ) : ?>
			<h2>Tumblr import</h2>
			<pre>
			<?php
				$importer = new Tumblr();
				$importer->import_tumblr( $_GET['social_id'], $_GET['blog_url'] );
			?>
			</pre>
		<?php endif; ?>

		<h2>Connected social accounts:</h2>

		<ul>
		<?php foreach ( $all_accounts as $account ) : ?>
			<li>
				<strong><?php echo esc_html( $account->social_type ); ?>:</strong> <?php echo esc_html( $account->social_username ); ?>
				<?php if ( $account->social_type === 'tumblr' ) : ?>
				<ul>
					<?php
						$client = new TumblrClient(
							SMOLBLOG_TUMBLR_APPLICATION_KEY,
							SMOLBLOG_TUMBLR_APPLICATION_SECRET,
							$account->oauth_token,
							$account->oauth_secret
						);
						$blogs  = $client->getUserInfo()->user->blogs;
					foreach ( $blogs as $blog ) :
						?>
					<li><a href="?page=smolblog&amp;smolblog_action=import_tumblr&amp;social_id=<?php echo rawurlencode( $account->id ); ?>&amp;blog_url=<?php echo rawurlencode( wp_parse_url( $blog->url, PHP_URL_HOST ) ); ?>" class="button">Import <?php echo esc_html( $blog->title ); ?> (<?php echo esc_html( $blog->name ); ?>)</a></li>
					<?php endforeach; ?>
				</ul>
				<?php else : ?>
				<a href="?page=smolblog&amp;smolblog_action=import_<?php echo esc_attr( $account->social_type ); ?>&amp;social_id=<?php echo esc_attr( $account->id ); ?>" class="button">Import</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
		</ul>

		<p>
			Add new account:
			<a href="<?php echo esc_attr( get_rest_url( null, 'smolblog/v1/twitter/init' ) ); ?>?_wpnonce=<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" class="button">Sign in with Twitter</a>
			<a href="<?php echo esc_attr( get_rest_url( null, 'smolblog/v1/tumblr/init' ) ); ?>?_wpnonce=<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" class="button">Sign in with Tumblr</a>
		</p>

		<p>Twitter callback: <code><?php echo esc_html( get_rest_url( null, 'smolblog/v1/twitter/callback' ) ); ?></code></p>
		<?php
	}
}
