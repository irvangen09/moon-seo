import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Loads and saves a module's settings through its custom REST route (for example '/moon-seo/v1/general-settings').
export default function useRestSettings( restPath ) {
	const [ settings, setSettings ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	useEffect( () => {
		let isMounted = true;

		apiFetch( { path: restPath } )
			.then( ( response ) => {
				if ( isMounted ) {
					setSettings( response || {} );
				}
			} )
			.catch( () => {
				// Without this the UI would stay on the spinner forever.
				if ( isMounted ) {
					setNotice( {
						status: 'error',
						message: __(
							'Failed to load settings. Please refresh the page.',
							'moon-seo'
						),
					} );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ restPath ] );

	const save = () => {
		setIsSaving( true );
		setNotice( null );

		apiFetch( {
			path: restPath,
			method: 'POST',
			data: settings,
		} )
			.then( ( response ) => {
				setSettings( response || settings );
				setNotice( {
					status: 'success',
					message: __( 'Settings saved successfully.', 'moon-seo' ),
				} );
			} )
			.catch( () => {
				setNotice( {
					status: 'error',
					message: __( 'Failed to save settings.', 'moon-seo' ),
				} );
			} )
			.finally( () => setIsSaving( false ) );
	};

	return { settings, setSettings, isSaving, notice, save };
}
