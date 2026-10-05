import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

import TitleSeparatorPicker from './title-separator-picker';
import MediaUploadField from './media-upload-field';

export default function SiteInfoPanel( { value, onChange } ) {
	const titleSeparator = value.title_separator || '|';

	return (
		<>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Website Name', 'moon-seo' ) }
				help={ __( 'The name of your website.', 'moon-seo' ) }
				value={ value.website_name || '' }
				onChange={ ( v ) => onChange( 'website_name', v ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Alternate Website Name', 'moon-seo' ) }
				help={ __(
					'Use the alternate website name for acronyms, or a shorter version of your website\u2019s name.',
					'moon-seo'
				) }
				value={ value.alternate_website_name || '' }
				onChange={ ( v ) => onChange( 'alternate_website_name', v ) }
			/>

			<div className="moon-seo-field">
				<p id="moon-seo-separator-picker-label">
					<strong>{ __( 'Title Separator', 'moon-seo' ) }</strong>
				</p>
				<p className="moon-seo-field__help">
					{ __(
						'Choose the separator used in title templates.',
						'moon-seo'
					) }
				</p>
				<TitleSeparatorPicker
					value={ titleSeparator }
					onChange={ ( v ) => onChange( 'title_separator', v ) }
					labelledBy="moon-seo-separator-picker-label"
				/>
				<p className="moon-seo-field__preview" aria-live="polite">
					{ __( 'Preview', 'moon-seo' ) }
					{ ': ' }
					{ __( 'Example Post Title', 'moon-seo' ) }{ ' ' }
					{ titleSeparator }{ ' ' }
					{ value.website_name || __( '(Website Name)', 'moon-seo' ) }
				</p>
			</div>

			<MediaUploadField
				label={ __( 'Site Image', 'moon-seo' ) }
				help={ __(
					'Used as a fallback for posts/pages that don\u2019t have any images set.',
					'moon-seo'
				) }
				recommendedSize={ __(
					'Recommended size: 1200 x 630 px',
					'moon-seo'
				) }
				imageId={ value.site_image_id || 0 }
				onSelect={ ( id ) => onChange( 'site_image_id', id ) }
				onRemove={ () => onChange( 'site_image_id', 0 ) }
			/>
		</>
	);
}