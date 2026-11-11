<?php
/**
 * Member latest update: The displayed member's latest activity update and profile header meta.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_displayed_user_id() ) {
	return;
}

do_action( 'bp_before_member_header_meta' );
?>
<div id="item-meta">
	<?php if ( bp_is_active( 'activity' ) ) : ?>
		<div id="latest-update">
			<?php bp_activity_latest_update( bp_displayed_user_id() ); ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'bp_profile_header_meta' ); ?>
</div>
