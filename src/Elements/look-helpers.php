<?php
/**
 * Helpers of the 2.0 look elements (member-header, member-about, group-header, directory-header, people-rail):
 * member and group data, icons, avatar stacks. Plain functions, read-only, shared by the renderers.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! function_exists( 'bpb_xprofile_value' ) ) {
	/**
	 * Value of an xProfile field by name (or id) for a member, as plain text ('' when empty or hidden from the
	 * current viewer).
	 *
	 * @param int        $user_id Member.
	 * @param string|int $field   Field name or id.
	 * @return string
	 */
	function bpb_xprofile_value( $user_id, $field ) {
		if ( ! $user_id || '' === $field || ! function_exists( 'xprofile_get_field_data' ) ) {
			return '';
		}

		$field_id = is_numeric( $field ) ? (int) $field : (int) xprofile_get_field_id_from_name( $field );

		if ( ! $field_id ) {
			return '';
		}

		if ( function_exists( 'bp_xprofile_get_hidden_fields_for_user' ) && in_array( $field_id, bp_xprofile_get_hidden_fields_for_user( $user_id ), true ) ) {
			return '';
		}

		$value = xprofile_get_field_data( $field_id, $user_id, 'comma' );

		return trim( wp_strip_all_tags( is_array( $value ) ? implode( ', ', $value ) : (string) $value ) );
	}
}

if ( ! function_exists( 'bpb_user_url' ) ) {
	/**
	 * URL of a member's profile (or one of its tabs), on BuddyPress 12+ and older APIs (BuddyBoss Platform).
	 *
	 * @param int    $user_id Member.
	 * @param string $slug    Tab slug ('' for the profile home).
	 * @return string
	 */
	function bpb_user_url( $user_id, $slug = '' ) {
		$base = function_exists( 'bp_members_get_user_url' ) ? bp_members_get_user_url( $user_id ) : bp_core_get_user_domain( $user_id );

		return '' === $slug ? $base : trailingslashit( $base ) . $slug . '/';
	}
}

if ( ! function_exists( 'bpb_group_url' ) ) {
	/**
	 * URL of a group, on BuddyPress 12+ and older APIs (BuddyBoss Platform).
	 *
	 * @param object $group Group.
	 * @return string
	 */
	function bpb_group_url( $group ) {
		return function_exists( 'bp_get_group_url' ) ? bp_get_group_url( $group ) : bp_get_group_permalink( $group );
	}
}

if ( ! function_exists( 'bpb_look_assets' ) ) {
	/**
	 * Enqueue the look styles: the shared components and, when given, one style (classic, creator, ...).
	 *
	 * @param string $style Style slug ('' for the components only).
	 */
	function bpb_look_assets( $style = '' ) {
		\Buddy_Builder\Assets\Assets::looks( $style );
	}
}

if ( ! function_exists( 'bpb_member_stats' ) ) {
	/**
	 * Counts shown in member headers: friends, groups, posts (activity updates). Only active components.
	 *
	 * @param int $user_id Member.
	 * @return array[] [ key, number, label, url ]
	 */
	function bpb_member_stats( $user_id ) {
		$stats = [];

		if ( bp_is_active( 'friends' ) ) {
			$n       = (int) friends_get_total_friend_count( $user_id );
			$stats[] = [ 'friends', $n, _n( 'Friend', 'Friends', $n, 'stax-buddy-builder' ), bpb_user_url( $user_id, bp_get_friends_slug() ) ];
		}

		if ( bp_is_active( 'groups' ) ) {
			$n       = (int) bp_get_total_group_count_for_user( $user_id );
			$stats[] = [ 'groups', $n, _n( 'Group', 'Groups', $n, 'stax-buddy-builder' ), bpb_user_url( $user_id, bp_get_groups_slug() ) ];
		}

		if ( bp_is_active( 'activity' ) ) {
			$posts   = bp_activity_get(
				[
					'filter'           => [
						'user_id' => $user_id,
						'action'  => 'activity_update',
					],
					'per_page'         => 1,
					'count_total'      => true,
					'update_meta_cache' => false,
				]
			);
			$n       = isset( $posts['total'] ) ? (int) $posts['total'] : 0;
			$stats[] = [ 'posts', $n, _n( 'Post', 'Posts', $n, 'stax-buddy-builder' ), bpb_user_url( $user_id, bp_get_activity_slug() ) ];
		}

		/**
		 * Filters the member stats of the 2.0 member header.
		 *
		 * @since 2.0.0
		 *
		 * @param array[] $stats   [ key, number, label, url ].
		 * @param int     $user_id Member.
		 */
		return (array) apply_filters( 'buddy_builder/member_stats', $stats, $user_id );
	}
}

