<?php
/**
 * Render dispatcher: prints the layout assigned to a template type with its builder (blocks or Elementor).
 *
 * Every call site in templates/buddypress goes through bpb_render_template( $type ). Elementor layouts render exactly
 * as in 1.x (the [elementor-template] shortcode); block layouts render with do_blocks() inside a neutral wrapper.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Integrations\Elementor\Integration as Elementor;

defined( 'ABSPATH' ) || die();

/**
 * Render dispatcher.
 */
final class Dispatcher {

	/**
	 * Context of the layout being rendered (stack, for nested item layouts).
	 *
	 * @var array[]
	 */
	private static $stack = [];

	/**
	 * Render the layout assigned to a type.
	 *
	 * @param string $type    Template type.
	 * @param array  $context Optional context for the elements (user_id, group_id, activity_id, preview).
	 * @return string
	 */
	public static function render( $type, array $context = [] ) {
		$entry = Registry::get( $type );

		if ( ! $entry['id'] || ! Registry::is_post_populated( $entry['id'] ) ) {
			return '';
		}

		/**
		 * Fires before a layout renders (assets per template type hook here).
		 *
		 * @param string $type  Template type.
		 * @param array  $entry [ builder, id ].
		 */
		do_action( 'buddy_builder/render_template', $type, $entry );

		if ( Registry::BLOCKS === $entry['builder'] ) {
			return self::render_blocks( $type, $entry['id'], $context );
		}

		return Elementor::is_available() ? Elementor::render( $entry['id'] ) : '';
	}

	/**
	 * Render a block layout.
	 *
	 * @param string $type    Template type.
	 * @param int    $id      bpb_template post id.
	 * @param array  $context Context.
	 * @return string
	 */
	public static function render_blocks( $type, $id, array $context = [] ) {
		$post = get_post( $id );

		if ( ! $post ) {
			return '';
		}

		self::$stack[] = array_merge( [ 'type' => $type ], $context );

		$html = do_blocks( $post->post_content );

		array_pop( self::$stack );

		return '<div class="bpb-template bpb-template-' . esc_attr( $type ) . '">' . $html . '</div>';
	}

	/**
	 * Context passed by the layout currently rendering (empty outside a layout).
	 *
	 * @return array
	 */
	public static function current_context() {
		$current = end( self::$stack );

		return is_array( $current ) ? $current : [];
	}

	/**
	 * Render one loop item (members, groups, activity loops).
	 *
	 * 1.x loops print an Elementor item layout through a filterable shortcode string; Pro and the listing widgets
	 * filter that string. A non-empty string keeps the 1.x path; otherwise a block item layout renders (2.1).
	 *
	 * @param string $type      Item template type (e.g. members-directory-item).
	 * @param string $shortcode Filtered shortcode string.
	 * @param array  $context   Item context.
	 * @return string
	 */
	public static function render_item( $type, $shortcode, array $context = [] ) {
		if ( is_string( $shortcode ) && '' !== $shortcode ) {
			return do_shortcode( $shortcode );
		}

		$entry = Registry::get( $type );

		if ( Registry::BLOCKS !== $entry['builder'] || ! Registry::is_post_populated( $entry['id'] ) ) {
			return '';
		}

		return self::render_blocks( $type, $entry['id'], $context );
	}

	/**
	 * Filter "buddy_builder/settings": the active layout id per type whatever the builder, so the 1.x "has template"
	 * checks (bpb_is_current_template_populated(), used by KLEO 6) work for block layouts too.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function filter_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		foreach ( Registry::all() as $type => $entry ) {
			if ( Registry::BLOCKS === $entry['builder'] ) {
				$settings['templates'][ $type ] = $entry['id'];
			}
		}

		return $settings;
	}
}
