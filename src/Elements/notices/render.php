<?php
/**
 * BuddyPress notices: Success and error messages after an action (saved settings, sent message...).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bp_nouveau_template_notices();

// Editor preview: a sample notice of the chosen type (BuddyPress only prints notices after an action).
if ( $ctx->is_preview() && ! did_action( 'bp_nouveau_template_notices' ) ) {
	printf(
		'<aside class="bp-feedback bp-messages bp-template-notice %1$s"><span class="bp-icon" aria-hidden="true"></span><p>%2$s</p></aside>',
		esc_attr( 'error' === $attrs['type'] ? 'error' : 'updated' ),
		esc_html( 'error' === $attrs['type'] ? __( 'Example error message.', 'stax-buddy-builder' ) : __( 'Example success message: your changes were saved.', 'stax-buddy-builder' ) )
	);
}
