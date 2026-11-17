<?php
/**
 * Editor previews of the BuddyPress screens that its scripts build on the live page: messages, group invites and the
 * group members manager. BuddyPress prints empty containers there and fills them over the REST API or AJAX, which
 * never happens in an editor preview. In editor previews only (block editor and Elementor), a short list with real
 * data is printed after the containers, labelled as a preview. The front end is unchanged.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Server-side previews of script-built BuddyPress screens.
 */
final class ScriptScreens {

	/** Rows shown per list. */
	const ROWS = 5;

	/**
	 * Hook the previews (called once an editor preview context is set up).
	 */
	public static function hooks() {
		static $hooked = false;

		if ( $hooked ) {
			return;
		}

		$hooked = true;

		add_action( '_bp_nouveau_messages_print_placeholders', [ __CLASS__, 'messages' ] );
		add_action( '_bp_nouveau_group_print_invites_placeholders', [ __CLASS__, 'invites' ] );
		add_action( 'bp_after_group_invites_creation_step', [ __CLASS__, 'invites' ] ); // Group creation, Invite step.
		add_action( '_bp_groups_print_manage_group_members_placeholders', [ __CLASS__, 'members' ] );
	}

	/**
	 * Messages: the inbox of the signed-in editor (the messages screen is always the visitor's own inbox).
	 */
	public static function messages() {
		$rows    = [];
		$user_id = get_current_user_id();

		if ( $user_id && bp_is_active( 'messages' ) && bp_has_message_threads(
			[
				'user_id'  => $user_id,
				'box'      => 'inbox',
				'per_page' => self::ROWS,
			]
		) ) {
			global $messages_template;

			while ( bp_message_threads() ) {
				bp_message_thread();
				$sender = isset( $messages_template->thread->last_sender_id ) ? (int) $messages_template->thread->last_sender_id : 0;
				$rows[] = [
					'user_id' => $sender,
					'title'   => wp_strip_all_tags( bp_get_message_thread_subject() ),
					/* translators: 1: sender name, 2: date of the last message */
					'meta'    => sprintf( __( '%1$s, %2$s', 'stax-buddy-builder' ), bp_core_get_user_displayname( $sender ), wp_strip_all_tags( bp_get_message_thread_last_post_date() ) ),
				];
			}
		}

		self::render(
			__( 'Messages', 'stax-buddy-builder' ),
			__( 'Preview: on the live page the member sees their messages here (inbox, sent, starred and a form to write a message). Below: your latest conversations.', 'stax-buddy-builder' ),
			$rows,
			__( 'You have no messages yet.', 'stax-buddy-builder' )
		);
	}

	/**
	 * Group invites: members who could be invited to the group (the editor's friends when invites are limited to
	 * friends, else members of the site), not already in it.
	 */
	public static function invites() {
		static $done = false;

		if ( $done ) {
			return; // Once per request (the creation step can reach both hooks).
		}

		$done = true;
		$group_id = self::group_id();
		$exclude  = $group_id ? self::member_ids( $group_id ) : [];
		$friends  = bp_is_active( 'friends' ) && ( ! function_exists( 'bp_nouveau_groups_disallow_all_members_invites' ) || bp_nouveau_groups_disallow_all_members_invites() );
		$query    = new \BP_User_Query(
			[
				'type'     => 'alphabetical',
				'per_page' => self::ROWS,
				'user_id'  => $friends ? get_current_user_id() : 0,
				'exclude'  => array_merge( $exclude, [ get_current_user_id() ] ),
			]
		);
		$rows     = [];

		foreach ( (array) $query->results as $user ) {
			$rows[] = [
				'user_id' => (int) $user->ID,
				'title'   => bp_core_get_user_displayname( $user->ID ),
				'meta'    => $friends ? __( 'Friend', 'stax-buddy-builder' ) : __( 'Member', 'stax-buddy-builder' ),
			];
		}

		self::render(
			__( 'Send invites', 'stax-buddy-builder' ),
			__( 'Preview: on the live page group admins pick members here, add a message and send the invites. Below: members who could be invited.', 'stax-buddy-builder' ),
			$rows,
			__( 'Nobody to invite yet.', 'stax-buddy-builder' )
		);
	}

