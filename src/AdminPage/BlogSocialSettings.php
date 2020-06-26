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
use Fieldmanager_Group;
use Fieldmanager_TextField;
use Fieldmanager_Checkbox;

/**
 * Registrar class to register our custom post types
 *
 * @since 0.1.0
 */
class BlogSocialSettings implements Hookable {
	/**
	 * All the hooks my object sets up, right in one place!
	 */
	public function register_hooks() {
		if ( function_exists( 'fm_register_submenu_page' ) ) {
			fm_register_submenu_page( 'smolblog_social_accounts', 'options-general.php', 'Social Accounts' );
			add_action( 'fm_submenu_smolblog_social_accounts', [ $this, 'show_fields' ] );
		}
	}

	/**
	 * My init callback.
	 */
	public function show_fields() {
		$fm = new Fieldmanager_Group( [
			'name'           => 'smolblog_social_accounts',
			'limit'          => 0,
			'label'          => 'Add Account',
			'label_macro'    => [ '%s', 'service' ],
			'add_more_label' => 'Add another account',
			'children'       => [
				'service' => new Fieldmanager_Textfield( 'Service' ),
				'name'    => new Fieldmanager_Textfield( 'Name' ),
				'owner'   => new Fieldmanager_Textfield( 'Owner' ),
				'push'    => new Fieldmanager_Checkbox(
					'Allow posting to this account',
					[
						'checked_value'   => true,
						'unchecked_value' => false,
						'save_empty'      => true,
					]
				),
				'pull'    => new Fieldmanager_Checkbox(
					'Allow importing from this account',
					[
						'checked_value'   => true,
						'unchecked_value' => false,
						'save_empty'      => true,
					]
				),
			],
		] );
		$fm->activate_submenu_page();
	}
}
