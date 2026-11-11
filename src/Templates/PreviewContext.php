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
		add_filter( 'rest_request_before_callbacks', [ __CLASS__, 'before_rest' ], 10, 3 );
	}

	/**
	 * Build the context before the block renderer runs (so BuddyPress state exists for every block of the request).
	 *
	 * @param mixed            $response Response.
	 * @param array            $handler  Handler.
	 * @param \WP_REST_Request $request  Request.
	 * @return mixed
	 */
	public static function before_rest( $response, $handler, $request ) {
		if ( $request instanceof \WP_REST_Request && 0 === strpos( $request->get_route(), '/wp/v2/block-renderer/' ) ) {
			self::ctx();
		}

		return $response;
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
		];

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

		if ( $group_id && groups_get_group( $group_id )->id ) {
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
	 * BuddyPress as on the member's profile (default component), as bp_parse_query would set it.
	 *
	 * @param int $user_id Member.
	 */
	private static function boot_member( $user_id ) {
		$bp = buddypress();

		$bp->displayed_user->id       = $user_id;
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $user_id );
		$bp->displayed_user->fullname = isset( $bp->displayed_user->userdata->display_name ) ? $bp->displayed_user->userdata->display_name : '';
		$bp->displayed_user->domain   = bp_members_get_user_url( $user_id );

		$component = bp_is_active( 'activity' ) ? 'activity' : 'profile';

		$bp->current_component = $component;
		$bp->current_action    = 'activity' === $component ? 'just-me' : 'public';
		$bp->current_item      = bp_members_get_user_slug( $user_id );

		self::setup_nav();
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
