<?php
/**
 * Seeds a realistic sample community on a THROWAWAY site, for look design, screenshots and docs:
 * 14 members (avatar, cover, headline, location, about, website), friendships, 6 groups (avatar, cover,
 * description, members), and about 40 activity updates and comments.
 *
 *   wp eval-file bin/sample-community.php <media dir from bin/sample-media.js> [--remove]
 *
 * Idempotent (members by login "demo-*", groups by slug "demo-*"). --remove deletes everything it created.
 * Never run on a real site: it refuses unless WP_ENVIRONMENT_TYPE is "local".
 *
 * @package Buddy_Builder
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped

if ( 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Local test sites only.' );
}

$bpb_media  = isset( $args[0] ) ? rtrim( $args[0], '/' ) : '';
$bpb_remove = in_array( '--remove', $args, true );

$bpb_people = [
	[ 'maya', 'Maya Okafor', 'Product designer at Lumen', 'Lagos, Nigeria', 'Designs calm software for busy people. Runs the Friday critique circle.' ],
	[ 'lucas', 'Lucas Moreau', 'Indie developer', 'Lyon, France', 'Building small, useful apps in public. Ask me about offline-first.' ],
	[ 'sofia', 'Sofia Lindqvist', 'Illustrator and children\'s book author', 'Uppsala, Sweden', 'Draws foxes, mostly. Two books out, a third on the way.' ],
	[ 'arjun', 'Arjun Mehta', 'Data engineer', 'Bengaluru, India', 'Pipelines by day, synth music by night.' ],
	[ 'hana', 'Hana Sato', 'UX researcher', 'Kyoto, Japan', 'I talk to users so you do not have to guess. Coffee nerd.' ],
	[ 'diego', 'Diego Alvarez', 'Community manager', 'Mexico City, Mexico', 'Hosts the monthly AMA. Welcome, new folks!' ],
	[ 'amira', 'Amira Haddad', 'Frontend engineer', 'Amman, Jordan', 'Accessibility first. CSS is a love language.' ],
	[ 'tom', 'Tom Becker', 'Photographer', 'Hamburg, Germany', 'Harbour light and street portraits. Prints on request.' ],
	[ 'lena', 'Lena Novak', 'Brand strategist', 'Ljubljana, Slovenia', 'Helping small studios find their voice.' ],
	[ 'kwame', 'Kwame Mensah', 'Founder, Fieldnotes', 'Accra, Ghana', 'Building tools for field researchers. Hiring soon.' ],
	[ 'isabel', 'Isabel Costa', 'Motion designer', 'Porto, Portugal', 'Makes interfaces move just enough.' ],
	[ 'noah', 'Noah Fischer', 'Student, interaction design', 'Zurich, Switzerland', 'Learning out loud. Portfolio feedback welcome.' ],
	[ 'priya', 'Priya Raman', 'Engineering manager', 'Toronto, Canada', 'Writes about teams, focus and good meetings.' ],
	[ 'samuel', 'Samuel Park', 'Podcaster, "Side Projects"', 'Seoul, South Korea', 'Interviewing makers about the things they build on weekends.' ],
];

$bpb_groups = [
	[ 'design-critique', 'Design Critique Circle', 'Share work in progress and get kind, specific feedback every Friday.', 'public', [ 'maya', 'sofia', 'hana', 'amira', 'isabel', 'noah', 'lena' ] ],
	[ 'indie-builders', 'Indie Builders', 'For people shipping their own products: launches, numbers, lessons.', 'public', [ 'lucas', 'kwame', 'samuel', 'arjun', 'priya', 'maya' ] ],
	[ 'photo-walks', 'Photo Walks', 'Monthly city walks with a camera. Everyone welcome, phones too.', 'public', [ 'tom', 'diego', 'sofia', 'isabel', 'noah' ] ],
	[ 'frontend-guild', 'Frontend Guild', 'CSS, accessibility and the craft of the web platform.', 'public', [ 'amira', 'lucas', 'arjun', 'noah', 'priya' ] ],
	[ 'research-ops', 'Research Ops', 'Methods, templates and recruiting tips for user research.', 'private', [ 'hana', 'maya', 'kwame', 'lena' ] ],
	[ 'cohort-spring', 'Spring Cohort 2026', 'Eight weeks, one portfolio project, weekly check-ins.', 'public', [ 'noah', 'isabel', 'sofia', 'diego', 'amira', 'tom' ] ],
];

$bpb_posts = [
	'maya'   => [ 'Friday critique is open! Drop your work in progress in the Design Critique Circle, we start at 4pm.', 'Small thing that made my week: a user wrote "it just felt calm". That is the whole brief.' ],
	'lucas'  => [ 'Shipped offline sync for Notekeeper today. 9 months of edge cases, 1 commit message: "it works".', 'Revenue update: 412 paying users, churn down to 3%. Thank you all for the honest feedback.' ],
	'sofia'  => [ 'New fox sketches for book three. Which one should get the red scarf?', 'Watercolour or gouache for night scenes? Asking for a fox.' ],
	'arjun'  => [ 'Wrote up how we cut our nightly pipeline from 3 hours to 25 minutes. Mostly by deleting things.' ],
	'hana'   => [ 'Five interviews this week taught me more than six months of analytics. Talk to your users.', 'Sharing my recruiting screener template in Research Ops.' ],
	'diego'  => [ 'Welcome to the 12 new members who joined this week! Say hi below and tell us what you are building.', 'This month\'s AMA: Priya on running calm engineering teams. Thursday, 6pm CET.' ],
	'amira'  => [ 'Reminder: :focus-visible is your friend. Keyboard users will thank you.', 'Container queries finally clicked for me. Rebuilt our card grid in 40 lines.' ],
	'tom'    => [ 'Harbour at 6am, fog lifting. Photo walk next Saturday, meet at the old crane.' ],
	'lena'   => [ 'A brand is a promise you keep in small places: the error message, the invoice, the empty state.' ],
	'kwame'  => [ 'Fieldnotes is looking for 5 beta testers who do research in places with bad internet.' ],
	'isabel' => [ 'A 120ms ease-out beats any fancy spring for most UI motion. Fight me (kindly).' ],
	'noah'   => [ 'Week 3 of the Spring Cohort: first usability test done. Terrifying and great.' ],
	'priya'  => [ 'Meetings without a written goal get cancelled on my team. Calendars are 30% lighter.' ],
	'samuel' => [ 'New episode: Maya on designing calm software. Link in my profile.' ],
];

/**
 * Login of a sample member.
 *
 * @param string $slug Short name.
 * @return string
 */
