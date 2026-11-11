<?php
/**
 * Group navigation: Main tabs of the group (Home, Members, Manage...).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_get_current_group_id() ) {
	return;
}

bp_get_template_part( 'groups/single/parts/item-nav' );
