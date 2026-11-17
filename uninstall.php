<?php
/**
 * Uninstall: removes the sample member data, the options, transients and user meta added in 2.0, the 1.x install
 * time and the Appsero tracker data (1.x).
 *
 * Kept on purpose: `bpb_settings` and `bpb_listings` (1.x assignments and list columns, also read by BuddyBuilder Pro
 * and KLEO 6), `buddybuilder_db_version` and `bpb_imported_templates` (1.x upgrade and starter kit records of content
 * that stays), Elementor templates and block layouts (site content).
 *
 * @package Buddy_Builder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Templates/SampleMember.php';
require_once __DIR__ . '/src/Compat/Appsero.php';
require_once __DIR__ . '/src/Upsell/Upsell.php';

/**
 * Clean one site.
 */
function bpb_uninstall_site() {
	global $wpdb;

	// The sample member, its friends, groups and activity (needs BuddyPress loaded for groups and activity).
	\Buddy_Builder\Templates\SampleMember::remove();

	foreach ( bpb_uninstall_options() as $option ) {
		delete_option( $option );
	}

	delete_transient( 'bpb_setup_redirect' );

	// What the Appsero usage tracker (bundled until 1.9.2) stored.
	\Buddy_Builder\Compat\Appsero::remove( dirname( WP_UNINSTALL_PLUGIN ) ); // "<folder>/loader.php".

	// Migration backups and the per user migration reports (transients).
	$prefixes = [ 'bpb_migration_backup_', '_transient_bpb_migration_report_', '_transient_timeout_bpb_migration_report_' ];

	foreach ( $prefixes as $prefix ) {
		$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach ( $names as $option ) {
			delete_option( $option );
		}
	}
}

/**
 * Options of one site BuddyBuilder removes.
 *
 * @return string[]
 */
function bpb_uninstall_options() {
	return [
		'bpb_templates',
		'bpb_migrations',
		'bpb_migrations_checked',
		'bpb_migration_lock',
		'bpb_db_version',
		'bpb_blocks_enabled',
		'bpb_sample_member',
		'bpb_replaced_elementor',
		'bpb_undo',
		'bpb_cards',
		'bpb_render_issues',
		'bpb_setup',
		'bpb_setup_pending',
		'_bpb_installed_time',
		\Buddy_Builder\Upsell\Upsell::SINCE_OPTION,
		\Buddy_Builder\Compat\Appsero::VERSION_OPTION,
	];
}

/**
 * User meta of every user (one users table for the whole network): dismissed notices.
 */
function bpb_uninstall_user_meta() {
	foreach ( [ \Buddy_Builder\Upsell\Upsell::DISMISSED_META, 'bpb_pairing_dismissed' ] as $key ) {
		delete_metadata( 'user', 0, $key, '', true );
	}
}

if ( is_multisite() ) {
	foreach ( get_sites( [ 'fields' => 'ids', 'number' => 0 ] ) as $bpb_site_id ) {
		switch_to_blog( $bpb_site_id );
		bpb_uninstall_site();
		restore_current_blog();
	}
} else {
	bpb_uninstall_site();
}

bpb_uninstall_user_meta();
