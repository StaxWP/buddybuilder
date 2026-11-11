<?php
/**
 * Migration runner: status, run, rollback.
 *
 * @package Buddy_Builder\Migrations
 */

namespace Buddy_Builder\Migrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the migration steps of the free plugin (and any added through `buddy_builder/migrations/steps`).
 *
 * Stored state:
 * - `bpb_db_version`: version of the newest step applied (string). The 1.x option `buddybuilder_db_version`
 *   (core/Upgrades.php, done 1.x upgrades) is left as it is.
 * - `bpb_migrations`: [ step id => [ state => done|failed|rolled_back, time, message ] ].
 * - `bpb_migration_backup_<id>`: options as they were before the step (not autoloaded).
 */
class Runner {

	const MORE    = 'more';
	const MISSING = '__bpb_missing__';
	const LOCK    = 'bpb_migration_lock';

	/**
	 * Registered steps, sorted by id.
	 *
	 * @return Step[]
	 */
	public static function steps() {
		$steps = [ new Steps\Registry_2_0_0_A() ];

		/**
		 * Filters the migration steps. Pro and add-ons append their own Step instances.
		 *
		 * @param Step[] $steps Steps.
		 */
		$steps = array_filter(
			(array) apply_filters( 'buddy_builder/migrations/steps', $steps ),
			static function ( $step ) {
				return $step instanceof Step;
			}
		);

		usort(
			$steps,
			static function ( $a, $b ) {
				return version_compare( $a->version(), $b->version() ) ?: strcmp( $a->id(), $b->id() );
			}
		);

		return $steps;
	}

	/**
	 * Status of every step.
	 *
	 * @return array[] id, description, version, state (done|pending|failed|rolled_back), message, time.
	 */
	public static function status() {
		$log = (array) get_option( 'bpb_migrations', [] );
		$out = [];

		foreach ( self::steps() as $step ) {
			$entry = isset( $log[ $step->id() ] ) ? (array) $log[ $step->id() ] : [];
			$state = isset( $entry['state'] ) ? $entry['state'] : 'pending';

			$out[] = [
				'id'          => $step->id(),
				'version'     => $step->version(),
				'description' => $step->description(),
				'state'       => 'done' === $state && ! $step->check() ? 'pending' : $state,
				'message'     => isset( $entry['message'] ) ? $entry['message'] : '',
				'time'        => isset( $entry['time'] ) ? $entry['time'] : 0,
			];
		}

		return $out;
	}

