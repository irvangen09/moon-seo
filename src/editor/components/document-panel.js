import { __, sprintf } from '@wordpress/i18n';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

import useSeoPreview from '../use-seo-preview';
import {
	META_KEY_TITLE,
	META_KEY_DESCRIPTION,
	TITLE_MAX_LENGTH,
	DESCRIPTION_MAX_LENGTH,
} from '../constants';

export default function DocumentPanel() {
	const { postId, postType, postTitle } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			postId: editor.getCurrentPostId(),
			postType: editor.getCurrentPostType(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
		};
	}, [] );

	const [ meta ] = useEntityProp( 'postType', postType, 'meta' );

	const seoTitle = meta?.[ META_KEY_TITLE ] || '';
	const metaDescription = meta?.[ META_KEY_DESCRIPTION ] || '';

	const preview = useSeoPreview( {
		postId,
		seoTitle,
		metaDescription,
		postTitle,
	} );

	if ( ! meta ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="moon-seo-panel"
			title={ __( 'Moon SEO', 'moon-seo' ) }
		>
			<p>
				{ __( 'SEO Title', 'moon-seo' ) + ': ' }
				{ getTitleStatus( preview, seoTitle ) }
			</p>
			<p>
				{ __( 'Meta Description', 'moon-seo' ) + ': ' }
				{ getDescriptionStatus( preview, metaDescription ) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

// With an answer from the server the length is that of the text the frontend prints.
function getTitleStatus( preview, seoTitle ) {
	if ( preview?.title ) {
		const length = preview.title.length;

		if ( seoTitle ) {
			return sprintf(
				/* translators: 1: character count of the resulting title, 2: recommended character limit */
				__( '%1$d/%2$d characters (custom)', 'moon-seo' ),
				length,
				TITLE_MAX_LENGTH
			);
		}

		return sprintf(
			/* translators: 1: character count of the resulting title, 2: recommended character limit */
			__( '%1$d/%2$d characters (from template)', 'moon-seo' ),
			length,
			TITLE_MAX_LENGTH
		);
	}

	if ( seoTitle ) {
		return sprintf(
			/* translators: 1: current character count, 2: recommended character limit */
			__( '%1$d/%2$d characters', 'moon-seo' ),
			seoTitle.length,
			TITLE_MAX_LENGTH
		);
	}

	return __( 'Using the post title (not overridden)', 'moon-seo' );
}

function getDescriptionStatus( preview, metaDescription ) {
	if ( preview ) {
		if ( ! preview.description ) {
			return __( 'No description will be output', 'moon-seo' );
		}

		const length = preview.description.length;

		if ( metaDescription ) {
			return sprintf(
				/* translators: 1: character count of the resulting description, 2: recommended character limit */
				__( '%1$d/%2$d characters (custom)', 'moon-seo' ),
				length,
				DESCRIPTION_MAX_LENGTH
			);
		}

		return sprintf(
			/* translators: 1: character count of the resulting description, 2: recommended character limit */
			__( '%1$d/%2$d characters (generated)', 'moon-seo' ),
			length,
			DESCRIPTION_MAX_LENGTH
		);
	}

	if ( metaDescription ) {
		return sprintf(
			/* translators: 1: current character count, 2: recommended character limit */
			__( '%1$d/%2$d characters', 'moon-seo' ),
			metaDescription.length,
			DESCRIPTION_MAX_LENGTH
		);
	}

	return __(
		'Using the auto-generated excerpt (not overridden)',
		'moon-seo'
	);
}
