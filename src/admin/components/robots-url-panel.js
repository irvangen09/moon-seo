import { __ } from '@wordpress/i18n';
import {
	CheckboxControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';

// "index" and "follow" are crawler defaults and are never printed in a robots meta tag.
// Must match Settings/RobotsUrl.php.
const ROBOTS_DIRECTIVES = [
	'noindex',
	'nofollow',
	'noarchive',
	'nosnippet',
	'noimageindex',
];

const ROBOTS_PRESET_OPTIONS = [
	{ label: __( 'Use Default Robots Meta', 'moon-seo' ), value: 'default' },
	{ label: __( 'index, follow', 'moon-seo' ), value: 'index_follow' },
	{ label: __( 'noindex, follow', 'moon-seo' ), value: 'noindex_follow' },
	{ label: __( 'noindex, nofollow', 'moon-seo' ), value: 'noindex_nofollow' },
];

export default function RobotsUrlPanel( { value, onChange } ) {
	const defaultRobotsMeta = value.default_robots_meta || [];

	const toggleDefaultRobotsDirective = ( directive ) => {
		const next = defaultRobotsMeta.includes( directive )
			? defaultRobotsMeta.filter( ( item ) => item !== directive )
			: [ ...defaultRobotsMeta, directive ];

		onChange( 'default_robots_meta', next );
	};

	return (
		<>
			<h3 id="moon-seo-robots-meta-label">
				{ __( 'Default Robots Meta', 'moon-seo' ) }
			</h3>
			<p className="moon-seo-field__help">
				{ __(
					'Choose the default search engine instructions for your site content.',
					'moon-seo'
				) }
			</p>
			<div role="group" aria-labelledby="moon-seo-robots-meta-label">
				{ ROBOTS_DIRECTIVES.map( ( directive ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ directive }
						label={ directive }
						checked={ defaultRobotsMeta.includes( directive ) }
						onChange={ () =>
							toggleDefaultRobotsDirective( directive )
						}
					/>
				) ) }
			</div>

			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Default Robots for Archives', 'moon-seo' ) }
				help={ __(
					'Apply these settings to archive pages such as categories, tags, authors, search results, and date archives.',
					'moon-seo'
				) }
				value={ value.archives_robots || 'default' }
				options={ ROBOTS_PRESET_OPTIONS }
				onChange={ ( v ) => onChange( 'archives_robots', v ) }
			/>

			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Default Robots for 404 Pages', 'moon-seo' ) }
				help={ __(
					'Choose how search engines should handle 404 (Not Found) pages.',
					'moon-seo'
				) }
				value={ value.not_found_robots || 'noindex_follow' }
				options={ ROBOTS_PRESET_OPTIONS }
				onChange={ ( v ) => onChange( 'not_found_robots', v ) }
			/>

			<h3>{ __( 'URL', 'moon-seo' ) }</h3>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Remove Category Base', 'moon-seo' ) }
				help={ __(
					'Remove /category/ from category URLs.',
					'moon-seo'
				) }
				checked={ !! value.remove_category_base }
				onChange={ ( checked ) =>
					onChange( 'remove_category_base', checked )
				}
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Remove Tag Base', 'moon-seo' ) }
				help={ __( 'Remove /tag/ from tag URLs.', 'moon-seo' ) }
				checked={ !! value.remove_tag_base }
				onChange={ ( checked ) =>
					onChange( 'remove_tag_base', checked )
				}
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Redirect Attachments to Parent', 'moon-seo' ) }
				help={ __(
					'Redirect attachment pages to their parent post or page.',
					'moon-seo'
				) }
				checked={ !! value.redirect_attachments_to_parent }
				onChange={ ( checked ) =>
					onChange( 'redirect_attachments_to_parent', checked )
				}
			/>
		</>
	);
}