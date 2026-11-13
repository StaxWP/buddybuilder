<?php
/**
 * BuddyBuilder > Settings: block layouts on/off, the sample member for previews, the setup.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Features;
use Buddy_Builder\Helpers;
use Buddy_Builder\Templates\SampleMember;

defined( 'ABSPATH' ) || die();

/**
 * Settings screen.
 */
final class Options {

	const SLUG   = 'options';
	const ACTION = 'bpb_options';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_filter( BPB_HOOK_PREFIX . 'admin_menu', [ __CLASS__, 'menu_item' ] );
		add_action( 'admin_init', [ __CLASS__, 'screen_hooks' ] );
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle' ] );
	}

	/**
	 * Menu entry.
	 *
	 * @param array $menu Menu.
	 * @return array
	 */
	public static function menu_item( $menu ) {
		$menu[] = [
			'slug'     => self::SLUG,
			'name'     => __( 'Settings', 'stax-buddy-builder' ),
			'link'     => admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ),
			'query'    => BPB_ADMIN_PREFIX . self::SLUG,
			'priority' => 5,
		];

		return $menu;
	}

	/**
	 * Content hook when the screen is open.
	 */
	public static function screen_hooks() {
		if ( Helpers::get_instance()->is_current_page( self::SLUG ) ) {
			add_filter(
				BPB_HOOK_PREFIX . 'current_slug',
				static function () {
					return Options::SLUG;
				}
			);
			add_action( BPB_HOOK_PREFIX . self::SLUG . '_page_content', [ __CLASS__, 'render' ] );
		}
	}

	/**
	 * Save the block layouts switch.
	 */
	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( self::ACTION ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		if ( Features::shows_blocks_toggle() ) {
			update_option( Features::BLOCKS_OPTION, empty( $_POST['bpb_blocks_enabled'] ) ? '0' : '1' );
		}

		wp_safe_redirect( add_query_arg( 'bpb_saved', 1, admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ) ) );
		exit;
	}

	/**
	 * The screen.
	 */
	public static function render() {
		if ( isset( $_GET['bpb_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'stax-buddy-builder' ) . '</p></div>';
		}

		if ( Features::shows_blocks_toggle() ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-card">';
			echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
			wp_nonce_field( self::ACTION );
			echo '<h2>' . esc_html__( 'Builders', 'stax-buddy-builder' ) . '</h2>';
			echo '<label class="bpb-choice"><input type="checkbox" name="bpb_blocks_enabled" value="1"' . checked( Features::blocks_enabled(), true, false ) . '> <strong>' . esc_html__( 'Design community pages with the block editor', 'stax-buddy-builder' ) . '</strong></label>';
			echo '<p class="description">' . esc_html__( 'Adds block layouts and the BuddyBuilder blocks. Your Elementor templates keep working; each page uses the layout you pick on Community pages.', 'stax-buddy-builder' ) . '</p>';
			submit_button( __( 'Save', 'stax-buddy-builder' ), 'primary', 'submit', false );
			echo '</form>';
		}

		$user = SampleMember::user_id();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-card">';
		wp_nonce_field( 'bpb_sample_member' );
		echo '<input type="hidden" name="action" value="bpb_sample_member">';
		echo '<h2>' . esc_html__( 'Sample member for previews', 'stax-buddy-builder' ) . '</h2>';
		echo '<p>' . esc_html__( 'A hidden member with updates, friends and groups, so previews look real before your community grows. It never shows in directories, counts or activity.', 'stax-buddy-builder' ) . '</p>';

		if ( $user ) {
			echo '<p><span class="bpb-status bpb-status--live">' . esc_html__( 'Sample member is there', 'stax-buddy-builder' ) . '</span></p>';
			echo '<button type="submit" class="button" name="remove" value="1">' . esc_html__( 'Remove the sample member', 'stax-buddy-builder' ) . '</button>';
		} else {
			echo '<button type="submit" class="button">' . esc_html__( 'Create the sample member', 'stax-buddy-builder' ) . '</button>';
		}

		echo '</form>';

		echo '<div class="bpb-card"><h2>' . esc_html__( 'Setup', 'stax-buddy-builder' ) . '</h2>';
		echo '<p>' . esc_html__( 'Check your community and pick new looks again. Nothing changes until you press Use these.', 'stax-buddy-builder' ) . '</p>';
		echo '<a class="button" href="' . esc_url( Setup::url() ) . '">' . esc_html__( 'Run setup again', 'stax-buddy-builder' ) . '</a></div>';
	}
}
