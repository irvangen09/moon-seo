import apiFetch from '@wordpress/api-fetch';
import { useEffect, useRef, useState } from '@wordpress/element';

const ROUTE = '/moon-seo/v1/seo-preview';
const DEBOUNCE_MS = 500;

// The sidebar and the document panel ask for the same preview at the same time; one request serves both.
let pendingKey = '';
let pendingRequest = null;

function requestPreview( payload ) {
	const key = JSON.stringify( payload );

	if ( key !== pendingKey ) {
		pendingKey = key;
		pendingRequest = apiFetch( {
			path: ROUTE,
			method: 'POST',
			data: payload,
		} );

		// Only requests in flight are shared, so a later identical request reads fresh data.
		const clear = () => {
			if ( pendingKey === key ) {
				pendingKey = '';
			}
		};
		pendingRequest.then( clear, clear );
	}

	return pendingRequest;
}

// The SEO title and meta description as the frontend will print them.
// Returns null until the first answer arrives and when the request fails, so callers keep their raw fallback.
export default function useSeoPreview( {
	postId,
	seoTitle,
	metaDescription,
	postTitle,
} ) {
	const [ preview, setPreview ] = useState( null );
	const isFirstRun = useRef( true );

	useEffect( () => {
		if ( ! postId ) {
			return undefined;
		}

		// No wait for the first answer; typing is debounced.
		const delay = isFirstRun.current ? 0 : DEBOUNCE_MS;
		isFirstRun.current = false;

		// An answer for an earlier input must not replace a newer one.
		let isCurrent = true;

		const timer = setTimeout( () => {
			requestPreview( {
				post_id: postId,
				seo_title: seoTitle,
				meta_description: metaDescription,
				title: postTitle,
			} )
				.then( ( result ) => {
					if ( isCurrent ) {
						setPreview( result );
					}
				} )
				.catch( () => {} );
		}, delay );

		return () => {
			isCurrent = false;
			clearTimeout( timer );
		};
	}, [ postId, seoTitle, metaDescription, postTitle ] );

	return preview;
}
