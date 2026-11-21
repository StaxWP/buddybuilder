<?php
/**
 * PSR-4 style autoloader for the 2.0 code in src/ (Buddy_Builder\<Path>\<Class> => src/<Path>/<Class>.php) and
 * BuddyBuilder's copy of the element kit (staxwp/element-kit, prefixed by Strauss: Buddy_Builder\Vendor\ElementKit\<Class>
 * => vendor-prefixed/staxwp/element-kit/src/<Class>.php; never edit that copy, see docs/hooks.md).
 *
 * The 1.x classes in core/ keep their own lowercase autoloader (Plugin::autoload).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

spl_autoload_register(
	static function ( $class ) {
		$roots = [
			'Buddy_Builder\\Vendor\\ElementKit\\' => dirname( __DIR__ ) . '/vendor-prefixed/staxwp/element-kit/src/',
			'Buddy_Builder\\'                     => __DIR__ . '/',
		];

		foreach ( $roots as $prefix => $dir ) {
			if ( 0 === strpos( $class, $prefix ) ) {
				$file = $dir . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';

				if ( is_readable( $file ) ) {
					require_once $file;
				}

				return;
			}
		}
	}
);
