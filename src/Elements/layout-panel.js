/**
 * BuddyBuilder layout panel (block editor, bpb_template only): template type, preview member or group, seen by.
 * The element kit's editor sends the unsaved choices with every preview request (editor_query_args).
 * Plain ES5 + wp globals, no build step.
 */
( function ( wp, config ) {
	if ( ! wp.plugins || ! wp.editor || ! wp.editor.PluginDocumentSettingPanel ) {
		return;
	}

	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var SelectControl = wp.components.SelectControl;
	var __ = wp.i18n.__;

	function options( list, emptyLabel ) {
		return [ { value: '0', label: emptyLabel } ].concat( ( list || [] ).map( function ( item ) {
			return { value: String( item.id ), label: item.name || ( item.title && item.title.rendered ) || ( '#' + item.id ) };
		} ) );
	}

	// "Seen by" choices of a type; without the moderation capability only "you" and "a guest" (the server checks too).
	function viewers( type ) {
		var list = ( config.viewers || {} )[ type ];
		if ( ! list ) {
			return null;
		}
		return Object.keys( list ).filter( function ( value ) {
			return config.canSimulate || '' === value || 'guest' === value;
		} ).map( function ( value ) {
			return { value: value, label: list[ value ] };
		} );
	}

	wp.plugins.registerPlugin( 'bpb-layout-panel', {
		render: function () {
			var data = useSelect( function ( select ) {
				var editor = select( 'core/editor' );
				var core = select( 'core' );
				return {
					postType: editor.getCurrentPostType(),
					meta: editor.getEditedPostAttribute( 'meta' ) || {},
					users: core.getUsers( { per_page: 50, context: 'view' } ) || []
				};
			}, [] );
			var groupsState = wp.element.useState( [] );
			var editPost = useDispatch( 'core/editor' ).editPost;
			var type = data.meta._bpb_type || 'member-profile';

			wp.element.useEffect( function () {
				if ( 'group-profile' !== type ) {
					return;
				}
				wp.apiFetch( { path: wp.url.addQueryArgs( '/buddypress/v1/groups', { per_page: 50, show_hidden: true } ) } ).then( function ( groups ) {
					groupsState[ 1 ]( groups || [] );
				} ).catch( function () {
					groupsState[ 1 ]( [] );
				} );
			}, [ type ] );

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
				{ name: 'bpb-layout', title: __( 'BuddyBuilder', 'stax-buddy-builder' ), initialOpen: true },
				el( SelectControl, {
					label: __( 'Template type', 'stax-buddy-builder' ),
					value: type,
					options: config.types,
					onChange: function ( v ) { setMeta( '_bpb_type', v ); },
					__nextHasNoMarginBottom: true
				} ),
				'member-profile' === type ? el( SelectControl, {
					label: __( 'Preview with', 'stax-buddy-builder' ),
					help: __( 'The member whose profile the preview shows.', 'stax-buddy-builder' ),
					value: String( data.meta._bpb_preview_user || 0 ),
					options: options( data.users, config.sampleMember ? __( 'Sample member', 'stax-buddy-builder' ) : __( 'Me (current user)', 'stax-buddy-builder' ) ),
					onChange: function ( v ) { setMeta( '_bpb_preview_user', parseInt( v, 10 ) || 0 ); },
					__nextHasNoMarginBottom: true
				} ) : null,
				'group-profile' === type ? el( SelectControl, {
					label: __( 'Preview with', 'stax-buddy-builder' ),
					help: __( 'The group the preview shows.', 'stax-buddy-builder' ),
					value: String( data.meta._bpb_preview_group || 0 ),
					options: options( groupsState[ 0 ], __( 'Newest group', 'stax-buddy-builder' ) ),
					onChange: function ( v ) { setMeta( '_bpb_preview_group', parseInt( v, 10 ) || 0 ); },
					__nextHasNoMarginBottom: true
				} ) : null,
				viewers( type ) ? el( SelectControl, {
					label: __( 'Seen by', 'stax-buddy-builder' ),
					help: __( 'Buttons, tabs and private fields show as this visitor would see them. Nothing is saved on the profile.', 'stax-buddy-builder' ),
					value: data.meta._bpb_preview_viewer || '',
					options: viewers( type ),
					onChange: function ( v ) { setMeta( '_bpb_preview_viewer', v ); },
					__nextHasNoMarginBottom: true
				} ) : null
			);
		}
	} );
}( window.wp, window.bpbLayout || { types: [] } ) );
