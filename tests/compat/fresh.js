#!/usr/bin/env node
/**
 * Fresh install check (review G5, G3, G9): BuddyBuilder activated on a site that never had it, the setup opened
 * by the first admin screen, a look picked for every page and used, then every community page rendered for a
 * visitor and a member: HTTP 200, no fatal, no BuddyBuilder PHP notice, BuddyBuilder blocks in the page, budgets.
 *
 *   NODE_PATH=... node tests/compat/fresh.js --site http://bbmin.test --wp ~/Sites/bbmin/tools/wp \
 *     [--admin admin] [--builder blocks|elementor] [--shots <dir>] [--keep]
 *
 * THROWAWAY site only (needs the compat env mu-plugin). Deletes every BuddyBuilder option and layout first, so the
 * plugin sees a new install. Seeds the compat members and group (load-fixtures.php). Exit 1 on any FAIL.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const zlib = require( 'zlib' );
const { execFileSync } = require( 'child_process' );
const { wpJson, loadPlaywright, TYPE_URLS } = require( './lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const site = opt( 'site', 'http://bbmin.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', path.join( process.env.HOME, 'Sites/bbmin/tools/wp' ) );
const admin = opt( 'admin', 'admin' );
const builder = opt( 'builder', 'blocks' );
const shots = opt( 'shots', '' );
const host = new URL( site ).hostname;

let fails = 0;
const ok = ( cond, msg ) => {
	console.log( ( cond ? 'PASS ' : 'FAIL ' ) + msg );
	fails += cond ? 0 : 1;
};
const php = ( code ) => wpJson( wp, [ 'eval', code + ';' ], 'BPB_FRESH' );
const echo = ( expr ) => `echo "\\nBPB_FRESH " . wp_json_encode( ${ expr } ) . "\\n"`;
const cookiesFor = ( login ) => wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/cookie.php' ), login ], 'BPB_COMPAT_COOKIES' );

( async () => {
	// A new install: no BuddyBuilder option, no layout; then activation as WordPress does it.
	execFileSync( wp, [ 'plugin', 'deactivate', 'stax-buddy-builder-pro', 'stax-buddy-builder' ], { stdio: 'ignore' } );
	php( `global $wpdb; foreach ( $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'bpb\\_%' OR option_name LIKE '_bpb\\_%' OR option_name LIKE '_transient_bpb\\_%' OR option_name = 'buddybuilder_db_version'" ) as $o ) { delete_option( $o ); } foreach ( get_posts( [ 'post_type' => [ 'bpb_template', 'elementor_library' ], 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bpb_page', 'meta_compare' => 'EXISTS' ] ) as $id ) { wp_delete_post( $id, true ); } foreach ( get_posts( [ 'post_type' => 'bpb_template', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) as $id ) { wp_delete_post( $id, true ); } ` + echo( '1' ) );
	execFileSync( wp, [ 'eval-file', path.join( __dirname, 'bin/load-fixtures.php' ), path.join( __dirname, 'fixtures-none' ) ], { stdio: 'ignore' } );
	execFileSync( wp, [ 'plugin', 'activate', 'stax-buddy-builder' ], { stdio: 'ignore' } );
	const version = php( echo( "get_bloginfo( 'version' ) . ' / PHP ' . PHP_VERSION . ' / ' . ( defined( 'ELEMENTOR_VERSION' ) ? 'Elementor ' . ELEMENTOR_VERSION : 'no Elementor' ) . ' / ' . get_stylesheet() . ( is_multisite() ? ' / multisite blog ' . get_current_blog_id() : '' )" ) );
	console.log( `site: ${ version }` );

	const { chromium } = loadPlaywright();
	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1366, height: 900 } } );
	await context.addCookies( Object.entries( cookiesFor( admin ) ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
	const page = await context.newPage();
	const jsErrors = [];
	page.on( 'pageerror', ( e ) => jsErrors.push( e.message ) );
	const shot = async ( name ) => {
		if ( shots ) {
			fs.mkdirSync( shots, { recursive: true } );
			await page.screenshot( { path: path.join( shots, name + '.png' ), fullPage: true } );
		}
	};

	try {
		await page.goto( site + '/wp-admin/plugins.php' );
		ok( page.url().includes( 'page=buddy-builder-setup' ), 'the first admin screen after activation opens the setup' );
		await shot( 'fresh-1' );

		// Step 1 (or the one-screen style picker): continue with the builder this row tests.
		const radio = page.locator( `input[name=builder][value=${ builder }]` );
		if ( await radio.count() ) {
			await radio.check();
		}
		for ( let step = 0; step < 4 && ! page.url().includes( 'bpb_done=setup' ); step++ ) {
			const primary = page.locator( '.bpb-setup__nav button.button-primary, .bpb-setup button.button-primary[type=submit]' ).last();
			if ( ! await primary.count() ) {
				break;
			}
			await Promise.all( [ page.waitForNavigation(), primary.click() ] );
			await shot( 'fresh-' + ( step + 2 ) );
		}
		ok( page.url().includes( 'bpb_done=setup' ), 'the setup ends on Community pages with the looks live' );

		const live = php( echo( "array_map( function ( $e ) { return $e['builder'] . ':' . ( $e['id'] ? get_post_type( $e['id'] ) : '' ); }, \\Buddy_Builder\\Templates\\Registry::all() )" ) );
		const wanted = builder === 'blocks' ? 'blocks:bpb_template' : 'elementor:elementor_library';
		const types = [ 'member-profile', 'members-directory', 'group-profile', 'groups-directory', 'sitewide-activity' ];
		ok( types.every( ( t ) => live[ t ] === wanted ), `the five free pages use ${ wanted } layouts (${ JSON.stringify( live ) })` );

		// Front end: visitor and member.
		const member = await browser.newContext( { viewport: { width: 1366, height: 900 } } );
		await member.addCookies( Object.entries( cookiesFor( 'mihai' ) ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
		const visitor = await browser.newContext( { viewport: { width: 1366, height: 900 } } );
		for ( const [ who, ctx ] of [ [ 'visitor', visitor ], [ 'member', member ] ] ) {
			for ( const t of types ) {
				const url = TYPE_URLS[ t ][ 0 ];
				const p = await ctx.newPage();
				const errs = [];
				p.on( 'pageerror', ( e ) => errs.push( e.message ) );
				const resp = await p.goto( site + url, { waitUntil: 'networkidle' } );
				const html = await resp.text();
				const notices = ( html.match( /<!-- bpb-compat-notices (\[.*?\]) -->/ ) || [ '', '["NO-ENV"]' ] )[ 1 ];
				const blocks = await p.locator( builder === 'blocks' ? '.bpb-el' : '[class*="elementor-widget-bpb-"]' ).count();
				let budget = '';
				const am = html.match( /<!-- bpb-compat-assets (\{.*?\}) -->/ );
				if ( am ) {
					const a = JSON.parse( am[ 1 ] );
					let js = 0;
					let jq = [];
					for ( const s of a.js.filter( ( x ) => x.owner !== 'k-elements' ) ) {
						const r = await ctx.request.get( s.src );
						js += zlib.gzipSync( await r.body(), { level: 9 } ).length;
						jq = jq.concat( s.jquery ? [ s.handle ] : [] );
					}
					const ours = a.dashicons && who === 'visitor' && ! ( a.dashicons_by || [] ).some( ( h ) => ! /^(stax-buddy-builder|bpb-)/.test( h ) );
					budget = ` JS ${ ( js / 1024 ).toFixed( 1 ) } KB${ jq.length ? ' jQuery: ' + jq.join( ',' ) : '' }${ ours ? ' dashicons' : '' }`;
					ok( js <= 25 * 1024 && ! jq.length && ! ours, `${ who } ${ url }: budgets${ budget }` );
				}
				ok( resp.status() === 200 && ! /critical error|Fatal error/.test( html ), `${ who } ${ url }: 200, no fatal` );
				ok( notices === '[]', `${ who } ${ url }: no BuddyBuilder notices ${ notices === '[]' ? '' : notices }` );
				ok( blocks > 0, `${ who } ${ url }: ${ blocks } BuddyBuilder elements rendered` );
				ok( ! errs.length, `${ who } ${ url }: no JavaScript errors ${ errs.join( ' | ' ) }` );
				if ( shots ) {
					await p.screenshot( { path: path.join( shots, `front-${ who }-${ t }.png` ), fullPage: true } );
				}
				await p.close();
			}
		}
		ok( ! jsErrors.length, 'no JavaScript errors in the setup ' + jsErrors.join( ' | ' ) );
	} catch ( e ) {
		ok( false, 'exception: ' + String( e.message || e ).split( '\n' )[ 0 ] );
	}
	await browser.close();
	console.log( fails ? `${ fails } FAIL` : 'ALL PASS' );
	process.exit( fails ? 1 : 0 );
} )();
