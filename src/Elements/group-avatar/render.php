<?php
/**
 * Group avatar: Photo of the current group.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_get_current_group_id() ) {
	return;
}

$bpb_args = 'type=' . $attrs['avatar_size'];
?>
<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
	<div id="item-header-avatar">
		<a href="<?php echo esc_url( bp_get_group_permalink() ); ?>"
			title="<?php echo esc_attr( bp_get_group_name() ); ?>">
			<?php bp_group_avatar( $bpb_args ); ?>
		</a>
	</div>
<?php endif; ?>
