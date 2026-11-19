<?php
/**
 * Member name: Display name or @username of the displayed member.
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
	echo 'full_name' === $attrs['username_type'] ? bpb_mh_name( bp_displayed_user_id(), true ) : bpb_mh_handle( bp_displayed_user_id(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
	return;
}

?>
<<?php echo esc_attr( $attrs['username_tag'] ); ?> class="user-nicename">
	<?php if ( 'full_name' === $attrs['username_type'] ) : ?>
		<?php bp_displayed_user_fullname(); ?>
	<?php elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		@<?php bp_displayed_user_mentionname(); ?>
	<?php endif; ?>
</<?php echo esc_attr( $attrs['username_tag'] ); ?>>
