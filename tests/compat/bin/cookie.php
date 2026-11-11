<?php
/**
 * Prints auth cookies for a test user as JSON (throwaway compat site only).
 *
 *   wp eval-file tests/compat/bin/cookie.php <login>
 *
 * @package Buddy_Builder\Tests\Compat
 */

if ( ! defined( 'BPB_COMPAT_ENV' ) ) {
	WP_CLI::error( 'Compat env mu-plugin missing: refusing to run on this site.' );
}

$bpb_user = get_user_by( 'login', isset( $args[0] ) ? $args[0] : 'mihai' );
if ( ! $bpb_user ) {
	WP_CLI::error( 'Unknown user.' );
}

$bpb_exp    = time() + 6 * HOUR_IN_SECONDS;
$bpb_secure = is_ssl() || 0 === strpos( home_url(), 'https' );
$bpb_scheme = $bpb_secure ? 'secure_auth' : 'auth';

echo "\nBPB_COMPAT_COOKIES " . wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	[
		( $bpb_secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE ) => wp_generate_auth_cookie( $bpb_user->ID, $bpb_exp, $bpb_scheme ),
		LOGGED_IN_COOKIE                                  => wp_generate_auth_cookie( $bpb_user->ID, $bpb_exp, 'logged_in' ),
	]
) . "\n";
