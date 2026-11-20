/**
 * Elementor editor preview: the containers of a Member header split into parts (BuddyBuilder Pro) drop Elementor's
 * container layout classes, as on the page (SplitHeader.php). The editor sets those classes again when it draws or
 * updates a container, so this watches the class attribute. Data: window.bpbSplitHeaderPreview (boxes, layout: a
 * pattern of the class names).
 */
( function ( data ) {
	'use strict';

	if ( ! data || ! window.MutationObserver ) {
		return;
	}

	const selector = data.boxes.map( ( name ) => '.elementor-element.' + name ).join( ',' );
	const layout = new RegExp( data.layout );

	function clean( node ) {
		if ( 1 !== node.nodeType || ! node.matches( selector ) ) {
			return;
		}

		const extra = Array.from( node.classList ).filter( ( name ) => layout.test( name ) );

		if ( extra.length ) {
			node.classList.remove.apply( node.classList, extra );
		}
	}

	function scan( node ) {
		if ( 1 === node.nodeType ) {
			clean( node );
			node.querySelectorAll( selector ).forEach( clean );
		}
	}

	new window.MutationObserver( ( records ) => {
		records.forEach( ( record ) => {
			if ( 'attributes' === record.type ) {
				clean( record.target );
			} else {
				record.addedNodes.forEach( scan );
			}
		} );
	} ).observe( document.documentElement, { subtree: true, childList: true, attributes: true, attributeFilter: [ 'class' ] } );

	scan( document.documentElement );
}( window.bpbSplitHeaderPreview ) );
