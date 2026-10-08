import { registerPlugin } from '@wordpress/plugins';

import Sidebar from './components/sidebar';
import DocumentPanel from './components/document-panel';
import './style.css';

const PLUGIN_NAME = 'moon-seo';

registerPlugin( PLUGIN_NAME, {
	render: Sidebar,
} );

registerPlugin( `${ PLUGIN_NAME }-document-panel`, {
	render: DocumentPanel,
} );
