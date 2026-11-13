/**
 * BuddyBuilder setup: the live preview follows the picked member profile look; review tabs mark the open page.
 * Plain JavaScript, no dependencies. The screen works without it (the preview then shows the first look).
 */
( function () {
	var preview = document.querySelector( '.bpb-setup__preview' );

	if ( preview ) {
		var urls = JSON.parse( preview.getAttribute( 'data-bpb-urls' ) || '{}' );
		var type = preview.getAttribute( 'data-bpb-type' );
		var frame = preview.querySelector( 'iframe' );
		var who = preview.querySelector( '.bpb-setup__who' );

		var update = function () {
			var checked = document.querySelector( 'input[name="look[' + type + ']"]:checked' );
			var user = who ? who.value : '0';
			var url = checked && urls[ user ] ? urls[ user ][ checked.value ] : '';

			preview.hidden = ! url;

			if ( url && frame.getAttribute( 'src' ) !== url ) {
				frame.setAttribute( 'src', url );
			}
		};

		document.querySelectorAll( 'input[name="look[' + type + ']"]' ).forEach( function ( input ) {
			input.addEventListener( 'change', update );
		} );

		if ( who ) {
			who.addEventListener( 'change', update );
		}

		update();
	}

	var tabs = document.querySelectorAll( '.bpb-review-tabs ul a' );
	var open = document.querySelector( '.bpb-review-tabs__open' );

	tabs.forEach( function ( link ) {
		link.addEventListener( 'click', function () {
			tabs.forEach( function ( other ) {
				other.removeAttribute( 'aria-current' );
			} );
			link.setAttribute( 'aria-current', 'page' );

			if ( open ) {
				open.setAttribute( 'href', link.getAttribute( 'data-bpb-open' ) || link.getAttribute( 'href' ) );
			}
		} );
	} );
}() );
