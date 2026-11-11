/* Synced from k-elements@bedf241 (src/Kit) by bin/sync-kit.sh, do not edit. */
/**
 * Element kit: generic block editor UI for every element (shared with BuddyBuilder).
 *
 * KIT RULES: plain ES5 and wp globals, no build step, no plugin-specific names.
 * The block metadata (title, attributes, supports) comes from the server registration;
 * this file adds edit() and save() and builds the inspector from the kit data. Each kit
 * copy pushes its data object to window.__elementKits (inline script printed before this file),
 * so two copies (K Elements, BuddyBuilder) never register the same block twice.
 */
( function ( wp, kits ) {
	var el = wp.element.createElement;
	var be = wp.blockEditor;
	var c = wp.components;
	var ServerSideRender = wp.serverSideRender;

	function control( def, attributes, setAttributes ) {
		var value = attributes[ def.key ];
		var set = function ( next ) {
			var update = {};
			update[ def.key ] = next;
			setAttributes( update );
		};
		var common = { key: def.key, label: def.label, help: def.help, __nextHasNoMarginBottom: true };

		switch ( def.control ) {
			case 'toggle':
				return el( c.ToggleControl, Object.assign( common, { checked: !! value, onChange: set } ) );
			case 'select':
				return el( c.SelectControl, Object.assign( common, { value: value, options: def.options || [], onChange: set, __next40pxDefaultSize: true } ) );
			case 'multiselect':
				return el(
					c.BaseControl,
					{ key: def.key, label: def.label, help: def.help, id: 'kit-' + def.key, __nextHasNoMarginBottom: true },
					( def.options || [] ).map( function ( option ) {
						var list = Array.isArray( value ) ? value : [];
						return el( c.CheckboxControl, {
							key: option.value,
							label: option.label,
							checked: list.indexOf( option.value ) !== -1,
							onChange: function ( on ) {
								var next = list.filter( function ( v ) {
									return v !== option.value;
								} );
								if ( on ) {
									// Keep the option order of the schema.
									next = ( def.options || [] ).map( function ( o ) {
										return o.value;
									} ).filter( function ( v ) {
										return v === option.value || next.indexOf( v ) !== -1;
									} );
								}
								set( next );
							},
							__nextHasNoMarginBottom: true,
						} );
					} )
				);
			case 'range':
				return el( c.RangeControl, Object.assign( common, { value: value, min: def.min, max: def.max, step: def.step || 1, onChange: set, __next40pxDefaultSize: true } ) );
			case 'number':
				return el( c.TextControl, Object.assign( common, {
					type: 'number',
					value: value === undefined || value === null ? '' : String( value ),
					min: def.min,
					max: def.max,
					step: def.step,
					onChange: function ( v ) {
						set( v === '' ? undefined : Number( v ) );
					},
					__next40pxDefaultSize: true,
				} ) );
			case 'textarea':
				return el( c.TextareaControl, Object.assign( common, { value: value || '', onChange: set } ) );
			default:
				return el( c.TextControl, Object.assign( common, { type: def.control === 'url' ? 'url' : 'text', value: value || '', onChange: set, __next40pxDefaultSize: true } ) );
		}
	}

	function panels( def, props, labels ) {
		var out = [];
		[ 'basic', 'advanced' ].forEach( function ( panel ) {
			var list = def.controls.filter( function ( ctl ) {
				return ctl.panel === panel;
			} );
			if ( list.length ) {
				out.push( el(
					c.PanelBody,
					{ key: panel, title: labels[ panel ], initialOpen: panel === 'basic' },
					list.map( function ( ctl ) {
						return control( ctl, props.attributes, props.setAttributes );
					} )
				) );
			}
		} );
		return out;
	}

	// post_id plus edited (unsaved) post meta as query args of the preview request (config editor_query_args).
	function useQueryArgs( map ) {
		var json = wp.data.useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var out = {};
			if ( ! editor || ! editor.getCurrentPostId ) {
				return '{}';
			}
			var meta = editor.getEditedPostAttribute( 'meta' ) || {};
			out.post_id = editor.getCurrentPostId();
			Object.keys( map || {} ).forEach( function ( arg ) {
				if ( meta[ map[ arg ] ] !== undefined ) {
					out[ arg ] = meta[ map[ arg ] ];
				}
			} );
			return JSON.stringify( out );
		}, [] );
		return wp.element.useMemo( function () {
			return JSON.parse( json );
		}, [ json ] );
	}

	function register( data ) {
		var labels = data.labels || {};

		( data.elements || [] ).forEach( function ( def ) {
			if ( wp.blocks.getBlockType( def.name ) ) {
				return;
			}
			wp.blocks.registerBlockType( def.name, {
				edit: function ( props ) {
					var args = useQueryArgs( data.queryArgs );
					var preview = def.preview === 'placeholder' ?
						el( c.Placeholder, { label: def.title } ) :
						el( c.Disabled, null, el( ServerSideRender, {
							block: def.name,
							attributes: props.attributes,
							urlQueryArgs: args,
							skipBlockSupportAttributes: true,
						} ) );

					return el(
						'div',
						be.useBlockProps(),
						el( be.InspectorControls, null, panels( def, props, labels ) ),
						preview
					);
				},
				save: function () {
					return null;
				},
			} );
		} );
	}

	kits.forEach( function ( data ) {
		if ( data && ! data.registered ) {
			data.registered = true;
			register( data );
		}
	} );
}( window.wp, window.__elementKits || [] ) );
