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

?>
<div class="activity"
	data-livestamp="<?php bp_core_iso8601_date( bp_get_user_last_activity( bp_displayed_user_id() ) ); ?>">
	<?php bp_last_activity( bp_displayed_user_id() ); ?>
</div>
