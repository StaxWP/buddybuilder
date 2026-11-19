<?php
/**
 * Member avatar: Profile photo of the displayed member.
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
	echo bpb_mh_avatar( bp_displayed_user_id(), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
	return;
}

$bpb_args = 'type=' . $attrs['avatar_size'];
?>
<div class="elementor-image-bpb-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( $bpb_args ); ?>
	</a>
</div>