function bpb_sample_login( $slug ) {
	return 'demo-' . $slug;
}

if ( $bpb_remove ) {
	foreach ( $bpb_groups as $bpb_g ) {
		$bpb_gid = groups_get_id( 'demo-' . $bpb_g[0] );
		if ( $bpb_gid ) {
			groups_delete_group( $bpb_gid );
		}
	}
	require_once ABSPATH . 'wp-admin/includes/user.php';
	foreach ( $bpb_people as $bpb_p ) {
		$bpb_u = get_user_by( 'login', bpb_sample_login( $bpb_p[0] ) );
		if ( $bpb_u ) {
			wp_delete_user( $bpb_u->ID );
		}
	}
	WP_CLI::success( 'Sample community removed.' );
	return;
}

if ( ! $bpb_media || ! is_dir( $bpb_media ) ) {
	WP_CLI::error( 'Pass the media dir made by bin/sample-media.js.' );
}

// xProfile fields used by the looks: Headline, Location, About, Website.
$bpb_fields = [];
if ( bp_is_active( 'xprofile' ) ) {
	foreach ( [ 'Headline' => 'textbox', 'Location' => 'textbox', 'About' => 'textarea', 'Website' => 'url' ] as $bpb_name => $bpb_type ) {
		$bpb_fid = xprofile_get_field_id_from_name( $bpb_name );
		if ( ! $bpb_fid ) {
			$bpb_fid = xprofile_insert_field(
				[
					'field_group_id' => 1,
					'name'           => $bpb_name,
					'type'           => $bpb_type,
					'can_delete'     => true,
				]
			);
		}
		$bpb_fields[ $bpb_name ] = (int) $bpb_fid;
	}
}

