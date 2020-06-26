/**
 * REGISTER: Smolblog Social.
 */
import edit from './edit';
import save from './save';

import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

registerBlockType( 'smolblog/social', {
	title: __( 'Smolblog Social', 'social' ),
	icon: 'edit',
	category: 'common',
	keywords: [
		__( 'Smolblog', 'social' ),
		__( 'social', 'social' ),
	],
	attributes: {
		content: {
			type: 'array',
			source: 'children',
			selector: 'p',
		},
	},
	edit,
	save,
} );
