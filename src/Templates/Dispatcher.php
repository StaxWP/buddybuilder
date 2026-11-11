<?php
/**
 * Builder-agnostic template registry and render dispatcher (2.0 spike).
 *
 * Storage in the spike: option bpb_template_builders = [ type => 'blocks' ], option bpb_block_templates = [ type => post id ].
 * The legacy option bpb_settings keeps the Elementor ids untouched, so switching back is one option change.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Registry + dispatcher.
 */
final class Dispatcher {

	/**
	 * Active { builder, id } for a type.
	 *
	 * @param string $type Template type.
	 * @return array
	 */
	public static function get( $type ) {
		$builders = (array) get_option( 'bpb_template_builders', [] );
		$blocks   = (array) get_option( 'bpb_block_templates', [] );

		if ( isset( $builders[ $type ], $blocks[ $type ] ) && 'blocks' === $builders[ $type ] && PostType::POST_TYPE === get_post_type( (int) $blocks[ $type ] ) ) {
			return [
				'builder' => 'blocks',
				'id'      => (int) $blocks[ $type ],
			];
		}

		$legacy = (array) get_option( 'bpb_settings', [] );

		return [
			'builder' => 'elementor',
			'id'      => isset( $legacy['templates'][ $type ] ) ? (int) $legacy['templates'][ $type ] : 0,
		];
	}

	/**
	 * Filter "buddy_builder/settings": the active template id per type, whatever the builder,
	 * so the existing "has template" checks keep working.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function filter_settings( $settings ) {
		foreach ( array_keys( (array) get_option( 'bpb_template_builders', [] ) ) as $type ) {
			$active = self::get( $type );

			if ( 'blocks' === $active['builder'] ) {
				$settings['templates'][ $type ] = $active['id'];
			}
		}

		return $settings;
	}

	/**
	 * Whether a template post has content for its builder.
	 *
	 * @param int $id Post id.
	 * @return bool|null Null when the post is not a block layout (caller checks Elementor data).
	 */
	public static function populated( $id ) {
		$post = get_post( $id );

		if ( ! $post || PostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return 'publish' === $post->post_status && has_blocks( $post->post_content );
	}

	/**
	 * Render the template assigned to a type: blocks or Elementor.
	 *
	 * @param string $type Template type.
	 * @return string
	 */
	public static function render( $type ) {
		$active = self::get( $type );

		if ( 'blocks' === $active['builder'] ) {
			$post = get_post( $active['id'] );

			return $post ? '<div class="bpb-template bpb-template-' . esc_attr( $type ) . '">' . do_blocks( $post->post_content ) . '</div>' : '';
		}

		$shortcode = bpb_get_shortcode_str( $type );

		return $shortcode ? do_shortcode( $shortcode ) : '';
	}
}
