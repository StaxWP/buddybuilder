<?php
/**
 * BuddyPress Members Directory
 *
 * @version 3.0.0
 */

$render    = bpb_is_template_populated( 'members-directory' );

?>

<?php bp_nouveau_before_members_directory_content(); ?>

<?php if ( $render ) : ?>
	<?php echo bpb_render_template( 'members-directory' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php bp_nouveau_after_members_directory_content(); ?>
<?php else : ?>
	<?php if ( ! bp_nouveau_is_object_nav_in_sidebar() ) : ?>

		<?php bp_get_template_part( 'common/nav/directory-nav' ); ?>

	<?php endif; ?>

	<div class="screen-content">

		<?php bp_get_template_part( 'common/search-and-filters-bar' ); ?>

		<div id="members-dir-list" class="members dir-list" data-bp-list="members">
			<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-members-loading' ); ?></div>
		</div><!-- #members-dir-list -->

		<?php bp_nouveau_after_members_directory_content(); ?>
	</div><!-- // .screen-content -->
<?php endif; ?>
