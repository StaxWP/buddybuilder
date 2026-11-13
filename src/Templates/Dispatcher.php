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
	 * Types rendered on this request.
	 *
	 * @var bool[]
	 */
	private static $rendered = [];

	/** Shortcode returned by bpb_get_shortcode_str() for block layouts (1.x theme overrides print it). */
	const SHORTCODE = 'bpb-layout';

	/**
	 * Render the layout assigned to a type.
	 *
	 * @param string $type    Template type.
	 * @param array  $context Optional context for the elements (user_id, group_id, activity_id, preview).
	 * @return string
	 */
	public static function render( $type, array $context = [] ) {
		$entry = self::resolve( $type, $context );

		if ( ! isset( $entry['content'] ) && ( ! $entry['id'] || ! Registry::is_post_populated( $entry['id'] ) ) ) {
			return '';
		}

		self::$rendered[ $type ] = true;

		/**
		 * Fires before a layout renders (assets per template type hook here).
		 *
		 * @param string $type  Template type.
		 * @param array  $entry [ builder, id ].
		 */
		do_action( 'buddy_builder/render_template', $type, $entry );

		if ( isset( $entry['content'] ) ) {
			return self::render_content( $type, (string) $entry['content'], $context );
		}

		if ( Registry::BLOCKS === $entry['builder'] ) {
			return self::render_blocks( $type, $entry['id'], $context );
		}

		return Elementor::is_available() ? Elementor::render( $entry['id'] ) : '';
	}

	/**
	 * Layout that renders a type on this request: the assigned one, unless a filter picks another (a preview of a
	 * look not live yet, or, in BuddyBuilder Pro 2.1, a layout variation for a member type, group type or role).
	 *
	 * @param string $type    Template type.
	 * @param array  $context Render context.
	 * @return array [ builder, id ] plus "content" (block markup) for a layout that is not a post.
	 */
	public static function resolve( $type, array $context = [] ) {
		$entry = Registry::get( $type );

		/**
		 * Filters the layout that renders a template type.
		 *
		 * Return [ 'builder' => 'blocks'|'elementor', 'id' => int ] for another layout post, or add 'content' with
		 * block markup to render it without a post. Fallback: the assigned layout.
		 *
		 * @since 2.0.0
		 *
		 * @param array  $entry   Assigned [ builder, id ].
		 * @param string $type    Template type.
		 * @param array  $context Render context (user_id, group_id, preview).
		 */
		$resolved = apply_filters( 'buddy_builder/template/resolve', $entry, $type, $context );

		if ( ! is_array( $resolved ) || ! isset( $resolved['builder'], $resolved['id'] ) ) {
			return $entry;
		}

		$resolved['id'] = (int) $resolved['id'];

		return $resolved;
	}

	/**
	 * Types rendered by BuddyBuilder on this request (the "layout not applied" checker reads it).
	 *
	 * @return string[]
	 */
	public static function rendered() {
		return array_keys( self::$rendered );
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

		return self::render_content( $type, $post->post_content, $context );
	}

	/**
	 * Render block markup as the layout of a type.
	 *
	 * @param string $type    Template type.
	 * @param string $content Block markup.
	 * @param array  $context Context.
	 * @return string
	 */
	public static function render_content( $type, $content, array $context = [] ) {
		self::$stack[] = array_merge( [ 'type' => $type ], $context );

		$html = do_blocks( $content );

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

		$entry = self::resolve( $type, $context );

		if ( isset( $entry['content'] ) ) {
			return self::render_content( $type, (string) $entry['content'], $context );
		}

		if ( Registry::BLOCKS !== $entry['builder'] || ! Registry::is_post_populated( $entry['id'] ) ) {
			return '';
		}

		return self::render_blocks( $type, $entry['id'], $context );
	}

	/**
	 * Shortcode string for a block layout, so 1.x callers of bpb_get_shortcode_str() (child-theme `buddybuilder/`
	 * overrides, KLEO 6, Pro 1.x) that do_shortcode() it print the layout. Item types are left out: their loops
	 * render block items with context through render_item().
	 *
	 * @param string   $type Template type ('' when only the id is known).
	 * @param int|null $id   Layout id (null: the layout assigned to $type).
	 * @return string '' when it is not a block layout.
	 */
	public static function legacy_shortcode( $type, $id = null ) {
		if ( null !== $id ) {
			$type = PostType::type_of( $id );
		}

		if ( '' === $type || '-item' === substr( $type, -5 ) ) {
			return '';
		}

		return null === $id ? '[' . self::SHORTCODE . ' type="' . esc_attr( $type ) . '"]' : '[' . self::SHORTCODE . ' id="' . (int) $id . '"]';
	}

	/**
	 * [bpb-layout type="member-profile"] (the assigned layout) or [bpb-layout id="123"] (a published block layout).
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'type' => '',
				'id'   => 0,
			],
			$atts,
			self::SHORTCODE
		);

		$id = absint( $atts['id'] );

		if ( $id ) {
			if ( PostType::POST_TYPE !== get_post_type( $id ) || ! Registry::is_post_populated( $id ) ) {
				return '';
			}

			return self::render_blocks( PostType::type_of( $id ), $id );
		}

		return '' !== $atts['type'] ? self::render( sanitize_key( $atts['type'] ) ) : '';
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
