/**
 * BuddyBuilder screens: the "More" and "Change style" menus are <details> elements (they work without JavaScript).
 * This adds the menu keyboard habits: Escape closes the open menu (or its open confirm step) and gives the focus back
 * to its button, opening one menu closes the others, a click outside closes it.
 * Plain ES5, no dependencies.
 */
( function () {
	'use strict';

	var SELECTOR = 'details.bpb-disclosure';

	function openMenus() {
		return Array.prototype.slice.call( document.querySelectorAll( SELECTOR + '[open]' ) );
	}

	function close( details, refocus ) {
		details.removeAttribute( 'open' );

		var summary = details.querySelector( 'summary' );

		if ( refocus && summary ) {
			summary.focus();
		}
	}

	document.addEventListener( 'toggle', function ( event ) {
		var target = event.target;

		if ( ! target.matches || ! target.matches( SELECTOR ) || ! target.open ) {
			return;
		}

		openMenus().forEach( function ( details ) {
			if ( details !== target && ! details.contains( target ) ) {
				close( details, false );
			}
		} );
	}, true );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key && 'Esc' !== event.key ) {
			return;
		}

		// The innermost open <details> around the focus (a confirm step inside a menu closes first).
		var inner = document.activeElement && document.activeElement.closest ? document.activeElement.closest( 'details[open]' ) : null;

		if ( inner && inner.closest( SELECTOR ) ) {
			event.preventDefault();
			close( inner, true );

			return;
		}

		openMenus().forEach( function ( details ) {
			close( details, false );
		} );
	} );

	document.addEventListener( 'click', function ( event ) {
		openMenus().forEach( function ( details ) {
			if ( ! details.contains( event.target ) ) {
				close( details, false );
			}
		} );
	} );
}() );
