<?php
/**
 * Member buttons: Friendship, message and follow buttons for the displayed member.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_displayed_user_id() ) {
	return;
}

// Part of a split Member header (BuddyBuilder Pro): the header's markup for this part (look-helpers.php).
if ( 'header' === $attrs['design'] ) {
	bpb_look_assets();
	echo bpb_mh_actions( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
	return;
}

bp_nouveau_member_header_buttons( [ 'container_classes' => [ 'member-header-actions' ] ] );
