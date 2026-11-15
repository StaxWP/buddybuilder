<?php
/**
 * Plugin Name: BuddyBuilder compat env (test sites only)
 * Description: Test hooks for the BuddyBuilder compatibility suite (tests/compat). Never ship or install on a real site.
 *
 * Installed as a mu-plugin on the throwaway compat site by tests/compat/bin/setup-env.sh.
 *
 * - Cookie `bpb_compat_tpl` = "type:id,type:id": assigns those templates for this request only (filters
 *   `bpb_settings` and the 2.0 registry option `bpb_templates`), so one site can render every fixture without
 *   writing options between requests.
 * - Cookie `bpb_compat_split=1` turns on the per template type CSS (filter `buddy_builder/split_css`).
 * - Collects PHP notices, warnings and deprecations raised from BuddyBuilder files and prints them in an HTML
 *   comment at the end of the page (`<!-- bpb-compat-notices [...] -->`), so each request reports its own.
 * - Local avatars only (no Gravatar calls), no heartbeat: deterministic screenshots.
 *
 * @package Buddy_Builder\Tests\Compat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) || 'local' !== WP_ENVIRONMENT_TYPE ) {
	return;
}

define( 'BPB_COMPAT_ENV', true );

/**
 * Template assignment from the cookie: [ type => id ], or null when the cookie is absent.
 *
 * @return array|null
 */
function bpb_compat_env_templates() {
	static $map = false;

	if ( false !== $map ) {
		return $map;
	}

	$map = null;

	if ( isset( $_COOKIE['bpb_compat_tpl'] ) ) {
		$map = [];
		foreach ( explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['bpb_compat_tpl'] ) ) ) as $pair ) {
			$parts = explode( ':', $pair );
			if ( 2 === count( $parts ) && '' !== $parts[0] ) {
				$map[ sanitize_key( $parts[0] ) ] = (int) $parts[1];
			}
		}
	}

	return $map;
}

add_filter(
	'option_bpb_settings',
	static function ( $value ) {
		$map = bpb_compat_env_templates();

		if ( null === $map ) {
			return $value;
		}

		$value = is_array( $value ) ? $value : [];
		$value['templates'] = isset( $value['templates'] ) && is_array( $value['templates'] ) ? $value['templates'] : [];

		foreach ( array_keys( $value['templates'] ) as $type ) {
			$value['templates'][ $type ] = 0;
		}

		// Block layouts live in bpb_templates only (Registry::set() leaves the legacy id empty for them).
		foreach ( $map as $type => $id ) {
			$value['templates'][ $type ] = ( $id && 'bpb_template' === get_post_type( $id ) ) ? 0 : $id;
		}

		return $value;
	},
	PHP_INT_MAX
);

// 2.0 registry: same assignment, builder Elementor (fixtures are Elementor templates saved on 1.9.2).
add_filter(
	'pre_option_bpb_templates',
	static function ( $pre ) {
		$map = bpb_compat_env_templates();

		if ( null === $map ) {
			return $pre;
		}

		$out = [];
		foreach ( $map as $type => $id ) {
			$builder      = ( $id && 'bpb_template' === get_post_type( $id ) ) ? 'blocks' : 'elementor';
			$out[ $type ] = [
				'builder' => $builder,
				'id'      => $id,
			];
		}

		return $out;
	},
	PHP_INT_MAX
);

// Cookie `bpb_compat_split=1|0`: per template type CSS on or off (default on since 2.0).
if ( isset( $_COOKIE['bpb_compat_split'] ) ) {
	add_filter( 'buddy_builder/split_css', '1' === $_COOKIE['bpb_compat_split'] ? '__return_true' : '__return_false' );
}

// Notices from BuddyBuilder files, printed per request.
$GLOBALS['bpb_compat_notices'] = [];

$bpb_compat_previous_handler = set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
	static function ( $errno, $errstr, $errfile = '', $errline = 0 ) use ( &$bpb_compat_previous_handler ) {
		if ( false !== strpos( $errfile, 'stax-buddy-builder' ) || false !== strpos( $errstr, 'stax-buddy-builder' ) || false !== strpos( $errstr, 'buddy_builder' ) || false !== strpos( $errstr, 'bpb_' ) ) {
			$file = preg_replace( '#^.*/plugins/#', '', $errfile );

			$GLOBALS['bpb_compat_notices'][] = $errno . ' ' . $errstr . ' @ ' . $file . ':' . $errline;
		}

		if ( $bpb_compat_previous_handler ) {
			return call_user_func( $bpb_compat_previous_handler, $errno, $errstr, $errfile, $errline );
		}

		return false;
	}
);

