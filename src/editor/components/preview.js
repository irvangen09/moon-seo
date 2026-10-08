import { __ } from '@wordpress/i18n';

export default function Preview( { title, description, url } ) {
	return (
		<div className="moon-seo-preview">
			<div className="moon-seo-preview__url">{ url }</div>
			<div className="moon-seo-preview__title">
				{ title || __( '(SEO Title not filled in)', 'moon-seo' ) }
			</div>
			<div className="moon-seo-preview__description">
				{ description ||
					__( '(Meta Description not filled in)', 'moon-seo' ) }
			</div>
		</div>
	);
}
