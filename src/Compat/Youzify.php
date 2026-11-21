<?php
/**
 * Youzify: not supported. Youzify replaces the BuddyPress pages with its own and forces the Legacy
 * template pack, while BuddyBuilder's layouts need Nouveau. While Youzify is active BuddyBuilder
 * leaves the BuddyPress pages alone (no fatal on /activity/) and says so in a notice.
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Youzify detection and notice.
 */
class Youzify {

	/** Plugin file of Youzify on wordpress.org. */
	const PLUGIN = 'youzify/youzify.php';

	/**
	 * Whether Youzify is active (site or network).
	 *
	 * @return bool
	 */
	public static function active() {
		$active = in_array( self::PLUGIN, (array) get_option( 'active_plugins', [] ), true );
		if ( ! $active && is_multisite() ) {
			$active = isset( ( (array) get_site_option( 'active_sitewide_plugins', [] ) )[ self::PLUGIN ] );
		}

		/**
		 * Filters whether BuddyBuilder treats Youzify as active (and stays off the BuddyPress pages).
		 *
		 * @param bool $active Whether Youzify is active.
		 */
		return (bool) apply_filters( 'buddy_builder/compat/youzify_active', $active );
	}

	/**
	 * Hooks the admin notice.
	 */
	public static function init() {
		if ( self::active() ) {
			add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
		}
	}

	/**
	 * Notice on the dashboard, plugins and BuddyBuilder screens.
	 */
	public static function notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = $screen ? (string) $screen->id : '';
		if ( ! in_array( $id, [ 'dashboard', 'plugins' ], true ) && false === strpos( $id, 'buddy-builder' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning bpb-youzify-notice"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'BuddyBuilder does not work together with Youzify: Youzify replaces the BuddyPress pages with its own. While Youzify is active, BuddyBuilder layouts are not used on your community pages. Deactivate one of the two plugins.', 'stax-buddy-builder' ),
			esc_url( admin_url( 'plugins.php' ) ),
			esc_html__( 'Go to Plugins', 'stax-buddy-builder' )
		);
	}
}
