import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CheckboxControl, TextControl } from '@wordpress/components';

const MIN_LINKS_PER_PAGE = 1;
const MAX_LINKS_PER_PAGE = 50000;

const CORE_CHECKBOXES = [
	{ key: 'include_homepage', label: __( 'Include homepage', 'moon-seo' ) },
	{ key: 'include_posts', label: __( 'Include posts', 'moon-seo' ) },
	{
		key: 'include_static_pages',
		label: __( 'Include static pages', 'moon-seo' ),
	},
	{
		key: 'include_categories',
		label: __( 'Include categories', 'moon-seo' ),
	},
	{ key: 'include_tag_pages', label: __( 'Include tag pages', 'moon-seo' ) },
	{ key: 'include_archives', label: __( 'Include archives', 'moon-seo' ) },
	{
		key: 'include_author_pages',
		label: __( 'Include author pages', 'moon-seo' ),
	},
];

const isValidLinksPerPage = ( number ) =>
	Number.isInteger( number ) &&
	number >= MIN_LINKS_PER_PAGE &&
	number <= MAX_LINKS_PER_PAGE;

// One group of checkboxes with a heading that names the group for assistive technology.
function CheckboxGroup( { id, title, children } ) {
	return (
		<>
			<h3 id={ id }>{ title }</h3>
			<div role="group" aria-labelledby={ id }>
				{ children }
			</div>
		</>
	);
}

export default function SitemapContentFields( {
	value,
	onChange,
	contentTypes,
} ) {
	const data = value || {};
	const customPostTypes = data.custom_post_types || {};
	const customTaxonomies = data.custom_taxonomies || {};
	const postTypes = contentTypes?.post_types || [];
	const taxonomies = contentTypes?.taxonomies || [];

	// The text is kept apart from the saved number, so clearing the field to type a new value does not snap it back.
	const [ linksText, setLinksText ] = useState( () =>
		String( data.links_per_page ?? '' )
	);

	const updateLinksPerPage = ( text ) => {
		setLinksText( text );

		const number = Number( text );

		if ( isValidLinksPerPage( number ) ) {
			onChange( 'links_per_page', number );
		}
	};

	// A number above the limit is brought down to it; an empty or non-numeric text goes back to the saved value.
	const settleLinksPerPage = () => {
		const number = Math.floor( Number( linksText ) );

		if ( '' === linksText.trim() || ! Number.isFinite( number ) ) {
			setLinksText( String( data.links_per_page ?? '' ) );
			return;
		}

		const clamped = Math.min(
			Math.max( number, MIN_LINKS_PER_PAGE ),
			MAX_LINKS_PER_PAGE
		);

		setLinksText( String( clamped ) );
		onChange( 'links_per_page', clamped );
	};

	const updateCustomToggle = ( group, current, slug, checked ) => {
		onChange( group, { ...current, [ slug ]: checked } );
	};

	return (
		<div>
			<CheckboxGroup
				id="moon-seo-sitemap-core-label"
				title={ __( 'WordPress standard content', 'moon-seo' ) }
			>
				{ CORE_CHECKBOXES.map( ( item ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ item.key }
						label={ item.label }
						checked={ !! data[ item.key ] }
						onChange={ ( checked ) =>
							onChange( item.key, checked )
						}
					/>
				) ) }
			</CheckboxGroup>

			{ postTypes.length > 0 && (
				<CheckboxGroup
					id="moon-seo-sitemap-post-types-label"
					title={ __( 'Custom post types', 'moon-seo' ) }
				>
					{ postTypes.map( ( postType ) => (
						<CheckboxControl
							__nextHasNoMarginBottom
							key={ postType.slug }
							label={ sprintf(
								/* translators: %s: custom post type name */
								__( 'Include custom post type %s', 'moon-seo' ),
								postType.label
							) }
							checked={ !! customPostTypes[ postType.slug ] }
							onChange={ ( checked ) =>
								updateCustomToggle(
									'custom_post_types',
									customPostTypes,
									postType.slug,
									checked
								)
							}
						/>
					) ) }
				</CheckboxGroup>
			) }

			{ taxonomies.length > 0 && (
				<CheckboxGroup
					id="moon-seo-sitemap-taxonomies-label"
					title={ __( 'Custom taxonomies', 'moon-seo' ) }
				>
					{ taxonomies.map( ( taxonomy ) => (
						<CheckboxControl
							__nextHasNoMarginBottom
							key={ taxonomy.slug }
							label={ sprintf(
								/* translators: %s: custom taxonomy name */
								__( 'Include custom taxonomy %s', 'moon-seo' ),
								taxonomy.label
							) }
							checked={ !! customTaxonomies[ taxonomy.slug ] }
							onChange={ ( checked ) =>
								updateCustomToggle(
									'custom_taxonomies',
									customTaxonomies,
									taxonomy.slug,
									checked
								)
							}
						/>
					) ) }
				</CheckboxGroup>
			) }

			<CheckboxGroup
				id="moon-seo-sitemap-options-label"
				title={ __( 'Further options', 'moon-seo' ) }
			>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Include the last modification time.',
						'moon-seo'
					) }
					help={ __(
						'Recommended: it tells search engines when your content last changed. This applies to every sitemap file and to the sitemap index.',
						'moon-seo'
					) }
					checked={ !! data.include_last_modified }
					onChange={ ( checked ) =>
						onChange( 'include_last_modified', checked )
					}
				/>

				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Links per page', 'moon-seo' ) }
					help={ sprintf(
						/* translators: 1: minimum number of links, 2: maximum number of links */
						__(
							'Between %1$s and %2$s. A longer list is split into several sitemap files.',
							'moon-seo'
						),
						MIN_LINKS_PER_PAGE.toLocaleString(),
						MAX_LINKS_PER_PAGE.toLocaleString()
					) }
					type="number"
					min={ MIN_LINKS_PER_PAGE }
					max={ MAX_LINKS_PER_PAGE }
					value={ linksText }
					onChange={ updateLinksPerPage }
					onBlur={ settleLinksPerPage }
				/>
			</CheckboxGroup>
		</div>
	);
}
