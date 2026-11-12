<?php
/**
 * Uninstall: removes the sample member data and the options added in 2.0.
 *
 * Kept on purpose: `bpb_settings` (1.x assignments, also read by BuddyBuilder Pro and KLEO 6), Elementor templates
 * and block layouts (site content).
 *
 * @package Buddy_Builder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Templates/SampleMember.php';

/**
 * Clean one site.
 */
function bpb_uninstall_site() {
	global $wpdb;

	// The sample member, its friends, groups and activity (needs BuddyPress loaded for groups and activity).
	\Buddy_Builder\Templates\SampleMember::remove();

	foreach ( [ 'bpb_templates', 'bpb_migrations', 'bpb_migrations_checked', 'bpb_db_version', 'bpb_blocks_enabled', 'bpb_sample_member' ] as $option ) {
		delete_option( $option );
	}

	$backups = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( 'bpb_migration_backup_' ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	foreach ( $backups as $option ) {
		delete_option( $option );
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
