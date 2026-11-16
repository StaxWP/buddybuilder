/**
 * Elementor editor preview: the "Replace with ..." button in the note of a deprecated BuddyBuilder widget (2.0).
 * Replaces the widget, and its deprecated siblings in the same column that point to the same new element, with one
 * new widget at the first one's place. Optional and never automatic; Elementor's Undo brings the old ones back.
 */
( function () {
	'use strict';

	const data = window.bpbReplace || { elements: {} };

	// On mousedown (capture): Elementor re-renders a widget when a click selects it, so the click's button would be gone.
	document.addEventListener( 'mousedown', ( event ) => {
		const button = event.target.closest ? event.target.closest( '[data-bpb-replace]' ) : null;
		const editor = window.parent;

		if ( ! button || ! editor || ! editor.$e || ! editor.elementor ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		const item = data.elements[ button.getAttribute( 'data-bpb-replace' ) ];
		const node = button.closest( '.elementor-element[data-id]' );

		if ( ! item || ! item.widget || ! node ) {
			return;
		}

		const container = editor.elementor.getContainer( node.getAttribute( 'data-id' ) );
		const parent = container && container.parent;

		if ( ! parent ) {
			return;
		}

		// The deprecated widgets of this column that the same new element replaces.
		const replaced = parent.children.filter( ( child ) => {
			const type = child.model.get( 'widgetType' );
			return Object.keys( data.elements ).some( ( name ) => data.elements[ name ].use === item.use && -1 !== data.elements[ name ].from_widgets.indexOf( type ) );
		} );
		const at = Math.min.apply( null, replaced.map( ( child ) => parent.children.indexOf( child ) ) );
		const settings = {};

		replaced.forEach( ( child ) => {
			const old = child.settings.toJSON();
			Object.keys( item.map || {} ).forEach( ( from ) => {
				if ( undefined !== old[ from ] ) {
					settings[ item.map[ from ] ] = old[ from ];
				}
			} );
		} );

		editor.$e.run( 'document/elements/create', {
			container: parent,
			model: { elType: 'widget', widgetType: item.widget, settings },
			options: { at },
		} );
		replaced.forEach( ( child ) => editor.$e.run( 'document/elements/delete', { container: child } ) );
	}, true );

	// Keyboard users: Enter or Space on the button.
	document.addEventListener( 'keydown', ( event ) => {
		if ( ( 'Enter' === event.key || ' ' === event.key ) && event.target.closest && event.target.closest( '[data-bpb-replace]' ) ) {
			event.target.dispatchEvent( new MouseEvent( 'mousedown', { bubbles: true, cancelable: true } ) );
		}
	}, true );
}() );
