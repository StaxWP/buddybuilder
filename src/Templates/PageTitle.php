<?php
/**
 * The theme's page title on community pages ("gabriel", "Members", "Groups" above the layout).
 *
 * Themes print the title of the page BuddyPress renders in (block themes: the Post Title block of the page template;
 * classic themes: the_title() in the loop). A BuddyBuilder layout has its own header, so the title repeats it. The
 * setting (BuddyBuilder > Settings) hides it on community pages that show a BuddyBuilder layout.
 *
 * - Default: hidden on new sites (set on activation), shown on sites that had BuddyBuilder before 2.0 (no surprise
 *   after an update; the Settings card offers the switch).
 * - Only where a BuddyBuilder layout renders (Current::entry()); BuddyPress default pages keep the title.
 * - KLEO 7 has its own page title settings: left alone there.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Features;

defined( 'ABSPATH' ) || die();

/**
 * Theme page title.
 */
final class PageTitle {

	/** Option: 'hide' or 'show' (missing: show). */
	const OPTION = 'bpb_theme_title';

	/** Body class while the title is hidden. */
	const BODY_CLASS = 'bpb-no-theme-title';

	/**
	 * Cached decision for this request.
	 *
	 * @var bool|null
	 */
	private static $hide = null;

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		if ( is_admin() ) {
			return;
		}

		add_filter( 'render_block_core/post-title', [ __CLASS__, 'block_title' ], 10, 3 );
		add_filter( 'the_title', [ __CLASS__, 'classic_title' ], 10, 2 );
		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'styles' ], 20 );
	}

	/**
	 * Whether the setting applies on this site (KLEO 7 manages its own titles).
	 *
	 * @return bool
	 */
	public static function available() {
		return ! Features::is_kleo7();
	}

	/**
	 * Whether the setting is on.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return 'hide' === get_option( self::OPTION, 'show' );
	}

	/**
	 * New sites hide the title (called on activation of a new install).
	 */
	public static function default_for_new_site() {
		add_option( self::OPTION, 'hide' );
	}

	/**
	 * Whether the theme title is hidden on this request.
	 *
	 * @return bool
	 */
	public static function hides() {
		if ( null === self::$hide ) {
			if ( ! did_action( 'wp' ) ) {
				return false;
			}

			$hide = self::available() && self::enabled() && null !== Current::entry();

			/**
			 * Filters whether the theme's page title is hidden on this community page.
			 *
			 * @since 2.0.0
			 *
			 * @param bool   $hide Hidden (the setting is on and a BuddyBuilder layout renders here).
			 * @param string $type Page type.
			 */
			self::$hide = (bool) apply_filters( 'buddy_builder/theme_title/hide', $hide, Current::type() );
		}

		return self::$hide;
	}

	/**
	 * Block themes: the page's Post Title block (not the titles inside a Query Loop).
	 *
	 * @param string    $content  Block output.
	 * @param array     $block    Block.
	 * @param \WP_Block $instance Block instance.
	 * @return string
	 */
	public static function block_title( $content, $block, $instance = null ) {
		if ( $instance instanceof \WP_Block && isset( $instance->context['queryId'] ) ) {
			return $content;
		}

		return self::hides() ? '' : $content;
	}

	/**
	 * Classic themes: the title of the page in the main loop.
	 *
	 * @param string $title Title.
	 * @param int    $id    Post id.
	 * @return string
	 */
	public static function classic_title( $title, $id = 0 ) {
		if ( ! in_the_loop() || ! is_main_query() || wp_is_block_theme() ) {
			return $title;
		}

		return self::hides() ? '' : $title;
	}

	/**
	 * Body class.
	 *
	 * @param array $classes Classes.
	 * @return array
	 */
	public static function body_class( $classes ) {
		if ( self::hides() ) {
			$classes[] = self::BODY_CLASS;
		}

		return $classes;
	}

	/**
	 * Classic themes keep an empty title wrapper (Twenty themes: .entry-header with its margins and border): hidden.
	 */
	public static function styles() {
		if ( ! self::hides() || wp_is_block_theme() ) {
			return;
		}

		wp_register_style( 'bpb-theme-title', false, [], BPB_VERSION );
		wp_enqueue_style( 'bpb-theme-title' );
		wp_add_inline_style(
			'bpb-theme-title',
			'.' . self::BODY_CLASS . ' .entry-title:empty,.' . self::BODY_CLASS . ' .entry-header:has(> .entry-title:empty):not(:has(img)),.' . self::BODY_CLASS . ' .page-header:has(> .page-title:empty){display:none}'
		);
	}
}
