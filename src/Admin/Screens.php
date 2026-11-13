<?php
/**
 * Admin: hooks of the BuddyBuilder screens (Community pages, setup, Settings, checker) and the "new block layout"
 * action. The 2.0-dev "Layouts" screen became the "Change look" panel of Community pages and step 2 of the setup.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Screens.
 */
final class Screens {

	/**
	 * Register hooks of the BuddyBuilder screens.
	 */
	public static function hooks() {
		add_action( 'admin_post_bpb_new_layout', [ __CLASS__, 'new_layout' ] );

		CommunityPages::hooks();
		Setup::hooks();
		Options::hooks();
		Checker::hooks();
	}

	/**
	 * Whether nothing is assigned yet (first run).
	 *
	 * @return bool
	 */
	public static function is_first_run() {
		return Setup::is_first_run();
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
