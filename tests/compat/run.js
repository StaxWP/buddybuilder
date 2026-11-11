#!/usr/bin/env node
/**
 * BuddyBuilder compatibility suite: snapshot run.
 *
 * Renders every fixture (tests/compat/fixtures) on a throwaway site for each page and visitor of its template
 * type and stores, per case: HTTP status, the normalised HTML of each BuddyBuilder widget, a screenshot of the
 * BuddyPress area, BuddyBuilder PHP notices (from env/bpb-compat-env.php) and axe violations inside the widgets.
 * Compare two runs with compare.js (release gate: inventory section 14.6).
 *
 * Usage (from the plugin root):
 *   NODE_PATH=<dir with playwright and @axe-core/playwright> node tests/compat/run.js \
 *     --site http://kleo7-bbcompat.test --wp ~/Sites/kleo7-bbcompat/tools/wp --label 1.9.2 \
 *     [--out tests/compat/results/<label>] [--only <fixture substring>] [--no-screens] [--no-axe]
 *
 * The site must have env/bpb-compat-env.php as a mu-plugin; load-fixtures.php refuses to run otherwise.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { TYPE_URLS, VARIANTS, wpJson, normalise, caseKey, caseFile, loadPlaywright } = require( './lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const flag = ( name ) => argv.includes( '--' + name );

const site = opt( 'site', 'http://kleo7-bbcompat.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', path.join( process.env.HOME, 'Sites/kleo7-bbcompat/tools/wp' ) );
const label = opt( 'label', 'run' );
const out = path.resolve( opt( 'out', path.join( __dirname, 'results', label ) ) );
const only = opt( 'only', '' );
const screens = ! flag( 'no-screens' );
const axe = ! flag( 'no-axe' );

const WIDGETS = '[class*="elementor-widget-bpb-"], .bpb-el';
const FREEZE_CSS = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';

( async () => {
	const { chromium } = loadPlaywright();
	let AxeBuilder = null;
	if ( axe ) {
		try {
			AxeBuilder = require( '@axe-core/playwright' ).default;
		} catch ( e ) {
			console.warn( 'axe: @axe-core/playwright not found, skipping accessibility checks' );
		}
	}

	const fixturesDir = path.join( __dirname, 'fixtures' );
	const { plan } = wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/load-fixtures.php' ), fixturesDir ], 'BPB_COMPAT_PLAN' );
	const memberCookies = wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/cookie.php' ), 'mihai' ], 'BPB_COMPAT_COOKIES' );

	const cases = [];
	for ( const p of plan ) {
		if ( only && ! p.fixture.includes( only ) ) {
			continue;
		}
		const urls = p.type === 'anywhere' ? [ p.url.replace( /^https?:\/\/[^/]+/, '' ) ] : TYPE_URLS[ p.type ] || [];
		for ( const variant of VARIANTS[ p.type ] || VARIANTS.default ) {
			for ( const url of urls ) {
				cases.push( { fixture: p.fixture, type: p.type, id: p.id, variant, url } );
			}
		}
	}

	fs.mkdirSync( path.join( out, 'html' ), { recursive: true } );
	fs.mkdirSync( path.join( out, 'png' ), { recursive: true } );

	const browser = await chromium.launch();
	const host = new URL( site ).hostname;
	const contexts = {};
	for ( const variant of [ 'anon', 'member' ] ) {
		contexts[ variant ] = await browser.newContext( { viewport: { width: 1280, height: 900 }, ignoreHTTPSErrors: true, reducedMotion: 'reduce' } );
		if ( variant === 'member' ) {
			await contexts[ variant ].addCookies( Object.entries( memberCookies ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
		}
	}

	const results = { label, site, date: new Date().toISOString(), cases: {} };
	for ( const c of cases ) {
		const ctx = contexts[ c.variant ];
		await ctx.addCookies( [ { name: 'bpb_compat_tpl', value: c.type === 'anywhere' ? 'none:0' : `${ c.type }:${ c.id }`, domain: host, path: '/' } ] );
		const page = await ctx.newPage();
		const r = { ...c, status: 0, notices: [], widgets: {}, axe: [], error: null };
		try {
			const resp = await page.goto( site + c.url, { waitUntil: 'networkidle', timeout: 45000 } );
			r.status = resp ? resp.status() : 0;
			await page.addStyleTag( { content: FREEZE_CSS } );
			const html = resp ? await resp.text() : await page.content();
			const m = html.match( /<!-- bpb-compat-notices (\[.*?\]) -->/ );
			r.notices = m ? JSON.parse( m[ 1 ] ) : [ 'NO-ENV-MARKER' ];
			r.fatal = /There has been a critical error|Fatal error/.test( html );

			// Top-level widgets only (item template widgets inside a listing belong to the listing's markup).
			const widgets = await page.$$eval( WIDGETS, ( els, sel ) => els
				.filter( ( el ) => ! el.parentElement || ! el.parentElement.closest( sel ) )
				.map( ( el, i ) => ( {
					key: ( el.dataset.id || 'n' + i ) + ' ' + ( ( el.className.match( /elementor-widget-bpb-[\w-]+|bpb-el-[\w-]+/ ) || [ '' ] )[ 0 ] ),
					html: el.outerHTML,
				} ) ), WIDGETS );
			for ( const w of widgets ) {
				r.widgets[ w.key ] = normalise( w.html );
			}
			fs.writeFileSync( path.join( out, 'html', caseFile( c ) + '.html' ), widgets.map( ( w ) => `<!-- ${ w.key } -->\n${ normalise( w.html ) }` ).join( '\n\n' ) );

			if ( screens ) {
				const target = ( await page.$( '#buddypress' ) ) || ( await page.$( `[data-elementor-id="${ c.id }"]` ) ) || ( await page.$( 'main' ) ) || ( await page.$( 'body' ) );
				await target.screenshot( { path: path.join( out, 'png', caseFile( c ) + '.png' ), animations: 'disabled' } );
				r.screenshot = caseFile( c ) + '.png';
			}

			if ( AxeBuilder && widgets.length ) {
				const res = await new AxeBuilder( { page } ).include( WIDGETS ).analyze();
				r.axe = res.violations.map( ( v ) => `${ v.id } x${ v.nodes.length }` );
			}
		} catch ( e ) {
			r.error = String( e.message || e ).split( '\n' )[ 0 ];
		}
		await page.close();
		results.cases[ caseKey( c ) ] = r;
		console.log( `${ r.status } ${ Object.keys( r.widgets ).length }w ${ r.notices.length }n ${ r.axe.length }a ${ r.error ? 'ERR ' + r.error : '' } ${ caseKey( c ) }` );
	}

	await browser.close();
	fs.writeFileSync( path.join( out, 'results.json' ), JSON.stringify( results, null, 1 ) );
	console.log( `\n${ cases.length } cases -> ${ out }` );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 2 );
} );
