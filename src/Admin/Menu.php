<?php
/**
 * Menu fold (decision 5, 2026-11-15): BuddyBuilder shows three entries, Community pages, Features (Pro) and Settings.
 * The app (App) shows the same screens as tabs, plus Help.
 *
 * - Starter kits is gone (1.x Elementor kits, replaced by the 2.0 styles); its slug opens Community pages (App::MOVED).
 * - Help and status leaves the menu (a tab of the app); License and Agents (Pro) become cards of Settings.
 * - Every slug stays registered and reachable (bookmarks, the 1.x admin_post handler, links from other plugins);
 *   only the menu entries move. The highlighted menu entry follows the tab.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

defined( 'ABSPATH' ) || die();

/**
 * Menu fold and Settings tabs.
 */
final class Menu {

	/** Parent menu slug. */
	const PARENT = 'buddy-builder-settings';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		// After WordPress checked access to the page (admin.php) and before the menu prints (removing them on admin_menu
		// would make the pages unreachable).
		add_action( 'admin_head', [ __CLASS__, 'fold' ] );
		add_filter( 'submenu_file', [ __CLASS__, 'submenu_file' ], 20, 2 );
	}

	/**
	 * Settings tabs: slug => label. Pro adds License through the filter.
	 *
	 * @return array
	 */
	public static function tabs() {
		$tabs = [
			'buddy-builder-' . Options::SLUG => __( 'General', 'stax-buddy-builder' ),
			'buddy-builder-status'           => __( 'Help and status', 'stax-buddy-builder' ),
		];

		/**
		 * Filters the Settings tabs (screens folded out of the menu into Settings).
		 *
		 * @since 2.0.0
		 *
		 * @param array $tabs page slug => label.
		 */
		return (array) apply_filters( 'buddy_builder/admin/settings_tabs', $tabs );
	}

	/**
	 * Screens that leave the menu: page slug => menu entry that stays highlighted.
	 *
	 * @return array
	 */
	public static function folded() {
		$folded = [];

		foreach ( array_keys( self::tabs() ) as $slug ) {
			if ( 'buddy-builder-' . Options::SLUG !== $slug ) {
				$folded[ $slug ] = 'buddy-builder-' . Options::SLUG;
			}
		}

		return $folded;
	}

	/**
	 * Remove the folded entries from the menu (the pages stay registered).
	 */
	public static function fold() {
		foreach ( array_keys( self::folded() ) as $slug ) {
			remove_submenu_page( self::PARENT, $slug );
		}
	}

	/**
	 * Highlight Settings on its tabs.
	 *
	 * @param string|null $submenu_file Current submenu file.
	 * @param string      $parent_file  Parent file.
	 * @return string|null
	 */
	public static function submenu_file( $submenu_file, $parent_file ) {
		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$folded = self::folded();

		return isset( $folded[ $page ] ) ? $folded[ $page ] : $submenu_file;
	}

	/**
	 * Tab bar of the 2.0 development Settings screens. The app (App) shows every screen as a tab now: kept as a no-op
	 * for add-ons that still call it.
	 *
	 * @deprecated 2.0.0 Use App::render().
	 */
	public static function render_tabs() {
	}
}
