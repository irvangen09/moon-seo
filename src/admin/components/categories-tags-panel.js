import { __ } from '@wordpress/i18n';

import TaxonomyFields from './taxonomy-fields';

export default function CategoriesTagsPanel( { value, onChange } ) {
	return (
		<>
			<h3>{ __( 'Categories', 'moon-seo' ) }</h3>
			<TaxonomyFields
				value={ value.categories }
				onChange={ ( v ) => onChange( 'categories', v ) }
				titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'term_title', 'site_name' ] }
			/>

			<h3>{ __( 'Tags', 'moon-seo' ) }</h3>
			<TaxonomyFields
				value={ value.tags }
				onChange={ ( v ) => onChange( 'tags', v ) }
				titleVariables={ [ 'term_title', 'separator', 'site_name' ] }
				descriptionVariables={ [ 'term_title', 'site_name' ] }
			/>
		</>
	);
}