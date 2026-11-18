/**
 * Elementor editor: "Replace with ..." for BuddyBuilder widgets that have a new element (2.0).
 * - Deprecated widgets: the button in their note in the preview (this script in the preview frame).
 * - Header parts (member and group header parts, first-class widgets): a quiet button at the end of their Content
 *   tab (this script in the editor frame, Elementor's "buddybuilder:replace" button event).
 * Replaces the widget, and its siblings in the same column that point to the same new element, with one new widget
 * at the first one's place. Optional and never automatic; Elementor's Undo brings the old ones back.
 */
( function () {
	'use strict';

	const data = window.bpbReplace || { elements: {} };

	// The new element of a widget type (by its 1.x widget name).
	function itemOf( type ) {
		const name = Object.keys( data.elements ).find( ( key ) => -1 !== data.elements[ key ].from_widgets.indexOf( type ) );
		return name ? data.elements[ name ] : null;
	}

	/**
	 * Replace a widget container and its siblings that point to the same new element.
	 *
	 * @param {Object} editor    The Elementor editor window.
	 * @param {Object} container Elementor container of the widget.
	 */
	function replace( editor, container ) {
		const item = container && container.model ? itemOf( container.model.get( 'widgetType' ) ) : null;
		const parent = container && container.parent;

		if ( ! item || ! item.widget || ! parent ) {
			return;
		}

		const replaced = parent.children.filter( ( child ) => {
			const other = itemOf( child.model.get( 'widgetType' ) );
			return other && other.use === item.use;
		} );
		const at = Math.min.apply( null, replaced.map( ( child ) => parent.children.indexOf( child ) ) );
		const settings = {};

		replaced.forEach( ( child ) => {
			const old = child.settings.toJSON();
			const map = itemOf( child.model.get( 'widgetType' ) ).map || {};
			Object.keys( map ).forEach( ( from ) => {
				if ( undefined !== old[ from ] ) {
					settings[ map[ from ] ] = old[ from ];
				}
			} );
		} );

		editor.$e.run( 'document/elements/create', {
			container: parent,
			model: { elType: 'widget', widgetType: item.widget, settings },
			options: { at },
		} );
		replaced.forEach( ( child ) => editor.$e.run( 'document/elements/delete', { container: child } ) );
	}

	// Editor frame: the button control of the header parts.
	function listen() {
		if ( ! window.elementor || ! window.elementor.channels || window.bpbReplaceListening ) {
			return;
		}
		window.bpbReplaceListening = true;
		window.elementor.channels.editor.on( 'buddybuilder:replace', ( view ) => {
			replace( window, view.container || ( view.options && view.options.container ) );
		} );
	}

	listen();
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor:init', listen );
	}

	// Preview frame: the button in the note of a deprecated widget. On mousedown (capture): Elementor re-renders a
	// widget when a click selects it, so the click's button would be gone.
	document.addEventListener( 'mousedown', ( event ) => {
		const button = event.target.closest ? event.target.closest( '[data-bpb-replace]' ) : null;
		const editor = window.parent;
		const node = button ? button.closest( '.elementor-element[data-id]' ) : null;

		if ( ! node || ! editor || editor === window || ! editor.$e || ! editor.elementor ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();
		replace( editor, editor.elementor.getContainer( node.getAttribute( 'data-id' ) ) );
	}, true );

	// Keyboard users: Enter or Space on the button.
	document.addEventListener( 'keydown', ( event ) => {
		if ( ( 'Enter' === event.key || ' ' === event.key ) && event.target.closest && event.target.closest( '[data-bpb-replace]' ) ) {
			event.target.dispatchEvent( new MouseEvent( 'mousedown', { bubbles: true, cancelable: true } ) );
		}
	}, true );
}() );
