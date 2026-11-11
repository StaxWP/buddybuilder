<?php
/**
 * Group last active: When the current group was last active.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_get_current_group_id() ) {
	return;
}

?>
<div class="activity"
	data-livestamp="<?php bp_core_iso8601_date( bp_get_group_last_active( 0, [ 'relative' => false ] ) ); ?>">
	<?php
	/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
	printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); // phpcs:ignore WordPress.Security.EscapeOutput, WordPress.WP.I18n.TextDomainMismatch
	?>
</div>
