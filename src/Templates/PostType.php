<?php
/**
 * Block layouts: the bpb_template post type (one post per layout, assigned to a template type in the registry).
 *
 * Elementor layouts stay elementor_library posts of type bpb-buddypress.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Features;

defined( 'ABSPATH' ) || die();

/**
 * bpb_template post type and its meta.
 */
final class PostType {

	const POST_TYPE     = 'bpb_template';
	const META_TYPE     = '_bpb_type';
	const META_USER     = '_bpb_preview_user';
	const META_GROUP    = '_bpb_preview_group';
	const META_PRESET   = '_bpb_preset';
	const TEMPLATE_TYPE = 'member-profile';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'init', [ __CLASS__, 'register' ] );
		add_filter( 'block_categories_all', [ __CLASS__, 'category' ] );
		add_action( 'save_post_' . self::POST_TYPE, [ Registry::class, 'flush' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'column' ], 10, 2 );
	}

	/**
	 * Register the post type and its meta. Always registered (assigned layouts must render); the admin UI only
	 * shows when block layouts are enabled (KLEO 7, or the "Blocks (beta)" toggle).
	 */
	public static function register() {
		$ui = Features::blocks_enabled();

		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Block layouts', 'stax-buddy-builder' ),
					'singular_name' => __( 'Block layout', 'stax-buddy-builder' ),
					'add_new'       => __( 'Add block layout', 'stax-buddy-builder' ),
					'add_new_item'  => __( 'Add block layout', 'stax-buddy-builder' ),
					'edit_item'     => __( 'Edit block layout', 'stax-buddy-builder' ),
					'all_items'     => __( 'Block layouts', 'stax-buddy-builder' ),
				],
				'public'              => false,
				'show_ui'             => $ui,
				'show_in_menu'        => $ui ? \Buddy_Builder\Helpers::get_instance()->get_slug() : false,
				'show_in_rest'        => true,
				'rest_base'           => 'bpb-templates',
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'supports'            => [ 'title', 'editor', 'custom-fields', 'revisions' ],
				'exclude_from_search' => true,
				'template'            => [],
			]
		);

		$auth = static function () {
			return current_user_can( 'edit_pages' );
		};

		register_post_meta(
			self::POST_TYPE,
			self::META_TYPE,
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => self::TEMPLATE_TYPE,
				'sanitize_callback' => [ __CLASS__, 'sanitize_type' ],
				'auth_callback'     => $auth,
			]
		);

		foreach ( [ self::META_USER, self::META_GROUP ] as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'type'          => 'integer',
					'single'        => true,
					'show_in_rest'  => true,
					'default'       => 0,
					'auth_callback' => $auth,
				]
			);
		}

		register_post_meta(
			self::POST_TYPE,
			self::META_PRESET,
			[
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => $auth,
			]
		);
	}

	/**
	 * Template type of a layout.
	 *
	 * @param int $post_id Post id.
	 * @return string
	 */
	public static function type_of( $post_id ) {
		$type = (string) get_post_meta( (int) $post_id, self::META_TYPE, true );

		return '' !== $type ? $type : self::TEMPLATE_TYPE;
	}

	/**
	 * Only known template types.
	 *
	 * @param string $type Type.
	 * @return string
	 */
	public static function sanitize_type( $type ) {
		$type = sanitize_key( $type );

		return array_key_exists( $type, Types::all() ) ? $type : self::TEMPLATE_TYPE;
	}

	/**
	 * Block category for BuddyBuilder elements.
	 *
	 * @param array $categories Categories.
	 * @return array
	 */
	public static function category( $categories ) {
		array_unshift(
			$categories,
			[
				'slug'  => 'buddybuilder',
				'title' => __( 'BuddyBuilder', 'stax-buddy-builder' ),
			]
		);

		return $categories;
	}

	/**
	 * List table columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : null;

		unset( $columns['date'] );

		$columns['bpb_type']   = __( 'Template type', 'stax-buddy-builder' );
		$columns['bpb_active'] = __( 'Active', 'stax-buddy-builder' );

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * List table column content.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post id.
	 */
	public static function column( $column, $post_id ) {
		$type  = self::type_of( $post_id );
		$types = Types::all();

		if ( 'bpb_type' === $column ) {
			echo esc_html( isset( $types[ $type ] ) ? $types[ $type ]['name'] : $type );
		}

		if ( 'bpb_active' === $column ) {
			$entry = Registry::get( $type );

			echo Registry::BLOCKS === $entry['builder'] && (int) $post_id === $entry['id'] ? esc_html__( 'Yes', 'stax-buddy-builder' ) : '&mdash;';
		}
	}
}
