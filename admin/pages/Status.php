<?php
/**
 * BuddyBuilder > Status screen.
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

use Buddy_Builder\Migrations\Runner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Versions, Free/Pro pairing, migration steps (run again, roll back), template assignments and theme overrides.
 */
class StatusPage {

	const SLUG = 'buddy-builder-status';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 99 );
		add_action( 'admin_post_bpb_migrations', [ __CLASS__, 'handle' ] );
	}

	/**
	 * Submenu under BuddyBuilder.
	 */
	public static function menu() {
		add_submenu_page(
			'buddy-builder-settings',
			__( 'BuddyBuilder - Status', 'stax-buddy-builder' ),
			__( 'Status', 'stax-buddy-builder' ),
			'manage_options',
			self::SLUG,
			[ __CLASS__, 'render' ]
		);
	}

	/**
	 * Run again / roll back (nonce + manage_options).
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'stax-buddy-builder' ), 403 );
		}
		check_admin_referer( 'bpb_migrations' );

		$do = isset( $_POST['bpb_do'] ) ? sanitize_key( wp_unslash( $_POST['bpb_do'] ) ) : '';
		if ( 'rollback' === $do ) {
			$report = Runner::rollback( '1.9.2' );
		} else {
			$report = Runner::run();
		}

		set_transient( 'bpb_migration_report_' . get_current_user_id(), $report, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
		exit;
	}

	/**
	 * Screen.
	 */
	public static function render() {
		$data   = Status::collect();
		$report = get_transient( 'bpb_migration_report_' . get_current_user_id() );
		delete_transient( 'bpb_migration_report_' . get_current_user_id() );
		?>
		<div class="wrap bpb-status">
			<h1><?php esc_html_e( 'BuddyBuilder status', 'stax-buddy-builder' ); ?></h1>

			<?php if ( $report ) : ?>
				<div class="notice notice-info"><p>
					<?php
					foreach ( $report as $row ) {
						echo esc_html( $row['id'] . ': ' . $row['result'] . ( $row['message'] ? ' (' . $row['message'] . ')' : '' ) ) . '<br>';
					}
					?>
				</p></div>
			<?php endif; ?>

			<?php if ( $data['pairing']['message'] ) : ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html( $data['pairing']['message'] ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Versions', 'stax-buddy-builder' ); ?></h2>
			<table class="widefat striped" style="max-width:640px">
				<tbody>
				<?php foreach ( $data['versions'] as $key => $value ) : ?>
					<tr><th scope="row"><?php echo esc_html( $key ); ?></th><td><?php echo esc_html( $value ); ?></td></tr>
				<?php endforeach; ?>
				<tr><th scope="row"><?php esc_html_e( 'Free/Pro pairing', 'stax-buddy-builder' ); ?></th><td><?php echo esc_html( $data['pairing']['state'] ); ?></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Data migrations', 'stax-buddy-builder' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Step', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'What', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'State', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'Message', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'When', 'stax-buddy-builder' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $data['steps'] as $step ) : ?>
					<tr>
						<td><code><?php echo esc_html( $step['id'] ); ?></code></td>
						<td><?php echo esc_html( $step['description'] ); ?></td>
						<td><?php echo esc_html( $step['state'] ); ?></td>
						<td><?php echo esc_html( $step['message'] ); ?></td>
						<td><?php echo $step['time'] ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $step['time'] ) ) : ''; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
				<input type="hidden" name="action" value="bpb_migrations">
				<?php wp_nonce_field( 'bpb_migrations' ); ?>
				<button class="button button-primary" name="bpb_do" value="run"><?php esc_html_e( 'Run again', 'stax-buddy-builder' ); ?></button>
				<button class="button" name="bpb_do" value="rollback" onclick="return confirm('<?php echo esc_js( __( 'Roll the data back to the 1.9.2 format? Use this before installing BuddyBuilder 1.9.2 again.', 'stax-buddy-builder' ) ); ?>');"><?php esc_html_e( 'Roll back to 1.9.2', 'stax-buddy-builder' ); ?></button>
			</form>

			<h2><?php esc_html_e( 'Templates', 'stax-buddy-builder' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Page', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'Builder', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'Layout', 'stax-buddy-builder' ); ?></th><th><?php esc_html_e( 'Has content', 'stax-buddy-builder' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $data['templates'] as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row['name'] ); ?></td>
						<td><?php echo esc_html( $row['builder'] ); ?></td>
						<td><?php echo $row['id'] ? '<a href="' . esc_url( (string) get_edit_post_link( $row['id'] ) ) . '">' . esc_html( $row['title'] ? $row['title'] : '#' . $row['id'] ) . '</a>' : '&mdash;'; ?></td>
						<td><?php echo esc_html( $row['populated'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Theme overrides', 'stax-buddy-builder' ); ?></h2>
			<?php if ( $data['overrides'] ) : ?>
				<p><?php esc_html_e( 'These theme files replace BuddyBuilder templates. Compare them with the plugin copy after each update.', 'stax-buddy-builder' ); ?></p>
				<ul><?php foreach ( $data['overrides'] as $file ) : ?><li><code><?php echo esc_html( $file ); ?></code></li><?php endforeach; ?></ul>
			<?php else : ?>
				<p><?php esc_html_e( 'None.', 'stax-buddy-builder' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
