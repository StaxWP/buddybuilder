<?php
/**
 * Add plugin notices.
 *
 * @package Buddy_Builder
 * @since 1.0.0
 */

namespace Buddy_Builder;

defined( 'ABSPATH' ) || die();

final class Notices extends Singleton {

	/**
	 * Notices a user can hide for good: the only user meta keys the dismiss request may set.
	 */
	const DISMISSIBLE = [ 'bpb_rate_us' ];

	/**
	 * Nonce action prefix of the dismiss request (the notice id is appended).
	 */
	const DISMISS_NONCE = 'bpb_notice_viewed_';

	/**
	 * Notices constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_bpb_admin_notice_viewed', [ $this, 'bpb_admin_notice_viewed' ] );
		add_action( 'admin_post_bpb_admin_notice_viewed', [ $this, 'bpb_admin_notice_viewed' ] );
	}

	/**
	 * Notice ids that can be dismissed.
	 *
	 * @return string[]
	 */
	public static function dismissible() {
		/**
		 * Notice ids (user meta keys) the dismiss request accepts.
		 *
		 * @param string[] $ids Notice ids.
		 */
		return array_map( 'sanitize_key', (array) apply_filters( 'buddy_builder/notices/dismissible', self::DISMISSIBLE ) );
	}

	/**
	 * Dismiss link of a notice (works without JavaScript; the AJAX request sends the same nonce).
	 *
	 * @param string $notice_id Notice id.
	 *
	 * @return string
	 */
	public static function dismiss_url( $notice_id ) {
		return wp_nonce_url(
			add_query_arg(
				[
					'action'    => 'bpb_admin_notice_viewed',
					'notice_id' => $notice_id,
				],
				admin_url( 'admin-post.php' )
			),
			self::DISMISS_NONCE . $notice_id
		);
	}

	/**
	 * Mark notice as viewed: a known notice id, with its nonce, for the current user only.
	 *
	 * @return void
	 */
	public function bpb_admin_notice_viewed() {
		$notice_id = isset( $_REQUEST['notice_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['notice_id'] ) ) : '';

		if ( ! $notice_id || ! in_array( $notice_id, self::dismissible(), true ) ) {
			wp_die( esc_html__( 'This notice cannot be dismissed.', 'stax-buddy-builder' ), '', [ 'response' => 400 ] );
		}

		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( ! is_user_logged_in() || ! wp_verify_nonce( $nonce, self::DISMISS_NONCE . $notice_id ) ) {
			wp_die( esc_html__( 'The link has expired. Please reload the page and try again.', 'stax-buddy-builder' ), '', [ 'response' => 403 ] );
		}

		update_user_meta( get_current_user_id(), $notice_id, 1 );

		if ( ! wp_doing_ajax() ) {
			wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
			die;
		}

		wp_die();
	}

	/**
	 * Elementor not installed notice
	 */
	public function elementor_notice() {
		$class = 'notice notice-warning';
		/* translators: %s: html tags */
		$message = sprintf( __( '%1$sBuddyBuilder%2$s requires %1$sElementor%2$s plugin installed & activated.', 'stax-buddy-builder' ), '<strong>', '</strong>' );

		$plugin = 'elementor/elementor.php';

		if ( Helpers::get_instance()->is_plugin_installed( $plugin ) ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$action_url   = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			$button_label = __( 'Activate Elementor', 'stax-buddy-builder' );

		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$action_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );
			$button_label = __( 'Install Elementor', 'stax-buddy-builder' );
		}

		$button = '<p><a href="' . $action_url . '" class="button-primary">' . $button_label . '</a></p><p></p>';

		printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), $message, $button );
	}

	/**
	 * Displays notice on the admin dashboard if Elementor version is lower than the
	 * required minimum.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function minimum_elementor_version_notice() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		if ( isset( $_GET['activate'] ) ) { // WPCS: CSRF ok, input var ok.
			unset( $_GET['activate'] ); // WPCS: input var ok.
		}

		$message = sprintf(
			'<span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">'
			/* translators: 1: Plugin name 2: Elementor */
			. esc_html__( '%1$s requires version %3$s or greater of %2$s plugin.', 'stax-buddy-builder' )
			. '</span>',
			'<strong>' . esc_html__( 'BuddyBuilder', 'stax-buddy-builder' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'stax-buddy-builder' ) . '</strong>',
			Plugin::$minimum_elementor_version
		);

		$file_path   = 'elementor/elementor.php';
		$update_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path, 'upgrade-plugin_' . $file_path );

		$message .= sprintf(
			'<span style="display: block; margin: 0.5em 0.5em 0 0; clear: both;">' .
			'<a class="button-primary" href="%1$s">%2$s</a></span>',
			$update_link,
			esc_html__( 'Update Elementor Now', 'stax-buddy-builder' )
		);

		printf( '<div class="notice notice-error"><p>%1$s</p></div>', $message );
	}

	/**
	 * Buddypress not installed notice
	 */
	public function buddypress_notice() {
		$class = 'notice notice-warning';
		/* translators: %s: html tags */
		$message = sprintf( __( '%1$sBuddyBuilder%2$s requires %1$sBuddyPress%2$s plugin installed & activated.', 'stax-buddy-builder' ), '<strong>', '</strong>' );

		$plugin = 'buddypress/bp-loader.php';

		if ( Helpers::get_instance()->is_plugin_installed( $plugin ) ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}

			$action_url   = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
			$button_label = __( 'Activate BuddyPress', 'stax-buddy-builder' );

		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$action_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=buddypress' ), 'install-plugin_buddypress' );
			$button_label = __( 'Install BuddyPress', 'stax-buddy-builder' );
		}

		$button = '<p><a href="' . $action_url . '" class="button-primary">' . $button_label . '</a></p><p></p>';

		printf( '<div class="%1$s"><p>%2$s</p>%3$s</div>', esc_attr( $class ), $message, $button );
	}

	/**
	 * Upgrade DB notice
	 */
	public function buddybuilder_upgrade_db_notice() {
		?>
		<div class="notice notice-warning">
			<p>
				<?php echo wp_kses_post( __( '<strong>BuddyBuilder - BuddyPress Builder for Elementor</strong> needs to update your database to the latest version. Please make sure to create a backup first.', 'stax-buddy-builder' ) ); ?>
			</p>
			<p>
				<?php echo wp_kses_post( sprintf( __( '<a href="%s" class="button-primary">Update now</a>', 'stax-buddy-builder' ), wp_nonce_url( add_query_arg( 'buddybuilder_db_update', '' ), 'action' ) ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Upgrade DB success notice
	 */
	public function buddybuilder_upgrade_db_success_notice() {
		?>
		<div class="notice notice-success">
			<p><?php esc_html_e( 'Awesome! The database has been updated to the latest version!', 'stax-buddy-builder' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Upgrade DB failed notice
	 */
	public function buddybuilder_upgrade_db_failed_notice() {
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Something went wrong. Please check your server logs or contact StaxWP.', 'stax-buddy-builder' ); ?></p>
		</div>
		<?php
	}

}
