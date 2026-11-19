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

if ( ! function_exists( 'bpb_groups_directory_url' ) ) {
	/**
	 * Groups directory URL on BuddyPress 12+ and older APIs (BuddyBoss Platform).
	 *
	 * @return string
	 */
	function bpb_groups_directory_url() {
		return function_exists( 'bp_get_groups_directory_url' ) ? bp_get_groups_directory_url() : bp_get_groups_directory_permalink();
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
	 * @param int  $user_id Member.
	 * @param bool $role    Include the site role badge.
	 * @param bool $types   Include the member type badges.
	 * @return string[] Labels.
	 */
	function bpb_member_badges( $user_id, $role = true, $types = true ) {
		$badges = [];

		if ( $role && user_can( $user_id, 'manage_options' ) ) {
			$badges[] = __( 'Admin', 'stax-buddy-builder' );
		} elseif ( $role && user_can( $user_id, 'bp_moderate' ) ) {
			$badges[] = __( 'Moderator', 'stax-buddy-builder' );
		}

		if ( $types && function_exists( 'bp_get_member_type' ) ) {
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

if ( ! function_exists( 'bpb_link_images' ) ) {
	/**
	 * Wraps images that are not already inside a link in a link to the image file (Post activity items: the
	 * full size opens on click, also where a style shows the image small).
	 *
	 * @param string $html Activity content.
	 * @return string
	 */
	function bpb_link_images( $html ) {
		if ( false === stripos( $html, '<img' ) ) {
			return $html;
		}

		$parts = preg_split( '#(<a\b[^>]*>.*?</a>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		foreach ( $parts as $i => $part ) {
			if ( 0 === stripos( $part, '<a' ) ) {
				continue;
			}
			$parts[ $i ] = preg_replace_callback(
				'#<img\b[^>]*\bsrc=(["\'])([^"\']+)\1[^>]*>#i',
				static function ( $m ) {
					return '<a class="bpb-media-link" href="' . esc_url( $m[2] ) . '" target="_blank" rel="noopener">' . $m[0] . '</a>';
				},
				$part
			);
		}

		return implode( '', $parts );
	}
}

/*
 * Parts of the 2.0 member header. The Member header element prints them all in one <header>; split into parts
 * (BuddyBuilder Pro), each part element prints one of them inside the header's groups, with the bpb-mh-part class.
 * One markup for both, so a split header looks like the one-element header.
 */

if ( ! function_exists( 'bpb_mh_class' ) ) {
	/**
	 * Class attribute value of a member header part: its own class, plus bpb-mh-part when it is printed as a part.
	 *
	 * @param string $class Part class (bpb-mh__avatar, ...).
	 * @param bool   $part  Printed by a part element.
	 * @return string
	 */
	function bpb_mh_class( $class, $part ) {
		return $class . ( $part ? ' bpb-mh-part' : '' );
	}
}

if ( ! function_exists( 'bpb_mh_cover' ) ) {
	/**
	 * Cover of the member header (the gradient when the member has no cover image).
	 *
	 * @param int  $user_id Member.
	 * @param bool $part    Printed by a part element.
	 * @return string
	 */
	function bpb_mh_cover( $user_id, $part = false ) {
		$url = bpb_cover_url( 'members', $user_id );

		return '<div class="' . esc_attr( bpb_mh_class( 'bpb-mh__cover' . ( $url ? '' : ' bpb-mh__cover--empty' ), $part ) ) . '"' . ( $url ? ' style="background-image:url(' . esc_url( $url ) . ')"' : '' ) . '></div>';
	}
}

if ( ! function_exists( 'bpb_mh_avatar' ) ) {
	/**
	 * Photo of the member header, with the online dot.
	 *
	 * @param int  $user_id Member.
	 * @param bool $part    Printed by a part element.
	 * @return string
	 */
	function bpb_mh_avatar( $user_id, $part = false ) {
		$html = '<div class="' . esc_attr( bpb_mh_class( 'bpb-mh__avatar', $part ) ) . '"><a href="' . esc_url( bpb_user_url( $user_id ) ) . '">'
			. bp_core_fetch_avatar(
				[
					'item_id' => $user_id,
					'type'    => 'full',
					'class'   => 'bpb-mh__photo',
					/* translators: %s: member name */
					'alt'     => sprintf( __( 'Profile photo of %s', 'stax-buddy-builder' ), bp_core_get_user_displayname( $user_id ) ),
				]
			) . '</a>';

		if ( bpb_member_is_online( $user_id ) ) {
			$html .= '<span class="bpb-mh__online" title="' . esc_attr__( 'Online', 'stax-buddy-builder' ) . '"><span class="screen-reader-text">' . esc_html__( 'Online', 'stax-buddy-builder' ) . '</span></span>';
		}

		return $html . '</div>';
	}
}

if ( ! function_exists( 'bpb_mh_badges' ) ) {
	/**
	 * Badges of the member header ('' without any). As a part they sit in a wrapper that takes no box, so each badge
	 * lines up next to the name like in the one-element header.
	 *
	 * @param int  $user_id Member.
	 * @param bool $part    Printed by a part element.
	 * @param bool $role    Site role badge.
	 * @param bool $types   Member type badges.
	 * @return string
	 */
	function bpb_mh_badges( $user_id, $part = false, $role = true, $types = true ) {
		$html = '';

		foreach ( bpb_member_badges( $user_id, $role, $types ) as $badge ) {
			$html .= '<span class="bpb-badge">' . esc_html( $badge ) . '</span>';
		}

		return ( $part && '' !== $html ) ? '<span class="' . esc_attr( bpb_mh_class( 'bpb-mh__badges', true ) ) . '">' . $html . '</span>' : $html;
	}
}

if ( ! function_exists( 'bpb_mh_name' ) ) {
	/**
	 * Name of the member header: the h1 with the badges in the one-element header; as a part the h1 alone
	 * (bpb-mh__title), inside the header's name row that carries the name style.
	 *
	 * @param int    $user_id Member.
	 * @param bool   $part    Printed by a part element.
	 * @param string $badges  Badges markup (one-element header).
	 * @return string
	 */
	function bpb_mh_name( $user_id, $part = false, $badges = '' ) {
		return '<h1 class="' . esc_attr( $part ? bpb_mh_class( 'bpb-mh__title', true ) : 'bpb-mh__name' ) . '">' . esc_html( bp_core_get_user_displayname( $user_id ) ) . $badges . '</h1>';
	}
}

if ( ! function_exists( 'bpb_mh_handle' ) ) {
	/**
	 * The member's @username ('' when mentions are off).
	 *
	 * @param int  $user_id Member.
	 * @param bool $part    Printed by a part element.
	 * @return string
	 */
	function bpb_mh_handle( $user_id, $part = false ) {
		if ( ! bp_is_active( 'activity' ) || ! bp_activity_do_mentions() ) {
			return '';
		}

		return '<p class="' . esc_attr( bpb_mh_class( 'bpb-mh__handle', $part ) ) . '">@' . esc_html( bp_activity_get_user_mentionname( $user_id ) ) . '</p>';
	}
}

if ( ! function_exists( 'bpb_mh_headline' ) ) {
	/**
	 * Headline line of the member header ('' when empty).
	 *
	 * @param string $text Plain text.
	 * @param bool   $part Printed by a part element.
	 * @return string
	 */
	function bpb_mh_headline( $text, $part = false ) {
		return '' === $text ? '' : '<p class="' . esc_attr( bpb_mh_class( 'bpb-mh__headline', $part ) ) . '">' . esc_html( $text ) . '</p>';
	}
}

if ( ! function_exists( 'bpb_mh_meta' ) ) {
	/**
	 * Location, join date and last activity of the member header.
	 *
	 * @param int    $user_id  Member.
	 * @param string $location Location (plain text, '' to leave it out).
	 * @param bool   $part     Printed by a part element.
	 * @return string
	 */
	function bpb_mh_meta( $user_id, $location, $part = false ) {
		$user = get_userdata( $user_id );
		$html = '<ul class="' . esc_attr( bpb_mh_class( 'bpb-mh__meta', $part ) ) . '">';

		if ( '' !== $location ) {
			$html .= '<li>' . bpb_icon( 'pin' ) . esc_html( $location ) . '</li>';
		}
		if ( $user ) {
			/* translators: %s: month and year */
			$html .= '<li>' . bpb_icon( 'calendar' ) . esc_html( sprintf( __( 'Joined %s', 'stax-buddy-builder' ), date_i18n( 'F Y', strtotime( $user->user_registered ) ) ) ) . '</li>';
		}

		return $html . '<li>' . bpb_icon( 'clock' ) . esc_html( bp_get_last_activity( $user_id ) ) . '</li></ul>';
	}
}

if ( ! function_exists( 'bpb_mh_stats' ) ) {
	/**
	 * Counts of the member header.
	 *
	 * @param array[] $stats [ key, number, label, url ] rows (bpb_member_stats()).
	 * @param bool    $part  Printed by a part element.
	 * @return string
	 */
	function bpb_mh_stats( array $stats, $part = false ) {
		$html = '<ul class="' . esc_attr( bpb_mh_class( 'bpb-mh__stats', $part ) ) . '">';

		foreach ( $stats as $stat ) {
			$html .= '<li class="bpb-mh__stat bpb-mh__stat--' . esc_attr( $stat[0] ) . '"><a href="' . esc_url( $stat[3] ) . '"><strong>' . esc_html( number_format_i18n( $stat[1] ) ) . '</strong> <span>' . esc_html( $stat[2] ) . '</span></a></li>';
		}

		return $html . '</ul>';
	}
}

if ( ! function_exists( 'bpb_mh_actions' ) ) {
	/**
	 * Buttons of the member header (BuddyPress' member header buttons).
	 *
	 * @param bool $part Printed by a part element.
	 * @return string
	 */
	function bpb_mh_actions( $part = false ) {
		ob_start();
		bp_nouveau_member_header_buttons(
			[
				'container'         => 'ul',
				'button_element'    => 'button',
				'container_classes' => [ 'member-header-actions', 'bpb-actions' ],
			]
		);

		return '<div class="' . esc_attr( bpb_mh_class( 'bpb-mh__actions', $part ) ) . '">' . ob_get_clean() . '</div>';
	}
}
