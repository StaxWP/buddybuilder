<?php
/**
 * Member "last active". Variables: $attrs, $ctx.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();
?>
<div class="activity" data-livestamp="<?php bp_core_iso8601_date( bp_get_user_last_activity( $ctx['user_id'] ) ); ?>">
	<?php bp_last_activity( $ctx['user_id'] ); ?>
</div>
