import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import MediaUploadField from './media-upload-field';

// onChange takes ( platform, field, value ) because each platform's settings are nested under its own key.
export default function SocialPanel( { value, onChange } ) {
	const openGraph = value.open_graph || {};
	const twitterCard = value.twitter_card || {};

	return (
		<>
			<h3>{ __( 'Open Graph', 'moon-seo' ) }</h3>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Enable Open Graph', 'moon-seo' ) }
				help={ __(
					'Add Open Graph meta tags to your site.',
					'moon-seo'
				) }
				checked={ !! openGraph.enabled }
				onChange={ ( checked ) =>
					onChange( 'open_graph', 'enabled', checked )
				}
			/>
			<MediaUploadField
				label={ __( 'Default Social Image', 'moon-seo' ) }
				help={ __(
					'This image will be used if a post or page does not have a featured image.',
					'moon-seo'
				) }
				recommendedSize={ __(
					'Recommended size: 1200 x 630 px',
					'moon-seo'
				) }
				imageId={ openGraph.image_id || 0 }
				onSelect={ ( id ) => onChange( 'open_graph', 'image_id', id ) }
				onRemove={ () => onChange( 'open_graph', 'image_id', 0 ) }
			/>

			<h3>{ __( 'Twitter (X) Card', 'moon-seo' ) }</h3>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Enable Twitter Card', 'moon-seo' ) }
				help={ __(
					'Add Twitter Card meta tags to your site.',
					'moon-seo'
				) }
				checked={ !! twitterCard.enabled }
				onChange={ ( checked ) =>
					onChange( 'twitter_card', 'enabled', checked )
				}
			/>
			<MediaUploadField
				label={ __( 'Default Twitter Image', 'moon-seo' ) }
				help={ __(
					'This image will be used if a post or page does not have a featured image.',
					'moon-seo'
				) }
				recommendedSize={ __(
					'Recommended size: 1200 x 600 px',
					'moon-seo'
				) }
				imageId={ twitterCard.image_id || 0 }
				onSelect={ ( id ) =>
					onChange( 'twitter_card', 'image_id', id )
				}
				onRemove={ () => onChange( 'twitter_card', 'image_id', 0 ) }
			/>
		</>
	);
}
