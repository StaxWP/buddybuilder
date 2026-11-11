<?php
/**
 * Public surface inventory as code (inventory section 14.4).
 *
 *   php surface.php dump  <plugin dir> [<label>]        JSON inventory of a release on stdout
 *   php surface.php check <plugin dir> <inventory.json>  static check: everything in the inventory still exists
 *   wp eval-file surface.php runtime <inventory.json>    runtime check on a site: functions, classes, shortcodes,
 *                                                         Elementor widget names
 *
 * What counts as public: action and filter names (literal or with a dynamic part, written as `{*}`), global
 * functions, classes and interfaces (class_alias targets count), template overrides under `templates/`, CSS
 * class names in the front CSS, shortcodes, Elementor widget names, option names.
 * Exit code 1 when anything is missing.
 *
 * @package Buddy_Builder\Tests\Compat
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * PHP files of a plugin, without tests, vendor and node_modules.
 *
 * @param string $dir Plugin dir.
 * @param string $ext Extension.
 * @return string[]
 */
function bpb_surface_files( $dir, $ext ) {
	$out = [];
	$it  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		$path = $file->getPathname();
		if ( preg_match( '#/(tests|vendor|node_modules|\.git)/#', $path ) || substr( $path, -strlen( $ext ) ) !== $ext ) {
			continue;
		}
		$out[] = $path;
	}
	sort( $out );
	return $out;
}

/**
 * Hook name from the source of the first argument: literals stay, every dynamic part becomes {*}.
 *
 * @param string $arg Source.
 * @return string|null
 */
function bpb_surface_hook_name( $arg ) {
	$arg = trim( $arg );
	if ( ! preg_match( '/^[\'"]/', $arg ) ) {
		return null; // Fully dynamic name: not inventoried.
	}
	$name = '';
	foreach ( preg_split( '/\s*\.\s*/', $arg ) as $part ) {
		$name .= preg_match( '/^([\'"])(.*)\1$/s', $part, $m ) ? $m[2] : '{*}';
	}
	return $name;
}

/**
 * Inventory of a plugin directory.
 *
 * @param string $dir Plugin dir.
 * @return array
 */
