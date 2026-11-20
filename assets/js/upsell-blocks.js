/**
 * Block editor, without BuddyBuilder Pro: "Split into parts (Pro)" on the Member header, where Pro has the real action
 * (toolbar and sidebar). Locked: the toolbar item opens the short Pro description of upsell.js (data-bpb-pro), the
 * sidebar shows the same line, a disabled button and the link to the Pro plans. Data: window.bpbUpsell (features,
 * labels) and window.bpbUpsellBlocks (block, feature, label).
 */
( function ( wp, upsell, data ) {
	'use strict';

	if ( ! wp || ! upsell || ! data || ! upsell.features[ data.feature ] ) {
		return;
	}

	const el = wp.element.createElement;
	const { BlockControls, InspectorControls } = wp.blockEditor;
	const { Button, ExternalLink, PanelBody, ToolbarButton, ToolbarGroup } = wp.components;
	const feature = upsell.features[ data.feature ];
	const title = data.label + ' (' + upsell.labels.pro + ')';

	const withLocked = wp.compose.createHigherOrderComponent( ( BlockEdit ) => ( props ) => {
		if ( data.block !== props.name || ! props.isSelected ) {
			return el( BlockEdit, props );
		}

		return el(
			wp.element.Fragment,
			null,
			el( BlockEdit, props ),
			el(
				BlockControls,
				{ group: 'other' },
				el( ToolbarGroup, null, el( ToolbarButton, { icon: 'lock', 'data-bpb-pro': data.feature, 'aria-haspopup': 'dialog', className: 'bpb-pro-locked' }, title ) )
			),
			el(
				InspectorControls,
				null,
				el(
					PanelBody,
					{ title, initialOpen: false },
					el( 'p', null, feature.text ),
					el( 'p', null, el( Button, { variant: 'secondary', icon: 'lock', disabled: true, accessibleWhenDisabled: true }, data.label ) ),
					el( ExternalLink, { href: feature.url }, upsell.labels.cta )
				)
			)
		);
	}, 'bpbUpsellSplit' );

	wp.hooks.addFilter( 'editor.BlockEdit', 'buddybuilder/upsell-split', withLocked );
}( window.wp, window.bpbUpsell, window.bpbUpsellBlocks ) );
