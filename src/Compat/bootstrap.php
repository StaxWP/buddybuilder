<?php
/**
 * Compatibility layer of BuddyBuilder 2.x: kept 1.x surface, Free/Pro pairing, data migrations
 * (runner, WP-CLI, status screen). Loaded by loader.php before the plugin boots.
 *
 * @package Buddy_Builder\Compat
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Legacy.php';
require_once __DIR__ . '/Pairing.php';
require_once __DIR__ . '/Status.php';
require_once dirname( __DIR__ ) . '/Migrations/Step.php';
require_once dirname( __DIR__ ) . '/Migrations/Runner.php';
require_once dirname( __DIR__ ) . '/Migrations/steps/Registry_2_0_0_A.php';

\Buddy_Builder\Compat\Legacy::init();

add_action(
	'plugins_loaded',
	static function () {
		\Buddy_Builder\Compat\Pairing::init();

		if ( is_admin() ) {
			require_once dirname( __DIR__, 2 ) . '/admin/pages/Status.php';
			\Buddy_Builder\Compat\StatusPage::init();
			add_action( 'admin_init', [ '\Buddy_Builder\Migrations\Runner', 'maybe_run_on_update' ] );
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once dirname( __DIR__ ) . '/Cli/Command.php';
			\WP_CLI::add_command( 'buddybuilder', '\Buddy_Builder\Cli\Command' );
		}
	},
	5
);
