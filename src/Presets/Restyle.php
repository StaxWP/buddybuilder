<?php
/**
 * "Restyle, keep my layout" (Community pages > Change style): a page changes style without losing the edits made to
 * its layout.
 *
 * How a layout is bound to its style: block layouts carry the style in the class list of their root group
 * (bpb-look bpb-look--<style> bpb-page--<page>, in the block comment and the HTML); Elementor templates carry the same
 * classes in the meta _bpb_look, printed on the document wrapper. Everything else (elements, their settings, column
 * classes such as bpb-look__main) is shared by every style; the look comes from the style's stylesheet, loaded for the
 * bpb-look--<style> class, and its tokens. So restyling swaps that one class: the elements and their settings stay.
 *
 * Like "Use this style", it makes a new layout live and keeps the current one (Undo puts it back): the restyled
 * layout is a published copy of the current one with the new style class.
 *
 * Whether a layout was edited: a fingerprint of its structure is kept when a style creates it (meta
 * _bpb_preset_hash); a different fingerprint now means the owner changed it. Layouts made before the fingerprint
 * existed: a block layout is compared with its style page, an Elementor template with its creation time.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

use Buddy_Builder\Admin\LayoutActions;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;

defined( 'ABSPATH' ) || die();

/**
 * Restyle a layout.
 */
final class Restyle {

	/** Fingerprint of the layout's structure when a style created it. */
	const META_HASH = '_bpb_preset_hash';

	/** Elementor template meta with the style classes. */
	const META_LOOK = '_bpb_look';

	/**
	 * Keep the fingerprint of a layout a style just created (Presets::create_block_layout() and
	 * create_elementor_layout(): Use this style, the setup, variations).
	 *
	 * @param int $post_id Layout id.
	 */
	public static function remember( $post_id ) {
		update_post_meta( (int) $post_id, self::META_HASH, self::fingerprint( (int) $post_id ) );
	}

	/**
	 * Fingerprint of a layout's structure: the block markup, or the Elementor elements without their random ids.
	 *
	 * @param int $id Layout id.
	 * @return string
	 */
	public static function fingerprint( $id ) {
		$post = get_post( $id );

		if ( ! $post ) {
			return '';
		}

		if ( PostType::POST_TYPE === $post->post_type ) {
			return md5( trim( (string) $post->post_content ) );
		}

		$data = json_decode( (string) get_post_meta( $post->ID, '_elementor_data', true ), true );

		return md5( (string) wp_json_encode( self::without_ids( is_array( $data ) ? $data : [] ) ) );
	}

	/**
	 * Elementor elements without their ids.
	 *
	 * @param array $elements Elements.
	 * @return array
	 */
	private static function without_ids( array $elements ) {
		foreach ( $elements as $i => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			unset( $elements[ $i ]['id'] );

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$elements[ $i ]['elements'] = self::without_ids( $element['elements'] );
			}
		}

		return $elements;
	}

	/**
	 * Whether the owner changed a layout since a style created it.
	 *
	 * @param int $id Layout id.
	 * @return bool
	 */
	public static function is_edited( $id ) {
		$post = get_post( $id );

		if ( ! $post ) {
			return false;
		}

		$hash = (string) get_post_meta( $post->ID, self::META_HASH, true );

		if ( '' !== $hash ) {
			return self::fingerprint( $post->ID ) !== $hash;
		}

		if ( PostType::POST_TYPE === $post->post_type ) {
			$preset = Presets::get( (string) get_post_meta( $post->ID, PostType::META_PRESET, true ) );

			return ! $preset || trim( (string) $post->post_content ) !== trim( Presets::blocks( $preset ) );
		}

		return strtotime( $post->post_modified_gmt ) - strtotime( $post->post_date_gmt ) > MINUTE_IN_SECONDS;
	}

	/**
	 * Whether a layout can take a style preset with Restyle: it uses a style, the preset is another style's page of
	 * the same type.
	 *
	 * @param int    $id     Layout id.
	 * @param string $preset Preset id.
	 * @return bool
	 */
	public static function can( $id, $preset ) {
		$found = Presets::get( $preset );
		$from  = Styles::of_layout( $id );

		return $found && ! empty( $found['style'] ) && '' !== $from && $from !== $found['style'] && LayoutActions::is_layout( $id ) && $found['type'] === PostType::type_of( $id );
	}

	/**
	 * Restyle: a published copy of the layout with the preset's style, made live on its page.
	 *
	 * @param int    $id     Layout id.
	 * @param string $preset Preset id.
	 * @return int|\WP_Error The new layout id.
	 */
	public static function apply( $id, $preset ) {
		if ( ! self::can( $id, $preset ) ) {
			return new \WP_Error( 'bpb_restyle', __( 'This layout cannot take that style.', 'stax-buddy-builder' ) );
		}

		$found  = Presets::get( $preset );
		$from   = Styles::of_layout( $id );
		$edited = self::is_edited( $id );
		$copy   = LayoutActions::duplicate( $id );

		if ( is_wp_error( $copy ) ) {
			return $copy;
		}

		$post   = get_post( $copy );
		$update = [
			'ID'          => $copy,
			'post_status' => 'publish',
			/* translators: %s: layout title (Member Profile: Hub) */
			'post_title'  => sprintf( __( '%s (restyled)', 'stax-buddy-builder' ), Presets::layout_title( $found ) ),
		];

		if ( PostType::POST_TYPE === $post->post_type ) {
			$update['post_content'] = self::swap( $post->post_content, $from, $found['style'] );
		} else {
			update_post_meta( $copy, self::META_LOOK, self::swap( (string) get_post_meta( $copy, self::META_LOOK, true ), $from, $found['style'] ) );
		}

		wp_update_post( wp_slash( $update ) );
		update_post_meta( $copy, PostType::META_PRESET, $preset );

		// An untouched layout stays untouched under its new style (the next Change style offers Replace first again).
		if ( ! $edited ) {
			self::remember( $copy );
		}

		Registry::set( $found['type'], Registry::builder_of( $copy ), $copy );

		/**
		 * Fires after a layout was restyled.
		 *
		 * @since 2.0.0
		 *
		 * @param int    $copy   New layout id (live).
		 * @param int    $id     Layout it was made from (kept).
		 * @param string $preset Preset id of the new style.
		 */
		do_action( 'buddy_builder/layout/restyled', $copy, $id, $preset );

		return $copy;
	}

	/**
	 * Swap the style class in block markup or a class list.
	 *
	 * @param string $text Markup or classes.
	 * @param string $from Style slug.
	 * @param string $to   Style slug.
	 * @return string
	 */
	public static function swap( $text, $from, $to ) {
		return (string) preg_replace( '/\bbpb-look--' . preg_quote( $from, '/' ) . '(?![a-z0-9_-])/', 'bpb-look--' . $to, (string) $text );
	}
}
