#!/usr/bin/env node
/**
 * Turns an export of a demo.staxwp.com starter kit feed into compat fixtures (one JSON per template).
 *
 *   node tests/compat/bin/import-kit-feed.js <export.json> <prefix> [--all]
 *
 * Export shape: { site, list: [ feed list items ], templates: { id: { is_pro, status, data: <get_template> } } }.
 * By default only the Pro templates are written (the free starter kit is already in fixtures/starter-bp-*).
 * Output: tests/compat/fixtures/<prefix>-<slug>.json, in the format load-fixtures.php reads.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const [ file, prefix ] = process.argv.slice( 2 );
const all = process.argv.includes( '--all' );
if ( ! file || ! prefix ) {
	console.error( 'usage: import-kit-feed.js <export.json> <prefix> [--all]' );
	process.exit( 2 );
}
const feed = JSON.parse( fs.readFileSync( file, 'utf8' ) );
const out = path.join( __dirname, '..', 'fixtures' );
const slug = ( s ) => s.toLowerCase().replace( /[^a-z0-9]+/g, '-' ).replace( /^-|-$/g, '' );
let n = 0;
for ( const item of feed.list ) {
	const t = feed.templates[ item.id ];
	if ( ! t || t.status !== 200 || ! t.data || ! Array.isArray( t.data.content ) ) {
		console.warn( `skip ${ item.id } ${ item.title }: no content` );
		continue;
	}
	if ( ! t.is_pro && ! all ) {
		continue;
	}
	const type = slug( item.subtype || t.data.subtype || '' ).replace( 'sitewide-activity', 'sitewide-activity' );
	const name = `${ prefix }-${ slug( item.title ) }`;
	const fixture = {
		name,
		title: item.title,
		type,
		builder: 'elementor',
		source: `${ feed.site.replace( /^https?:\/\//, '' ) } stax-feed template ${ item.id } (BuddyBuilder starter kit, ${ t.is_pro ? 'Pro' : 'free' })`,
		elementor_data: t.data.content,
	};
	fs.writeFileSync( path.join( out, name + '.json' ), JSON.stringify( fixture ) + '\n' );
	n++;
}
console.log( `${ n } fixtures written (${ prefix })` );
