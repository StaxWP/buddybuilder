<?php
/**
 * Admin: the "Layouts" screen (first run "Pick a look for each page", U2) and the "new block layout" action.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Features;
use Buddy_Builder\Helpers;
use Buddy_Builder\Presets\Presets;
use Buddy_Builder\Presets\Preview;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\SampleMember;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Layouts screen.
 */
final class Screens {

	const SLUG = 'layouts';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_filter( BPB_HOOK_PREFIX . 'admin_menu', [ __CLASS__, 'menu_item' ] );
		add_action( 'admin_init', [ __CLASS__, 'screen_hooks' ] );
		add_action( 'admin_post_bpb_new_layout', [ __CLASS__, 'new_layout' ] );
		add_action( 'admin_notices', [ __CLASS__, 'first_run_notice' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'styles' ] );
	}

	/**
	 * Menu entry (BuddyBuilder > Layouts), first after the dashboard.
	 *
	 * @param array $menu Menu.
	 * @return array
	 */
	public static function menu_item( $menu ) {
		$menu[] = [
			'slug'     => self::SLUG,
			'name'     => __( 'Layouts', 'stax-buddy-builder' ),
			'link'     => admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ),
			'query'    => BPB_ADMIN_PREFIX . self::SLUG,
			'priority' => 2,
		];

		return $menu;
	}

	/**
	 * Hooks of the 1.x admin layout when our screen is open.
	 */
	public static function screen_hooks() {
		if ( ! Helpers::get_instance()->is_current_page( self::SLUG ) ) {
			return;
		}

		add_filter(
			BPB_HOOK_PREFIX . 'current_slug',
			static function () {
				return Screens::SLUG;
			}
		);
		add_action( BPB_HOOK_PREFIX . self::SLUG . '_page_content', [ __CLASS__, 'render' ] );
	}

	/**
	 * Whether nothing is assigned yet (first run).
	 *
	 * @return bool
	 */
	public static function is_first_run() {
		foreach ( Registry::all() as $entry ) {
			if ( $entry['id'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Notice on the BuddyBuilder screens and the dashboard until a first layout is assigned.
	 */
	public static function first_run_notice() {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_first_run() || Helpers::get_instance()->is_current_page( self::SLUG ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ( 'dashboard' !== $screen->id && false === strpos( (string) $screen->id, 'buddy-builder' ) ) ) {
			return;
		}

		printf(
			'<div class="notice notice-info"><p><strong>%1$s</strong> %2$s <a class="button button-primary" href="%3$s">%4$s</a></p></div>',
			esc_html__( 'BuddyBuilder:', 'stax-buddy-builder' ),
			esc_html__( 'pick a look for your community pages in one click.', 'stax-buddy-builder' ),
			esc_url( admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . self::SLUG ) ),
			esc_html__( 'Pick a look', 'stax-buddy-builder' )
		);
	}

	/**
	 * Screen styles.
	 */
	public static function styles() {
		if ( Helpers::get_instance()->is_current_page( self::SLUG ) ) {
			wp_enqueue_style( 'bpb-admin-layouts', BPB_ASSETS_URL . 'css/admin-layouts.css', [], BPB_VERSION );
		}
	}

	/**
	 * Builders available for presets.
	 *
	 * @return array builder => label
	 */
	private static function builders() {
		$builders = [];

		if ( Features::blocks_enabled() ) {
			$builders[ Registry::BLOCKS ] = __( 'Block editor', 'stax-buddy-builder' );
		}

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$builders[ Registry::ELEMENTOR ] = __( 'Elementor', 'stax-buddy-builder' );
		}

		return $builders;
	}

	/**
	 * The Layouts screen: one row per page type with its presets.
	 */
	public static function render() {
		$builders = self::builders();
		$types    = Types::for_blocks();
		$nonce    = wp_create_nonce( 'bpb_apply_preset' );
		$action   = admin_url( 'admin-post.php' );
		$first    = [];

		echo '<div class="bpb-layouts">';

		if ( isset( $_GET['bpb_applied'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$ok = 'error' !== $_GET['bpb_applied']; // phpcs:ignore WordPress.Security.NonceVerification
			printf( '<div class="notice %1$s inline"><p>%2$s</p></div>', $ok ? 'notice-success' : 'notice-error', $ok ? esc_html__( 'Layout created and assigned.', 'stax-buddy-builder' ) : esc_html__( 'The layout could not be created.', 'stax-buddy-builder' ) );
		}

		echo '<h2>' . esc_html__( 'Pick a look for each page', 'stax-buddy-builder' ) . '</h2>';
		echo '<p>' . esc_html__( 'One click creates the layout, assigns it to the page and keeps it editable. You can change it any time.', 'stax-buddy-builder' ) . '</p>';

		if ( ! $builders ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Turn on "Blocks (beta)" in BuddyBuilder > Settings, or activate Elementor, to use the layouts.', 'stax-buddy-builder' ) . '</p></div></div>';

			return;
		}

		foreach ( $types as $type => $info ) {
			if ( ! Types::is_active( $type ) ) {
				continue;
			}

			$presets = Presets::all( $type );

			if ( ! $presets ) {
				continue;
			}

			$first[] = key( $presets );
			$entry   = Registry::get( $type );

			echo '<section class="bpb-layouts__type">';
			echo '<h3>' . esc_html( $info['name'] );

			if ( $entry['id'] ) {
				$post = get_post( $entry['id'] );
				/* translators: %s: layout title */
				echo ' <span class="bpb-layouts__current">' . esc_html( sprintf( __( 'Current: %s', 'stax-buddy-builder' ), $post ? $post->post_title : '#' . $entry['id'] ) ) . '</span>';
			}

			echo '</h3><div class="bpb-layouts__grid">';

			foreach ( $presets as $id => $preset ) {
				echo '<form class="bpb-layouts__card" method="post" action="' . esc_url( $action ) . '">';
				echo '<div class="bpb-layouts__thumb" aria-hidden="true">' . Preview::svg( $preset['layout'] ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '<strong>' . esc_html( $preset['title'] ) . '</strong>';
				echo '<p>' . esc_html( isset( $preset['description'] ) ? $preset['description'] : '' ) . '</p>';
				echo '<input type="hidden" name="action" value="bpb_apply_preset"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
				echo '<input type="hidden" name="preset[]" value="' . esc_attr( $id ) . '">';

				foreach ( $builders as $builder => $label ) {
					/* translators: %s: builder name */
					echo '<button class="button' . ( Registry::BLOCKS === $builder || 1 === count( $builders ) ? ' button-primary' : '' ) . '" name="builder" value="' . esc_attr( $builder ) . '">' . esc_html( count( $builders ) > 1 ? sprintf( __( 'Use with %s', 'stax-buddy-builder' ), $label ) : __( 'Use this', 'stax-buddy-builder' ) ) . '</button> ';
				}

				echo '</form>';
			}

			echo '</div></section>';
		}

		if ( $first ) {
			echo '<form method="post" action="' . esc_url( $action ) . '" class="bpb-layouts__all">';
			echo '<input type="hidden" name="action" value="bpb_apply_preset"><input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

			foreach ( $first as $id ) {
				echo '<input type="hidden" name="preset[]" value="' . esc_attr( $id ) . '">';
			}

			echo '<input type="hidden" name="builder" value="' . esc_attr( key( $builders ) ) . '">';
			echo '<button class="button button-hero button-primary">' . esc_html__( 'Use the first look for all pages', 'stax-buddy-builder' ) . '</button>';
			echo '</form>';
		}

		self::render_sample_member();

		echo '</div>';
	}

	/**
	 * Sample member box (previews on sites without members yet).
	 */
	private static function render_sample_member() {
		$user = SampleMember::user_id();

		echo '<section class="bpb-layouts__sample"><h3>' . esc_html__( 'Sample member for previews', 'stax-buddy-builder' ) . '</h3>';
		echo '<p>' . esc_html__( 'Creates a hidden sample member with updates, friends and groups, so previews look real before your community grows. It never shows in directories.', 'stax-buddy-builder' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'bpb_sample_member' );
		echo '<input type="hidden" name="action" value="bpb_sample_member">';

		if ( $user ) {
			echo '<button class="button" name="remove" value="1">' . esc_html__( 'Remove the sample member', 'stax-buddy-builder' ) . '</button>';
		} else {
			echo '<button class="button">' . esc_html__( 'Create the sample member', 'stax-buddy-builder' ) . '</button>';
		}

		echo '</form></section>';
	}

	/**
	 * Admin action: new empty block layout for a type, opened in the block editor.
	 */
	public static function new_layout() {
		if ( ! current_user_can( 'edit_pages' ) || ! check_admin_referer( 'bpb_new_layout' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'stax-buddy-builder' ) );
		}

		$type  = isset( $_GET['type'] ) ? PostType::sanitize_type( wp_unslash( $_GET['type'] ) ) : PostType::TEMPLATE_TYPE;
		$types = Types::all();

		$post_id = wp_insert_post(
			[
				'post_type'    => PostType::POST_TYPE,
				'post_status'  => 'draft',
				'post_title'   => isset( $types[ $type ] ) ? $types[ $type ]['name'] : $type,
				'post_content' => '<!-- wp:group {"layout":{"type":"constrained"}} -->' . "\n" . '<div class="wp-block-group"></div>' . "\n" . '<!-- /wp:group -->',
				'meta_input'   => [ PostType::META_TYPE => $type ],
			],
			true
		);

		if ( is_wp_error( $post_id ) ) {
			wp_die( esc_html( $post_id->get_error_message() ) );
		}

		wp_safe_redirect( get_edit_post_link( $post_id, 'raw' ) );
		exit;
	}
}
