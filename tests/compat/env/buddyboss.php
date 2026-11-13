<?php
/**
 * WP-CLI --require file (throwaway compat site only): loads BuddyBoss Platform instead of BuddyPress for this run,
 * without changing the active plugins option. bbPress is left out too: BuddyBoss Platform refuses to load next to
 * BuddyPress or bbPress.
 *
 *   wp --require=tests/compat/env/buddyboss.php eval-file tests/compat/bin/regressions-test.php preview-buddyboss
 *
 * @package Buddy_Builder\Tests\Compat
 */

WP_CLI::add_wp_hook(
	'option_active_plugins',
	static function ( $plugins ) {
		$plugins   = array_values( array_diff( (array) $plugins, [ 'buddypress/bp-loader.php', 'bbpress/bbpress.php' ] ) );
		// BPB_BUDDYBOSS_PLUGIN picks another copy, e.g. buddyboss-platform-372/bp-loader.php (ReadyLaunch builds).
		$plugins[] = getenv( 'BPB_BUDDYBOSS_PLUGIN' ) ? getenv( 'BPB_BUDDYBOSS_PLUGIN' ) : 'buddyboss-platform/bp-loader.php';

		return $plugins;
	}
);
