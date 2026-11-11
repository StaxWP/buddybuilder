#!/usr/bin/env node
/**
 * BuddyBuilder compatibility suite: compares a candidate run with a baseline run (both from run.js).
 *
 *   NODE_PATH=... node tests/compat/compare.js <baseline dir> <candidate dir> [--max-pixels 0.01] [--report <file.md>]
 *
 * Gate per case (inventory 14.6): same HTTP status, no fatal, identical normalised HTML per widget, at most
 * 1% different pixels in the BuddyPress area, no new BuddyBuilder PHP notices, no new axe violation ids.
 * Exit code 0 when every case passes, 1 otherwise.
 */
const fs = require( 'fs' );
const path = require( 'path' );
const { readResults, loadPlaywright, normalise } = require( './lib' );

const argv = process.argv.slice( 2 );
const [ baseDir, candDir ] = argv.filter( ( a, i ) => ! a.startsWith( '--' ) && ! ( argv[ i - 1 ] || '' ).startsWith( '--' ) );
const opt = ( name, def ) => {
	const i = argv.indexOf( '--' + name );
	return i === -1 ? def : argv[ i + 1 ];
};
const maxPixels = parseFloat( opt( 'max-pixels', '0.01' ) );
const reportFile = opt( 'report', path.join( candDir || '.', 'compare.md' ) );

/** Ratio of differing pixels between two PNG files, computed in the browser (no image libraries needed). */
async function pixelRatio( page, a, b ) {
	const toUrl = ( f ) => 'data:image/png;base64,' + fs.readFileSync( f ).toString( 'base64' );
	return page.evaluate( async ( [ ua, ub ] ) => {
		const load = ( u ) => new Promise( ( res, rej ) => {
			const img = new Image();
			img.onload = () => res( img );
			img.onerror = rej;
			img.src = u;
		} );
		const [ ia, ib ] = await Promise.all( [ load( ua ), load( ub ) ] );
		const w = Math.max( ia.width, ib.width );
		const h = Math.max( ia.height, ib.height );
		const data = ( img ) => {
			const c = document.createElement( 'canvas' );
			c.width = w;
			c.height = h;
			const ctx = c.getContext( '2d' );
			ctx.fillStyle = '#ff00ff';
			ctx.fillRect( 0, 0, w, h );
			ctx.drawImage( img, 0, 0 );
			return ctx.getImageData( 0, 0, w, h ).data;
		};
		const da = data( ia );
		const db = data( ib );
		let diff = 0;
		for ( let i = 0; i < da.length; i += 4 ) {
			if ( Math.abs( da[ i ] - db[ i ] ) + Math.abs( da[ i + 1 ] - db[ i + 1 ] ) + Math.abs( da[ i + 2 ] - db[ i + 2 ] ) > 48 ) {
				diff++;
			}
		}
		return { ratio: diff / ( w * h ), size: [ ia.width, ia.height, ib.width, ib.height ] };
	}, [ toUrl( a ), toUrl( b ) ] );
}

/** First differing slice of two normalised strings, for the report. */
function firstDiff( a, b ) {
	let i = 0;
	while ( i < a.length && a[ i ] === b[ i ] ) {
		i++;
	}
	return { at: i, base: a.slice( Math.max( 0, i - 60 ), i + 140 ), cand: b.slice( Math.max( 0, i - 60 ), i + 140 ) };
}

