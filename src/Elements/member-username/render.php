<?php
/**
 * Member name or @mention name. Variables: $attrs, $ctx. The tag comes from the block.json enum (allow-list).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_tag = $attrs['username_tag'];
?>
<<?php echo tag_escape( $bpb_tag ); ?> class="user-nicename">
	<?php if ( 'full_name' === $attrs['username_type'] ) : ?>
		<?php bp_displayed_user_fullname(); ?>
	<?php elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		@<?php bp_displayed_user_mentionname(); ?>
	<?php endif; ?>
</<?php echo tag_escape( $bpb_tag ); ?>>
