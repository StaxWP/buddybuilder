<?php
/**
 * The presets BuddyBuilder ships: 12 general presets (member profile Classic, Creator, Professional, Social; group
 * profile Community, Course; directories Grid cards, Compact list; activity Feed, Feed with sidebar) and the KLEO
 * defaults (listed only with KLEO 7). Dating-style presets are out by decision (KLEO decision 4.2).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

defined( 'ABSPATH' ) || die();

/**
 * Shipped presets.
 */
final class Library {

	/**
	 * Single-column row.
	 *
	 * @param array $elements Elements.
	 * @return array
	 */
	private static function row( array $elements ) {
		return [ 'columns' => [ [ 'elements' => $elements ] ] ];
	}

	/**
	 * Multi-column row.
	 *
	 * @param array  $columns width => elements pairs as [ [ width, elements ], ... ].
	 * @param string $align   Vertical alignment.
	 * @return array
	 */
	private static function cols( array $columns, $align = '' ) {
		$row = [ 'columns' => [] ];

		foreach ( $columns as $column ) {
			$row['columns'][] = [
				'width'    => $column[0],
				'elements' => $column[1],
			];
		}

		if ( $align ) {
			$row['align'] = $align;
		}

		return $row;
	}

	/**
	 * All shipped presets.
	 *
	 * @return array
	 */
	public static function presets() {
		$cover      = [ 'member-cover', [], [ 'minHeight' => '260px', 'radius' => '12px' ] ];
		$big_cover  = [ 'member-cover', [ 'overlay_background' => true ], [ 'minHeight' => '380px', 'radius' => '16px' ] ];
		$group_cov  = [ 'group-cover', [], [ 'minHeight' => '240px', 'radius' => '12px' ] ];
		$notices    = [ 'notices' ];
		$member_nav = [ 'member-nav' ];

		$presets = [
			// Member profile.
			'member-classic'      => [
				'type'        => 'member-profile',
				'title'       => __( 'Classic', 'stax-buddy-builder' ),
				'description' => __( 'Cover, avatar and name side by side, tabs below: the familiar BuddyPress profile.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ $cover ] ),
					self::cols(
						[
							[ 20, [ [ 'member-avatar' ] ] ],
							[ 80, [ [ 'member-username', [ 'username_type' => 'full_name' ] ], [ 'member-meta' ], [ 'member-buttons' ] ] ],
						],
						'center'
					),
					self::row( [ $notices, $member_nav, [ 'member-content' ] ] ),
				],
			],
			'member-creator'      => [
				'type'        => 'member-profile',
				'title'       => __( 'Creator', 'stax-buddy-builder' ),
				'description' => __( 'A big cover with a centered avatar and name, made for people who publish.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ $big_cover ] ),
					self::row(
						[
							[ 'member-avatar', [], [ 'textAlign' => 'center', 'margin' => [ 'top' => '-80px' ] ] ],
							[ 'member-username', [ 'username_type' => 'full_name', 'username_tag' => 'h1' ], [ 'textAlign' => 'center' ] ],
							[ 'member-latest-update', [], [ 'textAlign' => 'center' ] ],
							[ 'member-buttons' ],
						]
					),
					self::row( [ $notices, $member_nav, [ 'member-content' ] ] ),
				],
			],
			'member-professional' => [
				'type'        => 'member-profile',
				'title'       => __( 'Professional', 'stax-buddy-builder' ),
				'description' => __( 'A compact header with a sidebar for name, status and actions, content on the right.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ [ 'member-cover', [], [ 'minHeight' => '180px', 'radius' => '8px' ] ] ] ),
					self::cols(
						[
							[ 30, [ [ 'member-avatar' ], [ 'member-username', [ 'username_type' => 'full_name' ] ], [ 'member-username' ], [ 'member-meta' ], [ 'member-buttons' ], [ 'member-latest-update' ] ] ],
							[ 70, [ $notices, $member_nav, [ 'member-content' ] ] ],
						]
					),
				],
			],
			'member-social'       => [
				'type'        => 'member-profile',
				'title'       => __( 'Social', 'stax-buddy-builder' ),
				'description' => __( 'Activity first: a slim header and the stream next to a friends and groups rail.', 'stax-buddy-builder' ),
				'layout'      => [
					self::cols(
						[
							[ 15, [ [ 'member-avatar', [ 'avatar_size' => 'thumb' ] ] ] ],
							[ 55, [ [ 'member-username', [ 'username_type' => 'full_name', 'username_tag' => 'h3' ] ], [ 'member-meta' ] ] ],
							[ 30, [ [ 'member-buttons' ] ] ],
						],
						'center'
					),
					self::row( [ $notices, $member_nav, [ 'member-content' ] ] ),
				],
			],

			// Group profile.
			'group-community'     => [
				'type'        => 'group-profile',
				'title'       => __( 'Community', 'stax-buddy-builder' ),
				'description' => __( 'Cover, group name, status and join button, then the group tabs.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ $group_cov ] ),
					self::cols(
						[
							[ 20, [ [ 'group-avatar' ] ] ],
							[ 55, [ [ 'group-name', [], [ 'fontSize' => '1.75rem' ] ], [ 'group-status' ], [ 'group-last-activity' ], [ 'group-buttons' ] ] ],
							[ 25, [ [ 'group-leadership' ] ] ],
						],
						'center'
					),
					self::row( [ [ 'group-description' ], $notices, [ 'group-nav' ], [ 'group-content' ] ] ),
				],
			],
			'group-course'        => [
				'type'        => 'group-profile',
				'title'       => __( 'Course or cohort', 'stax-buddy-builder' ),
				'description' => __( 'Description first with the leaders on the side, made for classes and cohorts.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ [ 'group-name', [], [ 'fontSize' => '2.25rem' ] ], [ 'group-status' ] ] ),
					self::cols(
						[
							[ 65, [ [ 'group-description' ], [ 'group-buttons' ] ] ],
							[ 35, [ [ 'group-avatar' ], [ 'group-leadership' ], [ 'group-last-activity' ] ] ],
						]
					),
					self::row( [ $notices, [ 'group-nav' ], [ 'group-content' ] ] ),
				],
			],

			// Directories.
			'members-grid'        => [
				'type'        => 'members-directory',
				'title'       => __( 'Grid cards', 'stax-buddy-builder' ),
				'description' => __( 'Tabs, search and a three-column grid of member cards.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-nav' ], [ 'directory-filters' ], [ 'members-listing', [ 'columns' => 3, 'columns_tablet' => 2, 'columns_mobile' => 1 ] ] ] ) ],
			],
			'members-list'        => [
				'type'        => 'members-directory',
				'title'       => __( 'Compact list', 'stax-buddy-builder' ),
				'description' => __( 'Search first and a one-column list of members.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-filters', [ 'content_display' => '9' ] ], [ 'directory-nav' ], [ 'members-listing', [ 'columns' => 1, 'columns_tablet' => 1, 'columns_mobile' => 1 ] ] ] ) ],
			],
			'groups-grid'         => [
				'type'        => 'groups-directory',
				'title'       => __( 'Grid cards', 'stax-buddy-builder' ),
				'description' => __( 'Tabs, search and a three-column grid of group cards.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-nav' ], [ 'directory-filters' ], [ 'groups-listing', [ 'columns' => 3, 'columns_tablet' => 2, 'columns_mobile' => 1 ] ] ] ) ],
			],
			'groups-list'         => [
				'type'        => 'groups-directory',
				'title'       => __( 'Compact list', 'stax-buddy-builder' ),
				'description' => __( 'Search first and a one-column list of groups.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-filters', [ 'content_display' => '9' ] ], [ 'directory-nav' ], [ 'groups-listing', [ 'columns' => 1, 'columns_tablet' => 1, 'columns_mobile' => 1 ] ] ] ) ],
			],

			// Activity.
			'activity-feed'       => [
				'type'        => 'sitewide-activity',
				'title'       => __( 'Feed', 'stax-buddy-builder' ),
				'description' => __( 'Post form, tabs, filters and the stream in one column.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'activity-form' ], [ 'activity-nav' ], [ 'activity-filters' ], [ 'activity-stream' ] ] ) ],
			],
			'activity-sidebar'    => [
				'type'        => 'sitewide-activity',
				'title'       => __( 'Feed with sidebar', 'stax-buddy-builder' ),
				'description' => __( 'The stream with a side column of newest members and groups.', 'stax-buddy-builder' ),
				'layout'      => [
					self::cols(
						[
							[ 68, [ [ 'activity-form' ], [ 'activity-nav' ], [ 'activity-filters' ], [ 'activity-stream' ] ] ],
							[ 32, [ [ 'members-loop', [ 'columns' => 1, 'columns_tablet' => 1, 'columns_mobile' => 1 ] ], [ 'groups-loop', [ 'columns' => 1, 'columns_tablet' => 1, 'columns_mobile' => 1 ] ] ] ],
						]
					),
				],
			],
		];

		return array_merge( $presets, self::kleo() );
	}

	/**
	 * KLEO defaults (one per page type, listed only with KLEO 7; KLEO's setup wizard applies them through the
	 * apply-preset ability). They use KLEO's theme.json palette slugs (base, base-2, contrast, accent...).
	 *
	 * @return array
	 */
	private static function kleo() {
		$card = [
			'background' => 'base-2',
			'radius'     => '16px',
			'padding'    => '24px',
		];

		return [
			'kleo-member-profile'    => [
				'type'        => 'member-profile',
				'theme'       => 'kleo',
				'title'       => __( 'KLEO profile', 'stax-buddy-builder' ),
				'description' => __( 'The KLEO 7 member profile.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ [ 'member-cover', [ 'overlay_background' => true ], [ 'minHeight' => '320px', 'radius' => '20px' ] ] ] ),
					self::cols(
						[
							[ 25, [ [ 'member-avatar', [], [ 'margin' => [ 'top' => '-90px' ] ] ] ] ],
							[ 50, [ [ 'member-username', [ 'username_type' => 'full_name', 'username_tag' => 'h1' ] ], [ 'member-username', [], [ 'text' => 'contrast-2' ] ], [ 'member-meta' ] ] ],
							[ 25, [ [ 'member-buttons' ] ] ],
						],
						'center'
					),
					self::row( [ [ 'member-nav', [], [ 'background' => 'base-2', 'radius' => '12px', 'padding' => '8px' ] ], [ 'notices' ], [ 'member-content' ] ] ),
				],
			],
			'kleo-group-profile'     => [
				'type'        => 'group-profile',
				'theme'       => 'kleo',
				'title'       => __( 'KLEO group', 'stax-buddy-builder' ),
				'description' => __( 'The KLEO 7 group page.', 'stax-buddy-builder' ),
				'layout'      => [
					self::row( [ [ 'group-cover', [], [ 'minHeight' => '280px', 'radius' => '20px' ] ] ] ),
					self::cols(
						[
							[ 20, [ [ 'group-avatar', [], [ 'margin' => [ 'top' => '-70px' ] ] ] ] ],
							[ 55, [ [ 'group-name', [], [ 'fontSize' => '2rem' ] ], [ 'group-status' ], [ 'group-last-activity' ] ] ],
							[ 25, [ [ 'group-buttons' ] ] ],
						],
						'center'
					),
					self::cols(
						[
							[ 70, [ [ 'group-nav' ], [ 'notices' ], [ 'group-content' ] ] ],
							[ 30, [ [ 'group-description', [], $card ], [ 'group-leadership', [], $card ] ] ],
						]
					),
				],
			],
			'kleo-members-directory' => [
				'type'        => 'members-directory',
				'theme'       => 'kleo',
				'title'       => __( 'KLEO members', 'stax-buddy-builder' ),
				'description' => __( 'The KLEO 7 members directory.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-nav' ], [ 'directory-filters', [], $card ], [ 'members-listing', [ 'columns' => 4, 'columns_tablet' => 2, 'columns_mobile' => 1 ] ] ] ) ],
			],
			'kleo-groups-directory'  => [
				'type'        => 'groups-directory',
				'theme'       => 'kleo',
				'title'       => __( 'KLEO groups', 'stax-buddy-builder' ),
				'description' => __( 'The KLEO 7 groups directory.', 'stax-buddy-builder' ),
				'layout'      => [ self::row( [ [ 'directory-nav' ], [ 'directory-filters', [], $card ], [ 'groups-listing', [ 'columns' => 3, 'columns_tablet' => 2, 'columns_mobile' => 1 ] ] ] ) ],
			],
			'kleo-activity'          => [
				'type'        => 'sitewide-activity',
				'theme'       => 'kleo',
				'title'       => __( 'KLEO activity', 'stax-buddy-builder' ),
				'description' => __( 'The KLEO 7 activity page.', 'stax-buddy-builder' ),
				'layout'      => [
					self::cols(
						[
							[ 70, [ [ 'activity-form', [], $card ], [ 'activity-nav' ], [ 'activity-filters' ], [ 'activity-stream' ] ] ],
							[ 30, [ [ 'members-loop', [ 'columns' => 1, 'columns_tablet' => 1, 'columns_mobile' => 1 ], $card ] ] ],
						]
					),
				],
			],
		];
	}
}
