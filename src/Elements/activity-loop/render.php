<?php
/**
 * Activity list: Recent activity anywhere on the site.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_render_general_loop( 'activity', $attrs, $ctx->get( 'elementor_settings' ) );
