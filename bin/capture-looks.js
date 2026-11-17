#!/usr/bin/env node
/**
 * Real thumbnails of the shipped looks (assets/img/looks/<preset>.webp): each look rendered on the sample member
 * (sample group for group pages) on a throwaway site, the layout area only, 480 px wide WebP.
 *
 *   NODE_PATH=<dir with playwright> node bin/capture-looks.js --site http://bbux.test --wp ~/Sites/bbux/tools/wp \
 *     [--admin gabriel] [--only member-classic] [--pro [--out <dir>]]
 *
 * --pro: the BuddyBuilder Pro presets (register, group creation, member card, group card, activity item of every
 * style) into the Pro plugin's assets/img/presets/<preset>.webp (next to this plugin, or --out): each one as a
 * temporary layout assigned by the compat cookie, cards in their list, seen by the admin (the register page signed out).
 *
 * Needs cwebp. Use a neutral theme on the site while capturing (the thumbnails show the look, not a theme).
 */
const path = require( 'path' );
const fs = require( 'fs' );
const os = require( 'os' );
const { execFileSync } = require( 'child_process' );
const { wpJson, loadPlaywright } = require( '../tests/compat/lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const site = opt( 'site', 'http://bbux.test' );
const wp = opt( 'wp', path.join( os.homedir(), 'Sites/bbux/tools/wp' ) );
const only = opt( 'only', '' );
const pro = argv.includes( '--pro' );
const out = opt( 'out', pro ? path.join( __dirname, '../../stax-buddy-builder-pro/assets/img/presets' ) : path.join( __dirname, '../assets/img/looks' ) );
// Pro presets: what to frame per page type (cards: their list).
const FRAME = {
	'members-directory-item': '#members-dir-list',
	'groups-directory-item': '#groups-dir-list',
	'sitewide-activity-item': '#activity-stream',
};

( async () => {
	const data = wpJson( wp, [ 'eval-file', path.join( __dirname, 'capture-looks.php' ), opt( 'admin', 'gabriel' ) ].concat( pro ? [ 'pro' ] : [] ), 'BPB_LOOKS' );
	const { chromium } = loadPlaywright();
	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1200, height: 900 }, deviceScaleFactor: 1 } );
	const host = new URL( site ).hostname;
	const login = Object.entries( data.cookies ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) );
	await context.addCookies( login );
	let page = await context.newPage();
	const tmp = fs.mkdtempSync( path.join( os.tmpdir(), 'bpb-looks-' ) );
	fs.mkdirSync( out, { recursive: true } );

	for ( const [ id, look ] of Object.entries( data.looks ) ) {
		if ( only && only !== id ) {
			continue;
		}
		const url = pro ? look.url : look;
		if ( pro ) {
			// A fresh context per preset: the layout cookie, and the login except on the register page.
			await page.context().close().catch( () => {} );
			const ctx = await browser.newContext( { viewport: { width: 1000, height: 900 }, deviceScaleFactor: 1 } );
			await ctx.addCookies( ( 'register-page' === look.type ? [] : login ).concat( [ { name: 'bpb_compat_tpl', value: look.cookie, domain: host, path: '/' } ] ) );
			page = await ctx.newPage();
		}
		await page.goto( url, { waitUntil: 'networkidle' } );
		// A soft cover where the member has no cover photo yet (as most new members).
		await page.addStyleTag( {
			content: '*,*::before,*::after{animation:none!important;transition:none!important}' +
				'#header-cover-image{background:linear-gradient(120deg,#6d8bff,#b48cff)!important}',
		} );
		// AJAX streams (activity, directories) finish loading.
		await page.waitForFunction( () => ! document.querySelector( '.bpb-template .loading, .bpb-template [id$="-loading"]' ) || ! /Loading|Please wait/.test( document.querySelector( '.bpb-template' ).innerText ), null, { timeout: 8000 } ).catch( () => {} );
		await page.waitForTimeout( 500 );
		// Pro presets: wide pages are short, so the square frame runs past the layout; nothing below it shows.
		await page.addStyleTag( { content: '#wpadminbar{display:none!important}' + ( pro ? 'footer,.wp-site-blocks>footer{visibility:hidden!important}' : '' ) } );
		const layout = page.locator( pro ? ( FRAME[ look.type ] || '.bpb-look' ) : '.bpb-template' ).first();
		if ( ! await layout.count() ) {
			console.log( 'SKIP ' + id + ': layout not rendered' );
			continue;
		}
		const box = await layout.boundingBox();
		const png = path.join( tmp, id + '.png' );
		// Square frame from the top of the layout (the part that tells looks apart).
		await page.screenshot( { path: png, fullPage: true, clip: { x: box.x, y: box.y, width: box.width, height: pro ? Math.round( box.width ) : Math.min( box.height, Math.round( box.width ) ) } } );
		const webp = path.join( out, id + '.webp' );
		execFileSync( 'cwebp', [ '-quiet', '-q', '72', '-resize', '480', '0', '-m', '6', png, '-o', webp ] );
		console.log( id + ' ' + Math.round( fs.statSync( webp ).size / 1024 ) + ' KB' );
	}

	await browser.close();
	fs.rmSync( tmp, { recursive: true, force: true } );
	if ( pro ) {
		wpJson( wp, [ 'eval', 'foreach ( get_posts( [ "post_type" => "bpb_template", "post_status" => "any", "numberposts" => -1, "fields" => "ids", "meta_key" => "_bpb_capture" ] ) as $id ) { wp_delete_post( $id, true ); } echo "\\nBPB_LOOKS_DONE 1\\n";' ], 'BPB_LOOKS_DONE' );
	}
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 1 );
} );
