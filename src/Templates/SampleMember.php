<?php
/**
 * Sample member for previews on sites without members yet (U4).
 *
 * Created on demand (admin action), hidden from directories, removed with one click: one sample member with
 * profile name, 5 activity updates, 3 sample friends (when Friends is active) and 2 sample groups (when Groups is
 * active). Everything it creates is tagged so removal deletes exactly that.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Sample member.
 */
final class SampleMember {

	const OPTION    = 'bpb_sample_member';
	const USER_META = '_bpb_sample';
	const GROUP_META = '_bpb_sample';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_filter( 'bp_after_has_members_parse_args', [ __CLASS__, 'hide_members' ] );
		add_filter( 'bp_after_has_groups_parse_args', [ __CLASS__, 'hide_groups' ] );
		add_action( 'admin_post_bpb_sample_member', [ __CLASS__, 'handle' ] );
	}

	/**
	 * Sample member id (0 when none).
	 *
	 * @return int
	 */
	public static function user_id() {
		$data = self::data();

		return ! empty( $data['user'] ) && get_userdata( (int) $data['user'] ) ? (int) $data['user'] : 0;
	}

	/**
	 * Sample group id (0 when none).
	 *
	 * @return int
	 */
	public static function group_id() {
		$data = self::data();

		return ! empty( $data['groups'][0] ) ? (int) $data['groups'][0] : 0;
	}

	/**
	 * Stored ids.
	 *
	 * @return array [ user, friends[], groups[], activities[] ]
	 */
	public static function data() {
		$data = get_option( self::OPTION, [] );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Create the sample member and its data (idempotent: returns the existing one).
	 *
	 * @return int|\WP_Error Member id.
	 */
	public static function create() {
		$existing = self::user_id();

		if ( $existing ) {
			return $existing;
		}

		$data = [
			'user'       => 0,
			'friends'    => [],
			'groups'     => [],
			'activities' => [],
		];

		$user_id = self::create_user( 'bpb-sample-member', __( 'Alex Sample', 'stax-buddy-builder' ) );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$data['user'] = $user_id;

		$updates = [
			__( 'Just joined the community. Happy to be here!', 'stax-buddy-builder' ),
			__( 'Working on a new project this week, more soon.', 'stax-buddy-builder' ),
			__( 'Thanks everyone for the warm welcome.', 'stax-buddy-builder' ),
			__( 'Looking for recommendations: what should I read next?', 'stax-buddy-builder' ),
			__( 'Great meetup yesterday, see you at the next one.', 'stax-buddy-builder' ),
		];

		if ( bp_is_active( 'activity' ) ) {
			foreach ( $updates as $content ) {
				$activity_id = bp_activity_post_update(
					[
						'content' => $content,
						'user_id' => $user_id,
					]
				);

				if ( $activity_id ) {
					$data['activities'][] = (int) $activity_id;
				}
			}
		}

		if ( bp_is_active( 'friends' ) ) {
			foreach ( [ 'Maria Sample', 'Sam Sample', 'Ioana Sample' ] as $i => $name ) {
				$friend_id = self::create_user( 'bpb-sample-friend-' . ( $i + 1 ), $name );

				if ( ! is_wp_error( $friend_id ) ) {
					$data['friends'][] = $friend_id;
					friends_add_friend( $user_id, $friend_id, true );
				}
			}
		}

		if ( bp_is_active( 'groups' ) ) {
			foreach ( [ __( 'Sample Group: Photography', 'stax-buddy-builder' ), __( 'Sample Group: Book Club', 'stax-buddy-builder' ) ] as $name ) {
				$group_id = groups_create_group(
					[
						'creator_id'  => $user_id,
						'name'        => $name,
						'description' => __( 'A sample group created by BuddyBuilder for previews. Remove it from BuddyBuilder > Layouts.', 'stax-buddy-builder' ),
						'status'      => 'hidden',
						'enable_forum' => 0,
					]
				);

				if ( $group_id ) {
					groups_update_groupmeta( $group_id, self::GROUP_META, 1 );
					$data['groups'][] = (int) $group_id;

					foreach ( $data['friends'] as $friend_id ) {
						groups_join_group( $group_id, $friend_id );
					}
				}
			}
		}

		update_option( self::OPTION, $data, false );

		return $user_id;
	}

	/**
	 * Remove everything the sample created.
	 */
	public static function remove() {
		$data = self::data();

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		foreach ( isset( $data['groups'] ) ? (array) $data['groups'] : [] as $group_id ) {
			if ( function_exists( 'groups_delete_group' ) && groups_get_groupmeta( (int) $group_id, self::GROUP_META, true ) ) {
				groups_delete_group( (int) $group_id );
			}
		}

		foreach ( isset( $data['activities'] ) ? (array) $data['activities'] : [] as $activity_id ) {
			if ( function_exists( 'bp_activity_delete' ) ) {
				bp_activity_delete( [ 'id' => (int) $activity_id ] );
			}
		}

		$users = array_merge( isset( $data['friends'] ) ? (array) $data['friends'] : [], [ isset( $data['user'] ) ? (int) $data['user'] : 0 ] );

		foreach ( $users as $user_id ) {
			if ( $user_id && get_user_meta( (int) $user_id, self::USER_META, true ) ) {
				wp_delete_user( (int) $user_id );
			}
		}

		delete_option( self::OPTION );
	}

	/**
	 * Admin action: create or remove (nonce + manage_options).
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bpb_sample_member' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		if ( isset( $_POST['remove'] ) ) {
			self::remove();
		} else {
			self::create();
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Hide sample members from member loops (front end; previews and admins still find them by id).
	 *
	 * @param array $args Loop args.
	 * @return array
	 */
	public static function hide_members( $args ) {
		$ids = self::member_ids();

		if ( empty( $ids ) || PreviewContext::is_preview() ) {
			return $args;
		}

		$exclude         = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : [];
		$args['exclude'] = array_merge( $exclude, $ids );

		return $args;
	}

	/**
	 * Hide sample groups from group loops outside previews (they are hidden groups already; this covers admins).
	 *
	 * @param array $args Loop args.
	 * @return array
	 */
	public static function hide_groups( $args ) {
		$data = self::data();

		if ( empty( $data['groups'] ) || PreviewContext::is_preview() ) {
			return $args;
		}

		$exclude         = isset( $args['exclude'] ) ? wp_parse_id_list( $args['exclude'] ) : [];
		$args['exclude'] = array_merge( $exclude, array_map( 'intval', $data['groups'] ) );

		return $args;
	}

	/**
	 * Ids of every sample member.
	 *
	 * @return int[]
	 */
	private static function member_ids() {
		$data = self::data();

		if ( empty( $data['user'] ) ) {
			return [];
		}

		return array_map( 'intval', array_merge( [ $data['user'] ], isset( $data['friends'] ) ? (array) $data['friends'] : [] ) );
	}

	/**
	 * Create one tagged user (subscriber, random password, no email sent).
	 *
	 * @param string $login Login.
	 * @param string $name  Display name.
	 * @return int|\WP_Error
	 */
	private static function create_user( $login, $name ) {
		$user = get_user_by( 'login', $login );

		if ( $user ) {
			return get_user_meta( $user->ID, self::USER_META, true ) ? (int) $user->ID : new \WP_Error( 'bpb_sample_login', __( 'The sample member login is already used by a real account.', 'stax-buddy-builder' ) );
		}

		$user_id = wp_insert_user(
			[
				'user_login'   => $login,
				'user_pass'    => wp_generate_password( 32 ),
				'user_email'   => $login . '@example.invalid',
				'display_name' => $name,
				'role'         => 'subscriber',
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, self::USER_META, 1 );

		if ( function_exists( 'xprofile_set_field_data' ) && function_exists( 'bp_xprofile_fullname_field_id' ) ) {
			xprofile_set_field_data( bp_xprofile_fullname_field_id(), $user_id, $name );
		}

		if ( function_exists( 'bp_update_user_last_activity' ) ) {
			bp_update_user_last_activity( $user_id );
		}

		return (int) $user_id;
	}
}
