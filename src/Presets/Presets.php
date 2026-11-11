<?php
/**
 * Preset layouts (U1): one description per preset, turned into block markup (a block pattern and a bpb_template)
 * or into Elementor data (an elementor_library template), so both builders get the same layouts.
 *
 * A preset is: id, type (template type), title, description, layout (rows of columns of elements) and optional
 * "theme" (only listed when that theme is active, e.g. the KLEO defaults). Themes and add-ons add presets with the
 * filter buddy_builder/presets.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

use Buddy_Builder\Features;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Preset registry and the one-click "apply".
 */
final class Presets {

	/**
	 * Cached presets.
	 *
	 * @var array|null
	 */
	private static $presets = null;

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'init', [ __CLASS__, 'register_patterns' ], 20 );
		add_action( 'admin_post_bpb_apply_preset', [ __CLASS__, 'handle_apply' ] );
	}

	/**
	 * All presets available on this site.
	 *
	 * @param string $type Optional template type filter.
	 * @return array id => preset
	 */
	public static function all( $type = '' ) {
		if ( null === self::$presets ) {
			/**
			 * Filters the preset layouts.
			 *
			 * @param array $presets id => [ type, title, description, layout, theme? ].
			 */
			$presets = (array) apply_filters( 'buddy_builder/presets', Library::presets() );

			foreach ( $presets as $id => $preset ) {
				if ( ! empty( $preset['theme'] ) && 'kleo' === $preset['theme'] && ! Features::is_kleo7() ) {
					unset( $presets[ $id ] );
					continue;
				}

				$presets[ $id ]['id'] = $id;
			}

			self::$presets = $presets;
		}

		if ( '' === $type ) {
			return self::$presets;
		}

		return array_filter(
			self::$presets,
			static function ( $preset ) use ( $type ) {
				return isset( $preset['type'] ) && $type === $preset['type'];
			}
		);
	}

	/**
	 * One preset.
	 *
	 * @param string $id Preset id.
	 * @return array|null
	 */
	public static function get( $id ) {
		$all = self::all();

		return isset( $all[ $id ] ) ? $all[ $id ] : null;
	}

	/**
	 * Block patterns for the presets (inserter "BuddyBuilder" category, only in block layouts).
	 */
	public static function register_patterns() {
		if ( ! Features::blocks_enabled() || ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		register_block_pattern_category( 'buddybuilder', [ 'label' => __( 'BuddyBuilder', 'stax-buddy-builder' ) ] );

		foreach ( self::all() as $id => $preset ) {
			if ( ! isset( Types::for_blocks()[ $preset['type'] ] ) ) {
				continue;
			}

			register_block_pattern(
				'buddybuilder/' . $id,
				[
					'title'       => $preset['title'],
					'description' => isset( $preset['description'] ) ? $preset['description'] : '',
					'categories'  => [ 'buddybuilder' ],
					'postTypes'   => [ PostType::POST_TYPE ],
					'blockTypes'  => [],
					'content'     => Builder::to_blocks( $preset['layout'] ),
					'keywords'    => [ $preset['type'] ],
				]
			);
		}
	}

	/**
	 * Apply a preset: create the layout with the chosen builder, assign it to its type.
	 *
	 * @param string $id      Preset id.
	 * @param string $builder blocks|elementor (empty: blocks when enabled, else Elementor).
	 * @return int|\WP_Error Layout post id.
	 */
	public static function apply( $id, $builder = '' ) {
		$preset = self::get( $id );

		if ( ! $preset ) {
			return new \WP_Error( 'bpb_preset_missing', __( 'Unknown preset.', 'stax-buddy-builder' ) );
		}

		if ( '' === $builder ) {
			$builder = Features::blocks_enabled() || ! defined( 'ELEMENTOR_VERSION' ) ? Registry::BLOCKS : Registry::ELEMENTOR;
		}

		$post_id = Registry::BLOCKS === $builder ? self::create_block_layout( $preset ) : self::create_elementor_layout( $preset );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		Registry::set( $preset['type'], $builder, $post_id );

		/**
		 * Fires after a preset was applied.
		 *
		 * @param string $id      Preset id.
		 * @param int    $post_id Layout id.
		 * @param string $builder Builder.
		 */
		do_action( 'buddy_builder/preset_applied', $id, $post_id, $builder );

		return $post_id;
	}

	/**
	 * Create a block layout from a preset.
	 *
	 * @param array $preset Preset.
	 * @return int|\WP_Error
	 */
	public static function create_block_layout( array $preset ) {
		$post_id = wp_insert_post(
			[
				'post_type'    => PostType::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => self::layout_title( $preset ),
				'post_content' => Builder::to_blocks( $preset['layout'] ),
				'meta_input'   => [
					PostType::META_TYPE   => $preset['type'],
					PostType::META_PRESET => $preset['id'],
				],
			],
			true
		);

		Registry::flush();

		return $post_id;
	}

	/**
	 * Create an Elementor template (bpb-buddypress document) from a preset.
	 *
	 * @param array $preset Preset.
	 * @return int|\WP_Error
	 */
	public static function create_elementor_layout( array $preset ) {
		if ( ! class_exists( '\Elementor\Plugin' ) || ! isset( \Elementor\Plugin::$instance->documents ) ) {
			return new \WP_Error( 'bpb_no_elementor', __( 'Elementor is not active.', 'stax-buddy-builder' ) );
		}

		$document = \Elementor\Plugin::$instance->documents->create(
			'bpb-buddypress',
			[
				'post_title'  => self::layout_title( $preset ),
				'post_status' => 'publish',
			],
			[
				'_bpb_page'   => $preset['type'],
				'_bpb_preset' => $preset['id'],
			]
		);

		if ( is_wp_error( $document ) || ! $document ) {
			return is_wp_error( $document ) ? $document : new \WP_Error( 'bpb_elementor_create', __( 'Could not create the Elementor template.', 'stax-buddy-builder' ) );
		}

		$data    = Builder::to_elementor( $preset['layout'] );
		$post_id = (int) $document->get_main_id();

		// Document::save() refuses without an editing user (WP-CLI, abilities run by agents): write the data directly.
		if ( ! $document->save( [ 'elements' => $data ] ) ) {
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
		}

		Registry::flush();

		return $post_id;
	}

	/**
	 * Admin action: apply a preset (nonce + manage_options), then open the layout or go back.
	 */
	public static function handle_apply() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bpb_apply_preset' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		$ids     = isset( $_REQUEST['preset'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_REQUEST['preset'] ) ) : [];
		$builder = isset( $_REQUEST['builder'] ) ? sanitize_key( wp_unslash( $_REQUEST['builder'] ) ) : '';
		$last    = 0;
		$errors  = 0;

		foreach ( $ids as $id ) {
			$result = self::apply( $id, $builder );

			if ( is_wp_error( $result ) ) {
				++$errors;
			} else {
				$last = $result;
			}
		}

		$redirect = wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . 'settings' );
		$redirect = add_query_arg( 'bpb_applied', $errors ? 'error' : count( $ids ), $redirect );

		if ( 1 === count( $ids ) && $last && ! empty( $_REQUEST['open'] ) ) {
			$redirect = Registry::BLOCKS === Registry::builder_of( $last ) ? get_edit_post_link( $last, 'raw' ) : add_query_arg(
				[
					'post'   => $last,
					'action' => 'elementor',
				],
				admin_url( 'post.php' )
			);
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Title of a layout created from a preset.
	 *
	 * @param array $preset Preset.
	 * @return string
	 */
	private static function layout_title( array $preset ) {
		$types = Types::all();
		$type  = isset( $types[ $preset['type'] ] ) ? $types[ $preset['type'] ]['name'] : $preset['type'];

		/* translators: 1: template type, 2: preset name */
		return sprintf( __( '%1$s: %2$s', 'stax-buddy-builder' ), $type, $preset['title'] );
	}
}
