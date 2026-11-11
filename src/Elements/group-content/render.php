<?php
/**
 * Group content: The group's current screen: activity, members, manage.
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
<div class="bp-wrap">
	<div id="item-body" class="item-body">
		<?php
		/*
		 * Returning a falsy value from the filter short-circuits the default output.
		 */
		if ( apply_filters( 'buddy_builder/tpl/profile-group/content/render', true ) ) {
			bp_nouveau_group_template_part();
		}
		?>
	</div>
</div>
