<?php
/**
 * Member navigation: Main tabs of the member profile (Activity, Profile, Friends...).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_displayed_user_id() ) {
	return;
}

bp_get_template_part( 'members/single/parts/item-nav' );
