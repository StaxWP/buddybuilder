<?php
/**
 * BuddyBuilder > Settings: one screen of cards. BuddyBuilder's own (block layouts on/off, the sample member for
 * previews, the setup) and the cards add-ons give through `buddy_builder/admin/settings_cards` (Pro: License, Agents).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Features;
use Buddy_Builder\Helpers;
use Buddy_Builder\Templates\PageTitle;
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
		add_filter( 'buddy_builder/admin/settings_cards', [ __CLASS__, 'cards' ] );
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
			'icon'     => 'gear',
			'sidebar'  => true,
		];

		return $menu;
	}

	/**
	 * BuddyBuilder's own settings cards.
	 *
	 * @param array $cards Cards.
	 * @return array
	 */
	public static function cards( $cards ) {
		if ( Features::shows_blocks_toggle() ) {
			$cards[] = [
				'id'          => 'builders',
				'title'       => __( 'Builders', 'stax-buddy-builder' ),
				'description' => __( 'Which editors you design community pages with.', 'stax-buddy-builder' ),
				'icon'        => 'blocks',
				'render'      => [ __CLASS__, 'render_builders' ],
				'priority'    => 10,
			];
		}

		if ( PageTitle::available() ) {
			$cards[] = [
				'id'          => 'page-title',
				'title'       => __( 'Page title from your theme', 'stax-buddy-builder' ),
				'description' => __( 'Your theme prints the page name ("Members", the member\'s name) above the layout. BuddyBuilder layouts have their own header.', 'stax-buddy-builder' ),
				'icon'        => 'heading',
				'render'      => [ __CLASS__, 'render_page_title' ],
				'priority'    => 15,
			];
		}

		$cards[] = [
			'id'          => 'sample-member',
			'title'       => __( 'Sample member for previews', 'stax-buddy-builder' ),
			'description' => __( 'A hidden member with updates, friends and groups, so previews look real before your community grows. It never shows in directories, counts or activity.', 'stax-buddy-builder' ),
			'icon'        => 'user',
			'render'      => [ __CLASS__, 'render_sample_member' ],
			'priority'    => 20,
		];

		$cards[] = [
			'id'          => 'setup',
			'title'       => __( 'Setup', 'stax-buddy-builder' ),
			'description' => __( 'Check your community and pick new looks again. Nothing changes until you press Use these.', 'stax-buddy-builder' ),
			'icon'        => 'refresh',
			'render'      => [ __CLASS__, 'render_setup' ],
			'priority'    => 30,
		];

		return $cards;
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

		$card = isset( $_POST['bpb_card'] ) ? sanitize_key( wp_unslash( $_POST['bpb_card'] ) ) : 'builders';

		if ( 'page-title' === $card ) {
			update_option( PageTitle::OPTION, empty( $_POST['bpb_hide_theme_title'] ) ? 'show' : 'hide' );
		} elseif ( Features::shows_blocks_toggle() ) {
			update_option( Features::BLOCKS_OPTION, empty( $_POST['bpb_blocks_enabled'] ) ? '0' : '1' );
		}

		wp_safe_redirect( App::settings_url( $card, [ 'bpb_saved' => 1 ] ) );
		exit;
	}

	/**
	 * The screen: one card per setting.
	 */
	public static function render() {
		if ( isset( $_GET['bpb_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'stax-buddy-builder' ) . '</p></div>';
		}

		echo '<div class="bpb-app-stack">';

		foreach ( App::cards() as $card ) {
			App::card( $card );
		}

		echo '</div>';
	}

	/**
	 * Builders card.
	 */
	public static function render_builders() {
		self::form_open( 'builders' );
		self::switch_row(
			'bpb_blocks_enabled',
			Features::blocks_enabled(),
			__( 'Design community pages with the block editor', 'stax-buddy-builder' ),
			__( 'Adds block layouts and the BuddyBuilder blocks. Your Elementor templates keep working; each page uses the layout you pick on Community pages.', 'stax-buddy-builder' )
		);
		self::form_close();
	}

	/**
	 * Theme page title card.
	 */
	public static function render_page_title() {
		self::form_open( 'page-title' );
		self::switch_row(
			'bpb_hide_theme_title',
			PageTitle::enabled(),
			__( 'Hide the theme\'s page title on community pages', 'stax-buddy-builder' ),
			__( 'Only on pages that show a BuddyBuilder layout. Pages with the BuddyPress default keep the title.', 'stax-buddy-builder' )
		);
		self::form_close();
	}

	/**
	 * Start of a card form saved by handle().
	 *
	 * @param string $card Card id.
	 */
	private static function form_open( $card ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::ACTION ) . '">';
		echo '<input type="hidden" name="bpb_card" value="' . esc_attr( $card ) . '">';
		wp_nonce_field( self::ACTION );
	}

	/**
	 * Save button and end of a card form.
	 */
	private static function form_close() {
		echo '<div class="bpb-app-setting__foot"><button type="submit" class="bpb-app-btn bpb-app-btn--primary">' . esc_html__( 'Save', 'stax-buddy-builder' ) . '</button></div>';
		echo '</form>';
	}

	/**
	 * Sample member card.
	 */
	public static function render_sample_member() {
		$user = SampleMember::user_id();

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="bpb-app-setting__row">';
		wp_nonce_field( 'bpb_sample_member' );
		echo '<input type="hidden" name="action" value="bpb_sample_member">';

		if ( $user ) {
			echo '<span class="bpb-app-tag bpb-app-tag--ok">';
			App::icon( 'check' );
			echo esc_html__( 'Sample member is there', 'stax-buddy-builder' ) . '</span>';
			echo '<button type="submit" class="bpb-app-btn" name="remove" value="1">' . esc_html__( 'Remove the sample member', 'stax-buddy-builder' ) . '</button>';
		} else {
			echo '<span class="bpb-app-tag bpb-app-tag--muted">' . esc_html__( 'No sample member', 'stax-buddy-builder' ) . '</span>';
			echo '<button type="submit" class="bpb-app-btn bpb-app-btn--primary">' . esc_html__( 'Create the sample member', 'stax-buddy-builder' ) . '</button>';
		}

		echo '</form>';
	}

	/**
	 * Setup card.
	 */
	public static function render_setup() {
		echo '<div class="bpb-app-setting__row"><a class="bpb-app-btn" href="' . esc_url( Setup::url() ) . '">' . esc_html__( 'Run setup again', 'stax-buddy-builder' ) . '</a></div>';
	}

	/**
	 * One on/off setting: a native checkbox shown as a switch (keyboard, screen readers and forms work as usual).
	 *
	 * @param string $name        Field name.
	 * @param bool   $on          Current value.
	 * @param string $label       Label.
	 * @param string $description Description.
	 */
	public static function switch_row( $name, $on, $label, $description = '' ) {
		$id = 'bpb-switch-' . sanitize_key( $name );

		echo '<div class="bpb-app-switch-row">';
		echo '<input type="checkbox" role="switch" class="bpb-app-switch" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( $on, true, false ) . ( $description ? ' aria-describedby="' . esc_attr( $id ) . '-desc"' : '' ) . '>';
		echo '<div><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';

		if ( $description ) {
			echo '<p id="' . esc_attr( $id ) . '-desc">' . esc_html( $description ) . '</p>';
		}

		echo '</div></div>';
	}
}