	/**
	 * Whether a step still has to run.
	 *
	 * @return bool
	 */
	public static function has_pending() {
		foreach ( self::status() as $row ) {
			if ( 'done' !== $row['state'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Runs every pending or failed step (up to a time budget, so admin requests stay short).
	 *
	 * @param bool  $dry_run Report only.
	 * @param float $budget  Seconds; 0 = no limit (WP-CLI).
	 * @return array[] Per step: id, result (done|skipped|more|failed|would-run), message.
	 */
	public static function run( $dry_run = false, $budget = 0.0 ) {
		$report = [];

		if ( ! $dry_run && ! self::lock() ) {
			return [
				[
					'id'      => '*',
					'result'  => 'locked',
					'message' => 'Another request is running the migrations.',
				],
			];
		}

		$start = microtime( true );

		try {
			foreach ( self::steps() as $step ) {
				if ( $step->check() ) {
					if ( ! $dry_run ) {
						self::log( $step, 'done', 'already in the target shape' );
					}
					$report[] = self::row( $step, 'skipped', 'already in the target shape' );
					continue;
				}

				if ( $dry_run ) {
					$result   = $step->up( true );
					$report[] = self::row( $step, 'would-run', is_wp_error( $result ) ? $result->get_error_message() : '' );
					continue;
				}

				$backup_key = 'bpb_migration_backup_' . $step->id();
				if ( false === get_option( $backup_key, false ) ) {
					$backup = [];
					foreach ( $step->options() as $option ) {
						$backup[ $option ] = get_option( $option, self::MISSING );
					}
					add_option( $backup_key, $backup, '', false );
				}

				do {
					$result = $step->up( false );
				} while ( self::MORE === $result && ( ! $budget || microtime( true ) - $start < $budget ) );

				if ( is_wp_error( $result ) ) {
					self::log( $step, 'failed', $result->get_error_message() );
					$report[] = self::row( $step, 'failed', $result->get_error_message() );
					break;
				}

				if ( self::MORE === $result ) {
					$report[] = self::row( $step, 'more', 'time budget used, continues on the next request' );
					break;
				}

				self::log( $step, 'done', '' );
				update_option( 'bpb_db_version', $step->version() );
				$report[] = self::row( $step, 'done', '' );
			}
		} finally {
			if ( ! $dry_run ) {
				self::unlock();
			}
		}

		return $report;
	}

	/**
	 * Rolls back every applied step newer than a version, newest first, from the backups.
	 *
	 * @param string $to_version Version to go back to (e.g. `1.9.2`).
	 * @param bool   $dry_run    Report only.
	 * @return array[] Per step: id, result (rolled_back|failed|would-roll-back|no-backup), message.
	 */
	public static function rollback( $to_version, $dry_run = false ) {
		$report = [];
		$steps  = array_reverse( self::steps() );

		foreach ( $steps as $step ) {
			if ( version_compare( $step->version(), $to_version, '<=' ) ) {
				continue;
			}

			$backup = get_option( 'bpb_migration_backup_' . $step->id(), false );
			if ( false === $backup ) {
				$report[] = self::row( $step, 'no-backup', 'never applied on this site' );
				continue;
			}

			if ( $dry_run ) {
				$report[] = self::row( $step, 'would-roll-back', implode( ', ', array_keys( (array) $backup ) ) );
				continue;
			}

			$result = $step->down( (array) $backup );
			if ( is_wp_error( $result ) ) {
				self::log( $step, 'failed', 'rollback: ' . $result->get_error_message() );
				$report[] = self::row( $step, 'failed', $result->get_error_message() );
				break;
			}

			delete_option( 'bpb_migration_backup_' . $step->id() );
			self::log( $step, 'rolled_back', 'to ' . $to_version );
			$report[] = self::row( $step, 'rolled_back', '' );
		}

		if ( ! $dry_run ) {
			update_option( 'bpb_db_version', $to_version );
		}

		return $report;
	}

	/**
	 * Runs pending steps on `admin_init` after an update (0.5 s budget per request).
	 */
	public static function maybe_run_on_update() {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$code_version = defined( 'BPB_VERSION' ) ? BPB_VERSION : '0';
		if ( get_option( 'bpb_migrations_checked' ) === $code_version && ! self::has_failed() ) {
			return;
		}

		$report = self::run( false, 0.5 );
		$open   = array_filter(
			$report,
			static function ( $row ) {
				return in_array( $row['result'], [ 'more', 'failed', 'locked' ], true );
			}
		);

		if ( ! $open ) {
			update_option( 'bpb_migrations_checked', $code_version );
		}
	}

	/**
	 * Whether a step failed last time.
	 *
	 * @return bool
	 */
	public static function has_failed() {
		foreach ( (array) get_option( 'bpb_migrations', [] ) as $entry ) {
			if ( isset( $entry['state'] ) && 'failed' === $entry['state'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Report row.
	 *
	 * @param Step   $step    Step.
	 * @param string $result  Result.
	 * @param string $message Message.
	 * @return array
	 */
	private static function row( Step $step, $result, $message ) {
		return [
			'id'      => $step->id(),
			'result'  => $result,
			'message' => $message,
		];
	}

	/**
	 * Writes the step state.
	 *
	 * @param Step   $step    Step.
	 * @param string $state   State.
	 * @param string $message Message.
	 */
	private static function log( Step $step, $state, $message ) {
		$log                = (array) get_option( 'bpb_migrations', [] );
		$log[ $step->id() ] = [
			'state'   => $state,
			'message' => $message,
			'time'    => time(),
		];
		update_option( 'bpb_migrations', $log, false );

		/**
		 * Fires after a migration step changed state.
		 *
		 * @param string $id      Step id.
		 * @param string $state   done|failed|rolled_back.
		 * @param string $message Message.
		 */
		do_action( 'buddy_builder/migrations/step', $step->id(), $state, $message );
	}

	/**
	 * Takes the run lock (5 minutes).
	 *
	 * @return bool
	 */
	private static function lock() {
		if ( get_transient( self::LOCK ) ) {
			return false;
		}
		set_transient( self::LOCK, time(), 5 * MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Releases the run lock.
	 */
	private static function unlock() {
		delete_transient( self::LOCK );
	}
}
