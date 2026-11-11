<?php
/**
 * Kept 1.x surface: renamed hooks and moved classes (inventory 14.4).
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One place for every rename, so 2.x code only uses new names and old callers keep working.
 *
 * Policy (inventory 14.2): nothing public is removed in 2.x. Old names fire through `apply_filters_deprecated()` /
 * `do_action_deprecated()`, which only call `_deprecated_hook()` when something is hooked to the old name, and
 * WordPress only reports that with WP_DEBUG on. Removal earliest in 3.0, announced one major ahead.
 */
class Legacy {

	/**
	 * Renamed filters: old name => [ new name, version ].
	 * New code applies the new name; callbacks on the old name still run, with a deprecation notice.
	 *
	 * @var array
	 */
	const FILTERS = [
		// F6: 1.x applied the filter with a trailing space.
		'buddy_builder/page_template ' => [ 'buddy_builder/page_template', '2.0.0' ],
	];

	/**
	 * Renamed actions: old name => [ new name, version ].
	 *
	 * @var array
	 */
	const ACTIONS = [];

	/**
	 * Moved classes: old name => new name. Resolved lazily (the new class loads only when old code asks).
	 * Filled when the 2.0 refactor moves a class (bb2-core keeps widget classes at their 1.x names).
	 *
	 * @var array
	 */
	const CLASSES = [];

	/**
	 * Registers the bridges.
	 */
	public static function init() {
		foreach ( self::FILTERS as $old => $new ) {
			add_filter(
				$new[0],
				static function ( ...$args ) use ( $old, $new ) {
					if ( ! has_filter( $old ) ) {
						return $args[0];
					}
					return apply_filters_deprecated( $old, $args, $new[1], $new[0] );
				},
				PHP_INT_MIN + 1,
				99
			);
		}

		foreach ( self::ACTIONS as $old => $new ) {
			add_action(
				$new[0],
				static function ( ...$args ) use ( $old, $new ) {
					if ( has_action( $old ) ) {
						do_action_deprecated( $old, $args, $new[1], $new[0] );
					}
				},
				PHP_INT_MIN + 1,
				99
			);
		}

		if ( self::CLASSES ) {
			spl_autoload_register( [ __CLASS__, 'autoload_alias' ] );
		}
	}

	/**
	 * Autoloader for moved classes: loads the new class and aliases the old name to it.
	 *
	 * @param string $class Requested class.
	 */
	public static function autoload_alias( $class ) {
		$map = array_change_key_case( self::CLASSES );
		$key = strtolower( ltrim( $class, '\\' ) );

		if ( isset( $map[ $key ] ) && class_exists( $map[ $key ] ) ) {
			class_alias( $map[ $key ], $class );
		}
	}
}
