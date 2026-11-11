<?php
/**
 * WP-CLI: wp buddybuilder migrate|status.
 *
 * @package Buddy_Builder\Cli
 */

namespace Buddy_Builder\Cli;

use Buddy_Builder\Compat\Status;
use Buddy_Builder\Migrations\Runner;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage BuddyBuilder data migrations and see the compatibility status.
 */
class Command {

	/**
	 * Runs pending migration steps, or rolls them back.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Show what would change; write nothing.
	 *
	 * [--rollback=<version>]
	 * : Undo every step newer than this version from its backup (e.g. 1.9.2).
	 *
	 * ## EXAMPLES
	 *
	 *     wp buddybuilder migrate --dry-run
	 *     wp buddybuilder migrate
	 *     wp buddybuilder migrate --rollback=1.9.2
	 *
	 * @param array $args       Positional.
	 * @param array $assoc_args Named.
	 */
	public function migrate( $args, $assoc_args ) {
		$dry_run = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );

		if ( isset( $assoc_args['rollback'] ) ) {
			$report = Runner::rollback( (string) $assoc_args['rollback'], $dry_run );
		} else {
			$report = Runner::run( $dry_run );
		}

		WP_CLI\Utils\format_items( 'table', $report ?: [ [ 'id' => '-', 'result' => 'nothing to do', 'message' => '' ] ], [ 'id', 'result', 'message' ] );

		foreach ( $report as $row ) {
			if ( in_array( $row['result'], [ 'failed', 'locked' ], true ) ) {
				WP_CLI::halt( 1 );
			}
		}
	}

	/**
	 * Shows versions, pairing, migration steps and template assignments.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : table or json.
	 * ---
	 * default: table
	 * ---
	 *
	 * @param array $args       Positional.
	 * @param array $assoc_args Named.
	 */
	public function status( $args, $assoc_args ) {
		$data = Status::collect();

		if ( 'json' === $assoc_args['format'] ) {
			WP_CLI::line( wp_json_encode( $data, JSON_PRETTY_PRINT ) );
			return;
		}

		foreach ( $data['versions'] as $key => $value ) {
			WP_CLI::line( sprintf( '%-14s %s', $key, $value ) );
		}
		WP_CLI::line( sprintf( '%-14s %s', 'pairing', $data['pairing']['state'] . ( $data['pairing']['message'] ? ': ' . $data['pairing']['message'] : '' ) ) );
		WP_CLI::line( '' );
		WP_CLI\Utils\format_items( 'table', $data['steps'], [ 'id', 'state', 'description', 'message' ] );
		WP_CLI::line( '' );
		WP_CLI\Utils\format_items( 'table', $data['templates'], [ 'type', 'builder', 'id', 'title', 'populated' ] );
		if ( $data['overrides'] ) {
			WP_CLI::line( '' );
			WP_CLI::line( 'Theme overrides: ' . implode( ', ', $data['overrides'] ) );
		}
	}
}
