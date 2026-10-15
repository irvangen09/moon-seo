/**
 * Checks the release archive built by `npm run plugin-zip`: everything the
 * plugin needs at runtime must be inside, and tooling must not be.
 *
 * Usage: node scripts/check-release.js [path/to/archive.zip]
 */
const path = require( 'path' );

const { name } = require( '../package.json' );

const scriptsDir = path.dirname(
	require.resolve( '@wordpress/scripts/package.json' )
);
const AdmZip = require(
	require.resolve( 'adm-zip', { paths: [ scriptsDir ] } )
);

const archive = path.resolve( process.argv[ 2 ] ?? `${ name }.zip` );

let entries;
try {
	entries = new AdmZip( archive )
		.getEntries()
		.filter( ( entry ) => ! entry.isDirectory )
		.map( ( entry ) => entry.entryName.replace( /\\/g, '/' ) );
} catch ( error ) {
	process.stderr.write( `Cannot read ${ archive }: ${ error.message }\n` );
	process.exit( 1 );
}

// The archive may or may not wrap everything in a folder named after the plugin.
const paths = entries.map( ( entry ) =>
	entry.startsWith( `${ name }/` ) ? entry.slice( name.length + 1 ) : entry
);

const required = [
	'moon-seo.php',
	'uninstall.php',
	'includes/Autoloader.php',
	'includes/Bootstrap.php',
	'modules/general/Module.php',
	'modules/sitemap/Module.php',
	'modules/schema/Module.php',
	'build/admin.js',
	'build/admin.asset.php',
	'build/editor.js',
	'build/editor.asset.php',
	'build/sitemap-admin.js',
	'build/sitemap-admin.asset.php',
	'src/admin/index.js',
	'languages/moon-seo.pot',
];

const forbidden = [
	/(^|\/)(package(-lock)?\.json|composer\.(json|lock)|phpcs(-compat)?\.xml)$/,
	/(^|\/)(webpack\.config\.js|\.distignore|\.gitignore|\.gitattributes)$/,
	/(^|\/)(\.editorconfig|\.DS_Store)$/,
	/(^|\/)(node_modules|vendor|scripts|\.github|\.git)\//,
	/\.(zip|log)$/i,
	// Documentation stays out, except the license text.
	/^(?!licen[cs]e)[^/]*\.md$/i,
	/\/(?!licen[cs]e)[^/]*\.md$/i,
];

const problems = [];

required
	.filter( ( file ) => ! paths.includes( file ) )
	.forEach( ( file ) => problems.push( `missing: ${ file }` ) );

if ( ! paths.some( ( file ) => /^licen[cs]e[^/]*$/i.test( file ) ) ) {
	problems.push( 'missing: a license file at the plugin root' );
}

paths
	.filter( ( file ) => forbidden.some( ( rule ) => rule.test( file ) ) )
	.forEach( ( file ) => problems.push( `not allowed: ${ file }` ) );

if ( problems.length ) {
	process.stderr.write( `Release archive problems (${ archive }):\n` );
	problems.forEach( ( problem ) =>
		process.stderr.write( `  ${ problem }\n` )
	);
	process.exit( 1 );
}

process.stdout.write(
	`Release archive is correct (${ paths.length } files).\n`
);
