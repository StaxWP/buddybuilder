<?php
/**
 * Layout actions of the Community pages "More" menu (review B-L4): duplicate, history, export, move to the trash and
 * restore. Both builders: block layouts (bpb_template) and Elementor templates (elementor_library).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Admin;

use Buddy_Builder\Integrations\Elementor\Integration as Elementor;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use Buddy_Builder\Templates\Types;

defined( 'ABSPATH' ) || die();

/**
 * Duplicate, history, export, trash and restore of one layout.
 */
final class LayoutActions {

	/** Format name of a block layout export. */
	const FORMAT = 'buddybuilder-layout';

	/** Meta a copy does not take: editor locks, trash records, caches the builder rebuilds. */
	const SKIP_META = [ '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_wp_trash_meta_status', '_wp_trash_meta_time', '_wp_desired_post_slug', '_elementor_css', '_elementor_page_assets', '_elementor_element_cache', '_elementor_screenshot', '_elementor_screenshot_failed' ];

	/** Block layout meta that travels in an export (the preview member, group and viewer are site data). */
	const EXPORT_META = [ PostType::META_TYPE, PostType::META_PRESET, '_bpb_look' ];

	/** Query argument of the Elementor editor URL that opens History > Revisions. */
	const OPEN_HISTORY = 'bpb_history';

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'elementor_history' ] );
	}

	/**
	 * Elementor editor opened from History: show the Revisions panel once the editor is ready (Elementor's own
	 * "#e:route:" links are replaced by the default panel in Elementor 4).
	 */
	public static function elementor_history() {
		if ( empty( $_GET[ self::OPEN_HISTORY ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification -- opens a panel, changes nothing.
			return;
		}

		wp_add_inline_script(
			'elementor-editor',
			'jQuery( window ).on( "elementor:init", function () { elementor.once( "document:loaded", function () { setTimeout( function () { if ( window.$e ) { $e.route( "panel/history/revisions" ); } }, 0 ); } ); } );',
			'before'
		);
	}

	/**
	 * Whether a post is a BuddyBuilder layout: a block layout, an Elementor BuddyPress template, or any Elementor
	 * template assigned to a page type (1.x sites may have assigned other template types).
	 *
	 * @param int $id Post id.
	 * @return bool
	 */
	public static function is_layout( $id ) {
		$post = $id ? get_post( (int) $id ) : null;

		if ( ! $post ) {
			return false;
		}

		if ( PostType::POST_TYPE === $post->post_type ) {
			return true;
		}

		if ( 'elementor_library' !== $post->post_type ) {
			return false;
		}

		return 'bpb-buddypress' === get_post_meta( $post->ID, '_elementor_template_type', true ) || (bool) self::assigned_types( $post->ID );
	}

	/**
	 * Whether the current user may run an action on a layout (the screen's capability plus the post capability).
	 *
	 * @param string $do Action: duplicate|export|history|delete.
	 * @param int    $id Layout id.
	 * @return bool
	 */
	public static function can( $do, $id ) {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_layout( $id ) ) {
			return false;
		}

		return current_user_can( 'delete' === $do ? 'delete_post' : 'edit_post', (int) $id );
	}

	/**
	 * Page types a layout is assigned to.
	 *
	 * @param int $id Layout id.
	 * @return array type => builder
	 */
	public static function assigned_types( $id ) {
		$out = [];

		foreach ( array_keys( Types::all() ) as $type ) {
			$entry = Registry::get( $type );

			if ( (int) $id === (int) $entry['id'] ) {
				$out[ $type ] = $entry['builder'];
			}
		}

		return $out;
	}

	/**
	 * Copy of a layout as a draft, with the same builder, content, meta (look classes, Elementor data and settings)
	 * and terms (Elementor's template type). The copy is not assigned to any page.
	 *
	 * @param int $id Layout id.
	 * @return int|\WP_Error New layout id.
	 */
	public static function duplicate( $id ) {
		$post = get_post( (int) $id );

		if ( ! $post || ! self::is_layout( $post->ID ) ) {
			return new \WP_Error( 'bpb_not_layout', __( 'This is not a BuddyBuilder layout.', 'stax-buddy-builder' ) );
		}

		$copy = wp_insert_post(
			wp_slash(
				[
					'post_type'    => $post->post_type,
					'post_status'  => 'draft',
					/* translators: %s: layout title */
					'post_title'   => sprintf( __( '%s (copy)', 'stax-buddy-builder' ), $post->post_title ? $post->post_title : __( '(no title)', 'stax-buddy-builder' ) ),
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
					'post_author'  => get_current_user_id(),
				]
			),
			true
		);

		if ( is_wp_error( $copy ) ) {
			return $copy;
		}

		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( in_array( $key, self::SKIP_META, true ) ) {
				continue;
			}

			delete_post_meta( $copy, $key );

			foreach ( $values as $value ) {
				// Raw rows are serialized strings; meta functions unslash, and Elementor data is JSON with backslashes.
				add_post_meta( $copy, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}

		foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_wp_error( $terms ) && $terms ) {
				wp_set_object_terms( $copy, array_map( 'intval', $terms ), $taxonomy );
			}
		}

		Registry::flush();

		/**
		 * Fires after a layout was duplicated from Community pages.
		 *
		 * @since 2.0.0
		 *
		 * @param int $copy New layout id (draft).
		 * @param int $id   Layout it was copied from.
		 */
		do_action( 'buddy_builder/layout/duplicated', $copy, $post->ID );

		return (int) $copy;
	}

	/**
	 * Where the history of a layout opens: the newest WordPress revision (block layouts), Elementor's History >
	 * Revisions panel (Elementor templates). '' while a block layout has no revision yet, or Elementor is off.
	 *
	 * @param int $id Layout id.
	 * @return string
	 */
	public static function history_url( $id ) {
		if ( Registry::ELEMENTOR === Registry::builder_of( $id ) ) {
			return Elementor::is_available() ? add_query_arg( self::OPEN_HISTORY, 1, CommunityPages::edit_url( $id ) ) : '';
		}

		if ( ! wp_revisions_enabled( get_post( $id ) ) ) {
			return '';
		}

		$revisions = wp_get_post_revisions( $id, [ 'posts_per_page' => 1 ] );

		return $revisions ? admin_url( 'revision.php?revision=' . (int) reset( $revisions )->ID ) : '';
	}

	/**
	 * Export data and file name. Elementor templates use Elementor's own template format (Templates > Import
	 * Templates takes it); block layouts: title, content and their BuddyBuilder meta.
	 *
	 * @param int $id Layout id.
	 * @return array|\WP_Error [ name, data ].
	 */
	public static function export( $id ) {
		$post = get_post( (int) $id );

		if ( ! $post || ! self::is_layout( $post->ID ) ) {
			return new \WP_Error( 'bpb_not_layout', __( 'This is not a BuddyBuilder layout.', 'stax-buddy-builder' ) );
		}

		if ( Registry::ELEMENTOR === Registry::builder_of( $post->ID ) ) {
			$data = self::elementor_export( $post );
		} else {
			$meta = [];

			foreach ( self::EXPORT_META as $key ) {
				$value = get_post_meta( $post->ID, $key, true );

				if ( '' !== $value ) {
					$meta[ $key ] = $value;
				}
			}

			$data = [
				'format'    => self::FORMAT,
				'version'   => 1,
				'generator' => 'BuddyBuilder ' . BPB_VERSION,
				'builder'   => Registry::BLOCKS,
				'type'      => PostType::type_of( $post->ID ),
				'title'     => $post->post_title,
				'content'   => $post->post_content,
				'meta'      => $meta,
			];
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		/**
		 * Filters the data of a layout export (Community pages > More > Export).
		 *
		 * @since 2.0.0
		 *
		 * @param array    $data Export data.
		 * @param \WP_Post $post Layout.
		 */
		$data = (array) apply_filters( 'buddy_builder/layout/export', $data, $post );
		$slug = sanitize_title( $post->post_title );

		return [
			'name' => ( Registry::ELEMENTOR === Registry::builder_of( $post->ID ) ? 'elementor-' : 'buddybuilder-' ) . ( $slug ? $slug : $post->ID ) . '-' . gmdate( 'Y-m-d' ) . '.json',
			'data' => $data,
		];
	}

	/**
	 * Elementor's template export format (the same as Elementor > Templates > Export).
	 *
	 * @param \WP_Post $post Layout.
	 * @return array|\WP_Error
	 */
	private static function elementor_export( \WP_Post $post ) {
		if ( ! Elementor::is_available() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return new \WP_Error( 'bpb_no_elementor', __( 'Activate Elementor to export this layout.', 'stax-buddy-builder' ) );
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post->ID );

		if ( ! $document ) {
			return new \WP_Error( 'bpb_no_document', __( 'Elementor could not read this layout.', 'stax-buddy-builder' ) );
		}

		$export = $document->get_export_data();

		if ( empty( $export['content'] ) ) {
			return new \WP_Error( 'bpb_empty', __( 'The layout is empty.', 'stax-buddy-builder' ) );
		}

		return [
			'content'       => $export['content'],
			'page_settings' => $export['settings'],
			'version'       => class_exists( '\\Elementor\\DB' ) ? \Elementor\DB::DB_VERSION : '0.4',
			'title'         => $post->post_title,
			'type'          => (string) get_post_meta( $post->ID, '_elementor_template_type', true ),
		];
	}

	/**
	 * Send an export as a JSON download and stop.
	 *
	 * @param array $file [ name, data ].
	 */
	public static function send( array $file ) {
		$json = (string) wp_json_encode( $file['data'] );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $file['name'] ) . '"' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON file download.
		exit;
	}

	/**
	 * Move a layout to the trash. The page types that used it fall back to the BuddyPress default (as with no
	 * layout); the returned record restores both.
	 *
	 * @param int $id Layout id.
	 * @return array|false [ id, status, assigned => [ type => builder ] ].
	 */
	public static function trash( $id ) {
		$post = get_post( (int) $id );

		if ( ! $post || 'trash' === $post->post_status || ! self::is_layout( $post->ID ) ) {
			return false;
		}

		$assigned = self::assigned_types( $post->ID );

		if ( ! wp_trash_post( $post->ID ) ) {
			return false;
		}

		foreach ( $assigned as $type => $builder ) {
			CommunityPages::assign( $type, $builder, 0 );
		}

		return [
			'id'       => (int) $post->ID,
			'status'   => $post->post_status,
			'assigned' => $assigned,
		];
	}

	/**
	 * Restore a layout from the trash with its previous status (core restores drafts since WordPress 5.6) and give it
	 * back to the page types it had.
	 *
	 * @param array $record What trash() returned.
	 * @return bool
	 */
	public static function restore( array $record ) {
		$id   = isset( $record['id'] ) ? (int) $record['id'] : 0;
		$post = $id ? get_post( $id ) : null;

		if ( ! $post ) {
			return false;
		}

		if ( 'trash' === $post->post_status ) {
			$status = static function () use ( $record ) {
				return ! empty( $record['status'] ) ? sanitize_key( $record['status'] ) : 'draft';
			};

			add_filter( 'wp_untrash_post_status', $status );
			$done = wp_untrash_post( $id );
			remove_filter( 'wp_untrash_post_status', $status );

			if ( ! $done ) {
				return false;
			}
		}

		foreach ( isset( $record['assigned'] ) ? (array) $record['assigned'] : [] as $type => $builder ) {
			CommunityPages::assign( (string) $type, (string) $builder, $id );
		}

		return true;
	}
}
