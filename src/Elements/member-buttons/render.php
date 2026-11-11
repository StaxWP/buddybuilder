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

bp_nouveau_member_header_buttons( [ 'container_classes' => [ 'member-header-actions' ] ] );
