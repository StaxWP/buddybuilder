<?php
/**
 * Cleanup of the Appsero usage tracker bundled until 1.9.2 (on update and in uninstall.php).
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes what the Appsero tracker stored: four options and a weekly event, named after the plugin folder.
 */
class Appsero {

	/** Option holding the plugin version that last ran the cleanup. */
	const VERSION_OPTION = 'bpb_version';

	/**
	 * Once per version: remove the tracker data.
	 *
	 * @return void
	 */
	public static function maybe_cleanup() {
		if ( BPB_VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		self::remove( dirname( plugin_basename( BPB_FILE ) ) );

		update_option( self::VERSION_OPTION, BPB_VERSION );
	}

	/**
	 * Remove the options and the weekly event of the Appsero tracker.
	 *
	 * @param string $slug Plugin folder name (the Appsero slug).
	 * @return void
	 */
	public static function remove( $slug ) {
		foreach ( [ '_tracking_last_send', '_allow_tracking', '_tracking_notice', '_tracking_skipped' ] as $suffix ) {
			delete_option( $slug . $suffix );
		}

		wp_clear_scheduled_hook( $slug . '_tracker_send_event' );
	}
}
