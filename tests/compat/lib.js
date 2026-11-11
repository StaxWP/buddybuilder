/**
 * Shared helpers for the BuddyBuilder compatibility suite (run.js, compare.js).
 */
const { execFileSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );

/** Pages rendered per template type. `{ana}` style placeholders are BuddyPress slugs of the seeded data. */
const TYPE_URLS = {
	'member-profile': [ '/members/ana/', '/members/ana/profile/' ],
	'members-directory': [ '/members/' ],
	'members-directory-item': [ '/members/' ],
	'group-profile': [ '/groups/compat-group/' ],
	'groups-directory': [ '/groups/' ],
	'groups-directory-item': [ '/groups/' ],
	'sitewide-activity': [ '/activity/' ],
	'sitewide-activity-item': [ '/activity/', '/members/ana/' ],
	'groups-create': [ '/groups/create/step/group-details/' ],
	'register-page': [ '/register/' ],
};

/** Who looks at each page: anonymous visitor and a logged-in member (mihai, a friend of ana). */
const VARIANTS = {
	'groups-create': [ 'member' ],
	'register-page': [ 'anon' ],
	default: [ 'anon', 'member' ],
};

/**
 * Runs WP-CLI and returns the JSON after a marker line.
 *
 * @param {string}   wp     WP-CLI wrapper.
 * @param {string[]} args   Arguments.
 * @param {string}   marker Marker printed before the JSON.
 */
function wpJson( wp, args, marker ) {
	const out = execFileSync( wp, args, { encoding: 'utf8', maxBuffer: 64 * 1024 * 1024, stdio: [ 'ignore', 'pipe', 'ignore' ] } );
	const line = out.split( '\n' ).find( ( l ) => l.startsWith( marker + ' ' ) );
	if ( ! line ) {
		throw new Error( `No ${ marker } in WP-CLI output:\n${ out.slice( -2000 ) }` );
	}
	return JSON.parse( line.slice( marker.length + 1 ) );
}

/**
 * Normalises markup so two runs (or two versions) compare on what matters:
 * nonces, asset versions, relative times, random ids and whitespace are masked.
 *
 * @param {string} html Markup.
 */
function normalise( html ) {
	return html
		.replace( /<!--[\s\S]*?-->/g, '' )
		.replace( /((?:_wpnonce|_ajax_nonce|nonce|_wp_http_referer)[^=]*=["']?)[0-9a-f]{10}/gi, '$1X' )
		.replace( /(name="[^"]*nonce[^"]*"\s+value=")[0-9a-f]{10}"/gi, '$1X"' )
		.replace( /(value=")[0-9a-f]{10}("[^>]*name="[^"]*nonce)/gi, '$1X$2' )
		.replace( /([?&](?:amp;)?ver=)[^"'&\s]+/g, '$1X' )
		.replace( /\b(?:\d+|an?)\s+(?:second|minute|hour|day|week|month|year)s?(?:,\s*(?:\d+|an?)\s+(?:second|minute|hour|day|week|month|year)s?)?\s+ago\b/g, 'TIME ago' )
		.replace( /\s+data-livestamp="[^"]*"/g, '' ) // Added by the livestamp script after load (race).
		.replace( /(datetime|data-bp-timestamp)="[^"]*"/g, '$1="X"' )
		.replace( /(data-pw)="[^"]*"/g, '$1="X"' ) // Random suggested password on the register form.
		.replace( /\b(?:January|February|March|April|May|June|July|August|September|October|November|December) \d{1,2}, \d{4}(?: at \d{1,2}:\d{2} ?[ap]m)?/g, 'DATE' )
		.replace( /\s+/g, ' ' )
		.replace( />\s+</g, '><' )
		.trim();
}

/** Key of a result case. */
const caseKey = ( c ) => `${ c.fixture } ${ c.variant } ${ c.url }`;

/** Safe file name of a case. */
const caseFile = ( c ) => caseKey( c ).replace( /[^a-z0-9]+/gi, '_' ).replace( /^_|_$/g, '' );

/** Reads a results.json. */
const readResults = ( dir ) => JSON.parse( fs.readFileSync( path.join( dir, 'results.json' ), 'utf8' ) );

/**
 * Loads Playwright from the normal resolution path or from NODE_PATH (the KLEO 7 theme's node_modules work).
 */
function loadPlaywright() {
	try {
		return require( 'playwright' );
	} catch ( e ) {
		return require( '@playwright/test' );
	}
}

module.exports = { TYPE_URLS, VARIANTS, wpJson, normalise, caseKey, caseFile, readResults, loadPlaywright };
