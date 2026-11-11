<?php
/**
 * Editor preview context (2.0 spike): ServerSideRender requests carry post_id; for a bpb_template post the
 * displayed member becomes the template's preview member (meta _bpb_preview_user, else the current user)
 * and the BuddyPress navigation is built for that member, so the editor shows real output.
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
	 * Context for renderers: front end = the real displayed member; editor = the preview member.
	 *
	 * @return array
	 */
	public static function ctx() {
		if ( null !== self::$ctx ) {
			return self::$ctx;
		}

		self::$ctx = [];

		$post_id = self::preview_post_id();

		if ( $post_id ) {
			// The editor sends the unsaved choice; the saved meta is the fallback.
			$user_id = isset( $_GET['bpb_preview_user'] ) ? absint( $_GET['bpb_preview_user'] ) : (int) get_post_meta( $post_id, PostType::META_USER, true ); // phpcs:ignore WordPress.Security.NonceVerification
			$user_id = $user_id && get_userdata( $user_id ) ? $user_id : get_current_user_id();

			self::boot_member( $user_id );

			self::$ctx = [
				'user_id' => $user_id,
				'preview' => true,
			];
		}

		return self::$ctx;
	}

	/**
	 * Post id of the template being edited, when this is a block-renderer request for one of our blocks.
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
	 * Make BuddyPress behave as on the member's profile (default component), as bp_parse_query would.
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

		if ( ! did_action( 'bp_setup_nav' ) ) {
			do_action( 'bp_setup_canonical_stack' );
			do_action( 'bp_setup_nav' );
		}
	}
}
