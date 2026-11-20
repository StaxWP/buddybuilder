/* Synced from k-elements@1cfbcd7 (src/Kit) by bin/sync-kit.sh, do not edit. */
/**
 * Element kit: generic block editor UI for every element (shared with BuddyBuilder).
 *
 * KIT RULES: plain JavaScript (no JSX, no imports) on wp globals, no build step, no plugin-specific names.
 * The block metadata (title, attributes, supports) comes from the server registration;
 * this file adds edit() and save() and builds the inspector from the kit data. Each kit
 * copy pushes its data object to window.__elementKits (inline script printed before this file),
 * so two copies (K Elements, BuddyBuilder) never register the same block twice.
 *
 * @param {Object} wp   WordPress globals.
 * @param {Array}  kits Kit data objects.
 */
( function ( wp, kits ) {
	const el = wp.element.createElement;
	const be = wp.blockEditor;
	const c = wp.components;
	const ServerSideRender = wp.serverSideRender;

	/**
	 * Options of a select, plus the saved values that are not among them when any value is allowed ("anyValue", for
	 * example a profile field another site has): they stay visible and can be unpicked.
	 *
	 * @param {Object}   def    Control definition.
	 * @param {string[]} values Saved values.
	 * @return {Object[]} Options.
	 */
	function withValues( def, values ) {
		const options = def.options || [];
		if ( ! def.anyValue ) {
			return options;
		}
		return options.concat(
			values
				.filter( function ( v ) {
					return ! options.some( function ( o ) {
						return o.value === v;
					} );
				} )
				.map( function ( v ) {
					return { value: v, label: v };
				} )
		);
	}

	function control( def, attributes, setAttributes ) {
		const value = attributes[ def.key ];
		const set = function ( next ) {
			const update = {};
			update[ def.key ] = next;
			setAttributes( update );
		};
		const common = {
			key: def.key,
			label: def.label,
			help: def.help,
			__nextHasNoMarginBottom: true,
		};

		switch ( def.control ) {
			case 'toggle':
				return el(
					c.ToggleControl,
					Object.assign( common, {
						checked: !! value,
						onChange: set,
					} )
				);
			case 'select':
				return el(
					c.SelectControl,
					Object.assign( common, {
						value,
						options: withValues( def, value ? [ value ] : [] ),
						onChange: set,
						__next40pxDefaultSize: true,
					} )
				);
			case 'multiselect':
			case 'checklist': {
				// checklist: a string attribute with the values separated by commas, in the order they were picked
				// (older kit copies show it as a text field). multiselect: an array in the option order.
				const text = 'checklist' === def.control;
				const list = text
					? String( value || '' )
							.split( ',' )
							.map( function ( v ) {
								return v.trim();
							} )
							.filter( Boolean )
					: Array.isArray( value )
					? value
					: [];
				return el(
					c.BaseControl,
					{
						key: def.key,
						label: def.label,
						help: def.help,
						id: 'kit-' + def.key,
						__nextHasNoMarginBottom: true,
					},
					withValues( def, list ).map( function ( option ) {
						return el( c.CheckboxControl, {
							key: option.value,
							label: option.label,
							checked: list.indexOf( option.value ) !== -1,
							onChange( on ) {
								let next = list.filter( function ( v ) {
									return v !== option.value;
								} );
								if ( on && text ) {
									next = next.concat( [ option.value ] );
								} else if ( on ) {
									// Keep the option order of the schema.
									next = ( def.options || [] )
										.map( function ( o ) {
											return o.value;
										} )
										.filter( function ( v ) {
											return (
												v === option.value ||
												next.indexOf( v ) !== -1
											);
										} );
								}
								set( text ? next.join( ', ' ) : next );
							},
							__nextHasNoMarginBottom: true,
						} );
					} )
				);
			}
			case 'range':
				return el(
					c.RangeControl,
					Object.assign( common, {
						value,
						min: def.min,
						max: def.max,
						step: def.step || 1,
						onChange: set,
						__next40pxDefaultSize: true,
					} )
				);
			case 'number':
				return el(
					c.TextControl,
					Object.assign( common, {
						type: 'number',
						value:
							value === undefined || value === null
								? ''
								: String( value ),
						min: def.min,
						max: def.max,
						step: def.step,
						onChange( v ) {
							set( v === '' ? undefined : Number( v ) );
						},
						__next40pxDefaultSize: true,
					} )
				);
			case 'textarea':
				return el(
					c.TextareaControl,
					Object.assign( common, {
						value: value || '',
						onChange: set,
					} )
				);
			default:
				return el(
					c.TextControl,
					Object.assign( common, {
						type: def.control === 'url' ? 'url' : 'text',
						value: value || '',
						onChange: set,
						__next40pxDefaultSize: true,
					} )
				);
		}
	}

	/**
	 * Whether a control hides for these attributes ("hideWhen": other attributes with these values, opt-in per
	 * attribute in element.json, for settings that do nothing then).
	 *
	 * @param {Object} ctl        Control definition.
	 * @param {Object} attributes Block attributes.
	 * @return {boolean} Hidden.
	 */
	function hidden( ctl, attributes ) {
		return Object.keys( ctl.hideWhen || {} ).some( function ( key ) {
			return attributes[ key ] === ctl.hideWhen[ key ];
		} );
	}

	function panels( def, props, labels ) {
		const out = [];
		[ 'basic', 'advanced' ].forEach( function ( panel ) {
			const list = def.controls.filter( function ( ctl ) {
				return ctl.panel === panel && ! hidden( ctl, props.attributes );
			} );
			if ( list.length ) {
				out.push(
					el(
						c.PanelBody,
						{
							key: panel,
							title: labels[ panel ],
							initialOpen: panel === 'basic',
						},
						list.map( function ( ctl ) {
							return control(
								ctl,
								props.attributes,
								props.setAttributes
							);
						} )
					)
				);
			}
		} );
		return out;
	}

	// post_id plus edited (unsaved) post meta as query args of the preview request (config editor_query_args).
	function useQueryArgs( map ) {
		const json = wp.data.useSelect( function ( select ) {
			const editor = select( 'core/editor' );
			const out = {};
			if ( ! editor || ! editor.getCurrentPostId ) {
				return '{}';
			}
			const meta = editor.getEditedPostAttribute( 'meta' ) || {};
			out.post_id = editor.getCurrentPostId();
			Object.keys( map || {} ).forEach( function ( arg ) {
				if ( meta[ map[ arg ] ] !== undefined ) {
					out[ arg ] = meta[ map[ arg ] ];
				}
			} );
			return JSON.stringify( out );
		}, [] );
		return wp.element.useMemo(
			function () {
				return JSON.parse( json );
			},
			[ json ]
		);
	}

	function register( data ) {
		const labels = data.labels || {};

		( data.elements || [] ).forEach( function ( def ) {
			if ( wp.blocks.getBlockType( def.name ) ) {
				return;
			}
			wp.blocks.registerBlockType( def.name, {
				edit: function Edit( props ) {
					const args = useQueryArgs( data.queryArgs );
					const preview =
						def.preview === 'placeholder'
							? el( c.Placeholder, { label: def.title } )
							: el(
									c.Disabled,
									null,
									el( ServerSideRender, {
										block: def.name,
										attributes: props.attributes,
										urlQueryArgs: args,
										skipBlockSupportAttributes: true,
									} )
								);

					return el(
						'div',
						be.useBlockProps(),
						el(
							be.InspectorControls,
							null,
							panels( def, props, labels )
						),
						preview
					);
				},
				save() {
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
} )( window.wp, window.__elementKits || [] );
