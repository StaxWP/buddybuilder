<?php
/**
 * BuddyPress - Groups Admin
 *
 * @since 3.0.0
 * @version 3.0.0
 */

?>

<?php if ( bpb_is_buddyboss() ) : ?>
<div class="bp-manage-group-container">
<?php endif; ?>

	<?php bp_get_template_part( 'groups/single/parts/admin-subnav' ); ?>

	<div class="bpb-settings-container">
		<form action="<?php bp_group_admin_form_action(); ?>" name="group-settings-form" id="group-settings-form" class="standard-form" method="post" enctype="multipart/form-data">

			<?php bp_nouveau_group_manage_screen(); ?>

		</form><!-- #group-settings-form -->
	</div>

<?php if ( bpb_is_buddyboss() ) : ?>
</div>
<?php endif; ?>
