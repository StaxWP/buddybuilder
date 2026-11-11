/**
 * BuddyBuilder blocks editor (2.0 spike). One generic edit component for every element:
 * controls are generated from "bpbControls" in each block.json, the preview is the PHP renderer
 * through ServerSideRender with the template post id (the server reads the preview member from it).
 * Plain ES5 + wp globals so the spike needs no build step.
 */
( function ( wp ) {
	var el = wp.element.createElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	var useSelect = wp.data.useSelect;
	var __ = wp.i18n.__;

	function control( key, def, attributes, setAttributes ) {
		var set = function ( value ) {
			var next = {};
			next[ key ] = value;
			setAttributes( next );
		};

		if ( 'toggle' === def.type ) {
			return el( ToggleControl, {
				key: key,
				label: def.label,
				checked: 'yes' === attributes[ key ],
				onChange: function ( on ) { set( on ? 'yes' : '' ); },
				__nextHasNoMarginBottom: true
			} );
		}

		return el( SelectControl, {
			key: key,
			label: def.label,
			value: attributes[ key ],
			options: Object.keys( def.options || {} ).map( function ( value ) {
				return { value: value, label: def.options[ value ] };
			} ),
			onChange: set,
			__nextHasNoMarginBottom: true
		} );
	}

	( window.bpbBlocks || [] ).forEach( function ( block ) {
		wp.blocks.registerBlockType( block.name, {
			edit: function ( props ) {
				var post = useSelect( function ( select ) {
					var editor = select( 'core/editor' );
					var meta = editor ? editor.getEditedPostAttribute( 'meta' ) || {} : {};
					return { id: editor ? editor.getCurrentPostId() : 0, user: meta._bpb_preview_user || 0 };
				}, [] );
				var keys = Object.keys( block.controls );

				return el(
					'div',
					useBlockProps(),
					keys.length ? el(
						InspectorControls,
						null,
						el( PanelBody, { title: __( 'Settings', 'stax-buddy-builder' ) }, keys.map( function ( key ) {
							return control( key, block.controls[ key ], props.attributes, props.setAttributes );
						} ) )
					) : null,
					el( ServerSideRender, {
						block: block.name,
						attributes: props.attributes,
						urlQueryArgs: { post_id: post.id, bpb_preview_user: post.user },
						skipBlockSupportAttributes: true
					} )
				);
			},
			save: function () {
				return null;
			}
		} );
	} );
}( window.wp ) );

/**
 * Template settings panel (bpb_template only): template type and the member used for the editor preview.
 */
( function ( wp ) {
	if ( ! wp.plugins || ! ( wp.editor && wp.editor.PluginDocumentSettingPanel ) ) {
		return;
	}

	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var SelectControl = wp.components.SelectControl;
	var __ = wp.i18n.__;

	wp.plugins.registerPlugin( 'bpb-template-settings', {
		render: function () {
			var data = useSelect( function ( select ) {
				var editor = select( 'core/editor' );
				return {
					postType: editor.getCurrentPostType(),
					meta: editor.getEditedPostAttribute( 'meta' ) || {},
					users: select( 'core' ).getUsers( { per_page: 50, context: 'view' } ) || []
				};
			}, [] );
			var editPost = useDispatch( 'core/editor' ).editPost;

			if ( 'bpb_template' !== data.postType ) {
				return null;
			}

			var setMeta = function ( key, value ) {
				var meta = {};
				meta[ key ] = value;
				editPost( { meta: meta } );
			};

			return el(
				wp.editor.PluginDocumentSettingPanel,
				{ name: 'bpb-template', title: __( 'BuddyBuilder', 'stax-buddy-builder' ) },
				el( SelectControl, {
					label: __( 'Template type', 'stax-buddy-builder' ),
					value: data.meta._bpb_type || 'member-profile',
					options: [ { value: 'member-profile', label: __( 'Member profile', 'stax-buddy-builder' ) } ],
					onChange: function ( v ) { setMeta( '_bpb_type', v ); },
					__nextHasNoMarginBottom: true
				} ),
				el( SelectControl, {
					label: __( 'Preview as member', 'stax-buddy-builder' ),
					value: String( data.meta._bpb_preview_user || 0 ),
					options: [ { value: '0', label: __( 'Me (current user)', 'stax-buddy-builder' ) } ].concat( data.users.map( function ( u ) {
						return { value: String( u.id ), label: u.name };
					} ) ),
					onChange: function ( v ) { setMeta( '_bpb_preview_user', parseInt( v, 10 ) || 0 ); },
					__nextHasNoMarginBottom: true
				} )
			);
		}
	} );
}( window.wp ) );
