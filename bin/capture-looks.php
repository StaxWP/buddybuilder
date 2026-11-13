<?php
/**
 * Prints what bin/capture-looks.js needs, as JSON: login cookies with a fresh session and, for every shipped look,
 * a preview URL of its page (nonce bound to that session). THROWAWAY site only (needs the compat env mu-plugin).
 *
 *   wp eval-file bin/capture-looks.php <admin login>
 *
 * @package Buddy_Builder
 */

if ( ! defined( 'BPB_COMPAT_ENV' ) ) {
	WP_CLI::error( 'Compat env mu-plugin missing: refusing to run on this site.' );
}

$bpb_user = get_user_by( 'login', isset( $args[0] ) ? $args[0] : 'admin' );
if ( ! $bpb_user ) {
	WP_CLI::error( 'Unknown user.' );
}

wp_set_current_user( $bpb_user->ID );

$bpb_exp    = time() + HOUR_IN_SECONDS;
$bpb_token  = WP_Session_Tokens::get_instance( $bpb_user->ID )->create( $bpb_exp );
$bpb_secure = is_ssl();
$bpb_auth   = wp_generate_auth_cookie( $bpb_user->ID, $bpb_exp, $bpb_secure ? 'secure_auth' : 'auth', $bpb_token );
$bpb_login  = wp_generate_auth_cookie( $bpb_user->ID, $bpb_exp, 'logged_in', $bpb_token );

// wp_create_nonce() reads the session token from the logged-in cookie.
$_COOKIE[ LOGGED_IN_COOKIE ] = $bpb_login;

$bpb_sample = \Buddy_Builder\Templates\SampleMember::create();
$bpb_looks  = [];

foreach ( \Buddy_Builder\Presets\Library::presets() as $bpb_id => $bpb_preset ) {
	$bpb_url = \Buddy_Builder\Templates\LayoutPreview::page_url( $bpb_preset['type'], [ $bpb_preset['type'] => 'preset:' . $bpb_id ], true, is_wp_error( $bpb_sample ) ? 0 : (int) $bpb_sample );

	if ( $bpb_url ) {
		$bpb_looks[ $bpb_id ] = $bpb_url;
	}
}

echo "\nBPB_LOOKS " . wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	[
		'cookies' => [
			( $bpb_secure ? SECURE_AUTH_COOKIE : AUTH_COOKIE ) => $bpb_auth,
			LOGGED_IN_COOKIE                                   => $bpb_login,
		],
		'looks'   => $bpb_looks,
	]
) . "\n";