function bpb_surface_dump( $dir ) {
	$dir = rtrim( $dir, '/' );
	$inv = [
		'hooks'      => [],
		'functions'  => [],
		'classes'    => [],
		'templates'  => [],
		'css'        => [],
		'shortcodes' => [],
		'widgets'    => [],
		'options'    => [],
	];

	foreach ( bpb_surface_files( $dir, '.php' ) as $file ) {
		$src = file_get_contents( $file );

		if ( preg_match_all( '/\b(do_action|apply_filters|do_action_ref_array|apply_filters_ref_array|do_action_deprecated|apply_filters_deprecated)\s*\(\s*((?:[\'"][^\'"]*[\'"]|\$[\w\->\[\]\'"]+|[\w:]+\([^)]*\))(?:\s*\.\s*(?:[\'"][^\'"]*[\'"]|\$[\w\->\[\]\'"]+|[\w:]+\([^)]*\)))*)/', $src, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $hit ) {
				$name = bpb_surface_hook_name( $hit[2] );
				if ( null !== $name ) {
					$inv['hooks'][ $name ] = 0 === strpos( $hit[1], 'do_action' ) ? 'action' : 'filter';
				}
			}
		}
		if ( preg_match_all( '/\badd_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $src, $m ) ) {
			$inv['shortcodes'] = array_merge( $inv['shortcodes'], $m[1] );
		}
		if ( preg_match_all( '/[\'"]name[\'"]\s*=>\s*[\'"](bpb-[a-z0-9-]+)[\'"]/', $src, $m ) ) {
			$inv['widgets'] = array_merge( $inv['widgets'], $m[1] );
		}
		if ( preg_match_all( '/\b(?:get|update|add|delete)_option\s*\(\s*[\'"]([^\'"]+)[\'"]/', $src, $m ) ) {
			$inv['options'] = array_merge( $inv['options'], array_values( array_filter( $m[1], static function ( $o ) {
				return (bool) preg_match( '/^(_?bpb|bp_nouveau_appearance|buddy_builder|wc_am_client)/', $o );
			} ) ) );
		}
		if ( preg_match_all( '/\bclass_alias\s*\(\s*[^,]+,\s*[\'"]\\\\?([^\'"]+)[\'"]/', $src, $m ) ) {
			foreach ( $m[1] as $alias ) {
				$inv['classes'][] = str_replace( '\\\\', '\\', $alias );
			}
		}

		// Functions and classes through the tokenizer.
		$tokens    = token_get_all( $src );
		$namespace = '';
		$depth     = 0;
		$class_at  = [];
		$count     = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			$t = $tokens[ $i ];
			if ( is_string( $t ) ) {
				if ( '{' === $t ) {
					$depth++;
				} elseif ( '}' === $t ) {
					$depth--;
					if ( $class_at && end( $class_at ) === $depth ) {
						array_pop( $class_at );
					}
				}
				continue;
			}
			if ( in_array( $t[0], [ T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ], true ) ) {
				$depth++;
				continue;
			}
			if ( T_NAMESPACE === $t[0] ) {
				$namespace = '';
				for ( $j = $i + 1; $j < $count && ';' !== $tokens[ $j ] && '{' !== $tokens[ $j ]; $j++ ) {
					if ( is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], [ T_STRING, T_NS_SEPARATOR, defined( 'T_NAME_QUALIFIED' ) ? T_NAME_QUALIFIED : -1 ], true ) ) {
						$namespace .= $tokens[ $j ][1];
					}
				}
			}
			if ( in_array( $t[0], [ T_CLASS, T_INTERFACE, T_TRAIT ], true ) ) {
				$prev = $tokens[ $i - 1 ] ?? null;
				if ( is_array( $prev ) && T_DOUBLE_COLON === $prev[0] ) {
					continue; // Foo::class.
				}
				for ( $j = $i + 1; $j < $count; $j++ ) {
					if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
						$inv['classes'][] = ltrim( $namespace . '\\' . $tokens[ $j ][1], '\\' );
						break;
					}
					if ( '{' === $tokens[ $j ] || '(' === $tokens[ $j ] ) {
						break; // Anonymous class.
					}
				}
				$class_at[] = $depth;
			}
			if ( T_FUNCTION === $t[0] && ! $class_at ) {
				for ( $j = $i + 1; $j < $count; $j++ ) {
					if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
						if ( '' === $namespace && 0 === $depth || '' === $namespace && $depth <= 1 ) {
							$inv['functions'][] = $tokens[ $j ][1];
						}
						break;
					}
					if ( '(' === $tokens[ $j ] ) {
						break; // Closure.
					}
				}
			}
		}
	}

	foreach ( bpb_surface_files( $dir . '/templates', '.php' ) as $file ) {
		if ( false === strpos( $file, '/templates/preview/' ) ) {
			$inv['templates'][] = substr( $file, strlen( $dir ) + 1 );
		}
	}

	foreach ( array_merge( bpb_surface_files( $dir . '/assets', '.css' ), bpb_surface_files( $dir . '/templates', '.css' ) ) as $file ) {
		if ( preg_match( '/\.min\.css$|rtl/', $file ) ) {
			continue;
		}
		if ( preg_match_all( '/\.((?:elementor-widget-)?bpb-[a-zA-Z0-9_-]+|stax-[a-zA-Z0-9_-]+)/', file_get_contents( $file ), $m ) ) {
			$inv['css'] = array_merge( $inv['css'], $m[1] );
		}
	}

	foreach ( $inv as $k => $v ) {
		if ( 'hooks' === $k ) {
			ksort( $inv[ $k ] );
			continue;
		}
		$v = array_values( array_unique( $v ) );
		sort( $v );
		$inv[ $k ] = $v;
	}

	return $inv;
}

/**
 * Items of the inventory missing from a newer dump.
 *
 * @param array $old Inventory.
 * @param array $new Inventory.
 * @return array
 */
