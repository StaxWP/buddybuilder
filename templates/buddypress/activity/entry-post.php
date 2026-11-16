<?php
/**
 * BuddyBuilder 2.0 activity item, "Post" style (card style `post` of the activity lists): author header, the text,
 * a labelled action bar with counts, then the comments. Printed inside the <li> of activity/entry.php, so the
 * BuddyPress scripts (comment, favorite, delete, load more) find the same hooks: the action buttons and the comment
 * form are BuddyPress' own.
 *
 * Override in your theme as buddybuilder/activity/entry-post.php.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_comments  = (int) bp_activity_get_comment_count();
$bpb_favorites = (int) bp_activity_get_meta( bp_get_activity_id(), 'favorite_count' );
?>
<article class="bpb-post">
	<header class="bpb-post__head">
		<a class="bpb-post__avatar activity-avatar" href="<?php bp_activity_user_link(); ?>">
			<?php bp_activity_avatar( [ 'type' => 'thumb', 'width' => 48, 'height' => 48 ] ); ?>
		</a>
		<div class="bpb-post__meta activity-header">
			<?php bp_activity_action(); ?>
		</div>
	</header>

	<div class="activity-content bpb-post__content">
		<?php bp_nouveau_activity_hook( 'before', 'activity_content' ); ?>

		<?php if ( bp_nouveau_activity_has_content() ) : ?>
			<div class="activity-inner bpb-post__text">
				<?php
				add_filter( 'bp_get_activity_content_body', 'bpb_link_images', 20 );
				bp_nouveau_activity_content();
				remove_filter( 'bp_get_activity_content_body', 'bpb_link_images', 20 );
				?>
			</div>
		<?php endif; ?>

		<?php bp_nouveau_activity_hook( 'after', 'activity_content' ); ?>

		<?php if ( $bpb_comments || $bpb_favorites ) : ?>
			<p class="bpb-post__stats">
				<?php if ( $bpb_favorites ) : ?>
					<span>
						<?php
						/* translators: %s: number of members who saved the post */
						echo esc_html( sprintf( _n( '%s favorite', '%s favorites', $bpb_favorites, 'stax-buddy-builder' ), number_format_i18n( $bpb_favorites ) ) );
						?>
					</span>
				<?php endif; ?>
				<?php if ( $bpb_comments ) : ?>
					<span>
						<?php
						/* translators: %s: number of comments */
						echo esc_html( sprintf( _n( '%s comment', '%s comments', $bpb_comments, 'stax-buddy-builder' ), number_format_i18n( $bpb_comments ) ) );
						?>
					</span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<div class="bpb-post__bar"<?php echo $bpb_favorites ? ' style="counter-reset: bpb-favorites ' . (int) $bpb_favorites . '"' : ''; ?>>
			<?php bp_nouveau_activity_entry_buttons(); ?>
		</div>
	</div>

	<?php bp_nouveau_activity_hook( 'before', 'entry_comments' ); ?>

	<?php if ( $bpb_comments || ( is_user_logged_in() && ( bp_activity_can_comment() || bp_is_single_activity() ) ) ) : ?>
		<div class="activity-comments bpb-post__comments">
			<?php bp_activity_comments(); ?>
			<?php bp_nouveau_activity_comment_form(); ?>
		</div>
	<?php endif; ?>

	<?php bp_nouveau_activity_hook( 'after', 'entry_comments' ); ?>
</article>
