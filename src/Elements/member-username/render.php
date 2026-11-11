<?php
/**
 * Member name: Display name or @username of the displayed member.
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
<<?php echo esc_attr( $attrs['username_tag'] ); ?> class="user-nicename">
	<?php if ( 'full_name' === $attrs['username_type'] ) : ?>
		<?php bp_displayed_user_fullname(); ?>
	<?php elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
		@<?php bp_displayed_user_mentionname(); ?>
	<?php endif; ?>
</<?php echo esc_attr( $attrs['username_tag'] ); ?>>