	/**
	 * Group members manager: the group's members with their roles.
	 */
	public static function members() {
		$group_id = self::group_id();
		$rows     = [];

		if ( $group_id ) {
			$members = groups_get_group_members(
				[
					'group_id'            => $group_id,
					'per_page'            => self::ROWS,
					'exclude_admins_mods' => false,
				]
			);

			foreach ( isset( $members['members'] ) ? (array) $members['members'] : [] as $member ) {
				if ( ! empty( $member->is_admin ) ) {
					$role = __( 'Administrator', 'stax-buddy-builder' );
				} elseif ( ! empty( $member->is_mod ) ) {
					$role = __( 'Moderator', 'stax-buddy-builder' );
				} else {
					$role = __( 'Member', 'stax-buddy-builder' );
				}

				$rows[] = [
					'user_id' => (int) $member->ID,
					'title'   => bp_core_get_user_displayname( $member->ID ),
					'meta'    => $role,
				];
			}
		}

		self::render(
			__( 'Manage members', 'stax-buddy-builder' ),
			__( 'Preview: on the live page group admins promote, demote, ban or remove members here. Below: members of the group and their roles.', 'stax-buddy-builder' ),
			$rows,
			__( 'This group has no members yet.', 'stax-buddy-builder' )
		);
	}

	/**
	 * The group of the preview (the displayed group, or the group being created).
	 *
	 * @return int
	 */
	private static function group_id() {
		if ( ! bp_is_active( 'groups' ) ) {
			return 0;
		}

		$id = (int) bp_get_current_group_id();

		return $id ? $id : (int) bp_get_new_group_id();
	}

	/**
	 * Member ids of a group (admins and moderators included).
	 *
	 * @param int $group_id Group.
	 * @return int[]
	 */
	private static function member_ids( $group_id ) {
		$members = groups_get_group_members(
			[
				'group_id'            => $group_id,
				'per_page'            => false,
				'exclude_admins_mods' => false,
			]
		);

		return array_map( 'intval', wp_list_pluck( isset( $members['members'] ) ? (array) $members['members'] : [], 'ID' ) );
	}

	/**
	 * One labelled preview box with a list of members (photo, name, detail).
	 *
	 * @param string  $title Screen name.
	 * @param string  $note  What the live page shows.
	 * @param array[] $rows  Rows: user_id, title, meta.
	 * @param string  $empty Text when there are no rows.
	 */
	private static function render( $title, $note, array $rows, $empty ) {
		echo '<div class="bpb-script-screen-preview" style="margin:1em 0;padding:1em;border:1px dashed currentColor;border-radius:4px">';
		echo '<p style="margin:0 0 .25em;font-weight:600">' . esc_html( $title ) . '</p>';
		echo '<p class="bp-help-text" style="margin:0 0 .75em;font-size:13px;opacity:.75">' . esc_html( $note ) . '</p>';

		if ( ! $rows ) {
			echo '<p style="margin:0;font-size:13px">' . esc_html( $empty ) . '</p></div>';
			return;
		}

		echo '<ul style="list-style:none;margin:0;padding:0">';

		foreach ( $rows as $row ) {
			echo '<li style="display:flex;align-items:center;gap:.75em;margin:0 0 .5em">';
			echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress escapes the avatar markup.
				[
					'item_id' => $row['user_id'],
					'type'    => 'thumb',
					'width'   => 32,
					'height'  => 32,
					'alt'     => '',
				]
			);
			echo '<span><strong>' . esc_html( $row['title'] ) . '</strong><br><small>' . esc_html( $row['meta'] ) . '</small></span>';
			echo '</li>';
		}

		echo '</ul></div>';
	}
}
