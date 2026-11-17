<?php
/**
 * Front-end and editor styles for block layouts.
 *
 * Elementor layouts keep the 1.x asset logic (core/Plugin.php: front_css, bp_scripts) unchanged. Block layouts get
 * the same BuddyPress styles in the head, plus a small token layer (--bpb-* custom properties with fallbacks to the
 * theme.json presets), and the block editor canvas of a layout loads the same files so previews match the front end.
 * Per-element styles load only when an element renders (element.json "style", both builders).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Assets;

use Buddy_Builder\Templates\Current;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;

defined( 'ABSPATH' ) || die();

/**
 * Styles for block layouts.
 */
final class Assets {

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'front' ], 13 );
		add_action( 'bp_enqueue_scripts', [ __CLASS__, 'split_bp_styles' ], 20 );
		add_action( 'enqueue_block_assets', [ __CLASS__, 'editor_canvas' ] );
	}

	/**
	 * Suffix of the built files.
	 *
	 * @return string
	 */
	private static function min() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	/**
	 * Token layer handle (registered once).
	 *
	 * @return string
	 */
	public static function tokens() {
		if ( ! wp_style_is( 'bpb-tokens', 'registered' ) ) {
			wp_register_style( 'bpb-tokens', BPB_ASSETS_URL . 'css/tokens.css', [], BPB_VERSION );
		}

		return 'bpb-tokens';
	}

	/**
	 * Block layout on the current page: BuddyPress styles in the head (Elementor widgets print them on render).
	 */
	public static function front() {
		$entry = Current::entry();

		// A layout whose style is not available renders as Classic (Styles::fallback()): Classic's stylesheet.
		if ( $entry && ! empty( $entry['id'] ) && \Buddy_Builder\Presets\Styles::is_missing( (int) $entry['id'] ) ) {
			self::looks( 'classic' );
		}

		// An Elementor template made from a 2.0 style: the style's stylesheet in the head.
		if ( $entry && Registry::BLOCKS !== $entry['builder'] && ! empty( $entry['id'] ) ) {
			$look = (string) get_post_meta( (int) $entry['id'], '_bpb_look', true );

			if ( '' !== $look && preg_match( '/\bbpb-look--([a-z0-9_-]+)/', $look, $m ) ) {
				self::looks( $m[1] );

				// After Elementor's frontend CSS, so its image reset (no radius, border or shadow) does not win.
				$styles = wp_styles();
				if ( isset( $styles->registered['bpb-looks'], $styles->registered['elementor-frontend'] ) && ! in_array( 'elementor-frontend', $styles->registered['bpb-looks']->deps, true ) ) {
					$styles->registered['bpb-looks']->deps[] = 'elementor-frontend';
				}
			}
		}

		self::item_looks();

		if ( ! $entry || Registry::BLOCKS !== $entry['builder'] ) {
			return;
		}

		wp_enqueue_style( self::tokens() );

		// Looks: the component styles and the style of the layout, in the head (found in the layout markup).
		$post = $entry['id'] ? get_post( $entry['id'] ) : null;

		if ( $post ) {
			foreach ( self::looks_in( $post->post_content ) as $style ) {
				self::looks( $style );
			}
		}

		if ( wp_style_is( 'stax-buddy-builder-front', 'registered' ) ) {
			wp_enqueue_style( 'stax-buddy-builder-front' );
		}
	}

	/**
	 * Item layouts (member card, group card, activity item) made from a style: the style's stylesheet in the head of
	 * every BuddyPress page, so cards in lists, rails and AJAX loads look right from the first paint.
	 */
	private static function item_looks() {
		if ( ! function_exists( 'is_buddypress' ) || ! is_buddypress() ) {
			return;
		}

		foreach ( array_keys( \Buddy_Builder\Templates\Types::all() ) as $type ) {
			if ( '-item' !== substr( $type, -5 ) ) {
				continue;
			}

			foreach ( self::entry_looks( \Buddy_Builder\Templates\Dispatcher::resolve( $type ) ) as $style ) {
				self::looks( $style );
			}
		}
	}

	/**
	 * Styles a layout entry uses: the look classes of block markup (a layout or a look preview), the _bpb_look meta of
	 * an Elementor template.
	 *
	 * @param array $entry Registry entry [ builder, id, content? ].
	 * @return string[]
	 */
	public static function entry_looks( $entry ) {
		if ( ! is_array( $entry ) ) {
			return [];
		}

		if ( isset( $entry['content'] ) ) {
			return self::looks_in( (string) $entry['content'] );
		}

		if ( empty( $entry['id'] ) || ! Registry::is_post_populated( (int) $entry['id'] ) ) {
			return [];
		}

		if ( Registry::BLOCKS === $entry['builder'] ) {
			return self::looks_in( (string) get_post_field( 'post_content', (int) $entry['id'] ) );
		}

		$look = (string) get_post_meta( (int) $entry['id'], '_bpb_look', true );

		return preg_match( '/\bbpb-look--([a-z0-9_-]+)/', $look, $m ) ? [ $m[1] ] : [];
	}

	/** Look styles shipped in assets/css/looks/<style>.css (registered styles bring their own stylesheet). */
	const LOOK_STYLES = [ 'classic' ];

	/** Elements that need the look components. */
	const LOOK_ELEMENTS = [ 'member-header', 'member-about', 'group-header', 'directory-header', 'people-rail', 'composer' ];

	/**
	 * Enqueue the look components (handle bpb-looks) and one style (bpb-look-<style>).
	 *
	 * @param string $style Style slug, '' for the components only.
	 */
	public static function looks( $style = '' ) {
		if ( ! wp_style_is( 'bpb-looks', 'registered' ) ) {
			\Buddy_Builder\Templates\Cards::register();
			wp_register_style( 'bpb-looks', BPB_ASSETS_URL . 'css/looks/base.css', [ self::tokens(), 'bpb-cards' ], BPB_VERSION );
		}

		if ( ! wp_style_is( 'bpb-looks', 'enqueued' ) ) {
			// Words the look styles print with CSS (empty states), translated.
			wp_add_inline_style( 'bpb-looks', '.bpb-look{--bpb-t-no-posts:"' . addcslashes( wp_strip_all_tags( __( 'No posts yet', 'stax-buddy-builder' ) ), '"\\' ) . '"}' );
		}

		wp_enqueue_style( 'bpb-looks' );

		if ( '' === $style ) {
			return;
		}

		$styles = \Buddy_Builder\Presets\Styles::all();

		if ( ! empty( $styles[ $style ]['css'] ) ) {
			$deps = isset( $styles[ $style ]['css_deps'] ) ? (array) $styles[ $style ]['css_deps'] : [];
			wp_enqueue_style( 'bpb-look-' . $style, $styles[ $style ]['css'], array_merge( [ 'bpb-looks' ], $deps ), isset( $styles[ $style ]['version'] ) ? $styles[ $style ]['version'] : BPB_VERSION );
		} elseif ( in_array( $style, self::LOOK_STYLES, true ) && file_exists( BPB_BASE_PATH . 'assets/css/looks/' . $style . '.css' ) ) {
			wp_enqueue_style( 'bpb-look-' . $style, BPB_ASSETS_URL . 'css/looks/' . $style . '.css', [ 'bpb-looks' ], BPB_VERSION );
		}

		/**
		 * Fires after a style's stylesheet was enqueued (BuddyBuilder Pro adds the card, register and group creation
		 * rules of its presets).
		 *
		 * @since 2.0.0
		 *
		 * @param string $style Style slug.
		 */
		do_action( 'buddy_builder/looks/enqueued', $style );
	}

	/**
	 * Look styles used by block markup: '' when only look elements are used, plus each "bpb-look--<style>" class.
	 *
	 * @param string $content Block markup.
	 * @return string[]
	 */
	public static function looks_in( $content ) {
		$found = [];

		if ( preg_match_all( '/\bbpb-look--([a-z0-9_-]+)/', (string) $content, $m ) ) {
			$found = array_values( array_unique( $m[1] ) );
		}

		if ( ! $found && preg_match( '#wp:buddybuilder/(' . implode( '|', self::LOOK_ELEMENTS ) . ')\b#', (string) $content ) ) {
			$found[] = '';
		}

		return $found;
	}

	/**
	 * Per template type BuddyPress styles (task 10): the page loads only the sections of the Nouveau fork its type
	 * needs (templates/buddypress/assets/parts, built by bin/split-css.py, order kept). Same handle, so the BuddyPress
	 * cover image inline CSS still attaches to it. On by default (the compatibility suite passed with it on and off);
	 * turn it off with add_filter( 'buddy_builder/split_css', '__return_false' ).
	 */
	public static function split_bp_styles() {
		/**
		 * Filters whether BuddyPress pages load the per template type part of the Nouveau fork.
		 *
		 * @param bool $split Split.
		 */
		if ( bpb_is_buddyboss() || ! apply_filters( 'buddy_builder/split_css', true ) ) {
			return;
		}

		$type = Current::type();
		$file = 'templates/buddypress/assets/parts/' . $type . self::min() . '.css';

		if ( '' === $type || ! Current::entry() || ! file_exists( BPB_BASE_PATH . $file ) ) {
			return;
		}

		wp_deregister_style( 'stax-buddy-builder-bp' );
		wp_register_style( 'stax-buddy-builder-bp', BPB_BASE_URL . $file, [], BPB_VERSION );

		// Sections only some screens show live in screen files, loaded after the page part on those screens (W1).
		foreach ( self::screen_parts() as $screen ) {
			$handle = 'stax-buddy-builder-bp-' . $screen;
			wp_register_style( $handle, BPB_BASE_URL . 'templates/buddypress/assets/parts/screen-' . $screen . self::min() . '.css', [ 'stax-buddy-builder-bp' ], BPB_VERSION );
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Screen files the current BuddyPress screen needs (bin/split-css.py SCREENS).
	 *
	 * @return string[]
	 */
	public static function screen_parts() {
		$parts = [];

		if ( function_exists( 'bp_is_messages_component' ) && bp_is_messages_component() ) {
			$parts[] = 'messages';
		}

		if ( function_exists( 'bp_is_settings_component' ) && bp_is_settings_component() ) {
			$parts[] = 'settings';
		}

		if ( ( function_exists( 'bp_is_group_admin_page' ) && bp_is_group_admin_page() ) || ( function_exists( 'bp_is_group_invites' ) && bp_is_group_invites() ) || ( function_exists( 'bp_is_user_groups' ) && bp_is_user_groups() && bp_is_current_action( 'invites' ) ) ) {
			$parts[] = 'group-manage';
		}

		/**
		 * Filters the screen CSS files loaded next to the page part.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $parts messages|settings|group-manage.
		 */
		return (array) apply_filters( 'buddy_builder/screen_css', $parts );
	}

	/**
	 * Block editor canvas of a layout: the BuddyPress styles (the canvas iframe gets enqueue_block_assets styles).
	 */
	public static function editor_canvas() {
		if ( ! is_admin() || PostType::POST_TYPE !== get_post_type() ) {
			return;
		}

		$min = self::min();

		if ( ! bpb_is_buddyboss() ) {
			wp_enqueue_style( 'stax-buddy-builder-bp-editor', BPB_BASE_URL . 'templates/buddypress/assets/buddypress' . $min . '.css', [], BPB_VERSION );
		} else {
			wp_enqueue_style( 'stax-buddy-builder-boss-editor', BPB_ASSETS_URL . 'css/buddyboss' . $min . '.css', [], BPB_VERSION );
		}

		if ( function_exists( 'buddypress' ) ) {
			wp_enqueue_style( 'stax-buddy-builder-avatar-editor', buddypress()->plugin_url . 'bp-core/css/avatar' . $min . '.css', [], BPB_VERSION );
		}

		wp_enqueue_style( self::tokens() );
		wp_enqueue_style( 'stax-buddy-builder-front-editor', BPB_ASSETS_URL . 'css/index' . $min . '.css', [], BPB_VERSION );
		wp_enqueue_style( 'bpb-editor-canvas', BPB_ASSETS_URL . 'css/editor-canvas.css', [], BPB_VERSION );

		// The styles the layout uses (a layout made from a style, a card from "Design the card").
		foreach ( self::looks_in( (string) get_post_field( 'post_content', get_the_ID() ) ) as $style ) {
			self::looks( $style );
		}
	}
}
