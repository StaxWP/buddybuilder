<?php
/**
 * Front-end preview of layouts that are not live: the real page (your profile, a directory, a group) rendered with
 * a look picked in the setup wizard or a new layout not assigned yet, for the administrator who asked, only.
 *
 * URL: ?bpb_lp[<type>]=post:<id>|preset:<preset id>&bpb_lp_nonce=<nonce>[&bpb_lp_bare=1]. Nothing is saved: the
 * preview swaps the layout of those types for this request through the buddy_builder/template/resolve filter.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

use Buddy_Builder\Presets\Builder;
use Buddy_Builder\Presets\Presets;

defined( 'ABSPATH' ) || die();

/**
 * Not-live layout previews on the front end.
 */
final class LayoutPreview {

	const PARAM  = 'bpb_lp';
	const NONCE  = 'bpb_lp_nonce';
	const BARE   = 'bpb_lp_bare';
	const ACTION = 'bpb_layout_preview';

	/**
	 * Parsed preview map of this request (type => entry), null until computed.
	 *
	 * @var array|null
	 */
	private static $map = null;

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'wp', [ __CLASS__, 'boot' ] );
	}

	/**
	 * Preview URL of a page.
	 *
	 * @param string $url  Page URL.
	 * @param array  $map  type => 'post:<id>' | 'preset:<id>'.
	 * @param bool   $bare Without the admin bar and the preview banner (thumbnails).
	 * @return string
	 */
	public static function url( $url, array $map, $bare = false ) {
		$args = [
			self::PARAM => $map,
			self::NONCE => wp_create_nonce( self::ACTION ),
		];

		if ( $bare ) {
			$args[ self::BARE ] = 1;
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Preview URL of the page of a type (your profile for member pages), '' when the page cannot be found.
	 *
	 * @param string $type Template type.
	 * @param array  $map  Preview map.
	 * @param bool   $bare Bare (thumbnail).
	 * @param int    $user Member to show on profile pages (0: you).
	 * @return string
	 */
	public static function page_url( $type, array $map, $bare = false, $user = 0 ) {
		$base = self::type_url( $type, $user );

		return $base ? self::url( $base, $map, $bare ) : '';
	}

	/**
	 * Real URL of the page of a type.
	 *
	 * @param string $type Template type.
	 * @param int    $user Member for profile pages (0: current user).
	 * @return string
	 */
	public static function type_url( $type, $user = 0 ) {
		if ( ! function_exists( 'bp_is_active' ) ) {
			return '';
		}

		switch ( $type ) {
			case 'member-profile':
				return self::profile_url( $user );

			case 'members-directory':
			case 'members-directory-item':
				return function_exists( 'bp_get_members_directory_permalink' ) ? bp_get_members_directory_permalink() : '';

			case 'groups-directory':
			case 'groups-directory-item':
				if ( ! bp_is_active( 'groups' ) ) {
					return '';
				}

				return function_exists( 'bp_get_groups_directory_url' ) ? bp_get_groups_directory_url() : bp_get_groups_directory_permalink();

			case 'group-profile':
				return self::group_url();

			case 'sitewide-activity':
			case 'sitewide-activity-item':
				return bp_is_active( 'activity' ) && function_exists( 'bp_get_activity_directory_permalink' ) ? bp_get_activity_directory_permalink() : '';

			case 'register-page':
				return function_exists( 'bp_get_signup_page' ) ? bp_get_signup_page() : '';
		}

		return '';
	}

	/**
	 * Profile URL of a member (BuddyPress 12+ helper, with the BuddyBoss / older one as fallback).
	 *
	 * @param int $user_id Member (0: current user).
	 * @return string
	 */
	public static function profile_url( $user_id = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();

		if ( ! $user_id || ! function_exists( 'bp_core_get_user_domain' ) && ! function_exists( 'bp_members_get_user_url' ) ) {
			return '';
		}

		return function_exists( 'bp_members_get_user_url' ) ? bp_members_get_user_url( $user_id ) : bp_core_get_user_domain( $user_id );
	}

	/**
	 * URL of a group to preview group pages on: the sample group, else the newest public group.
	 *
	 * @return string
	 */
	public static function group_url() {
		if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'groups' ) ) {
			return '';
		}

		$group_id = SampleMember::group_id();

		if ( ! $group_id ) {
			$groups   = groups_get_groups(
				[
					'per_page'    => 1,
					'type'        => 'newest',
					'show_hidden' => false,
				]
			);
			$group_id = ! empty( $groups['groups'][0]->id ) ? (int) $groups['groups'][0]->id : 0;
		}

		if ( ! $group_id ) {
			return '';
		}

		$group = groups_get_group( $group_id );

		return function_exists( 'bp_get_group_url' ) ? bp_get_group_url( $group ) : bp_get_group_permalink( $group );
	}

	/**
	 * Preview map of this request, validated: only for administrators, with a valid nonce, on the front end.
	 *
	 * @return array type => [ builder, id, content? ]
	 */
	public static function map() {
		if ( null !== self::$map ) {
			return self::$map;
		}

		self::$map = [];

		// phpcs:disable WordPress.Security.NonceVerification
		if ( is_admin() || empty( $_GET[ self::PARAM ] ) || ! is_array( $_GET[ self::PARAM ] ) || empty( $_GET[ self::NONCE ] ) ) {
			return self::$map;
		}

		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ self::NONCE ] ) ), self::ACTION ) ) {
			return self::$map;
		}

		$types = Types::all();

		foreach ( wp_unslash( $_GET[ self::PARAM ] ) as $type => $value ) {
			$type  = sanitize_key( $type );
			$entry = isset( $types[ $type ] ) ? self::parse( $type, sanitize_text_field( (string) $value ) ) : null;

			if ( $entry ) {
				self::$map[ $type ] = $entry;
			}
		}
		// phpcs:enable

		return self::$map;
	}

	/**
	 * One preview value.
	 *
	 * @param string $type  Template type.
	 * @param string $value post:<id> | preset:<id>.
	 * @return array|null Registry entry (with the block markup in "content" for a look).
	 */
	private static function parse( $type, $value ) {
		$parts = explode( ':', $value, 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		if ( 'post' === $parts[0] ) {
			$id   = absint( $parts[1] );
			$post = $id ? get_post( $id ) : null;

			if ( ! $post || ! in_array( $post->post_type, [ PostType::POST_TYPE, 'elementor_library' ], true ) || ! Registry::is_post_populated( $id ) ) {
				return null;
			}

			return [
				'builder' => Registry::builder_of( $id ),
				'id'      => $id,
			];
		}

		if ( 'preset' === $parts[0] ) {
			$preset = Presets::get( sanitize_key( $parts[1] ) );

			if ( ! $preset || $type !== $preset['type'] ) {
				return null;
			}

			return [
				'builder' => Registry::BLOCKS,
				'id'      => 0,
				'content' => Builder::to_blocks( $preset['layout'] ),
			];
		}

		return null;
	}

	/**
	 * Whether this request is a layout preview.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return (bool) self::map();
	}

	/**
	 * Start the preview: swap the layouts, no caching, no indexing, a banner.
	 */
	public static function boot() {
		if ( ! self::is_active() ) {
			return;
		}

		add_filter( 'buddy_builder/template/resolve', [ __CLASS__, 'resolve' ], 5, 2 );
		add_filter( 'buddy_builder/is_template_populated/pre', [ __CLASS__, 'populated' ], 5, 2 );
		add_filter( 'buddy_builder/has_template/pre', [ __CLASS__, 'has_template' ], 5 );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );

		nocache_headers();

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
		}

		// phpcs:ignore WordPress.Security.NonceVerification
		if ( ! empty( $_GET[ self::BARE ] ) ) {
			add_filter( 'show_admin_bar', '__return_false' );
		} else {
			add_action( 'wp_footer', [ __CLASS__, 'banner' ] );
		}
	}

	/**
	 * Filter buddy_builder/template/resolve: the previewed layout.
	 *
	 * @param array  $entry Entry.
	 * @param string $type  Template type.
	 * @return array
	 */
	public static function resolve( $entry, $type ) {
		$map = self::map();

		return isset( $map[ $type ] ) ? $map[ $type ] : $entry;
	}

	/**
	 * Filter buddy_builder/is_template_populated/pre: previewed types have a layout.
	 *
	 * @param bool|null $pre  Short-circuit value.
	 * @param string    $type Template type.
	 * @return bool|null
	 */
	public static function populated( $pre, $type ) {
		return isset( self::map()[ $type ] ) ? true : $pre;
	}

	/**
	 * Filter buddy_builder/has_template/pre: BuddyBuilder takes over the previewed page.
	 *
	 * @param bool $has Short-circuit value.
	 * @return bool
	 */
	public static function has_template( $has ) {
		return $has || isset( self::map()[ Current::type() ] );
	}

	/**
	 * "Preview, not live" banner.
	 */
	public static function banner() {
		printf(
			'<div class="bpb-preview-banner" role="status" style="position:fixed;left:16px;bottom:16px;z-index:99999;background:#1d2327;color:#fff;padding:10px 14px;border-radius:6px;font:14px/1.4 -apple-system,BlinkMacSystemFont,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.3)">%s</div>',
			esc_html__( 'Preview: only you see this layout. Visitors still see the live page.', 'stax-buddy-builder' )
		);
	}
}
