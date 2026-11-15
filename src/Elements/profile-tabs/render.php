<?php
/**
 * Profile tabs (2.0 look element): the BuddyPress tabs of a member profile or group page, as our own nav. Same links,
 * ids and current state as BuddyPress' navigation (bp_nouveau_has_nav), no priority-menu script: on small screens
 * the row scrolls, with the current tab scrolled into view.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_group = bp_is_active( 'groups' ) && bp_is_group() ? groups_get_current_group() : null;

if ( ! $bpb_group && ! bp_displayed_user_id() ) {
	return;
}

$bpb_args = $bpb_group ? [ 'object' => 'groups' ] : [ 'type' => 'primary' ];

if ( ! function_exists( 'bp_nouveau_has_nav' ) || ! bp_nouveau_has_nav( $bpb_args ) ) {
	return;
}

$bpb_label = $bpb_group ? __( 'Group menu', 'stax-buddy-builder' ) : __( 'Member menu', 'stax-buddy-builder' );
?>
<nav class="bpb-tabs bpb-tabs--<?php echo esc_attr( $attrs['variant'] ); ?> bpb-tabs--<?php echo esc_attr( $attrs['align'] ); ?>" aria-label="<?php echo esc_attr( $bpb_label ); ?>">
	<ul class="bpb-tabs__list">
		<?php
		while ( bp_nouveau_nav_items() ) :
			bp_nouveau_nav_item();
			$bpb_current = false !== strpos( bp_nouveau_get_nav_classes(), 'current' ) || false !== strpos( bp_nouveau_get_nav_classes(), 'selected' );
			?>
			<li class="bpb-tabs__item<?php echo $bpb_current ? ' is-current' : ''; ?>" id="<?php bp_nouveau_nav_id(); ?>">
				<a class="bpb-tabs__link" href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>"<?php echo $bpb_current ? ' aria-current="page"' : ''; ?>>
					<span class="bpb-tabs__text"><?php bp_nouveau_nav_link_text(); ?></span>
					<?php if ( $attrs['show_counts'] && bp_nouveau_nav_has_count() ) : ?>
						<span class="bpb-tabs__count"><?php bp_nouveau_nav_count(); ?></span>
					<?php endif; ?>
				</a>
			</li>
		<?php endwhile; ?>
		<?php $bpb_group ? bp_nouveau_group_hook( '', 'options_nav' ) : bp_nouveau_member_hook( '', 'options_nav' ); ?>
	</ul>
</nav>
