<?php
/**
 * Directory tabs: All / My friends / My groups tabs of the members or groups directory.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_nouveau_is_object_nav_in_sidebar() ) {
	bp_get_template_part( 'common/nav/directory-nav' );
}
