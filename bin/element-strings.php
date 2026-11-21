<?php
/**
 * Writes src/Elements/strings.php: one __() call per translatable string of every element.json,
 * so `wp i18n make-pot` puts them in the POT file (it does not read element.json).
 *
 * Usage: php bin/element-strings.php [--check] [--root <plugin dir> --domain <text domain> --package <name>]
 *   --check: exit 1 when the file is out of date.
 *   --root, --domain, --package: another plugin whose elements join this kit (BuddyBuilder Pro:
 *   php ../stax-buddy-builder/bin/element-strings.php --root . --domain stax-buddy-builder-pro --package Buddy_Builder_Pro).
 * The work is done by the element kit (Strings, in vendor-prefixed/).
 *
 * @package Buddy_Builder
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals,WordPress.Security.EscapeOutput -- CLI script, no WordPress.

define( 'ABSPATH', __DIR__ . '/' );
require dirname( __DIR__ ) . '/vendor-prefixed/staxwp/element-kit/src/Schema.php';
require dirname( __DIR__ ) . '/vendor-prefixed/staxwp/element-kit/src/Strings.php';

$bpb_arg = static function ( $name, $fallback ) use ( $argv ) {
	$i = array_search( '--' . $name, $argv, true );
	return false !== $i && isset( $argv[ $i + 1 ] ) ? $argv[ $i + 1 ] : $fallback;
};
$domain  = $bpb_arg( 'domain', 'stax-buddy-builder' );
$package = $bpb_arg( 'package', 'Buddy_Builder' );
$own     = 'stax-buddy-builder' === $domain;

exit(
	Buddy_Builder\Vendor\ElementKit\Strings::run(
		[
			'root'      => rtrim( realpath( $bpb_arg( 'root', dirname( __DIR__ ) ) ), '/' ),
			'domain'    => $domain,
			'package'   => $package,
			'generator' => ( $own ? '' : "BuddyBuilder's " ) . 'bin/element-strings.php',
			'command'   => $own ? 'php bin/element-strings.php' : 'php ../stax-buddy-builder/bin/element-strings.php --root . --domain ' . $domain . ' --package ' . $package,
			'check'     => in_array( '--check', $argv, true ),
		]
	)
);
