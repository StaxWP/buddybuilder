<?php
/**
 * Editor preview context: block previews (ServerSideRender, REST block-renderer) of a bpb_template show real
 * BuddyPress output for a chosen member or group, as if the page were the template type's real page.
 *
 * The block editor sends post_id (the layout) plus the unsaved preview choices (bpb_preview_user,
 * bpb_preview_group). BuddyPress state (displayed user, current group, component, action, navigation) is built the
 * way bp_parse_query would build it, because that hook never runs in REST requests. AJAX-loaded streams (activity,
 * members, groups) are rendered server-side in previews, since the editor canvas does not run Nouveau's JS.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Preview context for the block renderer REST endpoint.
 */
final class PreviewContext {

	/**
	 * Context of this request, once computed.
	 *
	 * @var array|null
	 */
	private static $ctx = null;

	/**
	 * Feedback ids printed while Nouveau waits for its AJAX list => loop template rendered instead in previews.
	 */
	const LOOPS = [
		'directory-activity-loading' => 'activity/activity-loop',
		'member-activity-loading'    => 'activity/activity-loop',
		'group-activity-loading'     => 'activity/activity-loop',
		'directory-members-loading'  => 'members/members-loop',
		'member-friends-loading'     => 'members/members-loop',
		'group-members-loading'      => 'groups/single/members-loop',
		'directory-groups-loading'   => 'groups/groups-loop',
		'member-groups-loading'      => 'groups/groups-loop',
	];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		// After the permission check (rest_request_before_callbacks runs before it): the "seen by a guest" preview
		// renders logged out, which must not change who the permission check sees.
		add_filter( 'rest_dispatch_request', [ __CLASS__, 'dispatch' ], 10, 4 );
		add_filter( 'rest_request_after_callbacks', [ __CLASS__, 'after_rest' ], 10, 3 );
	}

	/**
	 * Viewers a preview can be seen by, per page type (value => label). '' is you.
	 *
	 * @return array type => [ value => label ]
	 */
	public static function viewer_options() {
		return [
			'member-profile' => [
				''       => __( 'you', 'stax-buddy-builder' ),
				'self'   => __( 'the member (own profile)', 'stax-buddy-builder' ),
				'friend' => __( 'a friend', 'stax-buddy-builder' ),
				'member' => __( 'another member', 'stax-buddy-builder' ),
				'guest'  => __( 'a guest (logged out)', 'stax-buddy-builder' ),
			],
			'group-profile'  => [
				''            => __( 'you', 'stax-buddy-builder' ),
				'groupmember' => __( 'a group member', 'stax-buddy-builder' ),
				'member'      => __( 'a member not in the group', 'stax-buddy-builder' ),
				'guest'       => __( 'a guest (logged out)', 'stax-buddy-builder' ),
			],
		];
	}

	/**
	 * Sanitize a viewer value.
	 *
	 * @param string $viewer Viewer.
	 * @return string
	 */
	public static function sanitize_viewer( $viewer ) {
		$viewer = sanitize_key( (string) $viewer );

		return in_array( $viewer, [ 'self', 'friend', 'member', 'groupmember', 'guest' ], true ) ? $viewer : '';
	}

	/**
	 * Whether the editor may preview as other members (community moderators). Everyone who can edit a layout may
	 * preview as a guest, and as anyone around BuddyBuilder's sample member or sample group.
	 *
	 * @return bool
	 */
	public static function can_simulate_members() {
		return function_exists( 'bp_current_user_can' ) && bp_current_user_can( 'bp_moderate' );
	}

	/**
	 * User switched out for a guest preview (restored after the request), null when none.
	 *
	 * @var int|null
	 */
	private static $switched_from = null;

	/**
	 * rest_dispatch_request: build the context of a block preview after the permission check.
	 *
	 * @param mixed            $result  Dispatch result (null).
	 * @param \WP_REST_Request $request Request.
	 * @param string           $route   Route.
	 * @param array            $handler Handler.
	 * @return mixed
	 */
	public static function dispatch( $result, $request, $route = '', $handler = [] ) {
		if ( $request instanceof \WP_REST_Request && 0 === strpos( $request->get_route(), '/wp/v2/block-renderer/' ) ) {
			$ctx = self::ctx();

			if ( ! empty( $ctx['viewer'] ) && 'guest' === $ctx['viewer'] && get_current_user_id() ) {
				self::$switched_from = get_current_user_id();
				wp_set_current_user( 0 );
			}
		}

		return $result;
	}

	/**
	 * rest_request_after_callbacks: back to the real user after a guest preview.
	 *
	 * @param mixed $response Response.
	 * @return mixed
	 */
	public static function after_rest( $response ) {
		if ( null !== self::$switched_from ) {
			wp_set_current_user( self::$switched_from );
			self::$switched_from = null;
		}

		return $response;
	}

	/**
	 * Who the preview is seen by: BuddyPress' logged-in member becomes the viewer (buttons, tabs, private fields and
	 * friends-only content follow), the WordPress user stays the same except for guests (logged out). Viewers other
	 * than guests need the bp_moderate capability, or the sample member / sample group.
	 *
	 * @param string $type     Template type.
	 * @param int    $user_id  Previewed member.
	 * @param int    $group_id Previewed group.
	 * @param int    $post_id  Layout.
	 * @return string Viewer applied ('' for you).
	 */
	private static function apply_viewer( $type, $user_id, $group_id, $post_id ) {
		$viewer = isset( $_GET['bpb_preview_viewer'] ) ? self::sanitize_viewer( wp_unslash( $_GET['bpb_preview_viewer'] ) ) : self::sanitize_viewer( get_post_meta( $post_id, PostType::META_VIEWER, true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$known  = self::viewer_options();

		if ( '' === $viewer || ! isset( $known[ $type ][ $viewer ] ) ) {
			return '';
		}

		$sample = SampleMember::data();
		$is_sample = ( $user_id && $user_id === SampleMember::user_id() ) || ( $group_id && ! empty( $sample['groups'] ) && in_array( $group_id, array_map( 'intval', (array) $sample['groups'] ), true ) );

		if ( 'guest' !== $viewer && ! self::can_simulate_members() && ! $is_sample ) {
			return '';
		}

		$status = null;

		switch ( $viewer ) {
			case 'guest':
				$viewer_id = 0;
				break;

			case 'self':
				$viewer_id = $user_id;
				break;

			case 'friend':
				$friends   = $user_id && bp_is_active( 'friends' ) ? array_map( 'intval', (array) friends_get_friend_user_ids( $user_id ) ) : [];
				$viewer_id = $friends ? $friends[0] : self::other_user( [ $user_id ] );
				$status    = 'is_friend';
				break;

			case 'groupmember':
				$viewer_id = self::group_member( $group_id );
				break;

			default: // member: logged in, not a friend, not in the group.
				$exclude = [ $user_id ];

				if ( $user_id && bp_is_active( 'friends' ) ) {
					$exclude = array_merge( $exclude, array_map( 'intval', (array) friends_get_friend_user_ids( $user_id ) ) );
				}

				if ( $group_id ) {
					$exclude[] = self::group_member( $group_id );
				}

				$viewer_id = self::other_user( $exclude, $group_id );
				$status    = 'not_friends';
		}

		add_filter(
			'bp_loggedin_user_id',
			static function () use ( $viewer_id ) {
				return (int) $viewer_id;
			},
			99
		);

		if ( null !== $status ) {
			add_filter(
				'bp_is_friend',
				static function () use ( $status ) {
					return $status;
				},
				99
			);
		}

		return $viewer;
	}

	/**
	 * A member who is not in the excluded list (and not in the group), 0 when the site has none.
	 *
	 * @param int[] $exclude  Excluded ids.
	 * @param int   $group_id Group the member must not belong to (0: any).
	 * @return int
	 */
	private static function other_user( array $exclude, $group_id = 0 ) {
		$ids = get_users(
			[
				'number'  => 50,
				'fields'  => 'ID',
				'exclude' => array_filter( array_map( 'intval', $exclude ) ),
				'orderby' => 'ID',
			]
		);

		foreach ( $ids as $id ) {
			if ( ! $group_id || ! groups_is_user_member( (int) $id, $group_id ) ) {
				return (int) $id;
			}
		}

		return 0;
	}

	/**
	 * A member of the group (not the previewing user when possible), else its creator.
	 *
	 * @param int $group_id Group.
	 * @return int
	 */
	private static function group_member( $group_id ) {
		if ( ! $group_id ) {
			return 0;
		}

		$members = groups_get_group_members(
			[
				'group_id'   => $group_id,
				'per_page'   => 5,
				'exclude_admins_mods' => false,
			]
		);

		foreach ( isset( $members['members'] ) ? $members['members'] : [] as $member ) {
			if ( (int) $member->ID !== get_current_user_id() ) {
				return (int) $member->ID;
			}
		}

		$group = groups_get_group( $group_id );

		return ! empty( $group->creator_id ) ? (int) $group->creator_id : 0;
	}

	/**
	 * Context for renderers: [] on the front end (renderers read the real BuddyPress state), or
	 * [ preview, type, user_id, group_id ] in an editor preview.
	 *
	 * @return array
	 */
	public static function ctx() {
		if ( null !== self::$ctx ) {
			return self::$ctx;
		}

		self::$ctx = [];

		$post_id = self::preview_post_id();

		if ( ! $post_id ) {
			return self::$ctx;
		}

		$type     = isset( $_GET['bpb_type'] ) ? PostType::sanitize_type( wp_unslash( $_GET['bpb_type'] ) ) : PostType::type_of( $post_id ); // phpcs:ignore WordPress.Security.NonceVerification
		$user_id  = self::preview_user( $post_id );
		$group_id = self::preview_group( $post_id );

		self::$ctx = [
			'preview'  => true,
			'type'     => $type,
			'user_id'  => 0,
			'group_id' => 0,
			'viewer'   => '',
		];

		// Before BuddyPress is booted: its navigation depends on who looks (own profile tabs, group admin tabs).
		self::$ctx['viewer'] = self::apply_viewer(
			$type,
			'member-profile' === $type ? $user_id : 0,
			'group-profile' === $type ? $group_id : 0,
			$post_id
		);

		switch ( $type ) {
			case 'member-profile':
				if ( $user_id ) {
					self::boot_member( $user_id );
					self::$ctx['user_id'] = $user_id;
				}
				break;

			case 'group-profile':
				if ( $group_id ) {
					self::boot_group( $group_id );
					self::$ctx['group_id'] = $group_id;
				}
				break;

			default:
				self::boot_directory( Types::component( $type ) );
				break;
		}

		self::server_render_streams();

		return self::$ctx;
	}

	/**
	 * Whether the current request is an editor preview of a block layout.
	 *
	 * @return bool
	 */
	public static function is_preview() {
		$ctx = self::ctx();

		return ! empty( $ctx['preview'] );
	}

	/**
	 * Post id of the layout being edited, when this is a block-renderer request for it by someone who can edit it.
	 *
	 * @return int
	 */
	private static function preview_post_id() {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST || empty( $_GET['post_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 0;
		}

		$post_id = absint( $_GET['post_id'] ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( PostType::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return 0;
		}

		return $post_id;
	}

	/**
	 * Preview member: unsaved choice, saved meta, the sample member, then the current user.
	 *
	 * @param int $post_id Layout id.
	 * @return int
	 */
	public static function preview_user( $post_id ) {
		$user_id = isset( $_GET['bpb_preview_user'] ) ? absint( $_GET['bpb_preview_user'] ) : (int) get_post_meta( $post_id, PostType::META_USER, true ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( $user_id && get_userdata( $user_id ) ) {
			return $user_id;
		}

		$sample = SampleMember::user_id();

		return $sample ? $sample : get_current_user_id();
	}

	/**
	 * Preview group: unsaved choice, saved meta, the sample group, then the newest public group.
	 *
	 * @param int $post_id Layout id.
	 * @return int
	 */
	public static function preview_group( $post_id ) {
		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
			return 0;
		}

		$group_id = isset( $_GET['bpb_preview_group'] ) ? absint( $_GET['bpb_preview_group'] ) : (int) get_post_meta( $post_id, PostType::META_GROUP, true ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( $group_id && self::can_preview_group( $group_id ) ) {
			return $group_id;
		}

		$sample = SampleMember::group_id();

		if ( $sample ) {
			return $sample;
		}

		$groups = groups_get_groups(
			[
				'per_page'    => 1,
				'type'        => 'newest',
				'show_hidden' => false,
			]
		);

		return ! empty( $groups['groups'][0]->id ) ? (int) $groups['groups'][0]->id : 0;
	}

	/**
	 * Whether the editor may preview a group: public groups, groups they belong to, BuddyBuilder's sample groups, or
	 * any group for community moderators. Private and hidden groups are not shown to other editors.
	 *
	 * @param int $group_id Group.
	 * @return bool
	 */
	private static function can_preview_group( $group_id ) {
		$group = groups_get_group( $group_id );

		if ( empty( $group->id ) ) {
			return false;
		}

		$sample = SampleMember::data();

		return 'public' === $group->status
			|| bp_current_user_can( 'bp_moderate' )
			|| groups_is_user_member( get_current_user_id(), $group->id )
			|| ( ! empty( $sample['groups'] ) && in_array( (int) $group->id, array_map( 'intval', (array) $sample['groups'] ), true ) );
	}

	/**
	 * BuddyPress as on the member's profile (default component), as bp_parse_query would set it.
	 *
	 * @param int $user_id Member.
	 */
	private static function boot_member( $user_id ) {
		$bp = buddypress();

		$bp->displayed_user->id       = $user_id;
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $user_id );
		$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';
		$bp->displayed_user->domain   = self::user_url( $user_id );

		$component = bp_is_active( 'activity' ) ? 'activity' : 'profile';

		$bp->current_component = $component;
		$bp->current_action    = 'activity' === $component ? 'just-me' : 'public';
		$bp->current_item      = self::user_slug( $user_id );

		self::setup_nav();
	}

	/**
	 * Profile URL (BuddyPress 12+ helper, with the BuddyBoss Platform / BuddyPress < 12 one as fallback).
	 *
	 * @param int $user_id Member.
	 * @return string
	 */
	private static function user_url( $user_id ) {
		return function_exists( 'bp_members_get_user_url' ) ? bp_members_get_user_url( $user_id ) : bp_core_get_user_domain( $user_id );
	}

	/**
	 * Profile slug (BuddyPress 12+ helper, with the BuddyBoss Platform / BuddyPress < 12 one as fallback).
	 *
	 * @param int $user_id Member.
	 * @return string
	 */
	private static function user_slug( $user_id ) {
		return function_exists( 'bp_members_get_user_slug' ) ? bp_members_get_user_slug( $user_id ) : bp_core_get_username( $user_id );
	}

	/**
	 * BuddyPress as on the group's home page.
	 *
	 * @param int $group_id Group.
	 */
	private static function boot_group( $group_id ) {
		$bp    = buddypress();
		$group = groups_get_group( $group_id );

		$bp->groups->current_group = $group;
		$bp->current_component     = $bp->groups->id;
		$bp->current_item          = $group->slug;
		$bp->current_action        = 'home';
		$bp->is_single_item        = true;
		$bp->is_item_admin         = bp_current_user_can( 'bp_moderate' ) || groups_is_user_admin( get_current_user_id(), $group_id );
		$bp->is_item_mod           = (bool) groups_is_user_mod( get_current_user_id(), $group_id );

		// As BP_Groups_Component::setup_current_group() does on a real group page.
		$group->front_template = bp_groups_get_front_template( $group );
		do_action_ref_array( 'bp_groups_set_current_group', [ $group ] );

		if ( empty( $bp->groups->nav ) ) {
			$bp->groups->nav = new \BP_Core_Nav( $group->id, $bp->groups->id );
		}

		// groups/single/home.php renders inside the single-group loop: the group template tags read it.
		if ( bp_has_groups(
			[
				'include'     => [ $group->id ],
				'show_hidden' => true,
				'per_page'    => 1,
			]
		) ) {
			bp_the_group();
		}

		self::setup_nav();
	}

	/**
	 * BuddyPress as on a directory page.
	 *
	 * @param string $component members|groups|activity.
	 */
	private static function boot_directory( $component ) {
		$bp = buddypress();

		$bp->current_component  = $component;
		$bp->current_action     = '';
		$bp->is_directory       = true;
		$bp->displayed_user->id = 0;
	}

	/**
	 * Build the BuddyPress navigation (bp_setup_nav normally runs on bp_parse_query).
	 */
	private static function setup_nav() {
		if ( ! did_action( 'bp_setup_nav' ) ) {
			do_action( 'bp_setup_canonical_stack' );
			do_action( 'bp_setup_nav' );
		}
	}

	/**
	 * In previews, replace Nouveau's "Loading..." placeholders by the first page of the real loop.
	 */
	private static function server_render_streams() {
		add_filter(
			'bp_nouveau_feedback_messages',
			static function ( $messages ) {
				foreach ( array_keys( self::LOOPS ) as $id ) {
					if ( isset( $messages[ $id ] ) ) {
						$messages[ $id ]['before'] = 'bpb_preview_loop_before';
						$messages[ $id ]['after']  = 'bpb_preview_loop_after_' . $id;
					}
				}

				return $messages;
			}
		);

		add_action(
			'bpb_preview_loop_before',
			static function () {
				ob_start();
			}
		);

		foreach ( self::LOOPS as $id => $template ) {
			add_action(
				'bpb_preview_loop_after_' . $id,
				static function () use ( $template ) {
					ob_end_clean();
					bp_get_template_part( $template );
				}
			);
		}
	}
}