function bpb_surface_missing( $old, $new ) {
	$missing = [];
	foreach ( $old as $kind => $items ) {
		if ( ! is_array( $items ) || 'meta' === $kind ) {
			continue;
		}
		$now  = $new[ $kind ] ?? [];
		$have = 'hooks' === $kind ? array_keys( $now ) : $now;
		$list = 'hooks' === $kind ? array_keys( $items ) : $items;
		if ( in_array( $kind, [ 'functions', 'classes' ], true ) ) {
			$have = array_map( 'strtolower', $have );
		}
		foreach ( $list as $item ) {
			$probe = in_array( $kind, [ 'functions', 'classes' ], true ) ? strtolower( $item ) : $item;
			if ( ! in_array( $probe, $have, true ) ) {
				$missing[] = $kind . ': ' . $item;
			}
		}
	}
	return $missing;
}

$bpb_args = isset( $args ) ? $args : array_slice( $argv, 1 );
$bpb_mode = $bpb_args[0] ?? '';

if ( 'dump' === $bpb_mode ) {
	$inv         = bpb_surface_dump( $bpb_args[1] );
	$inv['meta'] = [
		'label'  => $bpb_args[2] ?? basename( $bpb_args[1] ),
		'counts' => array_map( 'count', array_diff_key( $inv, [ 'meta' => 1 ] ) ),
	];
	echo json_encode( $inv, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
	exit( 0 );
}

if ( 'check' === $bpb_mode ) {
	$old     = json_decode( file_get_contents( $bpb_args[2] ), true );
	$missing = bpb_surface_missing( $old, bpb_surface_dump( $bpb_args[1] ) );
	// Intentional removals, decided and documented: surface/removals.json { "<label>": { "<kind>: <item>": "why" } }.
	$removals_file = dirname( $bpb_args[2] ) . '/removals.json';
	$removals      = is_file( $removals_file ) ? json_decode( file_get_contents( $removals_file ), true ) : [];
	$allowed       = $removals[ $old['meta']['label'] ] ?? [];
	foreach ( array_intersect( $missing, array_keys( $allowed ) ) as $item ) {
		echo 'removed on purpose: ' . $item . ' (' . $allowed[ $item ] . ")\n";
	}
	$missing = array_values( array_diff( $missing, array_keys( $allowed ) ) );
	echo ( $missing ? "MISSING\n" . implode( "\n", $missing ) : 'surface ok: ' . json_encode( $old['meta']['counts'] ) ) . "\n";
	exit( $missing ? 1 : 0 );
}

if ( 'runtime' === $bpb_mode && function_exists( 'add_action' ) ) {
	global $shortcode_tags;
	$old     = json_decode( file_get_contents( $bpb_args[1] ), true );
	$missing = [];
	foreach ( $old['functions'] as $f ) {
		if ( ! function_exists( $f ) ) {
			$missing[] = 'function ' . $f;
		}
	}
	if ( class_exists( '\Elementor\Plugin' ) && ! empty( $old['widgets'] ) ) {
		$registered = array_keys( \Elementor\Plugin::instance()->widgets_manager->get_widget_types() );
		foreach ( $old['widgets'] as $w ) {
			if ( ! in_array( $w, $registered, true ) ) {
				$missing[] = 'widget ' . $w;
			}
		}
	}
	foreach ( $old['shortcodes'] as $s ) {
		if ( ! isset( $shortcode_tags[ $s ] ) ) {
			$missing[] = 'shortcode ' . $s;
		}
	}
	// Classes other code extends or calls (14.4), checked after Elementor registered its widgets: autoloading a
	// widget base class earlier is a separate test (see runtime-classes.json "early").
	$classes_file = dirname( $bpb_args[1] ) . '/runtime-classes.json';
	$classes      = is_file( $classes_file ) ? json_decode( file_get_contents( $classes_file ), true ) : [];
	foreach ( $classes[ $old['meta']['label'] ] ?? [] as $c ) {
		if ( ! class_exists( $c ) && ! interface_exists( $c ) ) {
			$missing[] = 'class ' . $c;
		}
	}
	echo ( $missing ? "MISSING\n" . implode( "\n", $missing ) : 'runtime surface ok' ) . "\n";
	if ( $missing && class_exists( 'WP_CLI' ) ) {
		WP_CLI::halt( 1 );
	}
	return;
}

fwrite( STDERR, "Usage: php surface.php dump <dir> [label] | check <dir> <inventory.json> | (wp eval-file) runtime <inventory.json>\n" );
exit( 2 );
