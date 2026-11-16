/**
 * Block editor: "Replace with the new element" for deprecated BuddyBuilder blocks (2.0). Optional and never
 * automatic: a button in the block sidebar (and the toolbar) replaces the block, and its deprecated siblings that
 * point to the same new element, with one new block. Settings named in deprecated.map are carried over; Undo brings
 * the old blocks back.
 */
( function ( wp ) {
	'use strict';

	const data = window.bpbReplace || { elements: {} };
	const byBlock = {};
	Object.keys( data.elements ).forEach( ( name ) => {
		byBlock[ data.elements[ name ].from_block ] = data.elements[ name ];
	} );

	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const sprintf = wp.i18n.sprintf;

	function siblings( clientId, item ) {
		const editor = wp.data.select( 'core/block-editor' );
		const root = editor.getBlockRootClientId( clientId );
		return editor.getBlockOrder( root ).filter( ( id ) => {
			const other = byBlock[ editor.getBlockName( id ) ];
			return other && other.use === item.use;
		} );
	}

	function replace( clientId, item ) {
		const editor = wp.data.select( 'core/block-editor' );
		const ids = siblings( clientId, item );
		const attrs = {};

		ids.forEach( ( id ) => {
			const old = editor.getBlockAttributes( id ) || {};
			Object.keys( item.map || {} ).forEach( ( from ) => {
				if ( undefined !== old[ from ] ) {
					attrs[ item.map[ from ] ] = old[ from ];
				}
			} );
		} );

		wp.data.dispatch( 'core/block-editor' ).replaceBlocks( ids, wp.blocks.createBlock( item.block, attrs ) );
	}

	const withReplace = wp.compose.createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
		const item = byBlock[ props.name ];

		if ( ! item || ! props.isSelected ) {
			return el( BlockEdit, props );
		}

		const count = siblings( props.clientId, item ).length;
		const label = count > 1 ? sprintf( data.group, count, item.title ) : sprintf( data.button, item.title );
		const run = () => replace( props.clientId, item );

		return el(
			wp.element.Fragment,
			null,
			el( BlockEdit, props ),
			el( wp.blockEditor.BlockControls, { group: 'other' }, el( wp.components.ToolbarButton, { onClick: run }, sprintf( data.button, item.title ) ) ),
			el(
				wp.blockEditor.InspectorControls,
				null,
				el(
					wp.components.PanelBody,
					{ title: __( 'Deprecated', 'stax-buddy-builder' ), initialOpen: true },
					el( 'p', null, data.help ),
					el( wp.components.Button, { variant: 'secondary', onClick: run }, label )
				)
			)
		);
	}, 'bpbReplaceDeprecated' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'buddybuilder/replace-deprecated', withReplace );
}( window.wp ) );
