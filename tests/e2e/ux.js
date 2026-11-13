#!/usr/bin/env node
/**
 * BuddyBuilder 2.0 UX end-to-end checks (majors UX plan B-M1 to B-M6): setup wizard, Community pages, layout
 * previews, the "layout not applied" checker, Settings, Help and status, Pro modules; axe on every admin screen.
 *
 * THROWAWAY site only (needs the compat env mu-plugin, like tests/compat). The run snapshots the BuddyBuilder
 * options and restores them at the end, and deletes the layouts it created.
 *
 * Usage (from the plugin root):
 *   NODE_PATH=<dir with playwright and @axe-core/playwright> node tests/e2e/ux.js \
 *     --site http://bbux.test --wp ~/Sites/bbux/tools/wp [--admin gabriel] [--member ana] [--shots <dir>]
 *
 * Prints PASS / FAIL / SKIP lines; exits 1 on any FAIL.
 */
const path = require( 'path' );
const fs = require( 'fs' );
const { execFileSync } = require( 'child_process' );
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

const OPTIONS = [ 'bpb_settings', 'bpb_templates', 'bpb_db_version', 'buddybuilder_db_version', 'bpb_imported_templates', 'bpb_setup', 'bpb_setup_pending', 'bpb_blocks_enabled', 'bpb_render_issues', 'bpb_migrations', 'bpb_migrations_checked', '_bpb_enabled_widgets' ];

let fails = 0;
const ok = ( cond, msg ) => {
	console.log( ( cond ? 'PASS ' : 'FAIL ' ) + msg );
	fails += cond ? 0 : 1;
};
const skip = ( msg ) => console.log( 'SKIP ' + msg );

/** Run PHP on the site, return the JSON it prints after a marker. */
const php = ( code ) => wpJson( wp, [ 'eval', code + ';' ], 'BPB_E2E' );
const echo = ( expr ) => `echo "\\nBPB_E2E " . wp_json_encode( ${ expr } ) . "\\n"`;

const cookiesFor = ( login ) => wpJson( wp, [ 'eval-file', path.join( __dirname, '../compat/bin/cookie.php' ), login ], 'BPB_COMPAT_COOKIES' );

async function axeCheck( page, label ) {
	let AxeBuilder;
	try {
		AxeBuilder = require( '@axe-core/playwright' ).default;
	} catch ( e ) {
		skip( `axe ${ label }: @axe-core/playwright not found` );
		return;
	}
	const result = await new AxeBuilder( { page } ).include( '.bpb-admin' ).exclude( 'iframe' ).withTags( [ 'wcag2a', 'wcag2aa', 'wcag21aa' ] ).analyze();
	const bad = result.violations.filter( ( v ) => [ 'serious', 'critical' ].includes( v.impact ) );
	ok( ! bad.length, `axe ${ label }: no serious or critical violations` + ( bad.length ? ' (' + bad.map( ( v ) => v.id + ' x' + v.nodes.length ).join( ', ' ) + ')' : '' ) );
}

async function shot( page, name, locator ) {
	if ( ! shots ) {
		return;
	}
	fs.mkdirSync( shots, { recursive: true } );
	// Other plugins' notices are not part of BuddyBuilder's screens.
	await page.addStyleTag( { content: '#wpbody-content > .notice, #wpbody-content > .error, #wpbody-content > .updated, .wrap > .notice:not(.inline), .update-nag, div.pmpro_notification, .e-notice, .notice.elementor-message { display: none !important; }' } ).catch( () => {} );
	const file = path.join( shots, name + '.png' );

	if ( locator && locator.clip ) {
		await page.screenshot( { path: file, clip: locator.clip } );
	} else if ( locator ) {
		await page.locator( locator ).first().screenshot( { path: file } );
	} else {
		await page.screenshot( { path: file, fullPage: true } );
	}
}

