<?php
/**
 * Member last active: When the displayed member was last active.
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
	echo bpb_mh_meta( bp_displayed_user_id(), bpb_xprofile_value( bp_displayed_user_id(), $attrs['location_field'] ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
	return;
}

?>
<div class="activity"
	data-livestamp="<?php bp_core_iso8601_date( bp_get_user_last_activity( bp_displayed_user_id() ) ); ?>">
	<?php bp_last_activity( bp_displayed_user_id() ); ?>
</div>
