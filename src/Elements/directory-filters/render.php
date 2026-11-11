<?php
/**
 * Directory search and filters: Search, sort and grid/list toggle of the members or groups directory.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_has_list_toggle = ! in_array( (int) $attrs['content_display'], [ 9, 10, 13, 15 ], true );

if ( $bpb_has_list_toggle ) {
	add_filter( 'buddy_builder/widget/filters/list_toggle/enabled', '__return_true' );
}

bp_get_template_part( 'common/search-and-filters-bar' );