/**
 * Copy an image as a BuddyPress avatar (full + thumb) for a member or a group.
 *
 * @param string $object members|groups.
 * @param int    $id     Item id.
 * @param string $file   Source image.
 */
function bpb_sample_avatar( $object, $id, $file ) {
	$dir = bp_core_avatar_upload_path() . '/' . ( 'members' === $object ? 'avatars' : 'group-avatars' ) . '/' . $id;
	wp_mkdir_p( $dir );
	foreach ( glob( $dir . '/*' ) as $old ) {
		unlink( $old );
	}
	$hash = wp_hash( $object . $id . time() );
	foreach ( [ 'bpfull' => bp_core_avatar_full_width(), 'bpthumb' => bp_core_avatar_thumb_width() ] as $suffix => $size ) {
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			continue;
		}
		$editor->resize( max( $size, 300 ), max( $size, 300 ), true );
		$editor->save( $dir . '/' . $hash . '-' . $suffix . '.jpg', 'image/jpeg' );
	}
}

/**
 * Copy an image as a BuddyPress cover image.
 *
 * @param string $object members|groups.
 * @param int    $id     Item id.
 * @param string $file   Source image.
 */
function bpb_sample_cover( $object, $id, $file ) {
	$dir = bp_attachments_uploads_dir_get( 'basedir' ) . '/' . $object . '/' . $id . '/cover-image';
	wp_mkdir_p( $dir );
	foreach ( glob( $dir . '/*' ) as $old ) {
		unlink( $old );
	}
	copy( $file, $dir . '/' . wp_hash( $object . $id ) . '-cover-image.jpg' ); // BuddyPress finds the file by "cover-image" in its name.
}

$bpb_ids = [];
$bpb_i   = 0;
foreach ( $bpb_people as $bpb_p ) {
	list( $bpb_slug, $bpb_name, $bpb_headline, $bpb_loc, $bpb_about ) = $bpb_p;
	$bpb_login = bpb_sample_login( $bpb_slug );
	$bpb_user  = get_user_by( 'login', $bpb_login );
	$bpb_uid   = $bpb_user ? (int) $bpb_user->ID : (int) wp_insert_user(
		[
			'user_login'    => $bpb_login,
			'user_nicename' => $bpb_slug,
			'user_pass'     => wp_generate_password( 24 ),
			'user_email'    => $bpb_login . '@example.invalid',
			'display_name'  => $bpb_name,
		]
	);
	$bpb_ids[ $bpb_slug ] = $bpb_uid;
	xprofile_set_field_data( 1, $bpb_uid, $bpb_name );
	if ( $bpb_fields ) {
		xprofile_set_field_data( $bpb_fields['Headline'], $bpb_uid, $bpb_headline );
		xprofile_set_field_data( $bpb_fields['Location'], $bpb_uid, $bpb_loc );
		xprofile_set_field_data( $bpb_fields['About'], $bpb_uid, $bpb_about );
		xprofile_set_field_data( $bpb_fields['Website'], $bpb_uid, 'https://example.com/' . $bpb_slug );
	}
	++$bpb_i;
	bpb_sample_avatar( 'members', $bpb_uid, sprintf( '%s/avatar-%02d.jpg', $bpb_media, ( ( $bpb_i - 1 ) % 16 ) + 1 ) );
	bpb_sample_cover( 'members', $bpb_uid, sprintf( '%s/cover-%02d.jpg', $bpb_media, ( ( $bpb_i - 1 ) % 16 ) + 1 ) );
	bp_update_user_last_activity( $bpb_uid, gmdate( 'Y-m-d H:i:s', time() - $bpb_i * 1700 ) );
}

