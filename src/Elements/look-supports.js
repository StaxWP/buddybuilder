/**
 * Block editor of a BuddyBuilder layout: block settings that do nothing where a block sits are hidden, so owners do
 * not see dead controls. Inside a style (a group with the bpb-look class) the style's CSS draws the elements, so the
 * settings it overrides do nothing ("lookIgnores" in element.json); inside a Member header split into parts (a group
 * with the bpb-mh--parts class) the parts print the header's markup and their wrapper takes no box ("splitIgnores").
 * A block that already has such a value keeps that control until it is cleared; elsewhere every control stays.
 * Nothing changes in the saved content: the block supports stay registered, so saved values still validate and
 * render. Data: window.bpbLookSupports ({ look, split }: block name => supports). Plain ES5 + wp globals.
 */
( function ( wp, map ) {
	if ( ! wp || ! wp.hooks || ! wp.data || ! map ) {
		return;
	}

	// Where: the class of the group a block sits in, and the supports ignored there.
	var PLACES = [
		{ pattern: /(^|\s)bpb-look(\s|$)/, blocks: map.look || {} },
		{ pattern: /(^|\s)bpb-mh--parts(\s|$)/, blocks: map.split || {} },
	];

	var get = function ( obj, path ) {
		return path.split( '.' ).reduce( function ( o, key ) {
			return o && 'object' === typeof o ? o[ key ] : undefined;
		}, obj );
	};

	// Each support: the editor settings that show its control, and whether a block has a value for it.
	var SUPPORTS = {
		'color.background': {
			paths: [ 'color.background' ],
			set: function ( a ) {
				return a.backgroundColor || a.gradient || get( a, 'style.color.background' ) || get( a, 'style.color.gradient' );
			},
		},
		'color.text': {
			paths: [ 'color.text' ],
			set: function ( a ) {
				return a.textColor || get( a, 'style.color.text' );
			},
		},
		'color.link': {
			paths: [ 'color.link' ],
			set: function ( a ) {
				return get( a, 'style.elements.link.color.text' );
			},
		},
		'typography.fontSize': {
			paths: [ 'typography.customFontSize', 'typography.defaultFontSizes', 'typography.fontSizes.custom', 'typography.fontSizes.default', 'typography.fontSizes.theme' ],
			set: function ( a ) {
				return a.fontSize || get( a, 'style.typography.fontSize' );
			},
		},
		'typography.lineHeight': {
			paths: [ 'typography.lineHeight' ],
			set: function ( a ) {
				return get( a, 'style.typography.lineHeight' );
			},
		},
		'typography.textAlign': {
			paths: [ 'typography.textAlign' ],
			set: function ( a ) {
				return get( a, 'style.typography.textAlign' );
			},
		},
		border: {
			paths: [ 'border.color', 'border.radius', 'border.style', 'border.width' ],
			set: function ( a ) {
				var border = get( a, 'style.border' );

				return a.borderColor || ( border && Object.keys( border ).length );
			},
		},
		'spacing.padding': {
			paths: [ 'spacing.padding' ],
			set: function ( a ) {
				return get( a, 'style.spacing.padding' );
			},
		},
		'spacing.margin': {
			paths: [ 'spacing.margin' ],
			set: function ( a ) {
				return get( a, 'style.spacing.margin' );
			},
		},
		'dimensions.minHeight': {
			paths: [ 'dimensions.minHeight' ],
			set: function ( a ) {
				return get( a, 'style.dimensions.minHeight' );
			},
		},
	};

	// Editor setting => support.
	var owner = {};

	Object.keys( SUPPORTS ).forEach( function ( support ) {
		SUPPORTS[ support ].paths.forEach( function ( path ) {
			owner[ path ] = support;
		} );
	} );

	/**
	 * Whether a block sits in a group whose class matches.
	 *
	 * @param {Object} editor   core/block-editor selectors.
	 * @param {string} clientId Block.
	 * @param {RegExp} pattern  Class pattern.
	 * @return {boolean} Inside.
	 */
	function inside( editor, clientId, pattern ) {
		return editor.getBlockParents( clientId ).some( function ( id ) {
			var attrs = editor.getBlockAttributes( id );

			return attrs && pattern.test( attrs.className || '' );
		} );
	}

	wp.hooks.addFilter( 'blockEditor.useSetting.before', 'buddybuilder/look-supports', function ( value, path, clientId, blockName ) {
		var support = owner[ path ];

		if ( undefined !== value || ! clientId || ! support ) {
			return value;
		}

		var places = PLACES.filter( function ( place ) {
			return place.blocks[ blockName ] && -1 !== place.blocks[ blockName ].indexOf( support );
		} );

		if ( ! places.length ) {
			return value;
		}

		var editor = wp.data.select( 'core/block-editor' );

		if ( SUPPORTS[ support ].set( editor.getBlockAttributes( clientId ) || {} ) ) {
			return value;
		}

		return places.some( function ( place ) {
			return inside( editor, clientId, place.pattern );
		} ) ? ( -1 !== path.indexOf( '.fontSizes.' ) ? [] : false ) : value;
	} );
}( window.wp, window.bpbLookSupports ) );
