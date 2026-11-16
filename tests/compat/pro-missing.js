#!/usr/bin/env node
/**
 * Free/Pro split (2026-11-16): layouts that use BuddyBuilder Pro when Pro is missing. THROWAWAY site with the compat
 * env mu-plugin and both plugins installed.
 *
 *   NODE_PATH=... node tests/compat/pro-missing.js --site http://bbux.test --wp <wp-cli> [--pro-patterns <dir>]
 *
 * Cases, each for a visitor and a signed-in member, on the member profile:
 * - pro-element: the Classic profile with Pro elements in it (Community shortcuts, Highlights rail).
 * - pro-style: a profile made from a Pro style (Hub, from the Pro plugin's styles/ folder).
 *
 * Steps: render with Pro active, deactivate Pro, render, reactivate Pro, render again. With Pro missing each page
 * must answer 200, report no PHP notices from BuddyBuilder, print no Pro element markup and no empty element
 * wrapper, and (pro-style) render as Classic; after reactivation the HTML must equal the first render.
 * Exit 1 when a check fails. Pro is always reactivated at the end.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { wpJson, loadPlaywright, normalise } = require( './lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const site = opt( 'site', 'http://bbux.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', '' );
const proStyles = opt( 'pro-patterns', path.join( __dirname, '../../../stax-buddy-builder-pro/styles' ) );
const host = new URL( site ).hostname;

const classic = fs.readFileSync( path.join( __dirname, '../../patterns/classic/member-profile.html' ), 'utf8' );
const cases = {
	'pro-element': classic.replace( '<!-- wp:buddybuilder/member-about /-->', '<!-- wp:buddybuilder/member-about /-->\n<!-- wp:buddybuilder/shortcuts-nav /-->\n<!-- wp:buddybuilder/highlights-rail /-->' ),
	'pro-style': fs.readFileSync( path.join( proStyles, 'hub/member-profile.html' ), 'utf8' ),
};

const plugin = ( action ) => {
	require( 'child_process' ).execFileSync( wp, [ 'plugin', action, 'stax-buddy-builder-pro' ], { stdio: 'ignore' } );
};

( async () => {
	const ids = wpJson( wp, [ 'eval', `
		$out = [];
		foreach ( json_decode( base64_decode( '${ Buffer.from( JSON.stringify( cases ) ).toString( 'base64' ) }' ), true ) as $name => $content ) {
			$out[ $name ] = wp_insert_post( [ 'post_type' => 'bpb_template', 'post_status' => 'publish', 'post_title' => 'Pro missing ' . $name, 'post_content' => wp_slash( $content ), 'meta_input' => [ '_bpb_type' => 'member-profile', '_bpb_capture' => 1 ] ] );
		}
		echo "\\nBPB_PM " . wp_json_encode( $out ) . "\\n";
	` ], 'BPB_PM' );
	const member = wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/cookie.php' ), 'mihai' ], 'BPB_COMPAT_COOKIES' );
	const { chromium } = loadPlaywright();
	const browser = await chromium.launch();
	const fails = [];

	const render = async ( name, viewer ) => {
		const ctx = await browser.newContext();
		const cookies = [ { name: 'bpb_compat_tpl', value: `member-profile:${ ids[ name ] }`, domain: host, path: '/' } ];
		if ( 'member' === viewer ) {
			Object.entries( member ).forEach( ( [ n, value ] ) => cookies.push( { name: n, value, domain: host, path: '/' } ) );
		}
		await ctx.addCookies( cookies );
		const resp = await ctx.request.get( site + '/members/ana/' );
		const html = await resp.text();
		await ctx.close();
		const start = html.indexOf( 'bpb-template bpb-template-member-profile' );
		const end = html.indexOf( '<!-- bpb-compat-notices' );
		const tpl = -1 === start ? '' : html.slice( start, -1 === end ? undefined : end );
		return {
			status: resp.status(),
			notices: ( html.match( /<!-- bpb-compat-notices (\[.*?\]) -->/ ) || [ '', '["NO-ENV"]' ] )[ 1 ],
			html,
			body: normalise( tpl.replace( /\b(_wpnonce|nonce)=[^"&\s]+/g, '$1=X' ).replace( /value="[0-9a-f]{10}"/g, 'value="X"' ) ),
		};
	};

	const before = {};
	try {
		for ( const name of Object.keys( cases ) ) {
			for ( const viewer of [ 'anon', 'member' ] ) {
				before[ name + viewer ] = await render( name, viewer );
			}
		}

		plugin( 'deactivate' );
		for ( const name of Object.keys( cases ) ) {
			for ( const viewer of [ 'anon', 'member' ] ) {
				const r = await render( name, viewer );
				const key = `${ name } ${ viewer } (Pro missing)`;
				if ( 200 !== r.status ) {
					fails.push( `${ key }: status ${ r.status }` );
				}
				if ( '[]' !== r.notices ) {
					fails.push( `${ key }: notices ${ r.notices }` );
				}
				if ( /critical error|Fatal error/.test( r.html ) ) {
					fails.push( `${ key }: fatal` );
				}
				if ( /bpb-shortcuts|bpb-grail|bpb-mlist|bpb-rail--bubbles|wp-block-buddybuilder-(shortcuts-nav|group-rail|member-list|highlights-rail)/.test( r.html ) ) {
					fails.push( `${ key }: Pro element markup` );
				}
				if ( /<div class="[^"]*wp-block-buddybuilder-[^"]*"[^>]*>\s*<\/div>/.test( r.html ) ) {
					fails.push( `${ key }: empty element wrapper` );
				}
				if ( 'pro-style' === name && ( ! /bpb-look--classic/.test( r.html ) || /bpb-look--hub/.test( r.html ) ) ) {
					fails.push( `${ key }: not rendered as Classic` );
				}
				console.log( `${ fails.some( ( f ) => f.startsWith( key ) ) ? 'FAIL' : 'ok' } ${ key }` );
			}
		}
	} finally {
		plugin( 'activate' );
	}

	for ( const name of Object.keys( cases ) ) {
		for ( const viewer of [ 'anon', 'member' ] ) {
			// Same request as the first render, after reactivation.
			const r = await render( name, viewer );
			const key = `${ name } ${ viewer } (Pro back)`;
			const same = '' !== r.body && r.body === ( before[ name + viewer ] || {} ).body;
			if ( ! same ) {
				fails.push( `${ key }: differs from the render before deactivation` );
			}
			console.log( `${ same ? 'ok' : 'FAIL' } ${ key }` );
		}
	}

	await browser.close();
	wpJson( wp, [ 'eval', `foreach ( [ ${ Object.values( ids ).join( ',' ) } ] as $id ) { wp_delete_post( $id, true ); } echo "\\nBPB_PM_DONE 1\\n";` ], 'BPB_PM_DONE' );
	fails.forEach( ( f ) => console.log( 'FAIL ' + f ) );
	console.log( `pro-missing: ${ fails.length ? 'FAIL' : 'PASS' }` );
	process.exit( fails.length ? 1 : 0 );
} )();
