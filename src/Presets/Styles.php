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

	/** Pro elements, for the editor placeholder when BuddyBuilder Pro is not active. */
	const PRO_ELEMENTS = [ 'shortcuts-nav', 'group-rail', 'member-list', 'highlights-rail' ];

	/**
	 * Register hooks: the Classic fallback for pages whose style is not available, its notice, editor placeholders.
	 */
	public static function hooks() {
		add_filter( 'buddy_builder/template/resolve', [ __CLASS__, 'fallback' ], 99, 2 );
		add_action( 'admin_notices', [ __CLASS__, 'fallback_notice' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'placeholders' ] );
	}

	/**
	 * Style slug a layout uses ('' for none): the look class in a block layout, or the meta of an Elementor template.
	 *
	 * @param int $id Layout post id.
	 * @return string
	 */
	public static function of_layout( $id ) {
		$post = $id ? get_post( $id ) : null;

		if ( ! $post ) {
			return '';
		}

		$source = 'bpb_template' === $post->post_type ? (string) $post->post_content : (string) get_post_meta( $post->ID, '_bpb_look', true );

		return preg_match( '/\\bbpb-look--([a-z0-9_-]+)/', $source, $m ) ? $m[1] : '';
	}

	/**
	 * Whether a layout uses a style that is not available (for example a Pro style without BuddyBuilder Pro).
	 *
	 * @param int $id Layout post id.
	 * @return bool
	 */
	public static function is_missing( $id ) {
		$slug = self::of_layout( $id );

		return '' !== $slug && ! isset( self::all()[ $slug ] );
	}

	/**
	 * A page whose layout uses a style that is not available renders with the Classic style page for visitors (no
	 * stored data changes: reactivating BuddyBuilder Pro brings the layout back at once).
	 *
	 * @param array  $entry Entry.
	 * @param string $type  Template type.
	 * @return array
	 */
	public static function fallback( $entry, $type ) {
		if ( ! is_array( $entry ) || isset( $entry['content'] ) || empty( $entry['id'] ) || ! self::is_missing( (int) $entry['id'] ) ) {
			return $entry;
		}

		$classic = Presets::get( 'classic-' . $type );

		if ( ! $classic || empty( $classic['content'] ) ) {
			return $entry;
		}

		\Buddy_Builder\Assets\Assets::looks( 'classic' );

		return [
			'builder' => 'blocks',
			'id'      => (int) $entry['id'],
			'content' => $classic['content'],
		];
	}

	/**
	 * One notice on BuddyBuilder screens while a page shows Classic in place of an unavailable style.
	 */
	public static function fallback_notice() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! current_user_can( 'manage_options' ) || ( false === strpos( (string) $screen->id, 'buddy-builder' ) && 'bpb_template' !== $screen->post_type ) ) {
			return;
		}

		foreach ( \Buddy_Builder\Templates\Types::all() as $slug => $type ) {
			$entry = \Buddy_Builder\Templates\Registry::get( $slug );

			if ( ! empty( $entry['id'] ) && self::is_missing( (int) $entry['id'] ) ) {
				echo '<div class="notice notice-info"><p>' . esc_html__( 'Pages using Pro styles show the Classic style until BuddyBuilder Pro is active.', 'stax-buddy-builder' ) . '</p></div>';
				return;
			}
		}
	}

	/**
	 * Block editor: Pro elements without BuddyBuilder Pro show a small placeholder (editor only; visitors see nothing).
	 */
	public static function placeholders() {
		if ( defined( 'BPB_PRO_VERSION' ) ) {
			return;
		}

		wp_register_script( 'bpb-pro-placeholders', '', [ 'wp-blocks', 'wp-element' ], BPB_VERSION, true );
		wp_enqueue_script( 'bpb-pro-placeholders' );
		wp_add_inline_script(
			'bpb-pro-placeholders',
			'( function ( wp, names, text ) { names.forEach( function ( name ) { if ( wp.blocks.getBlockType( "buddybuilder/" + name ) ) { return; } wp.blocks.registerBlockType( "buddybuilder/" + name, { title: name, category: "buddybuilder", supports: { inserter: false, html: false }, attributes: {}, edit: function () { return wp.element.createElement( "p", { className: "bpb-el-pro-placeholder", style: { margin: 0, padding: "6px 10px", border: "1px dashed #c3c4c7", color: "#50575e", fontSize: "12px" } }, text ); }, save: function () { return null; } } ); } ); }( window.wp, ' . wp_json_encode( self::PRO_ELEMENTS ) . ', ' . wp_json_encode( __( 'This element needs BuddyBuilder Pro.', 'stax-buddy-builder' ) ) . ' ) );'
		);
	}

	/**
	 * BuddyBuilder Pro styles shown as previews in the setup while Pro is not active: slug => name ([] with Pro).
	 *
	 * @return array
	 */
	public static function pro_teasers() {
		$names = [
			'creator'      => __( 'Creator', 'stax-buddy-builder' ),
			'network'      => __( 'Network', 'stax-buddy-builder' ),
			'career'       => __( 'Career', 'stax-buddy-builder' ),
			'hub'          => __( 'Hub', 'stax-buddy-builder' ),
			'professional' => __( 'Professional', 'stax-buddy-builder' ),
			'social'       => __( 'Social', 'stax-buddy-builder' ),
		];

		return array_diff_key( $names, self::all() );
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
