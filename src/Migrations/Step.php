<?php
/**
 * One migration step: versioned, idempotent, reversible.
 *
 * @package Buddy_Builder\Migrations
 */

namespace Buddy_Builder\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class of a migration step.
 *
 * Rules (inventory 14.3):
 * - `check()` first: true means the data is already in the target shape, so `up()` is skipped (idempotent).
 * - `options()` lists every option the step writes. The runner copies them to `bpb_migration_backup_<id>`
 *   before `up()`, and the default `down()` restores them exactly (missing options are deleted again).
 * - 2.0 steps touch options only, never post content or `_elementor_data`.
 * - `up()` returns true when done, `Runner::MORE` when it processed one batch and wants to be called again,
 *   or a \WP_Error.
 */
abstract class Step {

	/**
	 * Step id, `<version>-<letter>` (e.g. `2.0.0-a`). Steps run in id order.
	 *
	 * @return string
	 */
	abstract public function id();

	/**
	 * One line for the status screen and WP-CLI.
	 *
	 * @return string
	 */
	abstract public function description();

	/**
	 * Options this step writes (backed up before `up()`).
	 *
	 * @return string[]
	 */
	abstract public function options();

	/**
	 * Whether the data is already in the target shape.
	 *
	 * @return bool
	 */
	abstract public function check();

	/**
	 * Applies the step.
	 *
	 * @param bool $dry_run Report only, write nothing.
	 * @return true|string|\WP_Error True, Runner::MORE, or an error.
	 */
	abstract public function up( $dry_run = false );

	/**
	 * Reverts the step. Default: restore the options backed up before `up()`.
	 *
	 * @param array $backup [ option => value ] as stored by the runner (Runner::MISSING for absent options).
	 * @return true|\WP_Error
	 */
	public function down( array $backup ) {
		foreach ( $this->options() as $option ) {
			if ( ! array_key_exists( $option, $backup ) ) {
				continue;
			}
			if ( Runner::MISSING === $backup[ $option ] ) {
				delete_option( $option );
			} else {
				update_option( $option, $backup[ $option ] );
			}
		}

		return true;
	}

	/**
	 * Plugin version the step belongs to (the id without the letter).
	 *
	 * @return string
	 */
	public function version() {
		return preg_replace( '/-[a-z]+$/', '', $this->id() );
	}
}
