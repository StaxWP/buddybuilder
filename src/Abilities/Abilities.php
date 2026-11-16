<?php
/**
 * Abilities (U5, 2.0 part): read and preset abilities for the KLEO 7 setup wizard, agents and MCP.
 *
 * The abilities (read only): buddybuilder/list-template-types, list-elements, list-presets, get-layout and get-status,
 * exposed to MCP. Write abilities (apply a preset or style, assign, update a layout) are BuddyBuilder Pro's (filter
 * buddy_builder/abilities). Registered only when the Abilities API exists (WordPress 6.9+), so the WordPress 6.6
 * minimum is not affected. Layout editing abilities follow in 2.2.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Abilities;

use Buddy_Builder\Elements\Elements;
use Buddy_Builder\Presets\Presets;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * BuddyBuilder abilities.
 */
final class Abilities {

	const CATEGORY = 'buddybuilder';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'register_abilities' ] );
	}

	/**
	 * Ability category.
	 */
	public static function register_category() {
		wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => __( 'BuddyBuilder', 'stax-buddy-builder' ),
				'description' => __( 'BuddyPress page layouts: template types, elements, presets and assignments.', 'stax-buddy-builder' ),
			]
		);
	}

	/**
	 * Object schema.
	 *
	 * @param array $properties Properties.
	 * @param array $required   Required keys.
	 * @return array
	 */
	private static function object( array $properties = [], array $required = [] ) {
		$schema = [
			'type'                 => 'object',
			'additionalProperties' => false,
		];

		if ( $properties ) {
			$schema['properties'] = $properties;
		}

		if ( ! $required ) {
			$schema['default'] = [];
		}

		if ( $required ) {
			$schema['required'] = $required;
		}

		return $schema;
	}

	/**
	 * Ability definitions.
	 *
	 * @return array name => [ label, description, input, capability, readonly, idempotent, callback ]
	 */
	public static function definitions() {
		$type_prop = [
			'type'        => 'string',
			'description' => 'Template type slug, from buddybuilder/list-template-types (for example "member-profile").',
		];

		return [
			'buddybuilder/list-template-types' => [
				__( 'List BuddyBuilder template types', 'stax-buddy-builder' ),
				'Lists the BuddyPress page types BuddyBuilder can design (member profile, group profile, directories, activity), whether each supports block layouts, whether its BuddyPress component is active, and the layout assigned now.',
				self::object(),
				'edit_pages',
				true,
				true,
				[ __CLASS__, 'list_template_types' ],
			],
			'buddybuilder/list-elements'       => [
				__( 'List BuddyBuilder elements', 'stax-buddy-builder' ),
				'Lists the BuddyBuilder elements (blocks "buddybuilder/<name>"): title, description, the page types they belong to, and their attributes with types, defaults and options.',
				self::object( [ 'type' => $type_prop ] ),
				'edit_pages',
				true,
				true,
				[ __CLASS__, 'list_elements' ],
			],
			'buddybuilder/list-presets'        => [
				__( 'List BuddyBuilder presets', 'stax-buddy-builder' ),
				'Lists the ready-made layouts (presets) per page type. With BuddyBuilder Pro, buddybuilder/apply-preset drafts one.',
				self::object( [ 'type' => $type_prop ] ),
				'edit_pages',
				true,
				true,
				[ __CLASS__, 'list_presets' ],
			],
			'buddybuilder/get-layout'          => [
				__( 'Get a BuddyBuilder layout', 'stax-buddy-builder' ),
				'Returns the layout assigned to a page type: builder (blocks or elementor), post id, title and, for block layouts, the block markup.',
				self::object( [ 'type' => $type_prop ], [ 'type' ] ),
				'edit_pages',
				true,
				true,
				[ __CLASS__, 'get_layout' ],
			],
			'buddybuilder/get-status'          => [
				__( 'Get the BuddyBuilder status', 'stax-buddy-builder' ),
				'Read-only overview: the page types with their assigned layout, builder and style, whether block layouts are enabled, and whether BuddyBuilder Pro is active.',
				self::object(),
				'edit_pages',
				true,
				true,
				[ __CLASS__, 'get_status' ],
			],
		];
	}

	/**
	 * Register the abilities.
	 */
	public static function register_abilities() {
		/**
		 * Filters the ability definitions. BuddyBuilder ships read-only abilities; BuddyBuilder Pro adds the write
		 * abilities (behind its agent setting).
		 *
		 * @since 2.0.0
		 *
		 * @param array $definitions name => [ label, description, input schema, capability, readonly, idempotent, callback ].
		 */
		foreach ( (array) apply_filters( 'buddy_builder/abilities', self::definitions() ) as $name => $def ) {
			list( $label, $description, $input, $capability, $readonly, $idempotent, $callback ) = $def;

			wp_register_ability(
				$name,
				[
					'label'               => $label,
					'description'         => $description,
					'category'            => self::CATEGORY,
					'input_schema'        => $input,
					'output_schema'       => [ 'type' => 'object' ],
					'permission_callback' => static function () use ( $capability ) {
						return current_user_can( $capability );
					},
					'execute_callback'    => static function ( $input = null ) use ( $callback ) {
						return call_user_func( $callback, is_array( $input ) ? $input : [] );
					},
					'meta'                => [
						'annotations'  => [
							'readonly'      => $readonly,
							'destructive'   => false,
							'idempotent'    => $idempotent,
							'openWorldHint' => false,
						],
						'public'       => true,
						'show_in_rest' => true,
						'mcp'          => [ 'type' => 'tool' ],
					],
				]
			);
		}
	}

	/**
	 * Template types.
	 *
	 * @return array
	 */
	public static function list_template_types() {
		$blocks = Types::for_blocks();
		$out    = [];

		foreach ( Types::all() as $slug => $type ) {
			$entry = Registry::get( $slug );
			$out[] = [
				'type'      => $slug,
				'name'      => $type['name'],
				'blocks'    => isset( $blocks[ $slug ] ),
				'active'    => Types::is_active( $slug ),
				'assigned'  => $entry['id'] ? $entry : null,
				'populated' => Registry::is_populated( $slug ),
			];
		}

		return [ 'types' => $out ];
	}

	/**
	 * Elements.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public static function list_elements( array $input ) {
		$type = isset( $input['type'] ) ? (string) $input['type'] : '';
		$out  = [];

		foreach ( Elements::kit()->elements() as $element ) {
			$schema = $element->schema();
			$types  = isset( $schema['templateTypes'] ) ? (array) $schema['templateTypes'] : [];

			if ( '' !== $type && $types && ! in_array( $type, $types, true ) ) {
				continue;
			}

			$attributes = [];

			foreach ( isset( $schema['attributes'] ) ? $schema['attributes'] : [] as $key => $def ) {
				$attributes[ $key ] = array_intersect_key( $def, array_flip( [ 'type', 'default', 'label', 'options', 'panel' ] ) );
			}

			$out[] = [
				'block'       => $element->block_name(),
				'title'       => $element->title(),
				'description' => isset( $schema['description'] ) ? $schema['description'] : '',
				'types'       => $types,
				'attributes'  => (object) $attributes,
			];
		}

		return [ 'elements' => $out ];
	}

	/**
	 * Presets.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public static function list_presets( array $input ) {
		$out = [];

		foreach ( Presets::all( isset( $input['type'] ) ? (string) $input['type'] : '' ) as $id => $preset ) {
			$out[] = [
				'id'          => $id,
				'type'        => $preset['type'],
				'title'       => $preset['title'],
				'description' => isset( $preset['description'] ) ? $preset['description'] : '',
				'theme'       => isset( $preset['theme'] ) ? $preset['theme'] : '',
			];
		}

		return [ 'presets' => $out ];
	}

	/**
	 * Assigned layout.
	 *
	 * @param array $input Input.
	 * @return array|\WP_Error
	 */
	public static function get_layout( array $input ) {
		$type  = (string) $input['type'];
		$entry = Registry::get( $type );
		$post  = $entry['id'] ? get_post( $entry['id'] ) : null;

		if ( ! $post ) {
			return [
				'type'     => $type,
				'assigned' => false,
			];
		}

		return [
			'type'      => $type,
			'assigned'  => true,
			'builder'   => $entry['builder'],
			'id'        => $post->ID,
			'title'     => $post->post_title,
			'populated' => Registry::is_post_populated( $post->ID ),
			'content'   => Registry::BLOCKS === $entry['builder'] ? $post->post_content : null,
		];
	}

	/**
	 * Ability: read-only status.
	 *
	 * @return array
	 */
	public static function get_status() {
		$pages = [];

		foreach ( Types::all() as $slug => $type ) {
			$entry          = Registry::get( $slug );
			$post           = $entry['id'] ? get_post( $entry['id'] ) : null;
			$pages[ $slug ] = [
				'builder' => $entry['builder'],
				'id'      => (int) $entry['id'],
				'title'   => $post ? $post->post_title : '',
				'style'   => $post && preg_match( '/\\bbpb-look--([a-z0-9_-]+)/', (string) $post->post_content . ' ' . (string) get_post_meta( $post->ID, '_bpb_look', true ), $m ) ? $m[1] : '',
			];
		}

		return [
			'pages'          => $pages,
			'blocks_enabled' => \Buddy_Builder\Features::blocks_enabled(),
			'pro_active'     => defined( 'BPB_PRO_VERSION' ),
			'version'        => BPB_VERSION,
		];
	}
}
