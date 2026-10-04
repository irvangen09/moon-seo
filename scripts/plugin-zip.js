/**
 * Builds the release archive with `wp-scripts plugin-zip`, then removes the
 * files that npm always packs but an installed plugin does not need.
 */
const { spawnSync } = require( 'child_process' );
const path = require( 'path' );

const { name } = require( '../package.json' );

const scriptsDir = path.dirname(
	require.resolve( '@wordpress/scripts/package.json' )
);
const AdmZip = require(
	require.resolve( 'adm-zip', { paths: [ scriptsDir ] } )
);

const build = spawnSync(
	process.execPath,
	[
		path.join( scriptsDir, 'bin', 'wp-scripts.js' ),
		'plugin-zip',
		...process.argv.slice( 2 ),
	],
	{ stdio: 'inherit' }
);

if ( build.status !== 0 ) {
	process.exit( build.status ?? 1 );
}

// License files stay so the license text ships with the plugin.
const isUnneeded = ( entryName ) => {
	const file = path.posix.basename( entryName );

	return (
		'package.json' === file ||
		( /\.md$/i.test( file ) && ! /^licen[cs]e/i.test( file ) )
	);
};

const archive = path.resolve( `${ name }.zip` );
const zip = new AdmZip( archive );
const removed = zip
	.getEntries()
	.filter(
		( entry ) => ! entry.isDirectory && isUnneeded( entry.entryName )
	);

removed.forEach( ( entry ) => zip.deleteFile( entry ) );
zip.writeZip( archive );

process.stdout.write(
	removed.length
		? `Removed from the archive:\n${ removed
				.map( ( entry ) => `  ${ entry.entryName }\n` )
				.join( '' ) }\n`
		: 'Nothing to remove from the archive.\n'
);