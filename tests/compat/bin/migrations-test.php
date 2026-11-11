<?php
/**
 * Integration test of the migration runner, rollback, pairing and the legacy hook bridge.
 * Runs on a THROWAWAY site (needs the compat env mu-plugin); restores every option it touches.
 *
 *   wp eval-file tests/compat/bin/migrations-test.php [<plugin dir with src/>]
 *
 * Works on 1.9.2 sites too (loads the 2.x files by hand when the plugin did not load them).
 *
 * @package Buddy_Builder\Tests\Compat
 */

if ( ! defined( 'BPB_COMPAT_ENV' ) ) {
	WP_CLI::error( 'Compat env mu-plugin missing: refusing to run on this site.' );
}

$dev = isset( $args[0] ) ? $args[0] : dirname( __DIR__, 3 );
if ( ! class_exists( '\Buddy_Builder\Migrations\Runner' ) ) {
	foreach ( [ 'Compat/Legacy', 'Compat/Pairing', 'Compat/Status', 'Migrations/Step', 'Migrations/Runner', 'Migrations/steps/Registry_2_0_0_A' ] as $f ) {
		require_once $dev . '/src/' . $f . '.php';
	}
}
use Buddy_Builder\Migrations\Runner;
$fails = 0;
$orig = get_option( 'bpb_settings' );
delete_option( 'bpb_templates' ); delete_option( 'bpb_migrations' ); delete_option( 'bpb_migration_backup_2.0.0-a' ); delete_option( 'bpb_db_version' );
$s = $orig; $any = get_posts( [ 'post_type' => 'elementor_library', 'posts_per_page' => 1, 'fields' => 'ids' ] ); $tid = (int) $any[0];
$s['templates']['member-profile'] = $tid; $s['templates']['groups-directory'] = 999999; update_option( 'bpb_settings', $s );
$ok = function ( $c, $m ) use ( &$fails ) { echo ( $c ? 'PASS ' : 'FAIL ' ) . $m . "\n"; $fails += $c ? 0 : 1; };
$r = Runner::run( true ); $ok( 'would-run' === $r[0]['result'] && false === get_option( 'bpb_templates', false ), 'dry run writes nothing' );
$r = Runner::run(); $t = get_option( 'bpb_templates' );
$ok( 'done' === $r[0]['result'] && $t === [ 'member-profile' => [ 'builder' => 'elementor', 'id' => $tid ] ], 'run: registry from bpb_settings, missing post skipped: ' . wp_json_encode( $t ) );
$ok( '2.0.0' === get_option( 'bpb_db_version' ), 'db version 2.0.0' );
$ok( get_option( 'bpb_settings' ) === $s, 'bpb_settings untouched' );
$r = Runner::run(); $ok( 'skipped' === $r[0]['result'] && get_option( 'bpb_templates' ) === $t, 'idempotent second run' );
$t2 = $t; $t2['group-profile'] = [ 'builder' => 'blocks', 'id' => 5 ]; update_option( 'bpb_templates', $t2 );
$s2 = $s; $s2['templates']['group-profile'] = $tid; update_option( 'bpb_settings', $s2 );
$r = Runner::run(); $ok( get_option( 'bpb_templates' )['group-profile']['builder'] === 'blocks', 'never overwrites an existing registry entry' );
$st = Runner::status(); $ok( 'done' === $st[0]['state'], 'status done' );
$r = Runner::rollback( '1.9.2', true ); $ok( 'would-roll-back' === $r[0]['result'] && get_option( 'bpb_templates' ), 'rollback dry run writes nothing' );
$r = Runner::rollback( '1.9.2' ); $ok( 'rolled_back' === $r[0]['result'] && false === get_option( 'bpb_templates', false ), 'rollback deletes registry (was missing before)' );
$ok( '1.9.2' === get_option( 'bpb_db_version' ) && get_option( 'bpb_settings' ) === $s2, 'rollback: db version 1.9.2, bpb_settings intact' );
$ok( 'pending' === Runner::status()[0]['state'] || 'rolled_back' === Runner::status()[0]['state'], 'status after rollback: ' . Runner::status()[0]['state'] );
// Rollback restores a pre-existing value.
update_option( 'bpb_templates', [ 'x' => [ 'builder' => 'blocks', 'id' => 1 ] ] ); delete_option( 'bpb_migration_backup_2.0.0-a' );
Runner::run(); Runner::rollback( '1.9.2' ); $ok( get_option( 'bpb_templates' ) === [ 'x' => [ 'builder' => 'blocks', 'id' => 1 ] ], 'rollback restores the previous registry value' );
set_transient( Runner::LOCK, 1, 60 ); $r = Runner::run(); $ok( 'locked' === $r[0]['result'], 'lock respected' ); delete_transient( Runner::LOCK );
$p = Buddy_Builder\Compat\Pairing::state(); $ok( 'legacy' === $p['state'] && '1.7.0' === $p['pro'], 'pairing with Pro 1.7.0: legacy' );
$rows = Buddy_Builder\Compat\Status::templates(); $ok( count( $rows ) >= 5, 'status templates rows ' . count( $rows ) );
// Legacy filter bridge (F6).
Buddy_Builder\Compat\Legacy::init(); add_filter( 'buddy_builder/page_template ', function ( $v ) { return $v . '+old'; } );
$ok( 'x+old' === apply_filters( 'buddy_builder/page_template', 'x' ), 'old misspelt filter still runs when the new name is applied' );
// Cleanup.
update_option( 'bpb_settings', $orig ); delete_option( 'bpb_templates' ); delete_option( 'bpb_migrations' ); delete_option( 'bpb_db_version' ); delete_option( 'bpb_migration_backup_2.0.0-a' );
if ( $fails ) {
	WP_CLI::halt( 1 );
}
