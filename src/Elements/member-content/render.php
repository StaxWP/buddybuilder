<?php
/**
 * Member content: The member's current screen: activity, profile, friends, groups, messages.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_displayed_user_id() ) {
	return;
}

/*
 * BuddyPress 12+ priority nav (block themes such as Twenty Twenty-Five, not KLEO): its script needs the sub nav list
 * with an id and the "more" toggle next to it. Our sub nav gets that markup; the screen template below prints
 * BuddyPress's own sub nav again (empty and hidden, see item-subnav.php), which must not be a priority nav: the script
 * keeps one overflow list per nav id, so the hidden copy made it move items that were never hidden ("Failed to
 * execute 'appendChild' on 'Node'").
 */
$bpb_priority    = function_exists( 'bp_nouveau_single_item_supports_priority_nav' ) && bp_nouveau_single_item_supports_priority_nav( 'member' );
$bpb_no_priority = static function () {
	return false;
};

?>
<div class="bp-wrap">
	<div id="item-body" class="item-body">
		<?php
		/*
		 * Returning a falsy value from the filter short-circuits the default output (Pro adds its own).
		 */
		if ( apply_filters( 'buddy_builder/tpl/profile-member/content/render', true ) ) {
			?>
			<?php if ( bp_nouveau_has_nav( [ 'type' => 'secondary' ] ) ) : ?>
				<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- 1.x markup and string, kept identical. ?>">
					<ul class="subnav<?php echo $bpb_priority ? ' bp-priority-subnav-nav-items" id="bpb-member-secondary-nav' : ''; ?>">
					<?php
					while ( bp_nouveau_nav_items() ) :
						bp_nouveau_nav_item();
						?>
						<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?>>
							<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
								<?php bp_nouveau_nav_link_text(); ?>

								<?php if ( bp_nouveau_nav_has_count() ) : ?>
								<span class="count"><?php bp_nouveau_nav_count(); ?></span>
								<?php endif; ?>
							</a>
						</li>

					<?php endwhile; ?>
					</ul>
					<?php
					if ( $bpb_priority ) {
						bp_nouveau_hidden_secondary_nav();
					}
					?>
				</nav>
			<?php endif; ?>

			<div class="bpb-settings-container">
				<?php
				if ( $bpb_priority ) {
					add_filter( 'bp_nouveau_single_item_supports_priority_nav', $bpb_no_priority );
				}

				bp_nouveau_member_template_part();

				if ( $bpb_priority ) {
					remove_filter( 'bp_nouveau_single_item_supports_priority_nav', $bpb_no_priority );
				}
				?>
			</div>

			<?php
		}
		?>
	</div>
</div>
