<?php
/**
 * Activity post form: The "What's new" form of the activity directory (logged-in members only).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( is_user_logged_in() ) {
	bp_get_template_part( 'activity/post-form' );
}
