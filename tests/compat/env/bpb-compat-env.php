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

		foreach ( $map as $type => $id ) {
			$value['templates'][ $type ] = $id;
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

// Deterministic output: local avatars, no heartbeat, no emoji script.
add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );
add_filter( 'get_avatar_url', static function ( $url ) {
	return false !== strpos( $url, 'gravatar.com' ) ? includes_url( 'images/blank.gif' ) : $url;
} );
add_action( 'init', static function () {
	wp_deregister_script( 'heartbeat' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
}, 1 );
