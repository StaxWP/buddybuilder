#!/usr/bin/env node
/**
 * Golden tests for block layouts (review G4): block layouts have no 1.9 baseline, so their own rendered output is
 * the reference. Every look (preset) and every card style is rendered as a block layout on the compat members and
 * group, for a visitor and a member; the normalised HTML of the layout is compared with the stored golden file and a
 * screenshot is taken (compared with --against <previous out dir> when given).
 *
 *   NODE_PATH=... node tests/compat/golden.js --site http://bbgate.test --wp ~/Sites/bbgate/tools/wp \
 *     --row tt25 [--update] [--strict] [--against <dir>] [--out <dir>] [--only <substring>]
 *
 * - Goldens live in tests/compat/golden/<row>/<case>.html (commit them; --update rewrites them after a reviewed
 *   design change). A missing golden is written and reported as NEW; with --strict (the gate) it is a FAIL and
 *   nothing is written.
 * - <row> names the environment (theme, Elementor on or off), e.g. tt25, tt25-noel, kleo7, kleo7-noel.
 * - THROWAWAY site only: creates the layouts as bpb_template posts (deleted at the end) and assigns them per
 *   request with the compat cookie.
 * - Phone cases (phone-<preset>): every member profile and group page preset as it is applied (the style's own
 *   pattern), at 390 px wide, signed out: the member or group header (.bpb-mh, .bpb-gh) must start within PHONE_MAX
 *   px of the layout's top (UX review 2026-11-17: the Career profile showed its header card after the whole activity
 *   stream on phones). No HTML golden; a screenshot is kept.
 * Exit 1 when an HTML golden differs, a page is not 200, a BuddyBuilder notice appears, a phone case has its header
 * further down, or (with --against) more than 1% of the pixels changed.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { TYPE_URLS, VARIANTS, wpJson, normalise, loadPlaywright } = require( './lib' );

const argv = process.argv.slice( 2 );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const site = opt( 'site', 'http://bbgate.test' ).replace( /\/$/, '' );
const wp = opt( 'wp', path.join( process.env.HOME, 'Sites/bbgate/tools/wp' ) );
const row = opt( 'row', 'default' );
const update = argv.includes( '--update' );
const strict = argv.includes( '--strict' );
const against = opt( 'against', '' );
const only = opt( 'only', '' );
const out = path.resolve( opt( 'out', path.join( __dirname, 'results', 'golden-' + row ) ) );
const goldenDir = path.join( __dirname, 'golden', row );
const host = new URL( site ).hostname;

// Lists with a card style: page type, block, styles.
const CARDS = [
	[ 'members-directory', 'members-listing', [ 'classic', 'compact', 'cover' ] ],
	[ 'groups-directory', 'groups-listing', [ 'classic', 'compact', 'cover' ] ],
	[ 'sitewide-activity', 'activity-stream', [ 'classic', 'compact', 'boxed' ] ],
];

// Phone cases: the header of a member profile or group page starts within this many px of the layout's top at 390 px.
const PHONE_MAX = 400;

// Dynamic parts of a block layout that change between runs without a code change.
const mask = ( html ) => normalise( html )
	.replace( /\b(?:user|group)-\d+-avatar\b/g, 'ID-avatar' )
	// Uploaded avatar file names carry a hash that changes when the photo is uploaded again (same photo).
	.replace( /(avatars\/\d+\/)[0-9a-f]{32}(-bp(?:full|thumb))/g, '$1H$2' )
	.replace( /\bdata-bp-item-id="\d+"/g, 'data-bp-item-id="N"' )
	.replace( /\b(activity|acomment|comment)-(\d+)\b/g, '$1-N' )
	.replace( /([?&](?:amp;)?(?:_wpnonce|nonce|rand|ver)=)[^"&\s]+/g, '$1X' );

/**
 * Phone case: the layout at 390 px wide; its member or group header must start near the top.
 *
 * @param {Object} ctx Browser context (signed out).
 * @param {Object} c   Case (name, type, id).
 * @param {string} url Page path.
 * @return {Promise<string>} Result line.
 */
