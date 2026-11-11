/**
 * Grid/list toggle of the members and groups directories (J1): no jQuery.
 * Same markup and classes as 1.x: `.bpb-listing-type` (data-component on its parent) with `.bpb-list-change`
 * buttons (data-list-type grid|list); list mode adds `grid-one-force` to #members-list / #groups-list.
 * The choice is kept per component in sessionStorage (`bp-<component>` -> `bpb-list-mode`), as before.
 */
( function () {
	'use strict';

	var FORCE = 'grid-one-force';

	function getStorage( type, property ) {
		var store = {};
		try {
			store = JSON.parse( window.sessionStorage.getItem( type ) || '{}' ) || {};
		} catch ( e ) {}

		return undefined !== property ? store[ property ] || false : store;
	}

	function setStorage( type, property, value ) {
		var store = getStorage( type );
		if ( undefined === value ) {
			delete store[ property ];
		} else {
			store[ property ] = value;
		}
		try {
			window.sessionStorage.setItem( type, JSON.stringify( store ) );
		} catch ( e ) {}
	}

	function component( el ) {
		return 'bp-' + ( ( el && el.parentNode && el.parentNode.dataset && el.parentNode.dataset.component ) || 'members' );
	}

	function init() {
		var toggle = document.querySelector( '.bpb-listing-type' );

		if ( toggle ) {
			setStorage( component( toggle ), 'bpb-list-hidden' );

			var saved = getStorage( component( toggle ), 'bpb-list-mode' );
			if ( saved ) {
				toggle.querySelectorAll( '.bpb-list-change' ).forEach( function ( b ) {
					b.classList.remove( 'bpb-active' );
				} );
				var active = toggle.querySelector( '.bpb-' + saved + '-mode' );
				if ( active ) {
					active.classList.add( 'bpb-active' );
				}
			}
		}

		document.addEventListener( 'click', function ( e ) {
			var button = e.target.closest( '.bpb-list-change' );
			if ( ! button ) {
				return;
			}
			e.preventDefault();

			var list = 'list' === button.dataset.listType;
			[ 'members-list', 'groups-list' ].forEach( function ( id ) {
				var el = document.getElementById( id );
				if ( el ) {
					el.classList.toggle( FORCE, list );
				}
			} );

			document.querySelectorAll( '.bpb-list-change' ).forEach( function ( b ) {
				b.classList.remove( 'bpb-active' );
			} );
			button.classList.add( 'bpb-active' );

			setStorage( component( button ), 'bpb-list-mode', button.dataset.listType );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