add_action(
	'shutdown',
	static function () {
		$error = error_get_last();
		if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) && false !== strpos( $error['file'], 'stax-buddy-builder' ) ) {
			$GLOBALS['bpb_compat_notices'][] = 'FATAL ' . $error['message'] . ' @ ' . preg_replace( '#^.*/plugins/#', '', $error['file'] ) . ':' . $error['line'];
		}

		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() && ! isset( $_COOKIE['bpb_compat_tpl'] ) ) {
			return;
		}

		echo "\n<!-- bpb-compat-notices " . wp_json_encode( array_values( array_unique( $GLOBALS['bpb_compat_notices'] ) ) ) . " -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	PHP_INT_MAX
);

/**
 * Front-end assets printed on this page by BuddyBuilder, BuddyBuilder Pro and K Elements, for the budget check
 * (tests/compat/budgets.js): handle, source, and whether the handle needs jQuery (directly or through its deps).
 * Printed as `<!-- bpb-compat-assets {...} -->` next to the notices.
 *
 * @return array
 */
function bpb_compat_env_assets() {
	$owners = [
		'buddybuilder' => '/plugins/stax-buddy-builder/',
		'pro'          => '/plugins/stax-buddy-builder-pro/',
		'k-elements'   => '/plugins/k-elements/',
	];
	$out    = [
		'js'        => [],
		'css'       => [],
		'dashicons' => false,
	];

	foreach ( [ 'js' => wp_scripts(), 'css' => wp_styles() ] as $kind => $deps ) {
		$needs_jquery = static function ( $handle, $seen = [] ) use ( &$needs_jquery, $deps ) {
			if ( in_array( $handle, [ 'jquery', 'jquery-core', 'jquery-migrate' ], true ) ) {
				return true;
			}
			if ( isset( $seen[ $handle ] ) || ! isset( $deps->registered[ $handle ] ) ) {
				return false;
			}
			$seen[ $handle ] = true;
			foreach ( (array) $deps->registered[ $handle ]->deps as $dep ) {
				if ( $needs_jquery( $dep, $seen ) ) {
					return true;
				}
			}
			return false;
		};

		foreach ( (array) $deps->done as $handle ) {
			if ( 'css' === $kind && 'dashicons' === $handle ) {
				$out['dashicons'] = true;
			}
			if ( ! isset( $deps->registered[ $handle ] ) || ! is_string( $deps->registered[ $handle ]->src ) ) {
				continue;
			}
			$src = $deps->registered[ $handle ]->src;
			foreach ( $owners as $owner => $needle ) {
				if ( false !== strpos( $src, $needle ) ) {
					$out[ $kind ][] = [
						'handle' => $handle,
						'owner'  => $owner,
						'src'    => 0 === strpos( $src, '/' ) && 0 !== strpos( $src, '//' ) ? site_url( $src ) : $src,
						'jquery' => 'js' === $kind ? $needs_jquery( $handle ) : false,
					];
				}
			}
		}
	}

	return $out;
}

add_action(
	'shutdown',
	static function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ! did_action( 'wp_footer' ) ) {
			return;
		}
		echo "\n<!-- bpb-compat-assets " . wp_json_encode( bpb_compat_env_assets() ) . " -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	PHP_INT_MAX - 1
);

// Deterministic output: local avatars, no heartbeat, no emoji script.
add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
add_filter( 'get_avatar_url', static function ( $url ) {
	return false !== strpos( $url, 'gravatar.com' ) ? includes_url( 'images/blank.gif' ) : $url;
} );
// No activity heartbeat: BuddyPress would make its scripts depend on the deregistered heartbeat script on the activity
// directory and group activity, and then print none of them (the stream never loaded there before 2026-11-15).
add_filter( 'bp_activity_do_heartbeat', '__return_false' );
add_action( 'init', static function () {
	wp_deregister_script( 'heartbeat' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
}, 1 );
