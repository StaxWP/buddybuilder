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

		if ( ! $entry || Registry::BLOCKS !== $entry['builder'] ) {
			return;
		}

		wp_enqueue_style( self::tokens() );

		if ( wp_style_is( 'stax-buddy-builder-front', 'registered' ) ) {
			wp_enqueue_style( 'stax-buddy-builder-front' );
		}
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
	}
}