( async () => {
	const { chromium } = loadPlaywright();
	const saved = php( echo( `array_combine( ${ JSON.stringify( OPTIONS ).replace( /"/g, "'" ) }, array_map( function ( $o ) { return get_option( $o, null ); }, ${ JSON.stringify( OPTIONS ).replace( /"/g, "'" ) } ) )` ) );
	const maxId = php( echo( "(int) $GLOBALS['wpdb']->get_var( \"SELECT MAX(ID) FROM {$GLOBALS['wpdb']->posts}\" )" ) );
	const stylesheet = php( echo( 'get_stylesheet_directory()' ) );
	const override = path.join( stylesheet, 'buddybuilder/members/single/home.php' );
	const hadOverride = fs.existsSync( override );

	const browser = await chromium.launch();
	const context = await browser.newContext( { viewport: { width: 1440, height: 900 } } );
	await context.addCookies( Object.entries( cookiesFor( admin ) ).map( ( [ name, value ] ) => ( { name, value, domain: host, path: '/' } ) ) );
	const page = await context.newPage();
	const jsErrors = [];
	page.on( 'pageerror', ( e ) => jsErrors.push( e.message ) );

	try {
		// 1. Setup wizard on a new install (B-M2).
		php( `foreach ( ${ JSON.stringify( OPTIONS ).replace( /"/g, "'" ) } as $o ) { delete_option( $o ); } update_option( 'bpb_blocks_enabled', '0' ); \\Buddy_Builder\\Admin\\Setup::on_activation(); ` + echo( "get_transient( 'bpb_setup_redirect' )" ) );
		await page.goto( site + '/wp-admin/edit.php' );
		ok( ! page.url().includes( 'page=buddy-builder-setup' ), 'pending setup does not interrupt other screens' );
		await page.goto( site + '/wp-admin/' );
		ok( page.url().includes( 'page=buddy-builder-setup' ), 'new install: the first admin page opens the setup' );
		await page.goto( site + '/wp-admin/' );
		ok( ! page.url().includes( 'page=buddy-builder-setup' ), 'only once' );
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-setup' );
		ok( ( await page.locator( '.bpb-steps [aria-current="step"]' ).textContent() ).includes( '1.' ), 'setup step 1 is current' );
		ok( await page.locator( '.bpb-checks__item' ).count() >= 3, 'step 1 lists the community checks' );
		await axeCheck( page, 'setup step 1' );
		await shot( page, 'setup-1-check' );
		await page.check( 'input[name=builder][value=blocks]' );
		await Promise.all( [ page.waitForNavigation(), page.click( 'button:has-text("Continue")' ) ] );
		ok( page.url().includes( 'step=2' ), 'step 1 leads to step 2' );
		ok( '1' === php( echo( "get_option( 'bpb_blocks_enabled' )" ) ), 'choosing the block editor turns block layouts on' );

		const frame = page.locator( '.bpb-setup__preview iframe' );
		const before = await frame.getAttribute( 'src' );
		ok( before && before.includes( 'bpb_lp' ), 'step 2 previews the look on a real profile' );
		await page.check( 'input[name="look[member-profile]"][value=member-creator]' );
		ok( ( await frame.getAttribute( 'src' ) ).includes( 'member-creator' ), 'picking a look updates the live preview' );
		await page.waitForTimeout( 1500 );
		await axeCheck( page, 'setup step 2' );
		await shot( page, 'setup-2-pick-a-look' );
		ok( 0 === php( echo( "count( array_filter( wp_list_pluck( \\Buddy_Builder\\Templates\\Registry::all(), 'id' ) ) )" ) ), 'nothing is assigned before Use these' );
		await Promise.all( [ page.waitForNavigation(), page.click( 'button:has-text("Use these and preview")' ) ] );
		ok( page.url().includes( 'step=3' ), 'step 2 leads to step 3' );
		ok( await page.locator( '.bpb-review-tabs ul a' ).count() >= 1, 'step 3 lists the pages to review' );
		ok( 0 === php( echo( "count( array_filter( wp_list_pluck( \\Buddy_Builder\\Templates\\Registry::all(), 'id' ) ) )" ) ), 'still nothing live on step 3' );
		await page.waitForTimeout( 1500 );
		await axeCheck( page, 'setup step 3' );
		await shot( page, 'setup-3-review' );
		await Promise.all( [ page.waitForNavigation(), page.click( '.bpb-setup__nav button:text-is("Use these")' ) ] );
		ok( page.url().includes( 'bpb_done=setup' ), 'Use these lands on Community pages' );
		const live = php( echo( "[ \\Buddy_Builder\\Templates\\Registry::get( 'member-profile' ), get_post_meta( \\Buddy_Builder\\Templates\\Registry::get( 'member-profile' )['id'], '_bpb_preset', true ) ]" ) );
		ok( 'blocks' === live[ 0 ].builder && 'member-creator' === live[ 1 ], 'member profile now uses the Creator look with the block editor' );

		// 2. Community pages (B-M1).
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-settings' );
		const row = page.locator( '#bpb-page-member-profile' );
		ok( ( await row.locator( '.bpb-cp__status' ).textContent() ).includes( 'Live' ), 'Community pages: member profile is live' );
		await axeCheck( page, 'community pages' );
		await shot( page, 'community-pages' );
		await row.locator( '.bpb-more > summary' ).first().click();
		const moreTop = await row.evaluate( ( el ) => {
			window.scrollTo( 0, el.getBoundingClientRect().top + window.scrollY - 60 );
			return el.getBoundingClientRect().top;
		} );
		await shot( page, 'community-pages-more', { clip: { x: 160, y: Math.max( 0, moreTop - 20 ), width: 1280, height: Math.min( 420, 900 - Math.max( 0, moreTop - 20 ) ) } } );
		await Promise.all( [ page.waitForNavigation(), row.locator( 'button:has-text("Use the BuddyPress default")' ).click() ] );
		ok( ( await page.locator( '#bpb-page-member-profile .bpb-cp__status' ).textContent() ).includes( 'BuddyPress default' ), 'Use the BuddyPress default works' );
		const looks = page.locator( '#bpb-page-member-profile .bpb-looks' );
		await looks.locator( 'summary' ).click();
		const top = await page.locator( '#bpb-page-member-profile' ).evaluate( ( el ) => {
			window.scrollTo( 0, el.getBoundingClientRect().top + window.scrollY - 60 );
			return el.getBoundingClientRect().top;
		} );
		await shot( page, 'community-pages-pick-a-look', { clip: { x: 160, y: Math.max( 0, top - 20 ), width: 1280, height: Math.min( 560, 900 - Math.max( 0, top - 20 ) ) } } );
		await Promise.all( [ page.waitForNavigation(), looks.locator( 'form.bpb-look' ).first().locator( 'button' ).first().click() ] );
		ok( ( await page.locator( '#bpb-page-member-profile .bpb-cp__status' ).textContent() ).includes( 'Live' ), 'Pick a look makes a new layout live' );
		const select = page.locator( '#bpb-layout-member-profile' );
		const options = await select.locator( 'option' ).count();
		ok( options >= 3, `existing layouts listed in More (${ options - 1 })` );

		// 3. Previews are for the administrator only (B-M2/B-M3). Preview URLs carry a nonce of the browser session,
		// so take one from the setup screen (rendering it changes nothing).
		const profile = php( echo( `\\Buddy_Builder\\Templates\\LayoutPreview::profile_url( get_user_by( 'login', '${ admin }' )->ID )` ) );
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-setup&step=2' );
		const bannerUrl = ( await page.locator( '.bpb-setup__preview iframe' ).getAttribute( 'src' ) ).replace( /&bpb_lp_bare=1/, '' );
		await page.goto( bannerUrl );
		ok( await page.locator( '.bpb-preview-banner' ).count() === 1, 'preview renders for the administrator and says only you see it' );
		ok( ( await page.content() ).includes( 'bpb-template-member-profile' ), 'preview renders the look' );
		const guest = await browser.newContext();
		const guestPage = await guest.newPage();
		await guestPage.goto( bannerUrl );
		ok( await guestPage.locator( '.bpb-preview-banner' ).count() === 0, 'a visitor with the same URL gets the live page, not the preview' );
		await guest.close();

		// 4. Layout not applied checker (B-M4): a theme file replaces the profile template.
		fs.mkdirSync( path.dirname( override ), { recursive: true } );
		fs.writeFileSync( override, '<?php /* BuddyBuilder e2e: a theme override that ignores the layout. */ ?><div id="bpb-e2e-override">theme override</div>\n' );
		await page.goto( profile );
		await page.waitForSelector( '#wp-admin-bar-bpb-community' );
		const bar = await page.locator( '#wp-admin-bar-bpb-community' ).textContent();
		ok( bar.includes( 'Layout not applied' ), 'admin bar says Layout not applied' );
		ok( /theme has BuddyBuilder template files/.test( bar ), 'the theme override is named as the cause' );
		await page.evaluate( () => document.getElementById( 'wp-admin-bar-bpb-community' ).classList.add( 'hover' ) );
		await shot( page, 'checker-admin-bar', { clip: { x: 0, y: 0, width: 1440, height: 260 } } );
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-settings' );
		ok( ( await page.locator( '#bpb-page-member-profile .bpb-cp__status' ).textContent() ).includes( 'Not applied on the last visit' ), 'Community pages shows the finding' );
		await shot( page, 'community-pages-not-applied', '#bpb-page-member-profile' );
		fs.unlinkSync( override );
		await page.goto( profile );
		ok( ( await page.locator( '#wp-admin-bar-bpb-community' ).textContent() ).includes( 'Edit this community page' ), 'without the override: Edit this community page' );
		ok( null === php( echo( "\\Buddy_Builder\\Admin\\Checker::last_issue( 'member-profile' )" ) ), 'the finding is forgotten once the layout shows' );

		// 5. Settings and Help and status (B-M6).
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-options' );
		ok( await page.locator( 'text=Sample member for previews' ).count() > 0, 'Settings screen loads' );
		await axeCheck( page, 'settings' );
		await shot( page, 'settings' );
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-status' );
		ok( await page.locator( 'h2:has-text("Data update")' ).count() > 0, 'Help and status screen loads' );
		await axeCheck( page, 'help and status' );
		await shot( page, 'help-and-status' );
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-templates' );
		if ( await page.locator( '.bpb-kits' ).count() ) {
			await axeCheck( page, 'starter kits' );
			await shot( page, 'starter-kits' );
		}

		// 6. Pro modules (B-M5), when BuddyBuilder Pro is active and licensed.
		await page.goto( site + '/wp-admin/admin.php?page=buddy-builder-modules' );
		if ( await page.locator( '.bpb-modules' ).count() ) {
			const sw = page.locator( '#bpb-module-link-preview' );
			const was = await sw.isChecked();
			await sw.click();
			await page.waitForFunction( () => document.querySelector( '#bpb-module-link-preview' ).closest( '.bpb-module' ).querySelector( '.bpb-module__state' ).textContent.length > 0 );
			ok( php( echo( "\\bpb_pro_is_module_active( 'link-preview' )" ) ) === ! was, 'Pro module switch saves at once' );
			await sw.click();
			await page.waitForTimeout( 800 );
			await axeCheck( page, 'pro modules' );
			await shot( page, 'pro-modules' );
		} else {
			skip( 'Pro modules: BuddyBuilder Pro not active or not licensed' );
		}

		ok( ! jsErrors.length, 'no JavaScript errors' + ( jsErrors.length ? ': ' + jsErrors.join( ' | ' ) : '' ) );
	} catch ( e ) {
		ok( false, 'run stopped: ' + e.message );
	} finally {
		await browser.close();

		if ( ! hadOverride && fs.existsSync( override ) ) {
			fs.unlinkSync( override );
		}

		// Restore the options and delete the layouts this run created.
		fs.writeFileSync( path.join( require( 'os' ).tmpdir(), 'bpb-e2e-options.json' ), JSON.stringify( saved ) );
		execFileSync( wp, [ 'eval', `$s = json_decode( file_get_contents( '${ path.join( require( 'os' ).tmpdir(), 'bpb-e2e-options.json' ) }' ), true ); foreach ( $s as $o => $v ) { if ( null === $v ) { delete_option( $o ); } else { update_option( $o, $v ); } } foreach ( get_posts( [ 'post_type' => [ 'bpb_template', 'elementor_library' ], 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) as $id ) { if ( $id > ${ maxId } ) { wp_delete_post( $id, true ); } }` ], { stdio: 'ignore' } );
	}

	process.exit( fails ? 1 : 0 );
} )();
