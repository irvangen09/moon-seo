/**
 * Fails when the plugin version differs between the plugin header, the
 * MOON_SEO_VERSION constant, and package.json.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const { version: packageVersion } = require( '../package.json' );

const source = fs.readFileSync(
	path.join( __dirname, '..', 'moon-seo.php' ),
	'utf8'
);

const header = source.match( /^\s*\*\s*Version:\s*(\S+)/m );
const constant = source.match(
	/define\(\s*'MOON_SEO_VERSION'\s*,\s*'([^']+)'/
);

const versions = [
	[ 'moon-seo.php header', header?.[ 1 ] ],
	[ 'MOON_SEO_VERSION', constant?.[ 1 ] ],
	[ 'package.json', packageVersion ],
];

const missing = versions.filter( ( [ , version ] ) => ! version );
const distinct = new Set( versions.map( ( [ , version ] ) => version ) );

if ( missing.length || distinct.size > 1 ) {
	process.stderr.write( 'The plugin version is not consistent:\n' );
	versions.forEach( ( [ label, version ] ) =>
		process.stderr.write( `  ${ label }: ${ version ?? 'not found' }\n` )
	);
	process.exit( 1 );
}

process.stdout.write( `Version ${ packageVersion } is consistent.\n` );
