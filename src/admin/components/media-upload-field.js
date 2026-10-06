import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEntityRecord } from '@wordpress/core-data';

export default function MediaUploadField( {
	imageId,
	onSelect,
	onRemove,
	label,
	help,
	recommendedSize,
} ) {
	// Without "enabled", an id of 0 makes core-data request the whole media collection.
	const { record: attachment } = useEntityRecord(
		'postType',
		'attachment',
		imageId || 0,
		{ enabled: imageId > 0 }
	);
	const imageUrl = imageId && attachment ? attachment.source_url : '';

	const openMediaLibrary = () => {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		const frame = window.wp.media( {
			title: label,
			button: { text: __( 'Use this image', 'moon-seo' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', () => {
			const selection = frame.state().get( 'selection' ).first().toJSON();
			onSelect( selection.id );
		} );

		frame.open();
	};

	return (
		<div className="moon-seo-media-upload-field">
			<p>
				<strong>{ label }</strong>
			</p>

			{ help && <p>{ help }</p> }

			<div className="moon-seo-media-upload-field__preview">
				{ imageUrl ? (
					<img src={ imageUrl } alt="" />
				) : (
					<p>{ __( 'No image selected', 'moon-seo' ) }</p>
				) }
				{ recommendedSize && <p>{ recommendedSize }</p> }
			</div>

			<Button variant="secondary" onClick={ openMediaLibrary }>
				{ __( 'Select Image', 'moon-seo' ) }
			</Button>

			{ imageId > 0 && (
				<Button variant="tertiary" isDestructive onClick={ onRemove }>
					{ __( 'Remove', 'moon-seo' ) }
				</Button>
			) }
		</div>
	);
}
