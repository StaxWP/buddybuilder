<?php
/**
 * Template types known to BuddyBuilder (free and Pro), with what block layouts support.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Template types.
 */
final class Types {

	/**
	 * Types that block layouts support in 2.0 (Pro item, register and group-create types follow in 2.1).
	 */
	const BLOCK_TYPES = [ 'member-profile', 'members-directory', 'group-profile', 'groups-directory', 'sitewide-activity' ];

	/**
	 * All types: slug => [ name, order ] (filter buddy_builder/get_template_types, Pro adds its own).
	 *
	 * @return array
	 */
	public static function all() {
		return function_exists( 'bpb_get_template_types' ) ? bpb_get_template_types() : [];
	}

	/**
	 * Types available for block layouts.
	 *
	 * @return array slug => [ name, order ]
	 */
	public static function for_blocks() {
		/**
		 * Filters the template types that block layouts can be built for.
		 *
		 * @param string[] $types Type slugs.
		 */
		$slugs = (array) apply_filters( 'buddy_builder/block_template_types', self::BLOCK_TYPES );

		return array_intersect_key( self::all(), array_flip( $slugs ) );
	}

	/**
	 * BuddyPress component a type needs (types whose component is inactive are hidden).
	 *
	 * @param string $type Type.
	 * @return string
	 */
	public static function component( $type ) {
		$map = [
			'member-profile'    => 'members',
			'members-directory' => 'members',
			'group-profile'     => 'groups',
			'groups-directory'  => 'groups',
			'sitewide-activity' => 'activity',
		];

		return isset( $map[ $type ] ) ? $map[ $type ] : 'members';
	}

	/**
	 * Whether the type's component is active.
	 *
	 * @param string $type Type.
	 * @return bool
	 */
	public static function is_active( $type ) {
		return function_exists( 'bp_is_active' ) && bp_is_active( self::component( $type ) );
	}
}
