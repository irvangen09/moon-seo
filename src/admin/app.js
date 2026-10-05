import { __ } from '@wordpress/i18n';
import {
	Button,
	Notice,
	Spinner,
	Panel,
	PanelBody,
} from '@wordpress/components';

import useRestSettings from '../shared/use-rest-settings';
import SiteInfoPanel from './components/site-info-panel';
import ContentPanel from './components/content-panel';
import CategoriesTagsPanel from './components/categories-tags-panel';
import SocialPanel from './components/social-panel';
import VerificationPanel from './components/verification-panel';
import RobotsUrlPanel from './components/robots-url-panel';

const REST_PATH = '/moon-seo/v1/general-settings';

export default function App() {
	const { settings, setSettings, isSaving, notice, save } =
		useRestSettings( REST_PATH );

	if ( null === settings ) {
		return notice ? (
			<Notice status={ notice.status } isDismissible={ false }>
				{ notice.message }
			</Notice>
		) : (
			<Spinner />
		);
	}

	const section = ( key ) => settings[ key ] || {};

	// Merges a partial update into one section of the settings object.
	const updateSection = ( key, patch ) => {
		setSettings( {
			...settings,
			[ key ]: { ...section( key ), ...patch },
		} );
	};

	const updateSocialPlatform = ( platform, field, value ) => {
		const social = section( 'social' );

		updateSection( 'social', {
			[ platform ]: { ...( social[ platform ] || {} ), [ field ]: value },
		} );
	};

	return (
		<div className="moon-seo-settings">
			<div className="moon-seo-settings__header">
				<h1>{ __( 'Moon SEO', 'moon-seo' ) }</h1>
				<Button
					variant="primary"
					isBusy={ isSaving }
					disabled={ isSaving }
					onClick={ save }
				>
					{ __( 'Save Changes', 'moon-seo' ) }
				</Button>
			</div>

			{ notice && (
				<Notice status={ notice.status } isDismissible={ false }>
					{ notice.message }
				</Notice>
			) }

			<Panel>
				<PanelBody title={ __( 'Site Info', 'moon-seo' ) } initialOpen>
					<SiteInfoPanel
						value={ section( 'site_info' ) }
						onChange={ ( field, value ) =>
							updateSection( 'site_info', { [ field ]: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Content', 'moon-seo' ) }
					initialOpen={ false }
				>
					<ContentPanel
						value={ section( 'content' ) }
						onChange={ ( contentType, value ) =>
							updateSection( 'content', {
								[ contentType ]: value,
							} )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Categories & Tags', 'moon-seo' ) }
					initialOpen={ false }
				>
					<CategoriesTagsPanel
						value={ section( 'categories_tags' ) }
						onChange={ ( taxonomyType, value ) =>
							updateSection( 'categories_tags', {
								[ taxonomyType ]: value,
							} )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Social', 'moon-seo' ) }
					initialOpen={ false }
				>
					<SocialPanel
						value={ section( 'social' ) }
						onChange={ updateSocialPlatform }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Verification', 'moon-seo' ) }
					initialOpen={ false }
				>
					<VerificationPanel
						value={ section( 'verification' ) }
						onChange={ ( platform, value ) =>
							updateSection( 'verification', {
								[ platform ]: value,
							} )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Robots & URL', 'moon-seo' ) }
					initialOpen={ false }
				>
					<RobotsUrlPanel
						value={ section( 'robots_url' ) }
						onChange={ ( field, value ) =>
							updateSection( 'robots_url', { [ field ]: value } )
						}
					/>
				</PanelBody>
			</Panel>
		</div>
	);
}