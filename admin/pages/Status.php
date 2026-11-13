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
			__( 'BuddyBuilder - Help and status', 'stax-buddy-builder' ),
			__( 'Help and status', 'stax-buddy-builder' ),
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
	 * Data update steps in plain words.
	 *
	 * @param array $step Step (id, description, state, message, time).
	 * @return array [ tone, text ]
	 */
	public static function describe_step( array $step ) {
		$when = $step['time'] ? wp_date( get_option( 'date_format' ), $step['time'] ) : '';

		switch ( $step['state'] ) {
			case 'done':
				return [
					'live',
					/* translators: 1: what the step does, 2: date */
					$when ? sprintf( __( '%1$s Done on %2$s. Undo is available below.', 'stax-buddy-builder' ), ( isset( $step['label'] ) ? $step['label'] : $step['description'] ), $when ) : sprintf( __( '%s Done. Undo is available below.', 'stax-buddy-builder' ), ( isset( $step['label'] ) ? $step['label'] : $step['description'] ) ),
				];
			case 'failed':
				return [
					'warn',
					/* translators: 1: what the step does, 2: error */
					sprintf( __( '%1$s Did not finish: %2$s. Press Try again; your pages keep their layouts meanwhile.', 'stax-buddy-builder' ), ( isset( $step['label'] ) ? $step['label'] : $step['description'] ), $step['message'] ),
				];
			case 'rolled_back':
			case 'rolled-back':
				return [
					'muted',
					/* translators: %s: what the step does */
					sprintf( __( '%s Undone (the 1.9 format is back).', 'stax-buddy-builder' ), ( isset( $step['label'] ) ? $step['label'] : $step['description'] ) ),
				];
		}

		return [
			'muted',
			/* translators: %s: what the step does */
			sprintf( __( '%s Waiting: it runs on the next admin page you open, or now with Run now.', 'stax-buddy-builder' ), ( isset( $step['label'] ) ? $step['label'] : $step['description'] ) ),
		];
	}

	/**
	 * Screen.
	 */
	public static function render() {
		$data   = Status::collect();
		$report = get_transient( 'bpb_migration_report_' . get_current_user_id() );
		delete_transient( 'bpb_migration_report_' . get_current_user_id() );

		$versions = $data['versions'];
		$platform = function_exists( 'bpb_is_buddyboss' ) && bpb_is_buddyboss() ? __( 'BuddyBoss Platform', 'stax-buddy-builder' ) : __( 'BuddyPress', 'stax-buddy-builder' );
		$failed   = false;

		foreach ( $data['steps'] as $step ) {
			$failed = $failed || 'failed' === $step['state'];
		}
		?>
		<div class="wrap bpb-admin bpb-status">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Help and status', 'stax-buddy-builder' ); ?></h1>
			<span class="bpb-admin__version"><?php echo esc_html( sprintf( /* translators: %s: version */ __( 'BuddyBuilder %s', 'stax-buddy-builder' ), $versions['buddybuilder'] ) ); ?></span>
			<hr class="wp-header-end">

			<?php if ( $report ) : ?>
				<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Done. The list below shows the result.', 'stax-buddy-builder' ); ?></p></div>
			<?php endif; ?>

			<?php if ( $data['pairing']['message'] ) : ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html( $data['pairing']['message'] ); ?></p></div>
			<?php endif; ?>

			<div class="bpb-card">
				<h2><?php esc_html_e( 'Get help', 'stax-buddy-builder' ); ?></h2>
				<ul class="bpb-help-links">
					<li><a href="https://docs.staxwp.com/buddybuilder" target="_blank" rel="noopener"><?php esc_html_e( 'BuddyBuilder documentation', 'stax-buddy-builder' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'stax-buddy-builder' ); ?></span></a></li>
					<li><a href="<?php echo esc_url( \Buddy_Builder\Admin\CommunityPages::url() ); ?>"><?php esc_html_e( 'Community pages: which layout each page uses and why one may not show', 'stax-buddy-builder' ); ?></a></li>
					<li><a href="<?php echo esc_url( \Buddy_Builder\Admin\Setup::url() ); ?>"><?php esc_html_e( 'Run setup again', 'stax-buddy-builder' ); ?></a></li>
					<li><a href="https://wordpress.org/support/plugin/stax-buddy-builder/" target="_blank" rel="noopener"><?php esc_html_e( 'Ask on the support forum', 'stax-buddy-builder' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'stax-buddy-builder' ); ?></span></a><?php echo '-' !== $versions['pro'] ? ' &middot; <a href="https://my.staxwp.com/" target="_blank" rel="noopener">' . esc_html__( 'Pro support (my.staxwp.com)', 'stax-buddy-builder' ) . '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'stax-buddy-builder' ) . '</span></a>' : ''; ?></li>
				</ul>
			</div>

			<div class="bpb-card">
				<h2><?php esc_html_e( 'Your setup', 'stax-buddy-builder' ); ?></h2>
				<ul class="bpb-checks">
					<li class="bpb-checks__item"><span class="dashicons dashicons-yes" aria-hidden="true"></span><span><?php echo esc_html( sprintf( /* translators: 1: version */ __( 'BuddyBuilder %s', 'stax-buddy-builder' ), $versions['buddybuilder'] ) ); ?><?php echo '-' !== $versions['pro'] ? esc_html( sprintf( /* translators: %s: version */ __( ' with BuddyBuilder Pro %s', 'stax-buddy-builder' ), $versions['pro'] ) ) : ''; ?></span></li>
					<li class="bpb-checks__item"><span class="dashicons dashicons-yes" aria-hidden="true"></span><span><?php echo esc_html( $platform . ' ' . $versions['buddypress'] ); ?></span></li>
					<li class="bpb-checks__item"><span class="dashicons dashicons-yes" aria-hidden="true"></span><span><?php echo esc_html( '-' !== $versions['elementor'] ? sprintf( /* translators: %s: version */ __( 'Elementor %s', 'stax-buddy-builder' ), $versions['elementor'] ) : __( 'Elementor is not active: block layouts only.', 'stax-buddy-builder' ) ); ?></span></li>
					<li class="bpb-checks__item bpb-checks__item--<?php echo 'ok' === $data['pairing']['state'] || 'none' === $data['pairing']['state'] ? 'ok' : 'warn'; ?>"><span class="dashicons <?php echo 'ok' === $data['pairing']['state'] || 'none' === $data['pairing']['state'] ? 'dashicons-yes' : 'dashicons-warning'; ?>" aria-hidden="true"></span><span><?php echo esc_html( 'ok' === $data['pairing']['state'] ? __( 'BuddyBuilder and BuddyBuilder Pro versions work together.', 'stax-buddy-builder' ) : ( 'none' === $data['pairing']['state'] ? __( 'BuddyBuilder Pro is not installed.', 'stax-buddy-builder' ) : __( 'BuddyBuilder and BuddyBuilder Pro need an update to work together (see the notice above).', 'stax-buddy-builder' ) ) ); ?></span></li>
				</ul>
			</div>

			<div class="bpb-card">
				<h2><?php esc_html_e( 'Data update', 'stax-buddy-builder' ); ?></h2>
				<p><?php esc_html_e( 'BuddyBuilder 2.0 copies which layout each page uses into a new format. Your pages look the same before and after.', 'stax-buddy-builder' ); ?></p>
				<ul class="bpb-checks">
					<?php foreach ( $data['steps'] as $step ) : ?>
						<?php list( $bpb_tone, $bpb_text ) = self::describe_step( $step ); ?>
						<li class="bpb-checks__item bpb-checks__item--<?php echo esc_attr( 'warn' === $bpb_tone ? 'warn' : 'ok' ); ?>"><span class="dashicons <?php echo 'warn' === $bpb_tone ? 'dashicons-warning' : ( 'live' === $bpb_tone ? 'dashicons-yes' : 'dashicons-clock' ); ?>" aria-hidden="true"></span><span><?php echo esc_html( $bpb_text ); ?></span></li>
					<?php endforeach; ?>
				</ul>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="bpb-status__actions">
					<input type="hidden" name="action" value="bpb_migrations">
					<?php wp_nonce_field( 'bpb_migrations' ); ?>
					<button class="button" name="bpb_do" value="run"><?php echo esc_html( $failed ? __( 'Try again', 'stax-buddy-builder' ) : __( 'Run now', 'stax-buddy-builder' ) ); ?></button>
					<button class="button" name="bpb_do" value="rollback" onclick="return confirm('<?php echo esc_js( __( 'Undo the data update? Use this only before installing BuddyBuilder 1.9 again. Your 2.0 layouts are kept but 1.9 cannot show block layouts.', 'stax-buddy-builder' ) ); ?>');"><?php esc_html_e( 'Undo (go back to the 1.9 format)', 'stax-buddy-builder' ); ?></button>
				</form>
			</div>

			<div class="bpb-card">
				<h2><?php esc_html_e( 'Theme files', 'stax-buddy-builder' ); ?></h2>
				<?php if ( $data['overrides'] ) : ?>
					<p><?php esc_html_e( 'Your theme replaces these BuddyBuilder files. If a page looks wrong after an update, compare them with the plugin copies or ask the theme author.', 'stax-buddy-builder' ); ?></p>
					<ul><?php foreach ( $data['overrides'] as $file ) : ?><li><code><?php echo esc_html( $file ); ?></code></li><?php endforeach; ?></ul>
				<?php else : ?>
					<p><?php esc_html_e( 'Your theme does not replace any BuddyBuilder file.', 'stax-buddy-builder' ); ?></p>
				<?php endif; ?>
			</div>

			<details class="bpb-card bpb-tech">
				<summary><?php esc_html_e( 'Technical details for support', 'stax-buddy-builder' ); ?></summary>
				<table class="widefat striped">
					<tbody>
					<?php foreach ( $versions as $key => $value ) : ?>
						<tr><th scope="row"><?php echo esc_html( $key ); ?></th><td><?php echo esc_html( $value ); ?></td></tr>
					<?php endforeach; ?>
					<tr><th scope="row"><?php esc_html_e( 'Free/Pro pairing', 'stax-buddy-builder' ); ?></th><td><?php echo esc_html( $data['pairing']['state'] ); ?></td></tr>
					<?php foreach ( $data['steps'] as $step ) : ?>
						<tr><th scope="row"><code><?php echo esc_html( $step['id'] ); ?></code></th><td><?php echo esc_html( $step['state'] . ( $step['message'] ? ': ' . $step['message'] : '' ) ); ?></td></tr>
					<?php endforeach; ?>
					<?php foreach ( $data['templates'] as $row ) : ?>
						<tr><th scope="row"><?php echo esc_html( $row['type'] ); ?></th><td><?php echo esc_html( $row['builder'] . ' #' . $row['id'] . ' (' . $row['populated'] . ')' ); ?></td></tr>
					<?php endforeach; ?>
					<?php if ( $report ) : ?>
						<?php foreach ( $report as $row ) : ?>
							<tr><th scope="row"><?php esc_html_e( 'Last run', 'stax-buddy-builder' ); ?></th><td><?php echo esc_html( $row['id'] . ': ' . $row['result'] . ( $row['message'] ? ' (' . $row['message'] . ')' : '' ) ); ?></td></tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</details>
		</div>
		<?php
	}
}
