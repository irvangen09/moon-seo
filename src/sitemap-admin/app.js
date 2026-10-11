import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Notice,
	Spinner,
	Panel,
	PanelBody,
} from '@wordpress/components';

import useRestSettings from '../shared/use-rest-settings';
import SitemapContentFields from './components/sitemap-content-fields';
import ExcludedItemsFields from './components/excluded-items-fields';

const REST_PATH = '/moon-seo/v1/sitemap-settings';
const CONTENT_TYPES_PATH = '/moon-seo/v1/sitemap-content-types';

export default function App() {
	const { settings, setSettings, isSaving, notice, save } =
		useRestSettings( REST_PATH );
	const [ contentTypes, setContentTypes ] = useState( null );

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: CONTENT_TYPES_PATH } )
			.then( ( response ) => {
				if ( isMounted ) {
					setContentTypes( response );
				}
			} )
			.catch( () => {
				// Not fatal: the fixed toggles still render without the custom type lists.
			} );

		return () => {
			isMounted = false;
		};
	}, [] );

	if ( null === settings ) {
		return notice ? (
			<Notice status={ notice.status } isDismissible={ false }>
				{ notice.message }
			</Notice>
		) : (
			<div role="status">
				<Spinner />
				<span className="screen-reader-text">
					{ __( 'Loading settings…', 'moon-seo' ) }
				</span>
			</div>
		);
	}

	const section = ( key ) => settings[ key ] || {};

	// Sets one field of one section of the settings object.
	const updateSection = ( key, field, value ) => {
		setSettings( {
			...settings,
			[ key ]: { ...section( key ), [ field ]: value },
		} );
	};

	return (
		<div className="moon-seo-settings">
			<div className="moon-seo-settings__header">
				<h1>{ __( 'Moon SEO - Sitemap', 'moon-seo' ) }</h1>
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
				<PanelBody
					title={ __( 'Sitemap Content', 'moon-seo' ) }
					initialOpen
				>
					<SitemapContentFields
						value={ section( 'sitemap_content' ) }
						onChange={ ( field, value ) =>
							updateSection( 'sitemap_content', field, value )
						}
						contentTypes={ contentTypes }
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Excluded Items', 'moon-seo' ) }
					initialOpen={ false }
				>
					<ExcludedItemsFields
						value={ section( 'excluded_items' ) }
						onChange={ ( field, value ) =>
							updateSection( 'excluded_items', field, value )
						}
					/>
				</PanelBody>
			</Panel>
		</div>
	);
}
