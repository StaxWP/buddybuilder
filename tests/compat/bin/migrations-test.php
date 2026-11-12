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
$by_hand = ! class_exists( '\Buddy_Builder\Migrations\Runner' );
if ( $by_hand ) {
	foreach ( [ 'Compat/Legacy', 'Compat/Pairing', 'Compat/Status', 'Migrations/Step', 'Migrations/Runner', 'Migrations/steps/Registry_2_0_0_A' ] as $f ) {
		require_once $dev . '/src/' . $f . '.php';
	}
}
use Buddy_Builder\Migrations\Runner;
$fails = 0;
// Every option the test touches, restored at the end exactly as found (missing ones deleted).
$touched = [ 'bpb_settings', 'bpb_templates', 'bpb_migrations', 'bpb_migration_backup_2.0.0-a', 'bpb_db_version', 'bpb_migrations_checked', 'bpb_replaced_elementor', Runner::LOCK ];
$snapshot = [];
foreach ( $touched as $name ) { $snapshot[ $name ] = get_option( $name, Runner::MISSING ); }
$orig = get_option( 'bpb_settings' );
foreach ( $touched as $name ) { if ( 'bpb_settings' !== $name ) { delete_option( $name ); } }
// Start from a site where only member-profile (an existing template) and groups-directory (a missing post) are assigned.
$s = is_array( $orig ) ? $orig : []; $s['templates'] = isset( $s['templates'] ) ? array_fill_keys( array_keys( (array) $s['templates'] ), 0 ) : [];
$any = get_posts( [ 'post_type' => 'elementor_library', 'posts_per_page' => 1, 'fields' => 'ids' ] ); $tid = (int) $any[0];
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
add_option( Runner::LOCK, time() ); $r = Runner::run(); $ok( 'locked' === $r[0]['result'], 'lock respected (run)' );
$r = Runner::rollback( '1.9.2' ); $ok( 'locked' === $r[0]['result'], 'lock respected (rollback)' ); delete_option( Runner::LOCK );
update_option( Runner::LOCK, time() - 600 ); $r = Runner::run(); $ok( 'locked' !== $r[0]['result'] && false === get_option( Runner::LOCK, false ), 'stale lock taken over and released' );
// After a rollback the same files do not migrate again; new files (an update) do.
Runner::rollback( '1.9.2' ); $c = (string) get_option( 'bpb_migrations_checked' );
$ok( 0 === strpos( $c, Runner::ROLLED_BACK ), 'rollback marks the installed files: ' . $c );
// Rollback puts back the Elementor template a block layout replaced (BB-11).
delete_option( 'bpb_templates' ); delete_option( 'bpb_migration_backup_2.0.0-a' ); Runner::run();
$bl = wp_insert_post( [ 'post_type' => 'bpb_template', 'post_status' => 'publish', 'post_title' => 'migrations-test', 'post_content' => '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ] );
\Buddy_Builder\Templates\Registry::set( 'member-profile', 'blocks', $bl ); $ok( 0 === (int) get_option( 'bpb_settings' )['templates']['member-profile'], 'block layout writes 0 in bpb_settings' );
Runner::rollback( '1.9.2' ); $ok( $tid === (int) get_option( 'bpb_settings' )['templates']['member-profile'] && false === get_option( 'bpb_replaced_elementor', false ), 'rollback restores the replaced Elementor template' );
wp_delete_post( $bl, true );
// Pairing: the state follows the Pro version installed on this site.
$p = Buddy_Builder\Compat\Pairing::state(); $pro = defined( 'BPB_PRO_VERSION' ) ? (string) BPB_PRO_VERSION : '';
$want = '' === $pro ? 'none' : ( (int) $pro >= 2 ? 'ok' : ( version_compare( $pro, Buddy_Builder\Compat\Pairing::PRO_LEGACY_MIN, '>=' ) ? 'legacy' : 'too-old' ) );
$ok( $want === $p['state'] && $pro === $p['pro'], 'pairing with Pro ' . ( $pro ? $pro : '(none)' ) . ': ' . $p['state'] );
$rows = Buddy_Builder\Compat\Status::templates(); $ok( count( $rows ) >= 5, 'status templates rows ' . count( $rows ) );
// Legacy filter bridge (F6).
// The plugin hooks the bridge on load; only a by-hand load (1.9.2 site) needs init() here, or the old filter runs twice.
if ( $by_hand ) { Buddy_Builder\Compat\Legacy::init(); }
add_filter( 'buddy_builder/page_template ', function ( $v ) { return $v . '+old'; } );
$ok( 'x+old' === apply_filters( 'buddy_builder/page_template', 'x' ), 'old misspelt filter still runs when the new name is applied' );
// Cleanup.
foreach ( $snapshot as $name => $value ) { if ( Runner::MISSING === $value ) { delete_option( $name ); } else { update_option( $name, $value ); } }
if ( $fails ) {
	WP_CLI::halt( 1 );
}
