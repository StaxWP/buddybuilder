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

$bpb_args = 'type=' . $attrs['avatar_size'];
?>
<div class="elementor-image-bpb-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( $bpb_args ); ?>
	</a>
</div>
