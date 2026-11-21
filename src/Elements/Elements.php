<?php
/**
 * BuddyBuilder elements: one element.json + render.php per element (src/Elements/<name>/), shared by the blocks
 * (registered by the element kit) and the 1.x Elementor widgets (thin adapters, see Widgets\Base).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Elements;

use Buddy_Builder\Features;
use Buddy_Builder\Vendor\ElementKit\Context;
use Buddy_Builder\Vendor\ElementKit\Element;
use Buddy_Builder\Vendor\ElementKit\Kit;
use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\PreviewContext;

defined( 'ABSPATH' ) || die();

/**
 * Element kit instance and BuddyBuilder's context rules.
 */
final class Elements {

	/**
	 * Kit instance.
	 *
	 * @var Kit|null
	 */
	private static $kit = null;

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		require_once __DIR__ . '/helpers.php';
		require_once __DIR__ . '/look-helpers.php';

		// Built on init (translated labels); the kit hooks its own init callback at priority 10.
		add_action(
			'init',
			static function () {
				Elements::kit()->register();
			},
			1
		);

		add_filter( 'allowed_block_types_all', [ __CLASS__, 'allowed_blocks' ], 10, 2 );
		add_filter( 'buddy_builder_kit_render', [ __CLASS__, 'preview_wrapper' ], 10, 4 );
		add_filter( 'render_block_buddybuilder/directory-filters', [ __CLASS__, 'filters_order_class' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'editor_assets' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'hide_from_inserter' ] );
		add_action( 'elementor/widgets/register', [ __CLASS__, 'elementor_widgets' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'replace_assets_blocks' ] );
		add_filter( 'buddy_builder_kit_elements', [ __CLASS__, 'card_options' ], 20, 2 );
		add_action( 'elementor/preview/enqueue_scripts', [ __CLASS__, 'replace_assets_elementor' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'replace_assets_elementor' ] );
	}

	/**
	 * Elementor widgets for the 2.0 elements that have no 1.x widget (member header, group header, ...): the kit's
	 * generic adapter (controls from element.json, markup from render.php, BuddyBuilder's KitWidget), named
	 * "bpb-el-<element>".
	 *
	 * @param \Elementor\Widgets_Manager $manager Widgets manager.
	 */
	public static function elementor_widgets( $manager ) {
		if ( ! class_exists( '\\Elementor\\Widget_Base' ) || ! class_exists( '\\Buddy_Builder\\Vendor\\ElementKit\\ElementorWidget' ) ) {
			return;
		}

		\Buddy_Builder\Vendor\ElementKit\ElementorWidget::$kit = self::kit();

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			if ( ! empty( $schema['legacy']['elementor'] ) || ( isset( $schema['elementor'] ) && false === $schema['elementor'] ) ) {
				continue;
			}

			$manager->register( new \Buddy_Builder\Integrations\Elementor\KitWidget( [], [ 'kit_element' => $element->name() ] ) );
		}
	}

	/**
	 * The element kit (BuddyBuilder's Strauss-prefixed copy of staxwp/element-kit in vendor-prefixed/, namespace Buddy_Builder\Vendor\ElementKit).
	 *
	 * @return Kit
	 */
	public static function kit() {
		if ( null === self::$kit ) {
			self::$kit = new Kit(
				[
					'namespace'         => 'buddybuilder',
					'prefix'            => 'buddy_builder_kit',
					'css_prefix'        => 'bpb',
					'text_domain'       => 'stax-buddy-builder',
					'dir'               => __DIR__,
					'url'               => BPB_BASE_URL . 'src/Elements',
					'version'           => BPB_VERSION,
					'js_global'         => 'buddyBuilderKit',
					'block_category'    => 'buddybuilder',
					'elementor'         => false, // The 1.x widget classes are the Elementor adapters; new elements: elementor_widgets().
					'elementor_prefix'  => 'bpb-el-',
					'elementor_category' => [ 'buddy-builder-elements', 'BuddyBuilder' ],
					'elementor_wrap'    => 'widget',
					'labels'            => [
						'basic'    => __( 'Settings', 'stax-buddy-builder' ),
						'advanced' => __( 'More settings', 'stax-buddy-builder' ), // Not "Advanced": core already has an Advanced panel.
						'empty'    => __( 'Nothing to show for this preview.', 'stax-buddy-builder' ),
						/* translators: %s: the choices, separated by commas */
						'choices'  => __( 'Separated by commas, in this order. On this site: %s', 'stax-buddy-builder' ),
					],
					'editor_query_args' => [
						'bpb_type'          => PostType::META_TYPE,
						'bpb_preview_user'  => PostType::META_USER,
						'bpb_preview_group' => PostType::META_GROUP,
						'bpb_preview_viewer' => PostType::META_VIEWER,
					],
					'context'           => [ __CLASS__, 'context' ],
					'empty_preview'     => [ __CLASS__, 'empty_preview' ],
				]
			);
			\Buddy_Builder\Templates\ProfileFields::add_sources( self::$kit );
		}

		return self::$kit;
	}

	/**
	 * One element.
	 *
	 * @param string $name Element name.
	 * @return Element|null
	 */
	public static function get( $name ) {
		return self::kit()->get( $name );
	}

	/**
	 * Legacy Elementor widget name => element name (from "legacy" in element.json).
	 *
	 * @return array
	 */
	public static function legacy_map() {
		$map = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			foreach ( isset( $schema['legacy']['elementor'] ) ? (array) $schema['legacy']['elementor'] : [] as $widget ) {
				$map[ $widget ] = $element->name();
			}
		}

		return $map;
	}

	/**
	 * Render context: the editor preview context (preview member or group), the layout being rendered, and the raw
	 * Elementor settings when an Elementor widget renders (1.x hooks receive them unchanged).
	 *
	 * @param Element $element Element.
	 * @param array   $attrs   Attributes.
	 * @param mixed   $source  WP_Block, Elementor widget or null.
	 * @param Context $base    Kit context (preview, post_id).
	 * @return Context
	 */
	public static function context( Element $element, array $attrs, $source, Context $base ) {
		$current = Dispatcher::current_context();
		$preview = PreviewContext::ctx();

		// A loop item (a card in a list) renders with its own page type and its own member, group or activity, also
		// inside the editor of the page around it: there the item wins over the editor's preview context.
		$item = isset( $current['type'] ) && '-item' === substr( (string) $current['type'], -5 );
		$data = $item ? array_merge( $preview, $current ) : array_merge( $current, $preview );

		if ( $item && isset( $preview['type'] ) && $preview['type'] !== $current['type'] ) {
			$data['nested_item'] = true;
		}

		if ( $base->is_preview() ) {
			$data['preview'] = true;
		}

		if ( $source instanceof \Elementor\Widget_Base ) {
			$data['elementor_settings'] = $source->get_settings_for_display();
		}

		return $base->with( $data );
	}

	/**
	 * Deprecated elements (element.json "deprecated": { use, since }): block name => the element to use instead.
	 * They keep rendering exactly as before; they only leave the inserter and the Elementor panel, and editors show
	 * a short note where they are already used. List: docs/deprecated-elements.md.
	 *
	 * @return array block name => replacement element name
	 */
	public static function deprecated() {
		$out = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			if ( ! empty( $schema['deprecated']['use'] ) ) {
				$out[ $element->block_name() ] = (string) $schema['deprecated']['use'];
			}
		}

		return $out;
	}

	/**
	 * The "card" option of list elements lists the card styles available on the site (Classic, plus the ones
	 * BuddyBuilder Pro adds through buddy_builder/card_style_labels).
	 *
	 * @param Element[] $elements Elements by name.
	 * @param Kit       $kit      Kit.
	 * @return Element[]
	 */
	public static function card_options( $elements, $kit ) {
		$labels = \Buddy_Builder\Templates\Cards::labels();

		foreach ( $elements as $name => $element ) {
			$schema = $element->schema();
			$loops  = [
				'members-loop'  => 'members',
				'groups-loop'   => 'groups',
				'activity-loop' => 'activity',
			];
			$object = isset( $loops[ $name ] ) ? $loops[ $name ] : \Buddy_Builder\Templates\Cards::object_of( $name );

			if ( ! isset( $schema['attributes']['card']['options'] ) || '' === $object || empty( $labels[ $object ] ) ) {
				continue;
			}

			$schema['attributes']['card']['options'] = array_merge( $schema['attributes']['card']['options'], (array) $labels[ $object ] );
			$elements[ $name ]                       = new Element( $kit, $element->dir(), $schema );
		}

		return $elements;
	}

	/**
	 * The element an element can be replaced with: "deprecated" (the old element is deprecated: a note in editors)
	 * or "partOf" (a part of a designed element, such as the member header parts: a first-class element, the
	 * replacement is a quiet optional action). Both take { use, map }.
	 *
	 * @param array $schema Element schema.
	 * @return array [ key ('deprecated' or 'partOf'), settings ] or [] when the element has no replacement.
	 */
	private static function replacement_of( array $schema ) {
		foreach ( [ 'deprecated', 'partOf' ] as $key ) {
			if ( ! empty( $schema[ $key ]['use'] ) ) {
				return [ $key, (array) $schema[ $key ] ];
			}
		}

		return [];
	}

	/**
	 * "Replace with the new element" data: per deprecated element or header part, its replacement as a block and as
	 * an Elementor widget. Settings are not carried over (the new element starts from its defaults); map in
	 * element.json can name old => new attributes to carry.
	 *
	 * @return array element name => [ use, title, block, from_block, widget, from_widgets, map, quiet ]
	 */
	public static function replacements() {
		$widgets = array_flip( self::legacy_map() );
		$out     = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema      = $element->schema();
			$replacement = self::replacement_of( $schema );
			$use         = $replacement ? self::get( $replacement[1]['use'] ) : null;

			if ( ! $use ) {
				continue;
			}

			$new_schema                 = $use->schema();
			$out[ $element->name() ] = [
				'use'          => $use->name(),
				'title'        => $use->title(),
				'block'        => $use->block_name(),
				'from_block'   => $element->block_name(),
				'widget'       => isset( $widgets[ $use->name() ] ) ? $widgets[ $use->name() ] : ( empty( $new_schema['legacy']['elementor'] ) ? 'bpb-el-' . $use->name() : '' ),
				'from_widgets' => isset( $schema['legacy']['elementor'] ) ? array_values( (array) $schema['legacy']['elementor'] ) : [],
				'map'          => isset( $replacement[1]['map'] ) ? (array) $replacement[1]['map'] : (object) [],
				'quiet'        => 'partOf' === $replacement[0],
			];
		}

		return $out;
	}

	/**
	 * Whether an element is a member or group header part (element.json category buddybuilder-header-parts): in
	 * Elementor it sits in the "BuddyBuilder Header parts" category.
	 *
	 * @param Element|null $element Element.
	 * @return bool
	 */
	public static function is_header_part( $element ) {
		$schema = $element ? $element->schema() : [];

		return isset( $schema['category'] ) && 'buddybuilder-header-parts' === $schema['category'];
	}

	/**
	 * The element a header part belongs to ("partOf" in element.json), or null.
	 *
	 * @param string $name Element name.
	 * @return Element|null
	 */
	public static function part_of( $name ) {
		$element = self::get( $name );
		$schema  = $element ? $element->schema() : [];

		return ! empty( $schema['partOf']['use'] ) ? self::get( $schema['partOf']['use'] ) : null;
	}

	/**
	 * Block editor: the "Replace with the new element" button on deprecated blocks.
	 */
	public static function replace_assets_blocks() {
		self::replace_script( 'bpb-replace-blocks', 'replace-deprecated.js', [ 'wp-hooks', 'wp-compose', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-blocks', 'wp-data', 'wp-i18n' ] );
	}

	/**
	 * Elementor editor: the button in the deprecated note (preview frame) and the header parts' Replace button (editor
	 * frame).
	 */
	public static function replace_assets_elementor() {
		self::replace_script( 'bpb-replace-elementor', 'replace-deprecated-elementor.js', [] );
	}

	/**
	 * Enqueue a replace script with the replacement data and its strings.
	 *
	 * @param string $handle Handle.
	 * @param string $file   File in src/Elements.
	 * @param array  $deps   Dependencies.
	 */
	private static function replace_script( $handle, $file, array $deps ) {
		wp_enqueue_script( $handle, BPB_BASE_URL . 'src/Elements/' . $file, $deps, BPB_VERSION, true );
		wp_add_inline_script(
			$handle,
			'window.bpbReplace = ' . wp_json_encode(
				[
					'elements' => self::replacements(),
					/* translators: %s: name of the new element */
					'button'   => __( 'Replace with %s', 'stax-buddy-builder' ),
					/* translators: 1: number of parts, 2: name of the new element */
					'group'    => __( 'Replace these %1$d parts with one %2$s', 'stax-buddy-builder' ),
					'help'     => __( 'Deprecated: it keeps working. The new element starts from its default settings; Undo brings the old one back.', 'stax-buddy-builder' ),
					/* translators: %s: name of the designed element, e.g. Member header */
					'quiet'    => __( 'Optional. This part keeps working as it is. One %s can show the header parts of this column in a style\'s design; it starts from its default settings, and Undo brings the parts back.', 'stax-buddy-builder' ),
				]
			) . ';',
			'before'
		);
	}

	/**
	 * Note shown in editors on a deprecated element ('' when it is not deprecated).
	 *
	 * @param string $name Element name.
	 * @return string HTML.
	 */
	public static function deprecated_note( $name ) {
		$element = self::get( $name );
		$schema  = $element ? $element->schema() : [];

		if ( empty( $schema['deprecated']['use'] ) ) {
			return '';
		}

		$use = self::get( $schema['deprecated']['use'] );

		/* translators: %s: name of the element to use instead */
		// Inline style: the Elementor canvas does not load BuddyBuilder's editor styles.
		$button = '';
		if ( $use && function_exists( 'bpb_is_elementor_editor' ) && bpb_is_elementor_editor() ) {
			/* translators: %s: name of the element to use instead */
			$button = ' <button type="button" class="bpb-el-replace" data-bpb-replace="' . esc_attr( $name ) . '" style="margin-left:6px;padding:1px 8px;border:1px solid #c3a24b;border-radius:3px;background:#fff;color:#50575e;font:inherit;cursor:pointer">' . esc_html( sprintf( __( 'Replace with %s', 'stax-buddy-builder' ), $use->title() ) ) . '</button>';
		}

		return '<p class="bpb-el-deprecated" style="margin:0 0 4px;padding:2px 6px;border-left:3px solid #dba617;background:#fcf9e8;color:#50575e;font:11px/1.4 -apple-system,BlinkMacSystemFont,sans-serif;letter-spacing:0;text-transform:none;text-align:left">' . esc_html( sprintf( __( 'Deprecated, use %s.', 'stax-buddy-builder' ), $use ? $use->title() : $schema['deprecated']['use'] ) ) . $button . '</p>';
	}

	/**
	 * Editor previews: BuddyPress styles are scoped to #buddypress / .buddypress-wrap, which the BuddyPress page
	 * provides on the front end. Previews get the same wrapper (preview markup only; the front end is unchanged).
	 *
	 * @param string  $html    Markup.
	 * @param Element $element Element.
	 * @param array   $attrs   Attributes.
	 * @param Context $ctx     Context.
	 * @return string
	 */
	public static function preview_wrapper( $html, $element, $attrs, $ctx ) {
		if ( ! $ctx instanceof Context || ! $ctx->is_preview() || ! \Buddy_Builder\Vendor\ElementKit\Kit::is_block_preview() ) {
			return $html;
		}

		return self::deprecated_note( $element->name() ) . '<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav">' . $html . '</div>';
	}

	/**
	 * Directory filters block: "Show and order" as a wrapper class (the Elementor widget does it with its own
	 * control selectors; the inner markup stays the same in both builders).
	 *
	 * @param string $html  Block markup.
	 * @param array  $block Parsed block.
	 * @return string
	 */
	public static function filters_order_class( $html, $block ) {
		$order = isset( $block['attrs']['content_display'] ) ? (int) $block['attrs']['content_display'] : 1;

		if ( '' === $html || $order < 1 || $order > 15 || ! class_exists( '\\WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$tags = new \WP_HTML_Tag_Processor( $html );

		if ( $tags->next_tag() ) {
			$tags->add_class( 'bpb-filters-order-' . $order );

			return $tags->get_updated_html();
		}

		return $html;
	}

	/**
	 * Editor placeholder when an element printed nothing.
	 *
	 * @param Element $element Element.
	 * @param Context $ctx     Context.
	 * @return string
	 */
	public static function empty_preview( Element $element, Context $ctx ) {
		// A card inside the list of the page being edited shows what the live page shows: nothing (an own card has no
		// friendship button, a member without a cover has none). Placeholders stay in the card's own editor.
		if ( $ctx->get( 'nested_item' ) ) {
			return '';
		}

		$schema = $element->schema();
		$types  = isset( $schema['templateTypes'] ) ? (array) $schema['templateTypes'] : [];
		$type   = (string) $ctx->get( 'type', '' );

		if ( $types && $type && ! in_array( $type, $types, true ) ) {
			$text = __( 'This block is made for another page type. Change the template type of this layout, or remove the block.', 'stax-buddy-builder' );
		} elseif ( $ctx->get( 'user_id' ) || $ctx->get( 'group_id' ) ) {
			$text = __( 'Nothing to show for this preview and viewer.', 'stax-buddy-builder' );
		} elseif ( in_array( 'member-profile', $types, true ) ) {
			$text = __( 'Pick a preview member in the BuddyBuilder panel (or create the sample member).', 'stax-buddy-builder' );
		} elseif ( in_array( 'group-profile', $types, true ) ) {
			$text = __( 'Pick a preview group in the BuddyBuilder panel (or create the sample member and groups).', 'stax-buddy-builder' );
		} else {
			$text = __( 'Nothing to show for this preview.', 'stax-buddy-builder' );
		}

		return '<p class="bpb-el-empty">' . esc_html( $element->title() . ': ' . $text ) . '</p>';
	}

	/**
	 * Page types of every element ("templateTypes" in element.json), in kit order: block name => types ([] when the
	 * element fits any page). The layout editor lists the elements of the page first (layout-panel.js).
	 *
	 * @return array
	 */
	public static function page_types() {
		$out = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			$out[ $element->block_name() ] = isset( $schema['templateTypes'] ) ? array_values( (array) $schema['templateTypes'] ) : [];
		}

		return $out;
	}

	/**
	 * Block supports that do nothing where a block sits, per block (element.json): inside a style, the ones the style's
	 * CSS overrides ("lookIgnores"); inside a Member header split into parts, the ones a part's wrapper cannot show, as
	 * it takes no box there ("splitIgnores"). The block editor hides their controls there (src/Elements/look-supports.js).
	 * The supports stay registered, so saved values keep validating and rendering.
	 *
	 * @return array look and split: block name => supports (color.background, color.text, color.link,
	 *               typography.fontSize, typography.lineHeight, typography.textAlign, border, spacing.padding,
	 *               spacing.margin, dimensions.minHeight).
	 */
	public static function ignored_supports() {
		$out = [
			'look'  => [],
			'split' => [],
		];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			foreach ( [ 'look' => 'lookIgnores', 'split' => 'splitIgnores' ] as $place => $key ) {
				if ( ! empty( $schema[ $key ] ) && is_array( $schema[ $key ] ) ) {
					$out[ $place ][ $element->block_name() ] = array_values( $schema[ $key ] );
				}
			}
		}

		return $out;
	}

	/**
	 * Blocks hidden from the inserter outside block layouts: the page blocks (they need a BuddyPress page), and every
	 * BuddyBuilder block while block layouts are disabled.
	 *
	 * @return string[] Block names.
	 */
	public static function inserter_hidden_blocks() {
		$hidden = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			if ( ! Features::blocks_enabled() || ! empty( $schema['templateTypes'] ) ) {
				$hidden[] = $element->block_name();
			}
		}

		return $hidden;
	}

	/**
	 * Inserter: an explicit allow list (set by the site or another plugin) loses the hidden BuddyBuilder blocks outside
	 * block layouts. `true` stays `true`: turning it into a list of the server-registered blocks would drop every block
	 * registered only in JavaScript, site-wide (BB-4). With `true`, editor_assets() hides the blocks instead.
	 *
	 * @param bool|string[]            $allowed Allowed block types.
	 * @param \WP_Block_Editor_Context $context Editor context.
	 * @return bool|string[]
	 */
	public static function allowed_blocks( $allowed, $context ) {
		if ( ! is_array( $allowed ) || self::is_layout_editor( $context ) ) {
			return $allowed;
		}

		return array_values( array_diff( $allowed, self::inserter_hidden_blocks() ) );
	}

	/**
	 * Whether a block editor context edits a block layout.
	 *
	 * @param \WP_Block_Editor_Context|null $context Editor context.
	 * @return bool
	 */
	private static function is_layout_editor( $context ) {
		return isset( $context->post ) && $context->post && PostType::POST_TYPE === $context->post->post_type;
	}

	/**
	 * Outside block layouts: hide the page blocks from the inserter (supports.inserter = false) before the kit
	 * editor script registers them. Saved posts that already contain them still load.
	 */
	public static function hide_from_inserter() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$layout = $screen && PostType::POST_TYPE === $screen->post_type;
		// Deprecated elements leave the inserter everywhere; outside block layouts all page blocks do.
		$hidden = array_values( array_unique( array_merge( $layout ? [] : self::inserter_hidden_blocks(), array_keys( self::deprecated() ) ) ) );

		if ( ! $hidden ) {
			return;
		}

		wp_add_inline_script(
			self::kit()->editor_handle(),
			'( function ( hidden ) { wp.hooks.addFilter( "blocks.registerBlockType", "buddybuilder/inserter", function ( settings, name ) { if ( hidden.indexOf( name ) === -1 ) { return settings; } return Object.assign( {}, settings, { supports: Object.assign( {}, settings.supports, { inserter: false } ) } ); } ); } )( ' . wp_json_encode( $hidden ) . ' );',
			'before'
		);
	}

	/**
	 * Block editor of a layout: BuddyPress styles in the canvas and the "BuddyBuilder" document panel.
	 */
	public static function editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		// Inside a style or a split Member header, the block settings that do nothing there are hidden.
		wp_enqueue_script( 'bpb-look-supports', BPB_BASE_URL . 'src/Elements/look-supports.js', [ 'wp-hooks', 'wp-data', 'wp-block-editor' ], BPB_VERSION, true );
		wp_add_inline_script( 'bpb-look-supports', 'window.bpbLookSupports = ' . wp_json_encode( array_map( static function ( $blocks ) { return (object) $blocks; }, self::ignored_supports() ) ) . ';', 'before' );

		// After the kit editor script: the element list per page re-registers the blocks it registered.
		wp_enqueue_script(
			'bpb-layout-panel',
			BPB_BASE_URL . 'src/Elements/layout-panel.js',
			[ 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-preferences', 'wp-dom-ready', 'wp-i18n', 'wp-api-fetch', 'wp-url', 'wp-blocks', self::kit()->editor_handle() ],
			BPB_VERSION,
			true
		);
		wp_set_script_translations( 'bpb-layout-panel', 'stax-buddy-builder', BPB_BASE_PATH . 'languages' );

		$types = [];

		foreach ( \Buddy_Builder\Templates\Types::for_blocks() as $slug => $type ) {
			$types[] = [
				'value' => $slug,
				'label' => $type['name'],
			];
		}

		wp_add_inline_script(
			'bpb-layout-panel',
			'window.bpbLayout = ' . wp_json_encode(
				[
					'types'        => $types,
					'type'         => get_post() ? PostType::type_of( get_post()->ID ) : PostType::TEMPLATE_TYPE,
					'elements'     => self::page_types(),
					'sampleMember' => \Buddy_Builder\Templates\SampleMember::user_id(),
					'viewers'      => \Buddy_Builder\Templates\PreviewContext::viewer_options(),
					'canSimulate'  => \Buddy_Builder\Templates\PreviewContext::can_simulate_members(),
					// Community pages "Card: ..., Change": the list to select when the editor opens.
					'select'       => \Buddy_Builder\Templates\Cards::editor_selection(),
				]
			) . ';',
			'before'
		);
	}
}
