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
use Buddy_Builder\Presets\CommunityStyle;
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
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	/**
	 * Settings screen script: the live sample of the Community style card.
	 */
	public static function assets() {
		if ( Helpers::get_instance()->is_current_page( self::SLUG ) ) {
			wp_enqueue_script( 'bpb-community-style', BPB_ASSETS_URL . 'js/community-style.js', [], BPB_VERSION, true );
		}
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

		$cards[] = [
			'id'          => 'community-style',
			'title'       => __( 'Community style', 'stax-buddy-builder' ),
			'description' => __( 'Your accent color, corners, photo shape and spacing on every community page, whatever style a page uses. Leave a choice on Style default to keep the style\'s own.', 'stax-buddy-builder' ),
			'icon'        => 'palette',
			'render'      => [ __CLASS__, 'render_community_style' ],
			'priority'    => 12,
		];

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
			'description' => __( 'Check your community and pick new styles again. Nothing changes until you press Use these.', 'stax-buddy-builder' ),
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

		if ( 'community-style' === $card ) {
			CommunityStyle::save(
				empty( $_POST['bpb_reset'] ) ? [
					'accent'  => empty( $_POST['bpb_cs_custom_accent'] ) || ! isset( $_POST['bpb_cs_accent'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['bpb_cs_accent'] ) ),
					'radius'  => isset( $_POST['bpb_cs_radius'] ) ? sanitize_key( wp_unslash( $_POST['bpb_cs_radius'] ) ) : '',
					'avatar'  => isset( $_POST['bpb_cs_avatar'] ) ? sanitize_key( wp_unslash( $_POST['bpb_cs_avatar'] ) ) : '',
					'density' => isset( $_POST['bpb_cs_density'] ) ? sanitize_key( wp_unslash( $_POST['bpb_cs_density'] ) ) : '',
					'font'    => isset( $_POST['bpb_cs_font'] ) ? sanitize_key( wp_unslash( $_POST['bpb_cs_font'] ) ) : '',
				] : []
			);
		} elseif ( 'page-title' === $card ) {
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
	 *
	 * @param string $extra Buttons before Save (escaped markup).
	 */
	private static function form_close( $extra = '' ) {
		echo '<div class="bpb-app-setting__foot">' . $extra . '<button type="submit" class="bpb-app-btn bpb-app-btn--primary">' . esc_html__( 'Save', 'stax-buddy-builder' ) . '</button></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $extra is escaped by the caller.
		echo '</form>';
	}

	/**
	 * Community style card: accent, corners, avatar shape, density, font, a live sample (the same tokens the styles
	 * read, see CommunityStyle) and Reset to style defaults.
	 */
	public static function render_community_style() {
		$values  = CommunityStyle::get();
		$choices = CommunityStyle::choices();
		$labels  = [
			'radius'  => __( 'Corners', 'stax-buddy-builder' ),
			'avatar'  => __( 'Photo shape', 'stax-buddy-builder' ),
			'density' => __( 'Spacing', 'stax-buddy-builder' ),
			'font'    => __( 'Font', 'stax-buddy-builder' ),
		];

		// Tokens of each choice for the live sample (the accent's are worked out in the browser, same rule).
		$tokens = [];
		foreach ( $choices as $key => $options ) {
			foreach ( array_keys( $options ) as $value ) {
				$tokens[ $key ][ $value ] = CommunityStyle::tokens( [ $key => $value ] );
			}
		}

		self::form_open( 'community-style' );
		echo '<div class="bpb-cs" data-tokens="' . esc_attr( wp_json_encode( $tokens ) ) . '" data-text="' . esc_attr( wp_json_encode( [ CommunityStyle::LIGHT_TEXT, CommunityStyle::DARK_TEXT ] ) ) . '"><div class="bpb-cs__fields">';

		echo '<div class="bpb-cs__field bpb-cs__field--accent">';
		self::switch_row( 'bpb_cs_custom_accent', '' !== $values['accent'], __( 'Use my own accent color', 'stax-buddy-builder' ), __( 'Buttons, links, tabs and highlights. Button text turns white or black, whichever is readable.', 'stax-buddy-builder' ) );
		echo '<label class="bpb-cs__color"><span>' . esc_html__( 'Accent color', 'stax-buddy-builder' ) . '</span><input type="color" name="bpb_cs_accent" value="' . esc_attr( '' !== $values['accent'] ? $values['accent'] : '#2563eb' ) . '"></label></div>';

		foreach ( $labels as $key => $label ) {
			$id = 'bpb-cs-' . $key;
			echo '<div class="bpb-cs__field"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label><select id="' . esc_attr( $id ) . '" name="bpb_cs_' . esc_attr( $key ) . '">';

			foreach ( $choices[ $key ] as $value => $text ) {
				echo '<option value="' . esc_attr( $value ) . '"' . selected( $values[ $key ], $value, false ) . '>' . esc_html( $text ) . '</option>';
			}

			echo '</select></div>';
		}

		echo '</div>';
		self::render_community_style_sample( CommunityStyle::tokens( $values ) );
		echo '</div>';

		self::form_close( '<button type="submit" class="bpb-app-btn bpb-app-btn--ghost" name="bpb_reset" value="1">' . esc_html__( 'Reset to style defaults', 'stax-buddy-builder' ) . '</button>' );
	}

	/**
	 * The live sample of the Community style card: a member card and a post, drawn with the community style tokens.
	 *
	 * @param array $tokens Current tokens.
	 */
	private static function render_community_style_sample( array $tokens ) {
		echo '<figure class="bpb-cs-sample" aria-hidden="true" style="' . esc_attr( CommunityStyle::declarations( $tokens ) ) . '">';
		echo '<div class="bpb-cs-sample__card"><span class="bpb-cs-sample__cover"></span><span class="bpb-cs-sample__avatar">MS</span>';
		echo '<strong class="bpb-cs-sample__name">Maya Stone <span class="bpb-cs-sample__badge">' . esc_html__( 'Admin', 'stax-buddy-builder' ) . '</span></strong>';
		echo '<span class="bpb-cs-sample__muted">' . esc_html__( 'Photographer, Lisbon', 'stax-buddy-builder' ) . '</span>';
		echo '<span class="bpb-cs-sample__actions"><span class="bpb-cs-sample__btn">' . esc_html__( 'Add friend', 'stax-buddy-builder' ) . '</span><span class="bpb-cs-sample__btn bpb-cs-sample__btn--2">' . esc_html__( 'Message', 'stax-buddy-builder' ) . '</span></span></div>';
		echo '<div class="bpb-cs-sample__card bpb-cs-sample__post"><span class="bpb-cs-sample__avatar bpb-cs-sample__avatar--sm">LB</span><span><strong>Lucas Brown</strong> <span class="bpb-cs-sample__link">' . esc_html__( 'posted an update', 'stax-buddy-builder' ) . '</span><span class="bpb-cs-sample__muted">' . esc_html__( 'Who is coming to the meetup on Friday?', 'stax-buddy-builder' ) . '</span></span></div>';
		echo '<figcaption>' . esc_html__( 'Sample in the Classic style. Every style applies your choices to its own design.', 'stax-buddy-builder' ) . '</figcaption></figure>';
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
