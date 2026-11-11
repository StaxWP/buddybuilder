<?php
/**
 * Template registry: which layout renders each BuddyPress template type, and with which builder.
 *
 * Storage: option `bpb_templates` = [ type => [ 'builder' => 'elementor'|'blocks', 'id' => int ] ].
 * Until migration 2.0.0-a has run, the legacy option `bpb_settings['templates'][ type ] = id` is read as
 * { elementor, id }. Elementor assignments are also written back to `bpb_settings` (read by Pro 1.x and KLEO 6).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Integrations\Elementor\Integration as Elementor;

defined( 'ABSPATH' ) || die();

/**
 * Template registry with a per-request cache.
 */
final class Registry {

	const OPTION = 'bpb_templates';

	const ELEMENTOR = 'elementor';
	const BLOCKS    = 'blocks';

	/**
	 * Per-request cache of "populated" checks, by post id.
	 *
	 * @var array
	 */
	private static $populated = [];

	/**
	 * Per-request cache of the registry.
	 *
	 * @var array|null
	 */
	private static $all = null;

	/**
	 * All assignments, legacy values included.
	 *
	 * @return array type => [ builder, id ]
	 */
	public static function all() {
		if ( null !== self::$all ) {
			return self::$all;
		}

		$stored = get_option( self::OPTION, null );
		$legacy = self::legacy_ids();
		$all    = [];

		foreach ( $legacy as $type => $id ) {
			$all[ $type ] = self::entry( self::ELEMENTOR, $id );
		}

		/*
		 * The legacy option stays the source of truth for Elementor assignments (Pro 1.x, KLEO 6 and demo importers
		 * write it directly). A stored block assignment applies while the legacy id for that type is empty, which is
		 * how set() leaves it; a later direct write of an Elementor id therefore wins, as it would have in 1.x.
		 */
		if ( is_array( $stored ) ) {
			foreach ( $stored as $type => $entry ) {
				$type = sanitize_key( $type );

				if ( ! is_array( $entry ) || ! isset( $entry['builder'], $entry['id'] ) ) {
					continue;
				}

				if ( self::BLOCKS === $entry['builder'] && empty( $legacy[ $type ] ) ) {
					$all[ $type ] = self::entry( self::BLOCKS, $entry['id'] );
				} elseif ( ! isset( $all[ $type ] ) ) {
					$all[ $type ] = self::entry( $entry['builder'], $entry['id'] );
				}
			}
		}

		self::$all = $all;

		return $all;
	}

	/**
	 * Assignment of a type.
	 *
	 * @param string $type Template type.
	 * @return array [ builder, id ]; id 0 when nothing is assigned.
	 */
	public static function get( $type ) {
		$all = self::all();

		return isset( $all[ $type ] ) ? $all[ $type ] : self::entry( self::ELEMENTOR, 0 );
	}

	/**
	 * Assign a layout to a type.
	 *
	 * @param string $type    Template type.
	 * @param string $builder elementor|blocks.
	 * @param int    $id      Post id (0 unassigns).
	 * @return bool
	 */
	public static function set( $type, $builder, $id ) {
		$type = sanitize_key( $type );
		$new  = self::entry( $builder, $id );

		if ( '' === $type || ( $new['id'] && ! self::is_valid_post( $new['builder'], $new['id'] ) ) ) {
			return false;
		}

		$old    = self::get( $type );
		$stored = get_option( self::OPTION, [] );
		$stored = is_array( $stored ) ? $stored : [];

		// First write: persist the legacy assignments too, so the option is complete.
		if ( empty( $stored ) ) {
			foreach ( self::all() as $legacy_type => $entry ) {
				$stored[ $legacy_type ] = $entry;
			}
		}

		$stored[ $type ] = $new;

		update_option( self::OPTION, $stored );

		// Keep the legacy option in sync for Pro 1.x and KLEO 6: Elementor id, or 0 for a block layout.
		$legacy = get_option( 'bpb_settings', [] );
		$legacy = is_array( $legacy ) ? $legacy : [];

		if ( ! isset( $legacy['templates'] ) || ! is_array( $legacy['templates'] ) ) {
			$legacy['templates'] = [];
		}

		$legacy['templates'][ $type ] = self::ELEMENTOR === $new['builder'] ? $new['id'] : 0;

		update_option( 'bpb_settings', $legacy );

		self::flush();

		/**
		 * Fires after a template type assignment changed.
		 *
		 * @param string $type Template type.
		 * @param array  $old  Previous [ builder, id ].
		 * @param array  $new  New [ builder, id ].
		 */
		do_action( 'buddy_builder/registry/changed', $type, $old, $new );

		return true;
	}

	/**
	 * Whether the layout assigned to a type exists and has content.
	 *
	 * @param string $type Template type.
	 * @return bool
	 */
	public static function is_populated( $type ) {
		$entry = self::get( $type );

		return $entry['id'] ? self::is_post_populated( $entry['id'] ) : false;
	}

	/**
	 * Whether a layout post has content for its builder (cached per request: F10).
	 *
	 * @param int $id Post id.
	 * @return bool
	 */
	public static function is_post_populated( $id ) {
		$id = (int) $id;

		if ( ! $id ) {
			return false;
		}

		if ( ! isset( self::$populated[ $id ] ) ) {
			$post = get_post( $id );

			if ( ! $post ) {
				self::$populated[ $id ] = false;
			} elseif ( PostType::POST_TYPE === $post->post_type ) {
				self::$populated[ $id ] = 'publish' === $post->post_status && has_blocks( $post->post_content );
			} else {
				self::$populated[ $id ] = Elementor::is_populated( $id );
			}
		}

		return self::$populated[ $id ];
	}

	/**
	 * Builder of a layout post.
	 *
	 * @param int $id Post id.
	 * @return string elementor|blocks
	 */
	public static function builder_of( $id ) {
		return PostType::POST_TYPE === get_post_type( (int) $id ) ? self::BLOCKS : self::ELEMENTOR;
	}

	/**
	 * Clear the per-request caches (after saving a layout or an assignment).
	 */
	public static function flush() {
		self::$all       = null;
		self::$populated = [];
	}

	/**
	 * Raw legacy ids (no filters, so a block id never leaks into bpb_settings).
	 *
	 * @return array type => id
	 */
	public static function legacy_ids() {
		$legacy = get_option( 'bpb_settings', [] );
		$ids    = [];

		if ( is_array( $legacy ) && isset( $legacy['templates'] ) && is_array( $legacy['templates'] ) ) {
			foreach ( $legacy['templates'] as $type => $id ) {
				$ids[ sanitize_key( $type ) ] = (int) $id;
			}
		}

		return $ids;
	}

	/**
	 * Normalized entry.
	 *
	 * @param string $builder Builder.
	 * @param int    $id      Post id.
	 * @return array
	 */
	private static function entry( $builder, $id ) {
		return [
			'builder' => self::BLOCKS === $builder ? self::BLOCKS : self::ELEMENTOR,
			'id'      => max( 0, (int) $id ),
		];
	}

	/**
	 * Whether a post can be assigned with that builder.
	 *
	 * @param string $builder Builder.
	 * @param int    $id      Post id.
	 * @return bool
	 */
	private static function is_valid_post( $builder, $id ) {
		$type = get_post_type( $id );

		return self::BLOCKS === $builder ? PostType::POST_TYPE === $type : 'elementor_library' === $type;
	}
}
