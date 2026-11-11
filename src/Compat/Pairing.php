<?php
/**
 * Free/Pro version pairing (inventory 14.5).
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Which Pro runs next to this free plugin, and the notice for old combinations.
 *
 * | Free | Pro         | State   | Behaviour |
 * | 2.x  | none        | none    | Normal |
 * | 2.x  | 2.x         | ok      | Normal (Pro checks BPB_API_VERSION itself) |
 * | 2.x  | 1.3 to 1.x  | legacy  | Pro 1.x keeps working through the kept 1.x surface; notice "update Pro" |
 * | 2.x  | below 1.3   | too-old | Not tested; warning notice "update Pro". Nothing is blocked (2.x removes nothing
 *                                  public), so it behaves like legacy as far as the kept surface goes |
 * Free 1.x + Pro 2.x is handled by Pro 2.x (it requires BPB_API_VERSION >= 2 and shows its own notice).
 */
class Pairing {

	/** Lowest Pro 1.x that the 2.x surface was tested with (suite: 1.6.7, 1.7.0). */
	const PRO_LEGACY_MIN = '1.3.0';

	/** First Pro version that speaks API 2. */
	const PRO_API_MIN = '2.0.0';

	/**
	 * Pairing state.
	 *
	 * @return array state (none|ok|legacy|too-old), pro (version or ''), message.
	 */
	public static function state() {
		$pro = defined( 'BPB_PRO_VERSION' ) ? (string) BPB_PRO_VERSION : '';

		if ( '' === $pro ) {
			return [
				'state'   => 'none',
				'pro'     => '',
				'message' => '',
			];
		}

		if ( version_compare( $pro, self::PRO_API_MIN, '>=' ) ) {
			return [
				'state'   => 'ok',
				'pro'     => $pro,
				'message' => '',
			];
		}

		$legacy = version_compare( $pro, self::PRO_LEGACY_MIN, '>=' );

		return [
			'state'   => $legacy ? 'legacy' : 'too-old',
			'pro'     => $pro,
			'message' => $legacy
				/* translators: %s: BuddyBuilder Pro version. */
				? sprintf( __( 'BuddyBuilder Pro %s runs in compatibility mode: its Elementor widgets and modules keep working. Update BuddyBuilder Pro to 2.x for blocks and the new features.', 'stax-buddy-builder' ), $pro )
				/* translators: %s: BuddyBuilder Pro version. */
				: sprintf( __( 'BuddyBuilder Pro %s is too old for this BuddyBuilder version and may not work correctly. Please update BuddyBuilder Pro to 2.x.', 'stax-buddy-builder' ), $pro ),
		];
	}

	/**
	 * Hooks the admin notice.
	 */
	public static function init() {
		add_action( 'admin_notices', [ __CLASS__, 'notice' ] );
		add_action( 'wp_ajax_bpb_dismiss_pairing', [ __CLASS__, 'dismiss' ] );
	}

	/**
	 * Notice on the dashboard, plugins and BuddyBuilder screens; dismissible per Pro version.
	 */
	public static function notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$state = self::state();
		if ( ! in_array( $state['state'], [ 'legacy', 'too-old' ], true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$id     = $screen ? (string) $screen->id : '';
		if ( ! in_array( $id, [ 'dashboard', 'plugins', 'update-core' ], true ) && false === strpos( $id, 'buddy-builder' ) ) {
			return;
		}

		if ( 'legacy' === $state['state'] && get_user_meta( get_current_user_id(), 'bpb_pairing_dismissed', true ) === $state['pro'] ) {
			return;
		}

		$class = 'legacy' === $state['state'] ? 'notice-info is-dismissible' : 'notice-warning';
		printf(
			'<div class="notice %1$s bpb-pairing-notice" data-nonce="%2$s"><p>%3$s <a href="%4$s">%5$s</a></p></div>',
			esc_attr( $class ),
			esc_attr( wp_create_nonce( 'bpb_dismiss_pairing' ) ),
			esc_html( $state['message'] ),
			esc_url( admin_url( 'plugins.php?s=buddybuilder' ) ),
			esc_html__( 'Go to Plugins', 'stax-buddy-builder' )
		);
		?>
		<script>
		document.addEventListener( 'click', function ( e ) {
			var n = e.target.closest( '.bpb-pairing-notice .notice-dismiss' );
			if ( ! n ) { return; }
			var body = new URLSearchParams( { action: 'bpb_dismiss_pairing', _ajax_nonce: n.parentNode.dataset.nonce } );
			fetch( window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } );
		} );
		</script>
		<?php
	}

	/**
	 * Remembers the dismissal for the current Pro version.
	 */
	public static function dismiss() {
		check_ajax_referer( 'bpb_dismiss_pairing' );
		$state = self::state();
		update_user_meta( get_current_user_id(), 'bpb_pairing_dismissed', $state['pro'] );
		wp_send_json_success();
	}
}