( async () => {
	if ( ! baseDir || ! candDir ) {
		console.error( 'Usage: compare.js <baseline dir> <candidate dir>' );
		process.exit( 2 );
	}
	const base = readResults( baseDir );
	const cand = readResults( candDir );
	const { chromium } = loadPlaywright();
	const browser = await chromium.launch();
	const page = await browser.newPage();

	const rows = [];
	const details = [];
	for ( const key of Object.keys( base.cases ) ) {
		const b = base.cases[ key ];
		const c = cand.cases[ key ];
		const fails = [];
		if ( ! c ) {
			rows.push( { key, fails: [ 'missing in candidate' ] } );
			continue;
		}
		if ( b.status !== c.status ) {
			fails.push( `status ${ b.status } -> ${ c.status }` );
		}
		if ( c.fatal || c.error ) {
			fails.push( c.fatal ? 'fatal error' : 'error: ' + c.error );
		}

		// Normalise again: runs stored with an older normaliser compare with the current rules.
		for ( const r of [ b, c ] ) {
			for ( const k of Object.keys( r.widgets ) ) {
				r.widgets[ k ] = normalise( r.widgets[ k ] );
			}
		}
		const keys = new Set( [ ...Object.keys( b.widgets ), ...Object.keys( c.widgets ) ] );
		let htmlDiffs = 0;
		for ( const k of keys ) {
			if ( b.widgets[ k ] !== c.widgets[ k ] ) {
				htmlDiffs++;
				const d = firstDiff( b.widgets[ k ] || '(missing)', c.widgets[ k ] || '(missing)' );
				details.push( `### ${ key }\nwidget \`${ k }\` at ${ d.at }\n\n- base: \`${ d.base }\`\n- cand: \`${ d.cand }\`\n` );
			}
		}
		if ( htmlDiffs ) {
			fails.push( `html ${ htmlDiffs }/${ keys.size } widgets` );
		}

		let px = null;
		if ( b.screenshot && c.screenshot ) {
			const r = await pixelRatio( page, path.join( baseDir, 'png', b.screenshot ), path.join( candDir, 'png', c.screenshot ) );
			px = r.ratio;
			if ( px > maxPixels ) {
				fails.push( `pixels ${ ( px * 100 ).toFixed( 2 ) }%` );
			}
		}

		const newNotices = ( c.notices || [] ).filter( ( n ) => ! ( b.notices || [] ).includes( n ) );
		if ( newNotices.length ) {
			fails.push( `${ newNotices.length } new notices` );
			details.push( `### ${ key }\nnew notices:\n${ newNotices.map( ( n ) => '- ' + n ).join( '\n' ) }\n` );
		}
		const axeIds = ( list ) => ( list || [] ).map( ( v ) => v.split( ' ' )[ 0 ] );
		const newAxe = axeIds( c.axe ).filter( ( id ) => ! axeIds( b.axe ).includes( id ) );
		if ( newAxe.length ) {
			fails.push( `axe new: ${ newAxe.join( ',' ) }` );
		}
		rows.push( { key, fails, px, widgets: keys.size } );
	}
	await browser.close();

	const failed = rows.filter( ( r ) => r.fails.length );
	const lines = [
		`# Compat: ${ cand.label } vs ${ base.label }`,
		'',
		`Baseline ${ base.date }, candidate ${ cand.date }. Cases: ${ rows.length }, passed: ${ rows.length - failed.length }, failed: ${ failed.length }. Pixel limit ${ maxPixels * 100 }%.`,
		'',
		'| Case | Widgets | Pixels | Result |',
		'|---|---|---|---|',
		...rows.map( ( r ) => `| ${ r.key } | ${ r.widgets ?? '' } | ${ r.px === null || r.px === undefined ? '' : ( r.px * 100 ).toFixed( 2 ) + '%' } | ${ r.fails.length ? r.fails.join( '; ' ) : 'pass' } |` ),
		'',
		details.length ? '## Details\n\n' + details.join( '\n' ) : '',
	];
	fs.writeFileSync( reportFile, lines.join( '\n' ) );
	console.log( lines.slice( 0, 3 ).join( '\n' ) );
	failed.forEach( ( r ) => console.log( `FAIL ${ r.key }: ${ r.fails.join( '; ' ) }` ) );
	console.log( `report: ${ reportFile }` );
	process.exit( failed.length ? 1 : 0 );
} )().catch( ( e ) => {
	console.error( e );
	process.exit( 2 );
} );
