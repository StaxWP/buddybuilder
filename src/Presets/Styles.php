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
 * Themes and kits register their own style with bpb_register_style() (docs/registering-a-style.md): a folder of page
 * patterns, a token stylesheet, a label, a preview image and an optional "recommended" flag that preselects it in the
 * setup.
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
	 * Styles registered by themes and kits (bpb_register_style()).
	 *
	 * @var array
	 */
	private static $registered = [];

	/**
	 * Register a style (public API, see bpb_register_style()).
	 *
	 * @param string $slug Style slug: lowercase letters, numbers, "-" and "_".
	 * @param array  $args {
	 *     @type string        $name        Label in the pickers. Required.
	 *     @type string        $dir         Folder with the page patterns (<page>.html, block markup). Required.
	 *     @type string        $css         URL of the token stylesheet, loaded after the look components. Optional.
	 *     @type string[]      $css_deps    Extra stylesheet handles the tokens depend on. Optional.
	 *     @type string        $version     Stylesheet version. Optional.
	 *     @type string        $preview     Preview image URL for the setup picker. Optional (a drawn thumbnail otherwise).
	 *     @type string        $description One sentence for the picker. Optional.
	 *     @type string        $audience    Who it is for. Optional.
	 *     @type bool|callable $recommended Preselect it in the setup (for example when the theme that ships it is
	 *                                      active). Optional, default false.
	 *     @type array         $elementor   Closest 2.0-dev look per page for the Elementor version. Optional.
	 * }
	 * @return bool False when the slug or the required arguments are missing.
	 */
	public static function register( $slug, array $args ) {
		$slug = strtolower( (string) $slug );

		if ( ! preg_match( '/^[a-z0-9_-]+$/', $slug ) || empty( $args['name'] ) || empty( $args['dir'] ) ) {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'A style needs a slug (a to z, 0 to 9, "-" and "_"), a name and a dir.', 'stax-buddy-builder' ), '2.0.0' );
			return false;
		}

		self::$registered[ $slug ] = wp_parse_args(
			$args,
			[
				'description' => '',
				'audience'    => '',
				'css'         => '',
				'css_deps'    => [],
				'version'     => BPB_VERSION,
				'preview'     => '',
				'recommended' => false,
				'elementor'   => [],
			]
		);
		Presets::flush();

		return true;
	}

	/**
	 * Unregister a style (a registered one, or a built-in one a theme wants to hide).
	 *
	 * @param string $slug Style slug.
	 */
	public static function unregister( $slug ) {
		unset( self::$registered[ $slug ] );
		add_filter(
			'buddy_builder/styles',
			static function ( $styles ) use ( $slug ) {
				unset( $styles[ $slug ] );
				return $styles;
			}
		);
		Presets::flush();
	}

	/**
	 * Whether a style is recommended on this site (its flag, or its callback's answer).
	 *
	 * @param array $style Style.
	 * @return bool
	 */
	public static function is_recommended( array $style ) {
		$flag = isset( $style['recommended'] ) ? $style['recommended'] : false;

		return (bool) ( is_callable( $flag ) ? call_user_func( $flag ) : $flag );
	}

	/**
	 * Styles: slug => [ name, description, for whom, closest 2.0-dev look per page (Elementor version) ], the
	 * registered ones after the built-in ones, a recommended one first.
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
				'description' => __( 'A creator platform layout: a centered profile with a gradient ring, stats as a row of numbers, highlight bubbles, posts as a photo grid and a tab bar on phones.', 'stax-buddy-builder' ),
				'audience'    => __( 'Creators, artists, photographers and fan communities', 'stax-buddy-builder' ),
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
			'network'      => [
				'name'        => __( 'Network', 'stax-buddy-builder' ),
				'description' => __( 'A social network layout: shortcuts on the left, the feed in the middle, people and groups on the right, a tab bar on phones.', 'stax-buddy-builder' ),
				'audience'    => __( 'Large friend networks, alumni and member communities', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-classic', 'members-directory' => 'members-grid', 'group-profile' => 'group-community', 'groups-directory' => 'groups-grid', 'sitewide-activity' => 'activity-sidebar' ],
			],
			'career'       => [
				'name'        => __( 'Career', 'stax-buddy-builder' ),
				'description' => __( 'A professional network layout: a sticky profile card and the profile as stacked section cards. Calm, light and dense.', 'stax-buddy-builder' ),
				'audience'    => __( 'Professional networks, associations, alumni and job communities', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-professional', 'members-directory' => 'members-list', 'group-profile' => 'group-course', 'groups-directory' => 'groups-list', 'sitewide-activity' => 'activity-sidebar' ],
			],
			'hub'          => [
				'name'        => __( 'Hub', 'stax-buddy-builder' ),
				'description' => __( 'A chat community layout, dark by default: a rail of groups, a column of sections, the stream as message rows and who is online.', 'stax-buddy-builder' ),
				'audience'    => __( 'Gaming, creator and fan communities that live in real time', 'stax-buddy-builder' ),
				'elementor'   => [ 'member-profile' => 'member-social', 'members-directory' => 'members-list', 'group-profile' => 'group-community', 'groups-directory' => 'groups-list', 'sitewide-activity' => 'activity-sidebar' ],
			],
		];

		$styles = array_merge( $styles, self::$registered );

		/**
		 * Filters the styles (prefer bpb_register_style(); pages come from their pattern files).
		 *
		 * @since 2.0.0
		 *
		 * @param array $styles slug => [ name, description, audience, elementor, dir?, css?, preview?, recommended? ].
		 */
		$styles = (array) apply_filters( 'buddy_builder/styles', $styles );

		// A recommended style leads the pickers.
		foreach ( $styles as $slug => $style ) {
			if ( self::is_recommended( (array) $style ) ) {
				$styles = [ $slug => $style ] + $styles;
				break;
			}
		}

		return $styles;
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
