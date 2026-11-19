/**
 * BuddyBuilder > Settings > Community style: the sample follows the choices before they are saved. The tokens of
 * each choice come from PHP (data-tokens, the same CommunityStyle::tokens() the site prints); the accent's text color
 * uses the same rule: white when it reaches 4.5:1, else whichever reads better.
 */
( function () {
	var root = document.querySelector( '.bpb-cs' );

	if ( ! root ) {
		return;
	}

	var form = root.closest( 'form' );
	var sample = root.querySelector( '.bpb-cs-sample' );
	var tokens = JSON.parse( root.getAttribute( 'data-tokens' ) || '{}' );
	var text = JSON.parse( root.getAttribute( 'data-text' ) || '["#ffffff","#111111"]' );
	var custom = form.querySelector( '[name="bpb_cs_custom_accent"]' );
	var color = form.querySelector( '[name="bpb_cs_accent"]' );

	function rgb( hex ) {
		hex = hex.replace( '#', '' );
		return [ 0, 2, 4 ].map( function ( i ) {
			return parseInt( hex.substr( i, 2 ), 16 );
		} );
	}

	function luminance( hex ) {
		var w = [ 0.2126, 0.7152, 0.0722 ];
		return rgb( hex ).reduce( function ( sum, c, i ) {
			c /= 255;
			return sum + w[ i ] * ( c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 ) );
		}, 0 );
	}

	function contrast( a, b ) {
		var la = luminance( a );
		var lb = luminance( b );
		return ( Math.max( la, lb ) + 0.05 ) / ( Math.min( la, lb ) + 0.05 );
	}

	function mix( a, b, amount ) {
		var x = rgb( a );
		var y = rgb( b );
		return '#' + x.map( function ( c, i ) {
			return ( '0' + Math.round( c + ( y[ i ] - c ) * amount ).toString( 16 ) ).slice( -2 );
		} ).join( '' );
	}

	function accentTokens( hex ) {
		var light = contrast( hex, text[ 0 ] );
		var on = light >= 4.5 || light >= contrast( hex, text[ 1 ] ) ? text[ 0 ] : text[ 1 ];
		return {
			'--bpb-accent': hex,
			'--bpb-style-accent': hex,
			'--bpb-style-accent-2': mix( hex, on === text[ 0 ] ? '#000000' : '#ffffff', 0.18 ),
			'--bpb-color-accent-contrast': on,
			'--bpb-button-color': on,
		};
	}

	function update() {
		var all = {};

		Object.keys( tokens ).forEach( function ( key ) {
			var field = form.querySelector( '[name="bpb_cs_' + key + '"]' );
			var set = field && tokens[ key ][ field.value ];
			if ( set && ! Array.isArray( set ) ) {
				Object.assign( all, set );
			}
		} );

		color.disabled = ! custom.checked;
		if ( custom.checked ) {
			Object.assign( all, accentTokens( color.value ) );
		}

		sample.setAttribute( 'style', Object.keys( all ).map( function ( name ) {
			return name + ':' + all[ name ];
		} ).join( ';' ) );
	}

	form.addEventListener( 'input', update );
	form.addEventListener( 'change', update );
	update();
}() );
