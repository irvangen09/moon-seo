import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import TemplateField from '../../shared/template-field';

export default function TaxonomyFields( {
	value,
	onChange,
	titleVariables,
	descriptionVariables,
} ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show in search results', 'moon-seo' ) }
				help={ __(
					'Enable to allow archives to appear in search engine results.',
					'moon-seo'
				) }
				checked={ data.show_in_search_results ?? true }
				onChange={ ( checked ) =>
					updateField( 'show_in_search_results', checked )
				}
			/>

			<TemplateField
				label={ __( 'SEO Title', 'moon-seo' ) }
				value={ data.seo_title }
				onChange={ ( v ) => updateField( 'seo_title', v ) }
				variables={ titleVariables }
				maxLength={ 60 }
			/>

			<TemplateField
				label={ __( 'Meta Description', 'moon-seo' ) }
				value={ data.meta_description }
				onChange={ ( v ) => updateField( 'meta_description', v ) }
				variables={ descriptionVariables }
				maxLength={ 160 }
				multiline
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Auto-generate meta description', 'moon-seo' ) }
				help={ __(
					"If the meta description is empty, we'll automatically generate one from your content.",
					'moon-seo'
				) }
				checked={ !! data.auto_generate_description }
				onChange={ ( checked ) =>
					updateField( 'auto_generate_description', checked )
				}
			/>
		</div>
	);
}
