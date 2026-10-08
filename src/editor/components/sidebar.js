import { __ } from '@wordpress/i18n';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, CheckboxControl, PanelBody } from '@wordpress/components';

import Preview from './preview';
import TemplateField from '../../shared/template-field';
import {
	META_KEY_TITLE,
	META_KEY_DESCRIPTION,
	META_KEY_CANONICAL,
	META_KEY_ROBOTS,
	TITLE_VARIABLES,
	DESCRIPTION_VARIABLES,
	ROBOTS_DIRECTIVES,
	TITLE_MAX_LENGTH,
	DESCRIPTION_MAX_LENGTH,
} from '../constants';

const SIDEBAR_NAME = 'moon-seo-sidebar';

export default function Sidebar() {
	const { postType, postTitle, permalink } = useSelect( ( select ) => {
		const editor = select( 'core/editor' );

		return {
			postType: editor.getCurrentPostType(),
			postTitle: editor.getEditedPostAttribute( 'title' ),
			permalink: editor.getPermalink(),
		};
	}, [] );

	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	// The post type does not expose its meta over REST, so there is nothing to edit.
	if ( ! meta ) {
		return null;
	}

	const seoTitle = meta[ META_KEY_TITLE ] || '';
	const metaDescription = meta[ META_KEY_DESCRIPTION ] || '';
	const canonical = meta[ META_KEY_CANONICAL ] || '';
	const robots = meta[ META_KEY_ROBOTS ] || [];

	const updateMeta = ( key, value ) => {
		setMeta( { ...meta, [ key ]: value } );
	};

	const toggleRobotsDirective = ( directive ) => {
		const next = robots.includes( directive )
			? robots.filter( ( item ) => item !== directive )
			: [ ...robots, directive ];

		updateMeta( META_KEY_ROBOTS, next );
	};

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR_NAME } icon="search">
				{ __( 'Moon SEO', 'moon-seo' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ SIDEBAR_NAME }
				title={ __( 'Moon SEO', 'moon-seo' ) }
				icon="search"
			>
				<div className="moon-seo-editor">
					<PanelBody
						title={ __( 'SEO Preview', 'moon-seo' ) }
						initialOpen
					>
						<Preview
							title={ seoTitle || postTitle }
							description={ metaDescription }
							url={ permalink }
						/>
					</PanelBody>

					<PanelBody
						title={ __( 'General', 'moon-seo' ) }
						initialOpen
					>
						<TemplateField
							label={ __( 'SEO Title', 'moon-seo' ) }
							value={ seoTitle }
							onChange={ ( value ) =>
								updateMeta( META_KEY_TITLE, value )
							}
							variables={ TITLE_VARIABLES }
							maxLength={ TITLE_MAX_LENGTH }
						/>

						<TemplateField
							label={ __( 'Meta Description', 'moon-seo' ) }
							value={ metaDescription }
							onChange={ ( value ) =>
								updateMeta( META_KEY_DESCRIPTION, value )
							}
							variables={ DESCRIPTION_VARIABLES }
							maxLength={ DESCRIPTION_MAX_LENGTH }
							multiline
						/>

						<TextControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Canonical URL', 'moon-seo' ) }
							value={ canonical }
							onChange={ ( value ) =>
								updateMeta( META_KEY_CANONICAL, value )
							}
							placeholder={ permalink }
							help={ __(
								'Leave empty to use the default URL.',
								'moon-seo'
							) }
						/>
					</PanelBody>

					<PanelBody
						title={ __( 'Robots', 'moon-seo' ) }
						initialOpen={ false }
					>
						<div
							role="group"
							aria-label={ __( 'Robots', 'moon-seo' ) }
						>
							{ ROBOTS_DIRECTIVES.map( ( directive ) => (
								<CheckboxControl
									__nextHasNoMarginBottom
									key={ directive }
									label={ directive }
									checked={ robots.includes( directive ) }
									onChange={ () =>
										toggleRobotsDirective( directive )
									}
								/>
							) ) }
						</div>
					</PanelBody>
				</div>
			</PluginSidebar>
		</>
	);
}
