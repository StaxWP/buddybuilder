<?php
/**
 * Member content: BuddyPress secondary navigation and the current screen (activity, profile, friends...).
 * Variables: $attrs, $ctx.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();
?>
<div class="bp-wrap">
	<div id="item-body" class="item-body">
		<?php
		/*
		 * Returning a falsy value from the filter short-circuits the default output (Pro adds its own).
		 */
		if ( apply_filters( 'buddy_builder/tpl/profile-member/content/render', true ) ) :
			if ( bp_nouveau_has_nav( [ 'type' => 'secondary' ] ) ) :
				?>
				<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'stax-buddy-builder' ); ?>">
					<ul class="subnav">
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
				</nav>
			<?php endif; ?>

			<div class="bpb-settings-container">
				<?php bp_nouveau_member_template_part(); ?>
			</div>
		<?php endif; ?>
	</div>
</div>
