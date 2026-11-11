<?php
/**
 * Group status: Public, private or hidden status of the group.
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
<p class="highlight group-status">
	<?php if ( bpb_is_buddyboss() ) : ?>
		<?php echo bp_get_group_type(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php else : ?>
		<?php echo bp_nouveau_the_group_meta( [ 'keys' => 'status' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php endif; ?>
</p>
