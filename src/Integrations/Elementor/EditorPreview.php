<?php
/**
 * Elementor editor: "Preview with" and "Seen by" for BuddyBuilder templates (decision 4, review H2). The editor
 * shows the chosen member or group with real data and one button state, like the block editor; the choices are the
 * same post meta the block editor uses (_bpb_preview_user, _bpb_preview_group, _bpb_preview_viewer), saved at once
 * and followed by a preview reload. Template data (_elementor_data) is never touched.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Integrations\Elementor;

use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\PreviewContext;
use Buddy_Builder\Templates\SampleMember;

defined( 'ABSPATH' ) || die();

/**
 * Elementor editor preview controls.
 */
final class EditorPreview {

	/** AJAX action. */
	const ACTION = 'bpb_elementor_preview';

	/** Document setting => post meta. */
	const KEYS = [
		'bpb_preview_user'   => PostType::META_USER,
		'bpb_preview_group'  => PostType::META_GROUP,
		'bpb_preview_viewer' => PostType::META_VIEWER,
	];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'elementor/documents/register_controls', [ __CLASS__, 'controls' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'script' ] );
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'save' ] );
	}

	/**
	 * Page type of a BuddyBuilder template ('' for other documents).
	 *
	 * @param int $post_id Template id.
	 * @return string
	 */
	private static function type_of( $post_id ) {
		return 'elementor_library' === get_post_type( $post_id ) ? (string) get_post_meta( $post_id, '_bpb_page', true ) : '';
	}

	/**
	 * Document settings section "Preview" on BuddyBuilder templates.
	 *
	 * @param \Elementor\Core\Base\Document $document Document.
	 */
	public static function controls( $document ) {
		$post_id = (int) $document->get_main_id();
		$type    = self::type_of( $post_id );

		if ( '' === $type || ! class_exists( '\\Elementor\\Controls_Manager' ) ) {
			return;
		}

		$member  = in_array( $type, [ 'member-profile', 'members-directory-item', 'sitewide-activity-item' ], true );
		$group   = in_array( $type, [ 'group-profile', 'groups-directory-item' ], true );
		$viewers = PreviewContext::viewer_options();

		if ( ! $member && ! $group ) {
			return;
		}

		$document->start_controls_section(
			'bpb_preview_section',
			[
				'label' => __( 'BuddyBuilder preview', 'stax-buddy-builder' ),
				'tab'   => \Elementor\Controls_Manager::TAB_SETTINGS,
			]
		);

		if ( $member ) {
			$options = [ '0' => SampleMember::user_id() ? __( 'Sample member', 'stax-buddy-builder' ) : __( 'Me (current user)', 'stax-buddy-builder' ) ];
			foreach ( get_users( [ 'number' => 100, 'orderby' => 'display_name', 'fields' => [ 'ID', 'display_name' ] ] ) as $user ) {
				$options[ (string) $user->ID ] = $user->display_name;
			}
			$document->add_control(
				'bpb_preview_user',
				[
					'label'       => __( 'Preview with', 'stax-buddy-builder' ),
					'description' => __( 'The member the preview shows, with real data.', 'stax-buddy-builder' ),
					'type'        => \Elementor\Controls_Manager::SELECT2,
					'options'     => $options,
					'default'     => (string) (int) get_post_meta( $post_id, PostType::META_USER, true ),
				]
			);
		}

		if ( $group && function_exists( 'groups_get_groups' ) ) {
			$options = [ '0' => __( 'Newest group', 'stax-buddy-builder' ) ];
			$list    = groups_get_groups(
				[
					'per_page'    => 100,
					'show_hidden' => true,
				]
			);
			foreach ( isset( $list['groups'] ) ? $list['groups'] : [] as $item ) {
				$options[ (string) $item->id ] = $item->name;
			}
			$document->add_control(
				'bpb_preview_group',
				[
					'label'   => __( 'Preview with', 'stax-buddy-builder' ),
					'type'    => \Elementor\Controls_Manager::SELECT2,
					'options' => $options,
					'default' => (string) (int) get_post_meta( $post_id, PostType::META_GROUP, true ),
				]
			);
		}

		if ( isset( $viewers[ $type ] ) ) {
			$document->add_control(
				'bpb_preview_viewer',
				[
					'label'       => __( 'Seen by', 'stax-buddy-builder' ),
					'description' => __( 'Buttons, tabs and private fields show as this visitor would see them. Nothing is saved on the profile.', 'stax-buddy-builder' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'options'     => $viewers[ $type ],
					'default'     => (string) get_post_meta( $post_id, PostType::META_VIEWER, true ),
				]
			);
		}

		$document->end_controls_section();
	}

	/**
	 * Editor script: a change saves the choice and reloads the preview.
	 */
	public static function script() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! $post_id || '' === self::type_of( $post_id ) ) {
			return;
		}

		$data = [
			'url'    => admin_url( 'admin-ajax.php' ),
			'action' => self::ACTION,
			'nonce'  => wp_create_nonce( self::ACTION ),
			'post'   => $post_id,
			'keys'   => array_keys( self::KEYS ),
		];

		wp_add_inline_script(
			'elementor-editor',
			'( function ( d ) { window.addEventListener( "elementor:init", function () { elementor.on( "preview:loaded", function once() { elementor.off( "preview:loaded", once ); d.keys.forEach( function ( key ) { elementor.settings.page.addChangeCallback( key, function ( value ) { var body = new FormData(); body.append( "action", d.action ); body.append( "_ajax_nonce", d.nonce ); body.append( "post_id", d.post ); body.append( "key", key ); body.append( "value", value ); fetch( d.url, { method: "POST", credentials: "same-origin", body: body } ).then( function () { elementor.reloadPreview(); } ); } ); } ); } ); } ); } )( ' . wp_json_encode( $data ) . ' );',
			'before'
		);
	}

	/**
	 * AJAX: save one preview choice (nonce, edit_post).
	 */
	public static function save() {
		check_ajax_referer( self::ACTION );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$key     = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';

		if ( ! $post_id || ! isset( self::KEYS[ $key ] ) || '' === self::type_of( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( null, 403 );
		}

		$raw   = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		$value = 'bpb_preview_viewer' === $key ? PreviewContext::sanitize_viewer( $raw ) : absint( $raw );

		update_post_meta( $post_id, self::KEYS[ $key ], $value );

		wp_send_json_success();
	}
}
