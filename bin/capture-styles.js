#!/usr/bin/env node
/**
 * Screenshots of the styles (patterns/<style>/<page>.html) on a THROWAWAY site with the sample community
 * (bin/sample-community.php) and the compat env mu-plugin: every page of every style, desktop (1366) and mobile (390),
 * seen by a signed-in member. For design review and the docs; nothing is saved on the site (temporary layouts are
 * deleted at the end, pages are assigned per request by the compat cookie).
 *
 *   NODE_PATH=<playwright> node bin/capture-styles.js --site http://bbux.test --wp ~/Sites/bbux/tools/wp \
 *     --out <dir> [--styles classic,creator] [--pages member-profile,...] [--viewer demo-lucas] [--widths 1366,390]
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { wpJson } = require( '../tests/compat/lib' );
const { chromium } = require( 'playwright' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const site = opt( 'site', 'http://bbux.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', path.join( process.env.HOME, 'Sites/bbux/tools/wp' ) );
const out = opt( 'out', '/tmp/bb-styles' );
const viewer = opt( 'viewer', 'demo-lucas' );
const widths = opt( 'widths', '1366,390' ).split( ',' ).map( Number );
const root = path.join( __dirname, '..', 'patterns' );
const styles = opt( 'styles', fs.readdirSync( root ).filter( ( d ) => fs.statSync( path.join( root, d ) ).isDirectory() ).join( ',' ) ).split( ',' );
const pages = opt( 'pages', 'member-profile,members-directory,group-profile,groups-directory,sitewide-activity' ).split( ',' );
const URLS = {
	'member-profile': '/members/maya/',
	'members-directory': '/members/',
	'group-profile': '/groups/demo-design-critique/',
	'groups-directory': '/groups/',
	'sitewide-activity': '/activity/',
};
const host = new URL( site ).hostname;

( async () => {
	const cases = [];
	for ( const style of styles ) {
		for ( const page of pages ) {
			const file = path.join( root, style, page + '.html' );
			if ( fs.existsSync( file ) ) {
				cases.push( { style, page, content: fs.readFileSync( file, 'utf8' ) } );
			}
		}
	}
	const made = wpJson( wp, [ 'eval', `
		$cases = json_decode( base64_decode( '${ Buffer.from( JSON.stringify( cases ) ).toString( 'base64' ) }' ), true );
		$out = [];
		foreach ( $cases as $c ) {
			$out[] = [ 'style' => $c['style'], 'page' => $c['page'], 'id' => wp_insert_post( [ 'post_type' => 'bpb_template', 'post_status' => 'publish', 'post_title' => 'Style ' . $c['style'] . ' ' . $c['page'], 'post_content' => wp_slash( $c['content'] ), 'meta_input' => [ '_bpb_type' => $c['page'], '_bpb_capture' => 1 ] ] ) ];
		}
		echo "\\nBPB_CAPTURE " . wp_json_encode( $out ) . "\\n";
	` ], 'BPB_CAPTURE' );
	const cookies = wpJson( wp, [ 'eval-file', path.join( __dirname, '../tests/compat/bin/cookie.php' ), viewer ], 'BPB_COMPAT_COOKIES' );
	const browser = await chromium.launch();
	try {
		for ( const width of widths ) {
			const ctx = await browser.newContext( { viewport: { width, height: 900 }, deviceScaleFactor: width < 600 ? 2 : 1 } );
			await ctx.addCookies( Object.entries( cookies ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
			for ( const c of made ) {
				// What assigning the layout does on a real site: the directory card style goes to the option the AJAX list reads.
				const card = ( cases.find( ( x ) => x.style === c.style && x.page === c.page ).content.match( /wp:buddybuilder\/(members|groups)-listing \{[^}]*"card":"(\w+)"/ ) || [] );
				if ( card[ 1 ] ) {
					wpJson( wp, [ 'eval', `\\Buddy_Builder\\Templates\\Cards::save( '${ card[ 1 ] }', '${ card[ 2 ] }' ); echo "\\nBPB_CARD 1\\n";` ], 'BPB_CARD' );
				}
				await ctx.addCookies( [ { name: 'bpb_compat_tpl', value: `${ c.page }:${ c.id }`, domain: host, path: '/' } ] );
				const page = await ctx.newPage();
				const errors = [];
				page.on( 'pageerror', ( e ) => errors.push( e.message ) );
				await page.goto( site + URLS[ c.page ], { waitUntil: 'networkidle' } );
				await page.waitForSelector( '[data-bp-list] .bp-list, [data-bp-list] .bp-feedback:not(.loading)', { timeout: 15000 } ).catch( () => {} );
				await page.waitForTimeout( 600 );
				await page.addStyleTag( { content: '#wpadminbar{display:none!important}html{margin-top:0!important}*{transition:none!important;animation:none!important}' } );
				fs.mkdirSync( path.join( out, c.style ), { recursive: true } );
				const file = path.join( out, c.style, `${ c.page }-${ width }.png` );
				const target = ( await page.$( '.bpb-look' ) ) || ( await page.$( 'main' ) );
				await target.screenshot( { path: file } );
				console.log( `${ file }${ errors.length ? ' JS: ' + errors.join( ' | ' ) : '' }` );
				await page.close();
			}
			await ctx.close();
		}
	} finally {
		await browser.close();
		wpJson( wp, [ 'eval', `foreach ( get_posts( [ 'post_type' => 'bpb_template', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bpb_capture' ] ) as $id ) { wp_delete_post( $id, true ); } echo "\\nBPB_CAPTURE_DONE 1\\n";` ], 'BPB_CAPTURE_DONE' );
	}
} )();
