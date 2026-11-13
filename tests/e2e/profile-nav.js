#!/usr/bin/env node
/**
 * Member profile navigation with a BuddyBuilder layout, on the site's theme and on Twenty Twenty-Five (BuddyPress 12+
 * priority nav: the "more" menu of block themes). Regression for "Failed to execute 'appendChild' on 'Node'" thrown
 * by BuddyPress's buddypress-priority-menu.js on Twenty Twenty-Five profiles that use a layout.
 *
 * For each theme and each builder (the Elementor layout assigned on the site, if any, and a block layout made from
 * the first preset): no JavaScript errors on the member's Activity and Profile screens, wide and narrow and after
 * resizing; where the theme uses the priority nav, one priority sub nav (not the hidden copy of the screen
 * template), and on a narrow screen the overflowing tabs are in the "more" menu, which opens.
 *
 * THROWAWAY site only (needs the compat env mu-plugin). Restores the theme and the BuddyBuilder options, deletes the
 * layouts it creates.
 *
 * Usage (from the plugin root):
 *   NODE_PATH=<dir with playwright> node tests/e2e/profile-nav.js --site http://bbux.test --wp ~/Sites/bbux/tools/wp \
 *     [--admin gabriel] [--member ana] [--themes kleo,twentytwentyfive] [--shots <dir>]
 *
 * Prints PASS / FAIL / SKIP lines; exits 1 on any FAIL.
 */
