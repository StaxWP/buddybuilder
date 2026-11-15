<?php
/**
 * Styles (2.0, decision 2026-11-15): complete, hand-designed looks for every community page, one folder per style
 * under patterns/<style>/<page>.html (block markup). Each page of a style is a preset with id "<style>-<page>", so
 * the look picker, the setup, the abilities and the block inserter use them like any preset.
 *
 * Elementor versions stay generated from the row and column description of the closest 2.0-dev look ('elementor').
 * The 2.0-dev looks (member-classic, members-grid, ...) stay resolvable for layouts made from them, but leave the
 * pickers (Presets::all() skips presets marked 'legacy').
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

defined( 'ABSPATH' ) || die();

/**
 * Style registry.
 */
final class Styles {

	/** Pages a style covers, in picker order. */
	const PAGES = [ 'member-profile', 'members-directory', 'group-profile', 'groups-directory', 'sitewide-activity' ];

	/**
	 * Styles: slug => [ name, description, for whom, closest 2.0-dev look per page (Elementor version) ].
	 *
	 * @return array
	 */
	public static function all() {
		$styles = [
			'classic'      => [
				'name'        => __( 'Classic', 'stax-buddy-builder' ),
				'description' => __( 'The familiar community page, finished: cover, photo and name on the side, cards with a soft lift.', 'stax-buddy-builder' ),
				'audience'    => __( 'Any community', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-classic', 'members-directory' => 'members-grid', 'group-profile' => 'group-community', 'groups-directory' => 'groups-grid', 'sitewide-activity' => 'activity-feed' ],
			],
			'creator'      => [
				'name'        => __( 'Creator', 'stax-buddy-builder' ),
				'description' => __( 'A big cover, a centered name and stats up front. Made for people who publish.', 'stax-buddy-builder' ),
				'audience'    => __( 'Creators, artists, fan communities', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-creator', 'members-directory' => 'members-grid', 'group-profile' => 'group-community', 'groups-directory' => 'groups-grid', 'sitewide-activity' => 'activity-feed' ],
			],
			'professional' => [
				'name'        => __( 'Professional', 'stax-buddy-builder' ),
				'description' => __( 'A compact header, facts in a side card and list directories. Quiet and precise.', 'stax-buddy-builder' ),
				'audience'    => __( 'Professional networks, alumni, associations', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-professional', 'members-directory' => 'members-list', 'group-profile' => 'group-course', 'groups-directory' => 'groups-list', 'sitewide-activity' => 'activity-sidebar' ],
			],
			'social'       => [
				'name'        => __( 'Social', 'stax-buddy-builder' ),
				'description' => __( 'Feed first, a slim header and people to know on the side.', 'stax-buddy-builder' ),
				'audience'    => __( 'Friend networks, campus and hobby communities', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-social', 'members-directory' => 'members-list', 'group-profile' => 'group-community', 'groups-directory' => 'groups-list', 'sitewide-activity' => 'activity-sidebar' ],
			],
		];

		/**
		 * Filters the styles (themes and add-ons can add their own; pages come from their pattern files).
		 *
		 * @since 2.0.0
		 *
		 * @param array $styles slug => [ name, description, audience, elementor, dir? ].
		 */
		return (array) apply_filters( 'buddy_builder/styles', $styles );
	}

	/**
	 * Presets of every style page that has a pattern file.
	 *
	 * @param array $legacy The 2.0-dev presets (for the Elementor descriptions).
	 * @return array id => preset
	 */
	public static function presets( array $legacy ) {
		$presets = [];

		foreach ( self::all() as $slug => $style ) {
			$dir = isset( $style['dir'] ) ? $style['dir'] : BPB_BASE_PATH . 'patterns/' . $slug;

			foreach ( self::PAGES as $page ) {
				$file = trailingslashit( $dir ) . $page . '.html';

				if ( ! is_readable( $file ) ) {
					continue;
				}

				$base = isset( $style['elementor'][ $page ], $legacy[ $style['elementor'][ $page ] ] ) ? $legacy[ $style['elementor'][ $page ] ] : null;

				$presets[ $slug . '-' . $page ] = [
					'type'        => $page,
					'style'       => $slug,
					'title'       => $style['name'],
					'description' => $style['description'],
					'content'     => (string) file_get_contents( $file ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file.
					'layout'      => $base ? $base['layout'] : [ 'rows' => [] ],
				];
			}
		}

		return $presets;
	}
}