async function phoneCase( ctx, c, url ) {
	await ctx.addCookies( [ { name: 'bpb_compat_tpl', value: `${ c.type }:${ c.id }`, domain: host, path: '/' } ] );
	const page = await ctx.newPage();
	await page.setViewportSize( { width: 390, height: 844 } );
	const resp = await page.goto( site + url, { waitUntil: 'networkidle' } );
	await page.waitForTimeout( 400 );
	const top = await page.evaluate( () => {
		const tpl = document.querySelector( '.bpb-template' );
		const head = tpl ? tpl.querySelector( '.bpb-mh, .bpb-gh' ) : null;
		return head ? Math.round( head.getBoundingClientRect().top - tpl.getBoundingClientRect().top ) : null;
	} );
	await page.screenshot( { path: path.join( out, 'png', c.name + '.png' ), fullPage: true } );
	await page.close();
	if ( resp.status() !== 200 || null === top ) {
		return `FAIL status ${ resp.status() }${ null === top ? ', no header' : '' }`;
	}
	return top <= PHONE_MAX ? `ok (header at ${ top }px)` : `FAIL header at ${ top }px at 390 px wide (over ${ PHONE_MAX })`;
}

( async () => {
	const { chromium } = loadPlaywright();
	const plan = wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/load-fixtures.php' ), path.join( __dirname, 'fixtures-none' ) ], 'BPB_COMPAT_PLAN' );
	// Directories and rails sort by last activity: fixed times make the order the same on every run.
	wpJson( wp, [ 'eval', 'foreach ( get_users( [ "fields" => "ID" ] ) as $id ) { bp_update_user_last_activity( $id, gmdate( "Y-m-d H:i:s", strtotime( "2025-01-01 10:00:00" ) - (int) $id * 60 ) ); } if ( bp_is_active( "groups" ) ) { foreach ( groups_get_groups( [ "per_page" => -1, "show_hidden" => true ] )["groups"] as $g ) { groups_update_groupmeta( $g->id, "last_activity", gmdate( "Y-m-d H:i:s", strtotime( "2025-01-01 10:00:00" ) - (int) $g->id * 60 ) ); } } wp_cache_flush(); echo "\\nBPB_GOLDEN_TIME 1\\n";' ], 'BPB_GOLDEN_TIME' );
	const member = wpJson( wp, [ 'eval-file', path.join( __dirname, 'bin/cookie.php' ), 'mihai' ], 'BPB_COMPAT_COOKIES' );

	// Create one block layout per look and per card style.
	const cards = CARDS.flatMap( ( [ type, block, styles ] ) => styles.map( ( style ) => ( { name: `card-${ type }-${ style }`, type, content: `<!-- wp:buddybuilder/${ block } {"card":"${ style }"} /-->` } ) ) );
	// Card layouts render in their list on a page with a block layout of the list (the same on every site, whatever
	// the site assigns to the directory or activity page).
	const lists = CARDS.map( ( [ type, block ] ) => ( { name: `list-${ type }`, type, content: `<!-- wp:buddybuilder/${ block } /-->`, list: true } ) );
	const made = wpJson( wp, [ 'eval', `
		$cases = json_decode( base64_decode( '${ Buffer.from( JSON.stringify( cards.concat( lists ) ) ).toString( 'base64' ) }' ), true );
		foreach ( \\Buddy_Builder\\Presets\\Presets::all() as $id => $preset ) {
			if ( isset( \\Buddy_Builder\\Templates\\Types::for_blocks()[ $preset['type'] ] ) ) {
				// BuddyBuilder Pro presets (register, group creation, cards) as they are applied; the five pages as before.
				$pro = ! in_array( $preset['type'], \\Buddy_Builder\\Templates\\Types::BLOCK_TYPES, true );
				$cases[] = [ 'name' => 'look-' . $id, 'type' => $preset['type'], 'content' => $pro ? \\Buddy_Builder\\Presets\\Presets::blocks( $preset ) : \\Buddy_Builder\\Presets\\Builder::to_blocks( $preset['layout'] ) ];
				if ( in_array( $preset['type'], [ 'member-profile', 'group-profile' ], true ) ) {
					$cases[] = [ 'name' => 'phone-' . $id, 'type' => $preset['type'], 'content' => \\Buddy_Builder\\Presets\\Presets::blocks( $preset ), 'phone' => true ];
				}
			}
		}
		// Block layouts on for the run (card, register and group creation layouts need it); the options a render can
		// write (card styles and columns mirrored from the layouts) are put back at the end.
		update_option( 'bpb_golden_was', array_map( function ( $o ) { return get_option( $o, '__none__' ); }, array_combine( [ 'bpb_blocks_enabled', 'bpb_cards', 'bpb_listings' ], [ 'bpb_blocks_enabled', 'bpb_cards', 'bpb_listings' ] ) ) );
		update_option( 'bpb_blocks_enabled', '1' );
		$out = [];
		foreach ( $cases as $c ) {
			$out[] = [ 'name' => $c['name'], 'type' => $c['type'], 'list' => ! empty( $c['list'] ), 'phone' => ! empty( $c['phone'] ), 'id' => wp_insert_post( [ 'post_type' => 'bpb_template', 'post_status' => 'publish', 'post_title' => 'Golden ' . $c['name'], 'post_content' => $c['content'], 'meta_input' => [ '_bpb_type' => $c['type'], '_bpb_golden' => 1 ] ] ) ];
		}
		echo "\\nBPB_GOLDEN " . wp_json_encode( $out ) . "\\n";
	` ], 'BPB_GOLDEN' );

	fs.mkdirSync( path.join( out, 'png' ), { recursive: true } );
	fs.mkdirSync( goldenDir, { recursive: true } );
	const browser = await chromium.launch();
	const contexts = {};
	for ( const v of [ 'anon', 'member' ] ) {
		contexts[ v ] = await browser.newContext( { viewport: { width: 1366, height: 900 }, reducedMotion: 'reduce' } );
		if ( v === 'member' ) {
			await contexts[ v ].addCookies( Object.entries( member ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
		}
	}

	let fails = 0;
	const lines = [];
	for ( const c of made ) {
		if ( c.list || ( only && ! c.name.includes( only ) ) ) {
			continue;
		}
		const url = ( TYPE_URLS[ c.type ] || [] )[ 0 ];
		if ( c.phone ) {
			const result = await phoneCase( contexts.anon, c, url );
			fails += result.startsWith( 'FAIL' ) ? 1 : 0;
			lines.push( `| ${ c.name } | ${ result } |` );
			console.log( `${ result } ${ c.name }` );
			continue;
		}
		for ( const v of VARIANTS[ c.type ] || VARIANTS.default ) {
			const key = `${ c.name }-${ v }`;
			const ctx = contexts[ v ];
			const list = c.type.endsWith( '-item' ) ? made.find( ( m ) => m.list && m.type === c.type.replace( /-item$/, '' ) ) : null;
			await ctx.addCookies( [ { name: 'bpb_compat_tpl', value: ( list ? `${ list.type }:${ list.id },` : '' ) + `${ c.type }:${ c.id }`, domain: host, path: '/' } ] );
			const page = await ctx.newPage();
			const resp = await page.goto( site + url, { waitUntil: 'networkidle' } );
			await page.waitForTimeout( 400 );
			await page.addStyleTag( { content: '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}#wpadminbar{display:none!important}' } );
			const raw = await resp.text();
			const notices = ( raw.match( /<!-- bpb-compat-notices (\[.*?\]) -->/ ) || [ '', '["NO-ENV"]' ] )[ 1 ];
			// Cards: the first card of the list (the page itself may have no layout).
			const tpl = page.locator( c.type.endsWith( '-item' ) ? `.bpb-template-${ c.type }` : '.bpb-template' ).first();
			const html = ( await tpl.count() ) ? mask( await tpl.evaluate( ( e ) => e.outerHTML ) ) : '';
			const file = path.join( goldenDir, key + '.html' );
			let result = 'ok';
			if ( resp.status() !== 200 || notices !== '[]' || ! html ) {
				result = `FAIL status ${ resp.status() } notices ${ notices } ${ html ? '' : 'no layout' }`;
			} else if ( ! fs.existsSync( file ) && strict ) {
				result = 'FAIL no golden (run without --strict to write it, then review and commit it)';
			} else if ( ! fs.existsSync( file ) || update ) {
				result = fs.existsSync( file ) ? 'updated' : 'NEW';
				fs.writeFileSync( file, html.replace( /></g, '>\n<' ) + '\n' );
			} else if ( fs.readFileSync( file, 'utf8' ).replace( /\n/g, '' ) !== html.replace( /></g, '><' ) ) {
				result = 'FAIL html differs from the golden';
				// The rendered markup next to the report, to diff against the golden before an --update.
				fs.mkdirSync( path.join( out, 'html' ), { recursive: true } );
				fs.writeFileSync( path.join( out, 'html', key + '.html' ), html.replace( /></g, '>\n<' ) + '\n' );
			}
			const png = path.join( out, 'png', key + '.png' );
			await ( ( await page.$( '#buddypress' ) ) || ( await page.$( 'main' ) ) || ( await page.$( 'body' ) ) ).screenshot( { path: png } );
			if ( against && result === 'ok' && fs.existsSync( path.join( against, 'png', key + '.png' ) ) ) {
				const ratio = await page.evaluate( async ( [ a, b ] ) => {
					const load = ( s ) => new Promise( ( r ) => {
						const i = new Image();
						i.onload = () => r( i );
						i.src = s;
					} );
					const [ x, y ] = await Promise.all( [ load( a ), load( b ) ] );
					const w = Math.max( x.width, y.width );
					const h = Math.max( x.height, y.height );
					const d = ( i ) => {
						const cv = document.createElement( 'canvas' );
						cv.width = w;
						cv.height = h;
						const t = cv.getContext( '2d' );
						t.fillStyle = '#f0f';
						t.fillRect( 0, 0, w, h );
						t.drawImage( i, 0, 0 );
						return t.getImageData( 0, 0, w, h ).data;
					};
					const A = d( x );
					const B = d( y );
					let n = 0;
					for ( let k = 0; k < A.length; k += 4 ) {
						if ( Math.abs( A[ k ] - B[ k ] ) + Math.abs( A[ k + 1 ] - B[ k + 1 ] ) + Math.abs( A[ k + 2 ] - B[ k + 2 ] ) > 48 ) {
							n++;
						}
					}
					return n / ( w * h );
				}, [ 'data:image/png;base64,' + fs.readFileSync( path.join( against, 'png', key + '.png' ) ).toString( 'base64' ), 'data:image/png;base64,' + fs.readFileSync( png ).toString( 'base64' ) ] );
				if ( ratio > 0.01 ) {
					result = `FAIL pixels ${ ( ratio * 100 ).toFixed( 2 ) }%`;
				}
			}
			fails += result.startsWith( 'FAIL' ) ? 1 : 0;
			lines.push( `| ${ key } | ${ result } |` );
			console.log( `${ result } ${ key }` );
			await page.close();
		}
	}
	await browser.close();

	wpJson( wp, [ 'eval', `foreach ( get_posts( [ 'post_type' => 'bpb_template', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_bpb_golden' ] ) as $id ) { wp_delete_post( $id, true ); } foreach ( (array) get_option( 'bpb_golden_was', [] ) as $o => $was ) { '__none__' === $was ? delete_option( $o ) : update_option( $o, $was ); } delete_option( 'bpb_golden_was' ); echo "\\nBPB_GOLDEN_DONE 1\\n";` ], 'BPB_GOLDEN_DONE' );
	fs.writeFileSync( path.join( out, 'golden.md' ), `# Golden ${ row }\n\n${ lines.length - fails }/${ lines.length } pass.\n\n| Case | Result |\n|---|---|\n${ lines.join( '\n' ) }\n` );
	console.log( `golden ${ row }: ${ lines.length - fails }/${ lines.length } pass -> ${ out }` );
	process.exit( fails ? 1 : 0 );
} )();
