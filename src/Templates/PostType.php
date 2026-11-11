<?php
/**
 * Block layouts for BuddyPress template types: the bpb_template post type (2.0 spike).
 *
 * Elementor layouts stay elementor_library posts of type bpb-buddypress.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * bpb_template post type and its meta.
 */
final class PostType {

	const POST_TYPE = 'bpb_template';
	const META_TYPE = '_bpb_type';
	const META_USER = '_bpb_preview_user';

	/**
	 * Register the post type, meta and the block category.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Block layouts', 'stax-buddy-builder' ),
					'singular_name' => __( 'Block layout', 'stax-buddy-builder' ),
					'add_new_item'  => __( 'Add block layout', 'stax-buddy-builder' ),
					'edit_item'     => __( 'Edit block layout', 'stax-buddy-builder' ),
				],
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => \Buddy_Builder\Helpers::get_instance()->get_slug(),
				'show_in_rest'        => true,
				'rest_base'           => 'bpb-templates',
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
				'supports'            => [ 'title', 'editor', 'custom-fields', 'revisions' ],
				'exclude_from_search' => true,
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
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => $auth,
			]
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_USER,
			[
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => true,
				'default'       => 0,
				'auth_callback' => $auth,
			]
		);
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
	 * Styles of the BuddyPress pages inside the editor canvas, so the preview matches the front end.
	 */
	public static function editor_styles() {
		if ( ! is_admin() || self::POST_TYPE !== get_post_type() ) {
			return;
		}

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'stax-buddy-builder-bp-editor', BPB_BASE_URL . 'templates/buddypress/assets/buddypress' . $min . '.css', [], BPB_VERSION );
		wp_enqueue_style( 'stax-buddy-builder-front-editor', BPB_ASSETS_URL . 'css/index' . $min . '.css', [], BPB_VERSION );
	}
}
