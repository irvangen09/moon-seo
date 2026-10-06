import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';

import TemplateField from '../../shared/template-field';

// Homepage, Post and Page have a description; Search and 404 only a title (see Settings/Content.php).
export default function ContentTypeFields( {
	value,
	onChange,
	hasDescription,
	titleVariables,
	descriptionVariables,
	titlePlaceholder,
} ) {
	const data = value || {};

	const updateField = ( field, fieldValue ) => {
		onChange( { ...data, [ field ]: fieldValue } );
	};

	return (
		<div>
			<TemplateField
				label={ __( 'SEO Title', 'moon-seo' ) }
				value={ data.seo_title }
				onChange={ ( v ) => updateField( 'seo_title', v ) }
				variables={ titleVariables }
				maxLength={ 60 }
				placeholder={ titlePlaceholder }
			/>

			{ hasDescription && (
				<>
					<TemplateField
						label={ __( 'Meta Description', 'moon-seo' ) }
						value={ data.meta_description }
						onChange={ ( v ) =>
							updateField( 'meta_description', v )
						}
						variables={ descriptionVariables }
						maxLength={ 160 }
						multiline
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Auto-generate meta description',
							'moon-seo'
						) }
						help={ __(
							"If the meta description is empty, we'll automatically generate one from your content.",
							'moon-seo'
						) }
						checked={ !! data.auto_generate_description }
						onChange={ ( checked ) =>
							updateField( 'auto_generate_description', checked )
						}
					/>
				</>
			) }
		</div>
	);
}