const path = require( 'path' );
const fs = require( 'fs' );
const { wpJson, loadPlaywright } = require( '../compat/lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};

const site = opt( 'site', 'http://bbux.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', path.join( process.env.HOME, 'Sites/bbux/tools/wp' ) );
const admin = opt( 'admin', 'gabriel' );
const member = opt( 'member', 'ana' );
const shots = opt( 'shots', '' );
const host = new URL( site ).hostname;

let fails = 0;
const ok = ( cond, msg ) => {
	console.log( ( cond ? 'PASS ' : 'FAIL ' ) + msg );
	fails += cond ? 0 : 1;
};
const skip = ( msg ) => console.log( 'SKIP ' + msg );

const php = ( code ) => wpJson( wp, [ 'eval', code + ' echo "\\nBPB_E2E " . wp_json_encode( $r ) . "\\n";' ], 'BPB_E2E' );

/** State of the priority navs on the page. */
const navState = ( page ) => page.evaluate( () => {
	const navs = [ ...document.querySelectorAll( '.bp-priority-nav' ) ];
	const list = ( n ) => n.parentNode.querySelector( '.bp-priority-' + n.id + '-nav-items[id]' );
	return {
		navs: navs.map( ( n ) => n.id ),
		visibleSubnavs: navs.filter( ( n ) => 'subnav' === n.id && n.offsetWidth > 0 ).length,
		hiddenSubnavs: navs.filter( ( n ) => 'subnav' === n.id && ! n.offsetWidth ).length,
		ready: navs.every( ( n ) => list( n ) && list( n ).nextElementSibling && list( n ).nextElementSibling.querySelector( '.hidden-items' ) && list( n ).nextElementSibling.querySelector( '.bp-priority-nav-more-toggle' ) ),
		primaryHidden: document.querySelectorAll( '#object-nav .hidden-items > li' ).length,
		primaryToggle: !! ( document.querySelector( '#object-nav .bp-priority-nav-more-toggle' ) || {} ).offsetParent,
		tabs: document.querySelectorAll( '#object-nav li a' ).length,
	};
} );

( async () => {
	const { chromium } = loadPlaywright();
	const saved = php( "$r = [ 'stylesheet' => get_stylesheet(), 'template' => get_template(), 'options' => array_combine( $o = [ 'bpb_templates', 'bpb_settings', 'bpb_replaced_elementor', 'bpb_blocks_enabled' ], array_map( function ( $n ) { return get_option( $n, null ); }, $o ) ), 'elementor' => \\Buddy_Builder\\Templates\\Registry::get( 'member-profile' ), 'themes' => array_keys( wp_get_themes() ) ];" );
	const themes = opt( 'themes', [ saved.stylesheet, 'twentytwentyfive' ].filter( ( t, i, a ) => a.indexOf( t ) === i ).join( ',' ) ).split( ',' );
	const created = [];

	// Builders to check: the site's Elementor layout (when there is one) and a block layout from the first preset.
	const builders = [];
	if ( saved.elementor && 'elementor' === saved.elementor.builder && saved.elementor.id ) {
		builders.push( { name: 'elementor', set: `\\Buddy_Builder\\Templates\\Registry::set( 'member-profile', 'elementor', ${ saved.elementor.id } );` } );
	} else {
		skip( 'Elementor layout: none assigned to the member profile on this site' );
	}
	const block = php( "$c = ''; foreach ( \\Buddy_Builder\\Presets\\Presets::all( 'member-profile' ) as $p ) { $c = \\Buddy_Builder\\Presets\\Builder::to_blocks( $p['layout'] ); break; } $r = wp_insert_post( [ 'post_type' => \\Buddy_Builder\\Templates\\PostType::POST_TYPE, 'post_status' => 'publish', 'post_title' => 'e2e profile nav', 'post_content' => $c, 'meta_input' => [ \\Buddy_Builder\\Templates\\PostType::META_TYPE => 'member-profile' ] ] );" );
	created.push( block );
	builders.push( { name: 'blocks', set: `update_option( 'bpb_blocks_enabled', '1' ); \\Buddy_Builder\\Templates\\Registry::set( 'member-profile', 'blocks', ${ block } );` } );

	const browser = await chromium.launch();
	const cookies = wpJson( wp, [ 'eval-file', path.join( __dirname, '../compat/bin/cookie.php' ), admin ], 'BPB_COMPAT_COOKIES' );

	try {
		for ( const theme of themes ) {
			if ( ! saved.themes.includes( theme ) ) {
				skip( `${ theme }: theme not installed` );
				continue;
			}
			php( `switch_theme( '${ theme }' ); $r = get_stylesheet();` );

			for ( const builder of builders ) {
				php( builder.set + ' \\Buddy_Builder\\Templates\\Registry::flush(); $r = 1;' );
				const label = `${ theme } + ${ builder.name } layout`;
				const context = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
				await context.addCookies( Object.entries( cookies ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
				const page = await context.newPage();
				// Errors from BuddyPress and BuddyBuilder scripts (and inline ones) fail the run; other plugins' errors are
				// listed (for example Elementor Pro Theme Builder parts on a block theme throw "elementorFrontendConfig is
				// not defined" on every page of such a site, BuddyBuilder or not).
				const errors = [];
				const others = [];
				page.on( 'pageerror', ( e ) => ( /\/wp-content\/plugins\/(?!buddypress\/|stax-buddy-builder)/.test( e.stack || '' ) ? others : errors ).push( e.message ) );

				for ( const screen of [ '', 'profile/' ] ) {
					const url = `${ site }/members/${ member }/${ screen }`;
					await page.setViewportSize( { width: 1440, height: 900 } );
					await page.goto( url, { waitUntil: 'load' } );
					await page.waitForTimeout( 400 );
					const wide = await navState( page );
					ok( wide.tabs > 0 && await page.locator( '#item-header .bpb-template-member-profile, #item-header > .elementor' ).count() > 0, `${ label } /${ screen }: the layout renders the member tabs (${ wide.tabs })` );

					if ( wide.navs.length ) {
						ok( wide.ready, `${ label } /${ screen }: every priority nav has its list and "more" menu (${ wide.navs.join( ', ' ) })` );
						ok( wide.hiddenSubnavs === 0 && wide.visibleSubnavs <= 1, `${ label } /${ screen }: one priority sub nav, no hidden copy (${ wide.visibleSubnavs } shown, ${ wide.hiddenSubnavs } hidden)` );

						await page.setViewportSize( { width: 360, height: 800 } );
						await page.goto( url, { waitUntil: 'load' } );
						await page.waitForTimeout( 400 );
						const narrow = await navState( page );
						ok( narrow.primaryHidden > 0 && narrow.primaryToggle, `${ label } /${ screen }: narrow screen moves ${ narrow.primaryHidden } tabs to the "more" menu` );
						await page.locator( '#object-nav .bp-priority-nav-more-toggle' ).hover();
						ok( await page.locator( '#object-nav .hidden-items > li a' ).first().isVisible(), `${ label } /${ screen }: the "more" menu opens with the hidden tabs` );
						if ( shots && '' === screen ) {
							fs.mkdirSync( shots, { recursive: true } );
							await page.screenshot( { path: path.join( shots, `profile-nav-${ theme }-${ builder.name }-narrow.png` ) } );
						}
						await page.setViewportSize( { width: 1440, height: 900 } );
						await page.waitForTimeout( 500 );
						await page.setViewportSize( { width: 700, height: 900 } );
						await page.waitForTimeout( 500 );
					} else {
						skip( `${ label } /${ screen }: the theme does not use the BuddyPress priority nav` );
					}
				}

				ok( ! errors.length, `${ label }: no JavaScript errors from BuddyPress or BuddyBuilder` + ( errors.length ? ': ' + errors.join( ' | ' ) : '' ) );
				if ( others.length ) {
					console.log( `NOTE ${ label }: other plugins' errors: ${ [ ...new Set( others ) ].join( ' | ' ) }` );
				}
				await context.close();
			}
		}
	} catch ( e ) {
		ok( false, 'run stopped: ' + e.message );
	} finally {
		await browser.close();
		fs.writeFileSync( path.join( require( 'os' ).tmpdir(), 'bpb-e2e-nav.json' ), JSON.stringify( saved ) );
		php( `$s = json_decode( file_get_contents( '${ path.join( require( 'os' ).tmpdir(), 'bpb-e2e-nav.json' ) }' ), true ); switch_theme( $s['stylesheet'] ); foreach ( $s['options'] as $o => $v ) { if ( null === $v ) { delete_option( $o ); } else { update_option( $o, $v ); } } foreach ( ${ JSON.stringify( created ) } as $id ) { wp_delete_post( $id, true ); } \\Buddy_Builder\\Templates\\Registry::flush(); $r = get_stylesheet();` );
	}

	process.exit( fails ? 1 : 0 );
} )();
