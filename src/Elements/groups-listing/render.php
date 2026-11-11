<?php
/**
 * Groups directory list: The groups list of the groups directory (loaded by BuddyPress).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

?>
<div id="groups-dir-list" class="groups dir-list" data-bp-list="groups">
	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-groups-loading' ); ?></div>
</div>
