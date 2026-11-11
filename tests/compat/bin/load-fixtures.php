<?php
/**
 * Loads the compatibility fixtures into a throwaway test site and prints the run plan as JSON.
 *
 *   wp eval-file tests/compat/bin/load-fixtures.php [<fixtures dir>]
 *
 * Idempotent: fixtures are found again by the meta `_bpb_compat_fixture`, test data by login/slug.
 * - Every fixture becomes an `elementor_library` post of type `bpb-buddypress` with `_bpb_page` (exactly what
 *   1.9.2 saves), except `widgets-anywhere`, which becomes an Elementor page.
 * - Seeds deterministic BuddyPress data: members ana, mihai (mihai is a friend of ana), group `compat-group`
 *   (ana admin, mihai member), one activity per member, registration on with a register page.
 * - Never touches options other than registration and BuddyPress pages; template assignment happens per request
 *   through the `bpb_compat_tpl` cookie (env/bpb-compat-env.php).
 *
 * @package Buddy_Builder\Tests\Compat
 */

if ( ! defined( 'BPB_COMPAT_ENV' ) ) {
	WP_CLI::error( 'tests/compat/env/bpb-compat-env.php is not installed as a mu-plugin: refusing to run on this site.' );
}

$bpb_dir = isset( $args[0] ) ? $args[0] : dirname( __DIR__ ) . '/fixtures';

/**
 * User id by login, created when missing.
 *
 * @param string $login Login.
 * @param string $name  Display name.
 * @return int
 */
function bpb_compat_user( $login, $name ) {
	$user = get_user_by( 'login', $login );
	if ( $user ) {
		return (int) $user->ID;
	}

	return (int) wp_insert_user(
		[
			'user_login'   => $login,
			'user_pass'    => wp_generate_password( 24 ),
			'user_email'   => $login . '@example.invalid',
			'display_name' => $name,
		]
	);
}

$bpb_ana   = bpb_compat_user( 'ana', 'Ana Popescu' );
$bpb_mihai = bpb_compat_user( 'mihai', 'Mihai Ionescu' );

// Fixed last activity: relative dates stay the same during a run.
foreach ( [ $bpb_ana, $bpb_mihai ] as $bpb_uid ) {
	bp_update_user_last_activity( $bpb_uid, '2025-01-01 10:00:00' );
}

if ( bp_is_active( 'friends' ) && ! friends_check_friendship( $bpb_ana, $bpb_mihai ) ) {
	friends_add_friend( $bpb_ana, $bpb_mihai, true );
}

$bpb_group_id = 0;
if ( bp_is_active( 'groups' ) ) {
	$bpb_group_id = (int) groups_get_id( 'compat-group' );
	if ( ! $bpb_group_id ) {
		$bpb_group_id = (int) groups_create_group(
			[
				'creator_id'   => $bpb_ana,
				'name'         => 'Compat Group',
				'slug'         => 'compat-group',
				'description'  => 'Fixture group for the BuddyBuilder compatibility suite.',
				'status'       => 'public',
				'enable_forum' => 0,
				'date_created' => '2025-01-01 10:00:00',
			]
		);
		groups_update_groupmeta( $bpb_group_id, 'last_activity', '2025-01-01 10:00:00' );
	}
	if ( ! groups_is_user_member( $bpb_mihai, $bpb_group_id ) ) {
		groups_join_group( $bpb_group_id, $bpb_mihai );
	}
	groups_update_groupmeta( $bpb_group_id, 'last_activity', '2025-01-01 10:00:00' );
	groups_update_groupmeta( $bpb_group_id, 'total_member_count', 2 );
}

if ( bp_is_active( 'activity' ) ) {
	foreach ( [ $bpb_ana => 'Hello from Ana (compat fixture).', $bpb_mihai => 'Hello from Mihai (compat fixture).' ] as $bpb_uid => $bpb_text ) {
		$bpb_existing = bp_activity_get(
			[
				'filter'      => [
					'user_id' => $bpb_uid,
					'action'  => 'activity_update',
				],
				'search_terms' => $bpb_text,
				'per_page'    => 1,
			]
		);
		if ( empty( $bpb_existing['activities'] ) ) {
			bp_activity_add(
				[
					'user_id'       => $bpb_uid,
					'component'     => 'activity',
					'type'          => 'activity_update',
					'content'       => $bpb_text,
					'primary_link'  => bp_members_get_user_url( $bpb_uid ),
					'recorded_time' => '2025-01-01 10:00:00',
					'action'        => '',
				]
			);
		}
	}
}

