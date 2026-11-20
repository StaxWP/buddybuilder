/**
 * BuddyBuilder Pro features in the free plugin: clicking a "Pro" item (Elementor panel or Community pages screen)
 * opens a short description with a link to the Pro plans (or the feature's own link, cta); the Pro notice dismisses
 * without a reload.
 * No tracking, no requests other than the dismiss. Data: window.bpbUpsell (Upsell::script_data()).
 */
( function () {
	'use strict';

	var data = window.bpbUpsell;

	if ( ! data || ! data.features ) {
		return;
	}

	var popover = null;
	var opener = null;

	/**
	 * @param {boolean} refocus Give the focus back to the item (Escape, the close button), not on an outside click.
	 */
	function close( refocus ) {
		if ( popover ) {
			popover.remove();
			popover = null;
		}

		if ( opener && true === refocus && opener.focus ) {
			opener.focus();
		}

		opener = null;
	}

	function el( tag, className, text ) {
		var node = document.createElement( tag );

		if ( className ) {
			node.className = className;
		}

		if ( text ) {
			node.textContent = text;
		}

		return node;
	}

	/**
	 * Popover next to the clicked item: below it on admin screens, beside it in the Elementor panel (Elementor's own
	 * promotion dialog differs between versions and offers Elementor Pro).
	 */
	function open( id, target, beside ) {
		var feature = data.features[ id ];

		close();

		popover = el( 'div', 'bpb-upsell-pop' );
		popover.setAttribute( 'role', 'dialog' );
		popover.setAttribute( 'aria-label', feature.title );

		var head = el( 'p', 'bpb-upsell-pop__title', feature.title + ' ' );
		head.appendChild( el( 'span', 'bpb-pro-tag', data.labels.pro ) );

		var link = el( 'a', 'bpb-upsell-pop__cta', feature.cta || data.labels.cta );
		link.href = feature.url;
		link.target = '_blank';
		link.rel = 'noopener';
		link.appendChild( el( 'span', 'screen-reader-text', ' ' + data.labels.tab ) );

		var shut = el( 'button', 'bpb-upsell-pop__close', '×' );
		shut.type = 'button';
		shut.setAttribute( 'aria-label', data.labels.close );
		shut.addEventListener( 'click', function () {
			close( true );
		} );

		popover.appendChild( shut );
		popover.appendChild( head );
		popover.appendChild( el( 'p', 'bpb-upsell-pop__text', feature.text ) );
		popover.appendChild( link );
		document.body.appendChild( popover );

		var box = target.getBoundingClientRect();
		var panel = beside && target.closest( '#elementor-panel' );
		var width = popover.offsetWidth;
		var left = Math.min( Math.max( 8, beside ? ( panel || target ).getBoundingClientRect().right + 8 : box.left ), window.innerWidth - width - 8 );
		var top = beside ? Math.min( box.top, window.innerHeight - popover.offsetHeight - 8 ) : box.bottom + 6;

		if ( ! beside && top + popover.offsetHeight > window.innerHeight - 8 ) {
			top = Math.max( 8, box.top - popover.offsetHeight - 6 );
		}

		popover.style.left = left + window.scrollX + 'px';
		popover.style.top = top + window.scrollY + 'px';

		opener = target;
		link.focus();
	}

	function featureOf( node ) {
		var item = node && node.closest ? node.closest( '[data-bpb-pro]' ) : null;

		return item && data.features[ item.getAttribute( 'data-bpb-pro' ) ] ? item.getAttribute( 'data-bpb-pro' ) : '';
	}

	if ( 'elementor' === data.context ) {
		// Capture phase: runs before Elementor's own handler of locked widgets, which would show Elementor Pro's offer.
		document.addEventListener(
			'mousedown',
			function ( event ) {
				var wrapper = event.target.closest ? event.target.closest( '.elementor-element-wrapper' ) : null;
				var id = wrapper ? featureOf( wrapper.querySelector( '[data-bpb-pro]' ) ) : '';

				if ( id ) {
					event.preventDefault();
					event.stopPropagation();
					open( id, wrapper, true );
				} else if ( popover && ! popover.contains( event.target ) ) {
					close();
				}
			},
			true
		);
	} else {
		document.addEventListener( 'click', function ( event ) {
			var id = featureOf( event.target );

			if ( id && ! event.target.closest( '.bpb-upsell-pop' ) ) {
				event.preventDefault();
				open( id, event.target.closest( '[data-bpb-pro]' ) );
			} else if ( popover && ! popover.contains( event.target ) ) {
				close();
			}
		} );
	}

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && popover ) {
			close( true );
		}
	} );

	// The notice: dismiss without leaving the screen (the link still works without JavaScript).
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '.bpb-upsell-notice__dismiss' ) : null;
		var notice = button ? button.closest( '[data-bpb-dismiss]' ) : null;

		if ( ! notice ) {
			return;
		}

		event.preventDefault();

		var body = new window.FormData();
		body.append( 'action', 'bpb_upsell_dismiss' );
		body.append( '_ajax_nonce', notice.getAttribute( 'data-bpb-dismiss' ) );

		window.fetch( data.ajax, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( response ) {
			if ( response.ok ) {
				notice.remove();
			} else {
				window.location.href = button.href;
			}
		} );
	} );
}() );
