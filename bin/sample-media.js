#!/usr/bin/env node
/**
 * Generates licence-free sample media for screenshots and the docs: illustrated avatars (flat portraits) and
 * abstract cover images, as JPEG files. Everything is drawn here (no downloads), so it can ship and be reused freely.
 *
 *   NODE_PATH=<dir with playwright> node bin/sample-media.js <out dir> [--avatars 16] [--covers 16]
 *
 * Deterministic: the same index always gives the same image.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( 'playwright' );

const out = process.argv[ 2 ];
const num = ( name, def ) => {
	const i = process.argv.indexOf( '--' + name );
	return i === -1 ? def : parseInt( process.argv[ i + 1 ], 10 );
};
if ( ! out ) {
	console.error( 'usage: sample-media.js <out dir>' );
	process.exit( 2 );
}
fs.mkdirSync( out, { recursive: true } );

const rnd = ( seed ) => () => {
	seed = ( seed * 16807 ) % 2147483647;
	return ( seed - 1 ) / 2147483646;
};
const pick = ( r, list ) => list[ Math.floor( r() * list.length ) ];

const SKIN = [ '#f5d0b5', '#eab896', '#d39b72', '#b97a56', '#8d5a3c', '#5e3a26', '#f2c9a8', '#c68863' ];
const HAIR = [ '#2b1d16', '#4a2f20', '#7a4a2a', '#c58b4b', '#e2c27a', '#1c1c1c', '#8a8f98', '#a33b2b' ];
const SHIRT = [ '#3b5bdb', '#0ca678', '#f08c00', '#e64980', '#7048e8', '#1098ad', '#495057', '#d9480f', '#2f9e44', '#c2255c' ];
const BG = [ [ '#e7f5ff', '#d0ebff' ], [ '#fff4e6', '#ffe8cc' ], [ '#ebfbee', '#d3f9d8' ], [ '#f3f0ff', '#e5dbff' ], [ '#fff0f6', '#ffdeeb' ], [ '#e3fafc', '#c5f6fa' ], [ '#fff9db', '#fff3bf' ], [ '#f1f3f5', '#e9ecef' ] ];

function avatarSvg( i ) {
	const r = rnd( 1000 + i * 7919 );
	const skin = pick( r, SKIN );
	const hair = pick( r, HAIR );
	const shirt = pick( r, SHIRT );
	const bg = pick( r, BG );
	const style = Math.floor( r() * 5 );
	const glasses = r() < 0.25;
	const hairShapes = [
		'<path d="M138 168c0-62 38-98 118-98s118 36 118 98c0 10-2 20-6 30-8-44-40-70-112-70s-104 26-112 70c-4-10-6-20-6-30z"/>',
		'<path d="M132 210c-6-86 40-142 124-142s132 56 124 142c-4-36-18-60-34-74-30 18-86 22-150 10-28 14-56 34-64 64z"/>',
		'<path d="M128 330c-20-120 8-262 128-262s148 142 128 262c-10-80-20-120-30-150-34 18-162 18-196 0-10 30-20 70-30 150z"/>',
		'<path d="M150 150c10-50 52-80 106-80 60 0 98 34 106 84-26-20-60-30-106-30s-80 8-106 26z"/>',
		'<path d="M136 196c-10-84 44-130 120-130 82 0 132 48 122 132-16-44-50-66-122-66s-104 20-120 64zM330 120c30 4 50 30 46 70-12-30-26-46-46-50z"/>',
	];
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="512" height="512">
<defs><linearGradient id="b" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${ bg[ 0 ] }"/><stop offset="1" stop-color="${ bg[ 1 ] }"/></linearGradient></defs>
<rect width="512" height="512" fill="url(#b)"/>
<path d="M76 512c10-104 86-160 180-160s170 56 180 160z" fill="${ shirt }"/>
<path d="M216 300h80v70c-10 18-70 18-80 0z" fill="${ skin }" opacity=".92"/>
<ellipse cx="256" cy="214" rx="104" ry="122" fill="${ skin }"/>
<ellipse cx="152" cy="222" rx="16" ry="24" fill="${ skin }"/><ellipse cx="360" cy="222" rx="16" ry="24" fill="${ skin }"/>
<g fill="${ hair }">${ hairShapes[ style ] }</g>
<ellipse cx="216" cy="226" rx="9" ry="11" fill="#2b2b2b"/><ellipse cx="296" cy="226" rx="9" ry="11" fill="#2b2b2b"/>
<path d="M196 196q20-12 40 0M276 196q20-12 40 0" stroke="${ hair }" stroke-width="7" fill="none" stroke-linecap="round"/>
<path d="M226 284q30 22 60 0" stroke="#9c4f3f" stroke-width="7" fill="none" stroke-linecap="round"/>
${ glasses ? '<g fill="none" stroke="#343a40" stroke-width="6"><circle cx="216" cy="226" r="26"/><circle cx="296" cy="226" r="26"/><path d="M242 226h28"/></g>' : '' }
<path d="M216 352c10 20 70 20 80 0l26 12c-22 36-110 36-132 0z" fill="#fff" opacity=".35"/>
</svg>`;
}

const PALETTES = [
	[ '#4263eb', '#748ffc', '#bac8ff', '#edf2ff' ],
	[ '#0ca678', '#38d9a9', '#96f2d7', '#e6fcf5' ],
	[ '#f76707', '#ff922b', '#ffc078', '#fff4e6' ],
	[ '#ae3ec9', '#da77f2', '#eebefa', '#f8f0fc' ],
	[ '#1098ad', '#3bc9db', '#99e9f2', '#e3fafc' ],
	[ '#e03131', '#ff6b6b', '#ffa8a8', '#fff5f5' ],
	[ '#364fc7', '#5c7cfa', '#f783ac', '#fff0f6' ],
	[ '#2b8a3e', '#69db7c', '#ffd43b', '#fff9db' ],
];

function coverSvg( i ) {
	const r = rnd( 5000 + i * 104729 );
	const p = PALETTES[ i % PALETTES.length ];
	const kind = i % 4;
	const blobs = Array.from( { length: 6 }, () => `<circle cx="${ Math.round( r() * 1600 ) }" cy="${ Math.round( r() * 500 ) }" r="${ Math.round( 120 + r() * 260 ) }" fill="${ pick( r, p.slice( 0, 3 ) ) }" opacity="${ ( 0.35 + r() * 0.4 ).toFixed( 2 ) }"/>` ).join( '' );
	let scene = '';
	if ( kind === 0 ) {
		// Hills.
		scene = [ 0, 1, 2 ].map( ( k ) => {
			const y = 300 + k * 70;
			let d = `M0 ${ y }`;
			for ( let x = 0; x <= 1600; x += 200 ) {
				d += ` Q${ x + 100 } ${ y - 60 - r() * 90 } ${ x + 200 } ${ y - r() * 30 }`;
			}
			return `<path d="${ d } V500 H0z" fill="${ p[ k ] }" opacity="${ 0.55 + k * 0.15 }"/>`;
		} ).join( '' );
	} else if ( kind === 1 ) {
		// Waves.
		scene = [ 0, 1, 2, 3 ].map( ( k ) => `<path d="M0 ${ 260 + k * 55 } C 400 ${ 180 + k * 55 + r() * 80 }, 1100 ${ 360 + k * 50 - r() * 80 }, 1600 ${ 240 + k * 55 } V500 H0z" fill="${ p[ k % 3 ] }" opacity="${ 0.35 + k * 0.15 }"/>` ).join( '' );
	} else if ( kind === 2 ) {
		// Grid of shapes.
		for ( let x = 0; x < 1600; x += 100 ) {
			for ( let y = 0; y < 500; y += 100 ) {
				const s = r();
				const c = pick( r, p );
				scene += s < 0.33 ? `<circle cx="${ x + 50 }" cy="${ y + 50 }" r="40" fill="${ c }"/>` : s < 0.66 ? `<rect x="${ x + 10 }" y="${ y + 10 }" width="80" height="80" rx="18" fill="${ c }"/>` : `<path d="M${ x + 10 } ${ y + 90 } A80 80 0 0 1 ${ x + 90 } ${ y + 10 } V${ y + 90 }z" fill="${ c }"/>`;
			}
		}
		scene = `<g opacity=".85">${ scene }</g>`;
	} else {
		// Sunset with mountains.
		scene = `<circle cx="${ 400 + r() * 800 }" cy="230" r="110" fill="#fff" opacity=".7"/>` + [ 0, 1 ].map( ( k ) => {
			let d = `M0 500 L0 ${ 360 + k * 40 }`;
			for ( let x = 0; x <= 1600; x += 160 ) {
				d += ` L${ x + 80 } ${ 200 + k * 80 + r() * 120 } L${ x + 160 } ${ 330 + k * 40 }`;
			}
			return `<path d="${ d } V500z" fill="${ p[ k ] }" opacity="${ 0.7 + k * 0.2 }"/>`;
		} ).join( '' );
	}
	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 500" width="1600" height="500">
<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${ p[ 3 ] }"/><stop offset="1" stop-color="${ p[ 2 ] }"/></linearGradient>
<filter id="f"><feGaussianBlur stdDeviation="60"/></filter></defs>
<rect width="1600" height="500" fill="url(#g)"/><g filter="url(#f)">${ blobs }</g>${ scene }
</svg>`;
}

( async () => {
	const browser = await chromium.launch();
	const page = await browser.newPage();
	for ( const [ kind, count, fn, w, h ] of [ [ 'avatar', num( 'avatars', 16 ), avatarSvg, 512, 512 ], [ 'cover', num( 'covers', 16 ), coverSvg, 1600, 500 ] ] ) {
		await page.setViewportSize( { width: w, height: h } );
		for ( let i = 0; i < count; i++ ) {
			await page.setContent( `<style>body{margin:0}</style>${ fn( i ) }` );
			await page.screenshot( { path: path.join( out, `${ kind }-${ String( i + 1 ).padStart( 2, '0' ) }.jpg` ), type: 'jpeg', quality: 86 } );
		}
	}
	await browser.close();
	console.log( `media written to ${ out }` );
} )();
