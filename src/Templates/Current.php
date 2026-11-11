<?php
/**
 * Template type of the current BuddyPress page.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Templates;

defined( 'ABSPATH' ) || die();

/**
 * Current page => template type.
 */
final class Current {

	/**
	 * Template type of the current BuddyPress page ('' outside the pages BuddyBuilder can take over).
	 *
	 * @return string
	 */
	public static function type() {
		if ( ! function_exists( 'bp_is_active' ) || ! did_action( 'bp_parse_query' ) ) {
			return '';
		}

		if ( bp_is_members_directory() ) {
			return 'members-directory';
		}

		if ( bp_is_active( 'groups' ) && bp_is_groups_directory() ) {
			return 'groups-directory';
		}

		if ( bp_is_user() ) {
			return 'member-profile';
		}

		if ( bp_is_active( 'groups' ) && bp_is_single_item() && bp_is_groups_component() ) {
			return 'group-profile';
		}

		if ( bp_is_active( 'activity' ) && bp_is_activity_directory() ) {
			return 'sitewide-activity';
		}

		if ( 'register' === bp_current_component() ) {
			return 'register-page';
		}

		return '';
	}

	/**
	 * Assignment of the current page ([ builder, id ] or null).
	 *
	 * @return array|null
	 */
	public static function entry() {
		$type = self::type();

		if ( '' === $type ) {
			return null;
		}

		$entry = Registry::get( $type );

		return $entry['id'] && Registry::is_post_populated( $entry['id'] ) ? $entry : null;
	}
}
