<?php
/**
 * PSR-4 style autoloader for the 2.0 code in src/ (Buddy_Builder\<Path>\<Class> => src/<Path>/<Class>.php).
 *
 * The 1.x classes in core/ keep their own lowercase autoloader (Plugin::autoload).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

spl_autoload_register(
	static function ( $class ) {
		if ( 0 !== strpos( $class, 'Buddy_Builder\\' ) ) {
			return;
		}

		$relative = str_replace( '\\', '/', substr( $class, strlen( 'Buddy_Builder\\' ) ) );

		$file = __DIR__ . '/' . $relative . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
