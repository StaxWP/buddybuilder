#!/usr/bin/env node
/**
 * BuddyBuilder compatibility suite: performance budgets on a run.js result (review G7, KLEO 7 spec 3.5).
 *
 *   node tests/compat/budgets.js <run dir> [--report <file.md>] [--js-kb 25] [--css-kb 20]
 *
 * Per case, from the assets the env mu-plugin reports (`bpb-compat-assets`) and run.js measures (gzip -9):
 * - JavaScript of BuddyBuilder + BuddyBuilder Pro on the page: at most --js-kb KB gzip.
 * - CSS of BuddyBuilder + BuddyBuilder Pro on the page: at most --css-kb KB gzip (review 4.3 proposal).
 * - No script handle of BuddyBuilder or Pro needs jQuery, except the Pro 1.x module scripts listed in
 *   JQUERY_ALLOWED (inventory section 6: post form, ads, link preview move off jQuery in Pro 2.1, J6 to J8).
 * - No dashicons on community pages (review W2), unless the page is viewed by a logged-in member (the admin bar
 *   needs it there).
 * K Elements is reported next to ours (it shares the 25 KB budget on KLEO 7) but only gates with --with-kelements.
 * Exit code 0 when every case passes.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { readResults } = require( './lib' );

const argv = process.argv.slice( 2 );
const dir = argv.find( ( a, i ) => ! a.startsWith( '--' ) && ! ( argv[ i - 1 ] || '' ).startsWith( '--' ) );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const jsBudget = parseFloat( opt( 'js-kb', '25' ) ) * 1024;
const cssBudget = parseFloat( opt( 'css-kb', '20' ) ) * 1024;
const withKe = argv.includes( '--with-kelements' );
const reportFile = opt( 'report', path.join( dir || '.', 'budgets.md' ) );

// Pro 1.x module scripts that use jQuery (Pro 2.0 dropped it: J6 to J8 done 2026-11-16); kept for the Pro 1.7.0 pairing. Free has no exception.
const JQUERY_ALLOWED = [ 'bpb-pro-post-update-form', 'bpb-pro-post-update-form-notify', 'stax-buddy-pro-ads-module', 'bp-activity-link-preview-js' ];

const res = readResults( dir );
const rows = [];
let fails = 0;
const kb = ( n ) => ( n / 1024 ).toFixed( 1 );
for ( const [ key, r ] of Object.entries( res.cases ) ) {
	if ( ! r.assets ) {
		rows.push( { key, ok: false, why: 'no asset report (env mu-plugin missing or page failed)' } );
		fails++;
		continue;
	}
	const owned = ( list ) => list.filter( ( a ) => a.owner === 'buddybuilder' || a.owner === 'pro' || ( withKe && a.owner === 'k-elements' ) );
	const sum = ( list ) => list.reduce( ( t, a ) => t + Math.max( 0, a.gz ), 0 );
	const js = sum( owned( r.assets.js ) );
	const css = sum( owned( r.assets.css ) );
	const ke = sum( r.assets.js.filter( ( a ) => a.owner === 'k-elements' ) );
	const jq = owned( r.assets.js ).filter( ( a ) => a.jquery && ! JQUERY_ALLOWED.includes( a.handle ) ).map( ( a ) => a.handle );
	const why = [];
	if ( js > jsBudget ) {
		why.push( `JS ${ kb( js ) } KB > ${ kb( jsBudget ) }` );
	}
	if ( css > cssBudget ) {
		why.push( `CSS ${ kb( css ) } KB > ${ kb( cssBudget ) }` );
	}
	if ( jq.length ) {
		why.push( 'jQuery: ' + jq.join( ', ' ) );
	}
	// dashicons for a visitor fail when BuddyBuilder or Pro needs them, or nobody visible does (a direct enqueue,
	// the 1.x behaviour); BuddyPress' own priority nav on block themes does not count against us.
	const others = ( r.assets.dashicons_by || [] ).filter( ( h ) => ! /^(stax-buddy-builder|bpb-)/.test( h ) );
	if ( r.assets.dashicons && r.variant === 'anon' && ! others.length ) {
		why.push( 'dashicons for a visitor' );
	}
	const ok = ! why.length;
	fails += ok ? 0 : 1;
	rows.push( { key, ok, js, css, ke, why: why.join( '; ' ) } );
}

const lines = [ `# Budgets: ${ res.label }`, '', `JS <= ${ kb( jsBudget ) } KB gzip, CSS <= ${ kb( cssBudget ) } KB gzip (BuddyBuilder + Pro${ withKe ? ' + K Elements' : '' }), no jQuery handles, no dashicons for visitors.`, '', `${ rows.length - fails }/${ rows.length } cases pass.`, '', '| Case | JS KB | CSS KB | K Elements JS KB | Result |', '|---|---|---|---|---|' ];
for ( const r of rows ) {
	lines.push( `| ${ r.key } | ${ r.js === undefined ? '' : kb( r.js ) } | ${ r.css === undefined ? '' : kb( r.css ) } | ${ r.ke === undefined ? '' : kb( r.ke ) } | ${ r.ok ? 'ok' : 'FAIL ' + r.why } |` );
}
fs.writeFileSync( reportFile, lines.join( '\n' ) + '\n' );
const max = ( k ) => Math.max( 0, ...rows.filter( ( r ) => r[ k ] !== undefined ).map( ( r ) => r[ k ] ) );
console.log( `budgets ${ res.label }: ${ rows.length - fails }/${ rows.length } pass (max JS ${ kb( max( 'js' ) ) } KB, max CSS ${ kb( max( 'css' ) ) } KB) -> ${ reportFile }` );
process.exit( fails ? 1 : 0 );
