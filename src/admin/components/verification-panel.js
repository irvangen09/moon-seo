import { __ } from '@wordpress/i18n';
import { TextControl } from '@wordpress/components';

export default function VerificationPanel( { value, onChange } ) {
	return (
		<>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Google', 'moon-seo' ) }
				help={ __(
					'Get your verification code in Google Search Console.',
					'moon-seo'
				) }
				placeholder={ __( 'Add verification code', 'moon-seo' ) }
				value={ value.google || '' }
				onChange={ ( v ) => onChange( 'google', v ) }
			/>

			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Bing', 'moon-seo' ) }
				help={ __(
					'Get your verification code in Bing Webmaster Tools.',
					'moon-seo'
				) }
				placeholder={ __( 'Add verification code', 'moon-seo' ) }
				value={ value.bing || '' }
				onChange={ ( v ) => onChange( 'bing', v ) }
			/>

			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Yandex', 'moon-seo' ) }
				help={ __(
					'Get your verification code in Yandex Webmaster.',
					'moon-seo'
				) }
				placeholder={ __( 'Add verification code', 'moon-seo' ) }
				value={ value.yandex || '' }
				onChange={ ( v ) => onChange( 'yandex', v ) }
			/>
		</>
	);
}
