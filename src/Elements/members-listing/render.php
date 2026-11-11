<?php
/**
 * Members directory list: The members list of the members directory (loaded by BuddyPress).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

?>
<div id="members-dir-list" class="members dir-list" data-bp-list="members">
	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-members-loading' ); ?></div>
</div>
