<?php
/**
 * Member avatar. Variables: $attrs, $ctx.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();
?>
<div class="elementor-image-bpb-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=' . $attrs['avatar_size'] ); ?>
	</a>
</div>
