<?php
/**
 * "View as" on the live page (review 3.4): an administrator opens a community page as a guest (logged out) or as the
 * sample member, for this one request only, from the admin bar.
 *
 * Rules (same as LayoutPreview): GET only, nonce, administrators only, no cache, noindex, a banner with the way back.
 * No real member can be impersonated: rendering a whole page as another real member would make every plugin on the
 * page act for that member. Friend and other-member views stay in the block editor preview ("Seen by").
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * View as a guest or the sample member.
 */
final class ViewAs {

	/** Query argument. */
	const ARG = 'bpb_view_as';

	/** Nonce action. */
	const NONCE = 'bpb_view_as';

	/**
	 * Viewer of this request ('' when not viewing as someone else).
	 *
	 * @var string
	 */
	private static $as = '';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		// Before BuddyPress sets up the logged-in member (bp_init runs at init 10).
		add_action( 'init', [ __CLASS__, 'maybe_switch' ], 1 );
	}

	/**
	 * Link that opens a page as a viewer ('' when not allowed or the viewer does not exist).
	 *
	 * @param string $url Page URL.
	 * @param string $as  guest|sample.
	 * @return string
	 */
	public static function url( $url, $as ) {
		if ( ! current_user_can( 'manage_options' ) || ( 'sample' === $as && ! SampleMember::user_id() ) || ! in_array( $as, [ 'guest', 'sample' ], true ) ) {
			return '';
		}

		return add_query_arg(
			[
				self::ARG  => $as,
				'_bpbnonce' => wp_create_nonce( self::NONCE ),
			],
			$url
		);
	}

	/**
	 * Switch the user of this request when asked by an administrator with a valid nonce.
	 */
	public static function maybe_switch() {
		if ( empty( $_GET[ self::ARG ] ) || is_admin() || wp_doing_ajax() || 'GET' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			return;
		}

		$as = sanitize_key( wp_unslash( $_GET[ self::ARG ] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['_bpbnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_bpbnonce'] ) ), self::NONCE ) ) {
			return;
		}

		$target = 'guest' === $as ? 0 : ( 'sample' === $as ? SampleMember::user_id() : -1 );

		if ( $target < 0 || ( 'sample' === $as && ! $target ) ) {
			return;
		}

		self::$as = $as;
		$admin    = get_current_user_id();

		nocache_headers();
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		wp_set_current_user( $target );
		add_filter( 'show_admin_bar', '__return_false' );
		add_action( 'wp_body_open', [ __CLASS__, 'banner' ], 1 );
		add_action( 'wp_footer', [ __CLASS__, 'banner_fallback' ], 1 );

		// The real user is back for anything that runs after the page (no state is written as the viewer).
		add_action(
			'shutdown',
			static function () use ( $admin ) {
				wp_set_current_user( $admin );
			},
			0
		);
	}

	/**
	 * Whether this request is viewed as someone else.
	 *
	 * @return string guest|sample|''
	 */
	public static function current() {
		return self::$as;
	}

	/**
	 * Banner at the top of the page with the way back.
	 */
	public static function banner() {
		static $done = false;

		if ( $done || '' === self::$as ) {
			return;
		}

		$done = true;
		$back = remove_query_arg( [ self::ARG, '_bpbnonce' ] );
		$text = 'guest' === self::$as ? __( 'You are viewing this page as a guest (logged out).', 'stax-buddy-builder' ) : __( 'You are viewing this page as the sample member.', 'stax-buddy-builder' );

		echo '<div class="bpb-view-as" role="status" style="position:sticky;top:0;z-index:99999;display:flex;gap:12px;align-items:center;justify-content:center;padding:10px 16px;background:#1d2327;color:#fff;font:14px/1.4 -apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">';
		echo '<span>' . esc_html( $text ) . '</span><a href="' . esc_url( $back ) . '" style="color:#72aee6">' . esc_html__( 'Back to your view', 'stax-buddy-builder' ) . '</a></div>';
	}

	/**
	 * Themes without wp_body_open: print the banner in the footer.
	 */
	public static function banner_fallback() {
		self::banner();
	}
}
