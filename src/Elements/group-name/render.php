<?php
/**
 * Group name: Name of the current group.
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
<div class="bpb-group-name">
	<?php bp_current_group_name(); ?>
</div>
