import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { CheckboxControl, TextControl } from '@wordpress/components';

const CATEGORIES_PER_PAGE = 100;

// Positive, unique IDs from text such as "110, 121"; anything that is not a number is ignored.
const parseIds = ( text ) => {
	const ids = text
		.split( ',' )
		.map( ( part ) => parseInt( part.trim(), 10 ) )
		.filter( ( id ) => Number.isInteger( id ) && id > 0 );

	return [ ...new Set( ids ) ];
};

export default function ExcludedItemsFields( { value, onChange } ) {
	const data = value || {};
	const excludedCategories = data.excluded_categories || [];
	const excludedPosts = data.excluded_posts || [];

	const [ categories, setCategories ] = useState( [] );
	const [ categoriesLoaded, setCategoriesLoaded ] = useState( false );

	// The raw text is kept apart from the parsed IDs: parsing on every keystroke removes the comma and makes a second ID impossible to type.
	const [ postsText, setPostsText ] = useState( () =>
		excludedPosts.join( ', ' )
	);

	useEffect( () => {
		let isMounted = true;

		// The REST API returns at most 100 categories per request, so every page is fetched.
		const fetchAllCategories = async () => {
			let page = 1;
			let all = [];

			while ( isMounted ) {
				let batch;

				try {
					batch = await apiFetch( {
						path: `/wp/v2/categories?per_page=${ CATEGORIES_PER_PAGE }&page=${ page }&orderby=name&order=asc&_fields=id,name`,
					} );
				} catch ( error ) {
					// A failed request ends the list; the categories fetched so far are still shown.
					break;
				}

				all = all.concat( batch );

				if ( batch.length < CATEGORIES_PER_PAGE ) {
					break;
				}

				page += 1;
			}

			if ( isMounted ) {
				setCategories( all );
				setCategoriesLoaded( true );
			}
		};

		fetchAllCategories();

		return () => {
			isMounted = false;
		};
	}, [] );

	const toggleCategory = ( id, checked ) => {
		onChange(
			'excluded_categories',
			checked
				? [ ...excludedCategories, id ]
				: excludedCategories.filter(
						( categoryId ) => categoryId !== id
				  )
		);
	};

	const updatePosts = ( text ) => {
		setPostsText( text );
		onChange( 'excluded_posts', parseIds( text ) );
	};

	// Shows the IDs that will be saved once the field is left.
	const normalizePostsText = () => {
		setPostsText( parseIds( postsText ).join( ', ' ) );
	};

	return (
		<div>
			<h3 id="moon-seo-sitemap-excluded-categories-label">
				{ __( 'Excluded categories', 'moon-seo' ) }
			</h3>
			<p className="moon-seo-field__help">
				{ __(
					'Only the category page is removed from the sitemap. Posts in a checked category stay listed.',
					'moon-seo'
				) }
			</p>
			<div
				className="moon-seo-sitemap-categories"
				role="group"
				aria-labelledby="moon-seo-sitemap-excluded-categories-label"
			>
				{ categoriesLoaded && 0 === categories.length && (
					<p>{ __( 'No categories found.', 'moon-seo' ) }</p>
				) }
				{ categories.map( ( category ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ category.id }
						label={ category.name }
						checked={ excludedCategories.includes( category.id ) }
						onChange={ ( checked ) =>
							toggleCategory( category.id, checked )
						}
					/>
				) ) }
			</div>

			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Exclude posts', 'moon-seo' ) }
				help={ __(
					'Post or page IDs separated by commas, for example 110, 121. Child pages are not excluded automatically.',
					'moon-seo'
				) }
				value={ postsText }
				onChange={ updatePosts }
				onBlur={ normalizePostsText }
			/>
		</div>
	);
}