// Fixed dates for every activity of the test members (friendship items are created "now"): order kept by id.
if ( bp_is_active( 'activity' ) ) {
	global $wpdb;
	$bpb_table = buddypress()->activity->table_name;
	$wpdb->query( $wpdb->prepare( "UPDATE {$bpb_table} SET date_recorded = DATE_ADD( '2025-01-01 10:00:00', INTERVAL id SECOND ) WHERE user_id IN ( %d, %d )", $bpb_ana, $bpb_mihai ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	wp_cache_flush();
}

// Registration and the register/activate pages (register-page fixtures).
update_option( 'users_can_register', 1 );
$bpb_pages = (array) get_option( 'bp-pages', [] );
foreach ( [ 'register' => 'Register', 'activate' => 'Activate' ] as $bpb_key => $bpb_title ) {
	if ( empty( $bpb_pages[ $bpb_key ] ) || ! get_post( $bpb_pages[ $bpb_key ] ) ) {
		$bpb_pages[ $bpb_key ] = wp_insert_post(
			[
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => $bpb_title,
				'post_name'   => $bpb_key,
			]
		);
	}
}
update_option( 'bp-pages', $bpb_pages );

// Fixtures.
$bpb_plan = [];
foreach ( glob( trailingslashit( $bpb_dir ) . '*.json' ) as $bpb_file ) {
	$bpb_fixture = json_decode( file_get_contents( $bpb_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	if ( empty( $bpb_fixture['name'] ) || ! isset( $bpb_fixture['elementor_data'] ) ) {
		WP_CLI::warning( 'Skipping ' . basename( $bpb_file ) );
		continue;
	}

	$bpb_is_page = 'anywhere' === $bpb_fixture['type'];
	$bpb_found   = get_posts(
		[
			'post_type'      => [ 'elementor_library', 'page' ],
			'post_status'    => 'any',
			'meta_key'       => '_bpb_compat_fixture', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $bpb_fixture['name'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'posts_per_page' => 1,
			'fields'         => 'ids',
		]
	);

	$bpb_postarr = [
		'post_type'   => $bpb_is_page ? 'page' : 'elementor_library',
		'post_status' => 'publish',
		'post_title'  => 'Compat: ' . $bpb_fixture['title'],
		'post_name'   => 'bpb-compat-' . $bpb_fixture['name'],
	];
	if ( $bpb_found ) {
		$bpb_postarr['ID'] = $bpb_found[0];
	}
	$bpb_id = wp_insert_post( $bpb_postarr );

	update_post_meta( $bpb_id, '_bpb_compat_fixture', $bpb_fixture['name'] );
	update_post_meta( $bpb_id, '_elementor_edit_mode', 'builder' );
	update_post_meta( $bpb_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.20.0' );
	update_post_meta( $bpb_id, '_elementor_data', wp_slash( wp_json_encode( $bpb_fixture['elementor_data'] ) ) );
	delete_post_meta( $bpb_id, '_elementor_css' );

	if ( $bpb_is_page ) {
		update_post_meta( $bpb_id, '_wp_page_template', 'elementor_header_footer' );
	} else {
		update_post_meta( $bpb_id, '_elementor_template_type', 'bpb-buddypress' );
		update_post_meta( $bpb_id, '_bpb_page', $bpb_fixture['type'] );
		wp_set_object_terms( $bpb_id, 'bpb-buddypress', 'elementor_library_type' );
	}

	$bpb_plan[] = [
		'fixture' => $bpb_fixture['name'],
		'type'    => $bpb_fixture['type'],
		'id'      => $bpb_id,
		'url'     => $bpb_is_page ? get_permalink( $bpb_id ) : '',
	];
}

// Files written by Elementor for old CSS must not leak between versions.
if ( class_exists( '\Elementor\Plugin' ) ) {
	\Elementor\Plugin::$instance->files_manager->clear_cache();
}

echo "\nBPB_COMPAT_PLAN " . wp_json_encode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	[
		'users'   => [
			'ana'   => $bpb_ana,
			'mihai' => $bpb_mihai,
		],
		'group'   => $bpb_group_id,
		'members' => bp_get_members_directory_permalink(),
		'plan'    => $bpb_plan,
	]
) . "\n";