if ( ! function_exists( 'bpb_member_badges' ) ) {
	/**
	 * Badges next to a member's name: site role (Admin, Moderator) and member types.
	 *
	 * @param int $user_id Member.
	 * @return string[] Labels.
	 */
	function bpb_member_badges( $user_id ) {
		$badges = [];

		if ( user_can( $user_id, 'manage_options' ) ) {
			$badges[] = __( 'Admin', 'stax-buddy-builder' );
		} elseif ( user_can( $user_id, 'bp_moderate' ) ) {
			$badges[] = __( 'Moderator', 'stax-buddy-builder' );
		}

		if ( function_exists( 'bp_get_member_type' ) ) {
			foreach ( (array) bp_get_member_type( $user_id, false ) as $type ) {
				$object = bp_get_member_type_object( $type );
				if ( $object && ! empty( $object->labels['singular_name'] ) ) {
					$badges[] = $object->labels['singular_name'];
				}
			}
		}

		/**
		 * Filters the badges of a member in the 2.0 member header and cards.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $badges  Labels.
		 * @param int      $user_id Member.
		 */
		return array_values( array_filter( (array) apply_filters( 'buddy_builder/member_badges', $badges, $user_id ) ) );
	}
}

if ( ! function_exists( 'bpb_cover_url' ) ) {
	/**
	 * Cover image URL of a member or a group ('' without one).
	 *
	 * @param string $object members|groups.
	 * @param int    $id     Item id.
	 * @return string
	 */
	function bpb_cover_url( $object, $id ) {
		if ( ! function_exists( 'bp_attachments_get_attachment' ) || ! $id ) {
			return '';
		}

		return (string) bp_attachments_get_attachment(
			'url',
			[
				'object_dir' => $object,
				'item_id'    => $id,
			]
		);
	}
}

if ( ! function_exists( 'bpb_icon' ) ) {
	/**
	 * Small inline SVG icon (decorative, aria-hidden).
	 *
	 * @param string $name pin|calendar|clock|link|lock|globe|users.
	 * @return string
	 */
	function bpb_icon( $name ) {
		$paths = [
			'pin'      => 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zm0-9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
			'calendar' => 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
			'clock'    => 'M12 7v5l3 2M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18z',
			'link'     => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
			'lock'     => 'M7 11V8a5 5 0 0 1 10 0v3M6 11h12v9H6z',
			'globe'    => 'M12 21a9 9 0 1 1 0-18 9 9 0 0 1 0 18zM3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z',
			'users'    => 'M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM20 20v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.3a3.5 3.5 0 0 1 0 6.4',
		];

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg class="bpb-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="' . esc_attr( $paths[ $name ] ) . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
	}
}

if ( ! function_exists( 'bpb_avatar_stack' ) ) {
	/**
	 * A row of overlapping member avatars with "+N" when there are more.
	 *
	 * @param int[] $user_ids Members (first ones shown).
	 * @param int   $total    Total count (for "+N").
	 * @param int   $max      Avatars shown.
	 * @return string
	 */
	function bpb_avatar_stack( array $user_ids, $total, $max = 5 ) {
		$html = '<span class="bpb-stack">';

		foreach ( array_slice( $user_ids, 0, $max ) as $user_id ) {
			$html .= bp_core_fetch_avatar(
				[
					'item_id' => $user_id,
					'type'    => 'thumb',
					'width'   => 32,
					'height'  => 32,
					'class'   => 'bpb-stack__avatar',
					'alt'     => '',
				]
			);
		}

		$more = (int) $total - min( count( $user_ids ), $max );

		if ( $more > 0 ) {
			$html .= '<span class="bpb-stack__more">+' . esc_html( number_format_i18n( $more ) ) . '</span>';
		}

		return $html . '</span>';
	}
}

if ( ! function_exists( 'bpb_member_is_online' ) ) {
	/**
	 * Whether a member was active in the last 5 minutes.
	 *
	 * @param int $user_id Member.
	 * @return bool
	 */
	function bpb_member_is_online( $user_id ) {
		$last = bp_get_user_last_activity( $user_id );

		return $last && ( time() - strtotime( $last . ' UTC' ) ) < 5 * MINUTE_IN_SECONDS;
	}
}
