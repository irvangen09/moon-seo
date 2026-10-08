import { __, sprintf } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

import {
	META_KEY_TITLE,
	META_KEY_DESCRIPTION,
	TITLE_MAX_LENGTH,
	DESCRIPTION_MAX_LENGTH,
} from '../constants';

export default function DocumentPanel() {
	const { postType, postTitle } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			postType: editor.getCurrentPostType(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
		};
	}, [] );

	const [ meta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( ! meta ) {
		return null;
	}

	const seoTitle = meta[ META_KEY_TITLE ] || '';
	const metaDescription = meta[ META_KEY_DESCRIPTION ] || '';

	// Without an override the title falls back to the post title.
	const resolvedTitleLength = ( seoTitle || postTitle || '' ).length;

	return (
		<PluginDocumentSettingPanel
			name="moon-seo-panel"
			title={ __( 'Moon SEO', 'moon-seo' ) }
		>
			<p>
				{ __( 'SEO Title', 'moon-seo' ) + ': ' }
				{ seoTitle
					? sprintf(
							/* translators: 1: current character count, 2: recommended character limit */
							__( '%1$d/%2$d characters', 'moon-seo' ),
							resolvedTitleLength,
							TITLE_MAX_LENGTH
					  )
					: __(
							'Using the post title (not overridden)',
							'moon-seo'
					  ) }
			</p>
			<p>
				{ __( 'Meta Description', 'moon-seo' ) + ': ' }
				{ metaDescription
					? sprintf(
							/* translators: 1: current character count, 2: recommended character limit */
							__( '%1$d/%2$d characters', 'moon-seo' ),
							metaDescription.length,
							DESCRIPTION_MAX_LENGTH
					  )
					: __(
							'Using the auto-generated excerpt (not overridden)',
							'moon-seo'
					  ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}