// Friendships: a ring plus a few extra links, so counts differ.
$bpb_slugs = array_keys( $bpb_ids );
foreach ( $bpb_slugs as $bpb_k => $bpb_s ) {
	foreach ( [ 1, 3, 5 ] as $bpb_step ) {
		$bpb_other = $bpb_slugs[ ( $bpb_k + $bpb_step ) % count( $bpb_slugs ) ];
		if ( bp_is_active( 'friends' ) && ! friends_check_friendship( $bpb_ids[ $bpb_s ], $bpb_ids[ $bpb_other ] ) && ( $bpb_k % 3 || 1 === $bpb_step ) ) {
			friends_add_friend( $bpb_ids[ $bpb_s ], $bpb_ids[ $bpb_other ], true );
		}
	}
}

$bpb_gi = 0;
foreach ( $bpb_groups as $bpb_g ) {
	list( $bpb_gslug, $bpb_gname, $bpb_gdesc, $bpb_status, $bpb_members ) = $bpb_g;
	$bpb_gid = (int) groups_get_id( 'demo-' . $bpb_gslug );
	if ( ! $bpb_gid ) {
		$bpb_gid = (int) groups_create_group(
			[
				'creator_id'  => $bpb_ids[ $bpb_members[0] ],
				'name'        => $bpb_gname,
				'slug'        => 'demo-' . $bpb_gslug,
				'description' => $bpb_gdesc,
				'status'      => $bpb_status,
			]
		);
	}
	foreach ( $bpb_members as $bpb_m ) {
		if ( ! groups_is_user_member( $bpb_ids[ $bpb_m ], $bpb_gid ) ) {
			groups_join_group( $bpb_gid, $bpb_ids[ $bpb_m ] );
		}
	}
	groups_update_groupmeta( $bpb_gid, 'total_member_count', count( $bpb_members ) );
	groups_update_groupmeta( $bpb_gid, 'last_activity', gmdate( 'Y-m-d H:i:s', time() - $bpb_gi * 5400 ) );
	++$bpb_gi;
	bpb_sample_avatar( 'groups', $bpb_gid, sprintf( '%s/cover-%02d.jpg', $bpb_media, ( ( $bpb_gi + 7 ) % 16 ) + 1 ) );
	bpb_sample_cover( 'groups', $bpb_gid, sprintf( '%s/cover-%02d.jpg', $bpb_media, ( ( $bpb_gi + 8 ) % 16 ) + 1 ) );
}

// Activity: one or two updates per member, a few comments, spread over the last days.
if ( bp_is_active( 'activity' ) ) {
	$bpb_n = 0;
	foreach ( $bpb_posts as $bpb_slug => $bpb_texts ) {
		foreach ( $bpb_texts as $bpb_text ) {
			$bpb_found = bp_activity_get(
				[
					'filter'       => [ 'user_id' => $bpb_ids[ $bpb_slug ] ],
					'search_terms' => substr( $bpb_text, 0, 40 ),
					'per_page'     => 1,
				]
			);
			if ( ! empty( $bpb_found['activities'] ) ) {
				continue;
			}
			++$bpb_n;
			$bpb_aid = bp_activity_post_update(
				[
					'user_id' => $bpb_ids[ $bpb_slug ],
					'content' => $bpb_text,
				]
			);
			if ( $bpb_aid ) {
				bp_activity_update_meta( $bpb_aid, 'favorite_count', $bpb_n % 7 );
				if ( 0 === $bpb_n % 3 ) {
					$bpb_by = $bpb_slugs[ ( $bpb_n * 5 ) % count( $bpb_slugs ) ];
					bp_activity_new_comment(
						[
							'activity_id' => $bpb_aid,
							'user_id'     => $bpb_ids[ $bpb_by ],
							'content'     => 'Love this. Saving it for later!',
						]
					);
				}
			}
		}
	}
	global $wpdb;
	$bpb_table = buddypress()->activity->table_name;
	$bpb_in    = implode( ',', array_map( 'intval', $bpb_ids ) );
	$wpdb->query( "UPDATE {$bpb_table} SET date_recorded = DATE_SUB( UTC_TIMESTAMP(), INTERVAL ( 1000 - id % 1000 ) * 17 MINUTE ) WHERE user_id IN ( {$bpb_in} )" ); // phpcs:ignore WordPress.DB
	wp_cache_flush();
}

WP_CLI::success( sprintf( 'Sample community ready: %d members, %d groups.', count( $bpb_ids ), count( $bpb_groups ) ) );
