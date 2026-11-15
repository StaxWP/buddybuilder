<?php
/**
 * Feed filters (2.0 look element): the activity directory scopes (All, Friends, Groups, Favorites, Mentions) as a
 * segmented control, and the type filter as a compact menu. Keeps BuddyPress' hooks (`.bp-navs` with
 * `data-bp-scope` / `data-bp-object`, `data-bp-filter`), so filtering, the AJAX stream and "load more" work as before.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

if ( ! bp_is_active( 'activity' ) || ! function_exists( 'bp_nouveau_has_nav' ) ) {
	return;
}
?>
<div class="bpb-feed-filters">
	<?php if ( is_user_logged_in() && bp_nouveau_has_nav( [ 'object' => 'directory' ] ) ) : ?>
		<nav class="bp-navs bpb-segmented" aria-label="<?php esc_attr_e( 'Show activity from', 'stax-buddy-builder' ); ?>">
			<ul class="bpb-segmented__list">
				<?php
				while ( bp_nouveau_nav_items() ) :
					bp_nouveau_nav_item();
					?>
					<li id="<?php bp_nouveau_nav_id(); ?>" class="bpb-segmented__item <?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
						<a href="<?php bp_nouveau_nav_link(); ?>">
							<?php bp_nouveau_nav_link_text(); ?>
							<?php if ( $attrs['show_counts'] && bp_nouveau_nav_has_count() ) : ?>
								<span class="bpb-segmented__count"><?php bp_nouveau_nav_count(); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endwhile; ?>
			</ul>
		</nav>
	<?php endif; ?>

	<?php if ( $attrs['show_type'] ) : ?>
		<label class="bpb-feed-filters__type">
			<span class="bpb-feed-filters__label"><?php esc_html_e( 'Show', 'stax-buddy-builder' ); ?></span>
			<select id="activity-filter-by" data-bp-filter="activity">
				<?php bp_nouveau_filter_options(); ?>
			</select>
		</label>
	<?php endif; ?>
</div>
