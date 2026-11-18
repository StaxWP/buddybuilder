/**
 * BuddyBuilder layout panel (block editor, bpb_template only): page, preview member (searchable) or group, seen by.
 * The element kit's editor sends the unsaved choices with every preview request (editor_query_args).
 * Element list per page (review 3.5, U3): the BuddyBuilder inserter category lists the elements made for the page
 * first, then the ones for any page; elements made for other pages leave the inserter (layouts that already use them
 * keep them). It follows the Page setting.
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

	// Block name => whether the inserter offered it before the page filter (deprecated elements stay out).
	var offered = {};
	var arranged = null;

	/**
	 * Re-register the BuddyBuilder blocks in page order: the inserter lists a category's blocks in registration
	 * order. Runs before the editor starts (the page saved on the layout) and when the Page setting changes.
	 *
	 * @param {string} type Page type.
	 */
	function arrange( type ) {
		var elements = config.elements || {};
		var names = Object.keys( elements ).filter( function ( name ) {
			return !! wp.blocks.getBlockType( name );
		} );
		var fits = function ( name ) {
			return elements[ name ].indexOf( type ) !== -1;
		};
		var any = function ( name ) {
			return ! elements[ name ].length;
		};

		if ( ! type || type === arranged || ! names.length ) {
			return;
		}

		arranged = type;

		// Made for this page only first (headers, about, content), then the ones shared with other pages.
		names.filter( fits ).sort( function ( a, b ) {
			return elements[ a ].length - elements[ b ].length;
		} ).concat( names.filter( any ), names.filter( function ( name ) {
			return ! fits( name ) && ! any( name );
		} ) ).forEach( function ( name ) {
			var settings = wp.blocks.unregisterBlockType( name );

			if ( ! settings ) {
				return;
			}

			if ( ! offered.hasOwnProperty( name ) ) {
				offered[ name ] = ! settings.supports || false !== settings.supports.inserter;
			}

			settings.supports = Object.assign( {}, settings.supports, { inserter: offered[ name ] && ( fits( name ) || any( name ) ) } );
			wp.blocks.registerBlockType( name, settings );
		} );
	}

	arrange( config.type || 'member-profile' );

	// Opened from Community pages ("Card: ..., Change"): select the list whose Card setting changes the cards. Polls
	// until the editor has its blocks and keeps the selection (the editor clears it while it starts).
	if ( config.select && config.select.length ) {
		var find = function ( blocks ) {
			for ( var i = 0; i < blocks.length; i++ ) {
				if ( 0 === blocks[ i ].name.indexOf( 'buddybuilder/' ) && config.select.indexOf( blocks[ i ].name.slice( 13 ) ) !== -1 ) {
					return blocks[ i ];
				}
				var inner = find( blocks[ i ].innerBlocks || [] );
				if ( inner ) {
					return inner;
				}
			}
			return null;
		};
		var tries = 0;
		var kept = 0;
		var poll = function () {
			var editor = wp.data.select( 'core/block-editor' );
			var block = find( editor.getBlocks() );

			if ( block && editor.getSelectedBlockClientId() === block.clientId ) {
				kept++;
			} else if ( block ) {
				wp.data.dispatch( 'core/block-editor' ).selectBlock( block.clientId );
				if ( wp.data.dispatch( 'core/edit-post' ) && wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar ) {
					wp.data.dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/block' );
				}
			}

			if ( kept < 3 && ++tries < 40 ) {
				setTimeout( poll, 300 );
			}
		};
		setTimeout( poll, 300 );
	}

	function options( list, emptyLabel ) {
		return [ { value: '0', label: emptyLabel } ].concat( ( list || [] ).map( function ( item ) {
			return { value: String( item.id ), label: item.name || ( item.title && item.title.rendered ) || ( '#' + item.id ) };
		} ) );
	}

	// Pages previewed with a member (profile, member card, activity item) or with a group (group page, group card).
	function usesMember( type ) {
		return [ 'member-profile', 'members-directory-item', 'sitewide-activity-item' ].indexOf( type ) !== -1;
	}

	function usesGroup( type ) {
		return [ 'group-profile', 'groups-directory-item' ].indexOf( type ) !== -1;
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
			// Member search (REST), so large communities can pick any member, not only the first 50.
			var searchState = wp.element.useState( '' );
			var data = useSelect( function ( select ) {
				var editor = select( 'core/editor' );
				var core = select( 'core' );
				return {
					postType: editor.getCurrentPostType(),
					meta: editor.getEditedPostAttribute( 'meta' ) || {},
					users: core.getUsers( searchState[ 0 ] ? { per_page: 20, context: 'view', search: searchState[ 0 ] } : { per_page: 50, context: 'view' } ) || []
				};
			}, [ searchState[ 0 ] ] );
			var groupsState = wp.element.useState( [] );
			var editPost = useDispatch( 'core/editor' ).editPost;
			var type = data.meta._bpb_type || 'member-profile';

			wp.element.useEffect( function () {
				if ( ! usesGroup( type ) ) {
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
					label: __( 'Page', 'stax-buddy-builder' ),
					value: type,
					options: config.types,
					onChange: function ( v ) { setMeta( '_bpb_type', v ); },
					__nextHasNoMarginBottom: true
				} ),
				usesMember( type ) ? el( wp.components.ComboboxControl, {
					label: __( 'Preview with', 'stax-buddy-builder' ),
					help: __( 'The member the preview shows. Type to search all members.', 'stax-buddy-builder' ),
					value: String( data.meta._bpb_preview_user || 0 ),
					options: options( data.users, config.sampleMember ? __( 'Sample member', 'stax-buddy-builder' ) : __( 'Me (current user)', 'stax-buddy-builder' ) ),
					onFilterValueChange: function ( v ) { searchState[ 1 ]( v || '' ); },
					onChange: function ( v ) { setMeta( '_bpb_preview_user', parseInt( v, 10 ) || 0 ); },
					__nextHasNoMarginBottom: true
				} ) : null,
				usesGroup( type ) ? el( SelectControl, {
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
