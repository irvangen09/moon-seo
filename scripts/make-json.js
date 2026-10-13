/**
 * Generates the JSON translation files WordPress loads for the JavaScript bundles.
 *
 * `wp i18n make-json` names each file after the source file a string comes from, but WordPress looks for the
 * compiled bundle (build/admin.js, and so on). This script maps every source file to the bundles it ends up in,
 * so the generated names match.
 */
const { spawnSync } = require( 'child_process' );
const crypto = require( 'crypto' );
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const { entry } = require( '../webpack.config.js' );

const IMPORT_PATTERN = /(?:from|import)\s*['"](\.{1,2}\/[^'"]+)['"]/g;

const resolveImport = ( fromFile, request ) => {
	const base = path.resolve( path.dirname( fromFile ), request );

	return [ base, `${ base }.js`, path.join( base, 'index.js' ) ].find(
		( candidate ) =>
			fs.existsSync( candidate ) && fs.statSync( candidate ).isFile()
	);
};

const collect = ( file, found = new Set() ) => {
	if ( found.has( file ) ) {
		return found;
	}

	found.add( file );

	if ( /\.js$/.test( file ) ) {
		const source = fs.readFileSync( file, 'utf8' );

		for ( const [ , request ] of source.matchAll( IMPORT_PATTERN ) ) {
			const target = resolveImport( file, request );

			if ( target ) {
				collect( target, found );
			}
		}
	}

	return found;
};

const map = {};

for ( const [ name, entryFile ] of Object.entries( entry ) ) {
	for ( const file of collect( path.resolve( root, entryFile ) ) ) {
		if ( ! file.endsWith( '.js' ) ) {
			continue;
		}

		const source = path
			.relative( root, file )
			.split( path.sep )
			.join( '/' );
		map[ source ] = map[ source ] || [];
		map[ source ].push( `build/${ name }.js` );
	}
}

const mapDir = path.join( root, 'node_modules', '.cache' );
const mapFile = path.join( mapDir, 'moon-seo-i18n-map.json' );

fs.mkdirSync( mapDir, { recursive: true } );
fs.writeFileSync( mapFile, JSON.stringify( map ) );

const result = spawnSync(
	'wp',
	[
		'i18n',
		'make-json',
		'languages',
		'--no-purge',
		`--use-map=${ path.relative( root, mapFile ) }`,
	],
	{ cwd: root, stdio: 'inherit', shell: true }
);

if ( 0 !== result.status ) {
	process.exit( result.status ?? 1 );
}

/*
 * WP-CLI strips ".min.js" from a file name with an unescaped pattern, so "build/admin.js" is treated as
 * "build/a.js" and its JSON file gets the wrong hash. WordPress looks the file up by the real path.
 */
const md5 = ( value ) =>
	crypto.createHash( 'md5' ).update( value ).digest( 'hex' );
const languagesDir = path.join( root, 'languages' );
const bundles = new Set( Object.values( map ).flat() );

for ( const bundle of bundles ) {
	const mangled = bundle.replace( /.min.js$/, '.js' );

	if ( mangled === bundle ) {
		continue;
	}

	for ( const file of fs.readdirSync( languagesDir ) ) {
		if ( file.endsWith( `-${ md5( mangled ) }.json` ) ) {
			fs.renameSync(
				path.join( languagesDir, file ),
				path.join(
					languagesDir,
					file.replace( md5( mangled ), md5( bundle ) )
				)
			);
		}
	}
}
