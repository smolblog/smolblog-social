<?php
/**
 * Page to handle social connetions for the blog
 *
 * @author  Evan Hildreth <me@eph.me>
 * @since   0.1.0
 * @package Smolblog\Social
 */

namespace Smolblog\Social\AdminPage;

use WebDevStudios\OopsWP\Utility\Hookable;

/**
 * Class to handle our admin page
 *
 * @since 0.1.0
 */
class ManageConnections implements Hookable {
	/**
	 * All the hooks my object sets up, right in one place!
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'add_manage_connections_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * My init callback.
	 */
	public function add_manage_connections_page() {
		add_submenu_page(
			'smolblog',
			'Social Connections',
			'Social Connections',
			'edit_others_posts',
			'smolblog_connections',
			[ $this, 'manage_connections' ],
			1
		);
	}

	/**
	 * Add the Smolblog admin javascript
	 *
	 * @param string $admin_page Current page.
	 */
	public function enqueue_scripts( $admin_page ) {
		if ( $admin_page !== 'smolblog_page_smolblog_connections' ) {
			return;
		}

		wp_enqueue_script( 'smolblog_admin' );
	}

	/**
	 * Output the Smolblog dashboard page
	 */
	public function manage_connections() {
		?>
		<h1>Social Connections</h1>
		<div id="smolblog-social-connections-app"></div>

		<h2>Add account</h2>
		<p>
			<a href="<?php echo esc_attr( get_rest_url( null, 'smolblog/v1/twitter/init' ) ); ?>?_wpnonce=<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" class="button">Sign in with Twitter</a>
			<a href="<?php echo esc_attr( get_rest_url( null, 'smolblog/v1/tumblr/init' ) ); ?>?_wpnonce=<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>" class="button">Sign in with Tumblr</a>
		</p>
		<?php
	}
}
