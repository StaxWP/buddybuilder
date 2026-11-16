<?php
/**
 * Post composer (2.0 element): BuddyBuilder's own "What's new" form, replacing BuddyPress' script-built form in the
 * styles. It posts through BuddyPress (AJAX action post_update with BuddyPress' nonce, or the /activity/post/ form
 * handler without JavaScript), so bp_activity_posted_update, bp_groups_posted_update and every plugin listening to
 * new updates keep working. Fires bp_before_activity_post_form, bp_activity_post_form_options and
 * bp_after_activity_post_form like the BuddyPress form; buddy_builder/composer/fields adds fields of your own.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! is_user_logged_in() || ! bp_is_active( 'activity' ) ) {
	return;
}

// Where the post goes: the group on a group page (members only), your profile on your own profile, else a choice.
$bpb_object  = 'user';
$bpb_item_id = 0;

if ( bp_is_group() ) {
	if ( ! bp_group_is_member() && ! bp_current_user_can( 'bp_moderate' ) ) {
		return;
	}
	$bpb_object  = 'group';
	$bpb_item_id = (int) bp_get_current_group_id();
} elseif ( bp_displayed_user_id() && ! bp_is_my_profile() ) {
	return;
}

bpb_look_assets();
wp_enqueue_script( 'bpb-composer', BPB_ASSETS_URL . 'js/composer.js', [], BPB_VERSION, true );

$bpb_uid    = get_current_user_id();
$bpb_name   = bp_core_get_user_displayname( $bpb_uid );
$bpb_first  = strtok( (string) $bpb_name, ' ' );
$bpb_id     = 'bpb-composer-' . wp_unique_id();
$bpb_hint   = '' !== $attrs['placeholder'] ? $attrs['placeholder'] : sprintf( /* translators: %s: first name */ __( "What's new, %s?", 'stax-buddy-builder' ), $bpb_first );
$bpb_groups = [];

if ( 'user' === $bpb_object && ! bp_displayed_user_id() && $attrs['show_post_in'] && bp_is_active( 'groups' ) ) {
	$bpb_list   = groups_get_groups(
		[
			'user_id'     => $bpb_uid,
			'per_page'    => 50,
			'show_hidden' => true,
			'orderby'     => 'name',
			'order'       => 'ASC',
		]
	);
	$bpb_groups = isset( $bpb_list['groups'] ) ? $bpb_list['groups'] : [];
}

/** This action is documented in BuddyPress' activity/post-form.php. */
do_action( 'bp_before_activity_post_form' );
?>
<form class="bpb-composer" method="post" action="<?php echo esc_url( bp_get_activity_post_form_action() ); ?>" data-bpb-composer data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-error="<?php esc_attr_e( 'Sorry, the post could not be published. Please try again.', 'stax-buddy-builder' ); ?>">
	<?php if ( $attrs['show_avatar'] ) : ?>
		<span class="bpb-composer__avatar" aria-hidden="true">
			<?php echo bp_core_fetch_avatar( [ 'item_id' => $bpb_uid, 'type' => 'thumb', 'width' => 44, 'height' => 44, 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup. ?>
		</span>
	<?php endif; ?>
	<div class="bpb-composer__body">
		<label class="bp-screen-reader-text" for="<?php echo esc_attr( $bpb_id ); ?>"><?php esc_html_e( 'Post what is new', 'stax-buddy-builder' ); ?></label>
		<textarea id="<?php echo esc_attr( $bpb_id ); ?>" class="bpb-composer__input bp-suggestions" name="whats-new" rows="1" placeholder="<?php echo esc_attr( $bpb_hint ); ?>" required></textarea>
		<div class="bpb-composer__bar">
			<?php if ( $bpb_groups ) : ?>
				<label class="bpb-composer__in">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Post in', 'stax-buddy-builder' ); ?></span>
					<select name="whats-new-post-in" data-bpb-post-in>
						<option value="0"><?php esc_html_e( 'My profile', 'stax-buddy-builder' ); ?></option>
						<?php foreach ( $bpb_groups as $bpb_group ) : ?>
							<option value="<?php echo (int) $bpb_group->id; ?>"><?php echo esc_html( $bpb_group->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<?php
			/** This action is documented in BuddyPress' activity/post-form.php. */
			do_action( 'bp_activity_post_form_options' );

			/**
			 * Prints extra fields inside the composer (sent with the post as form fields).
			 *
			 * @since 2.0.0
			 *
			 * @param string $object  user or group.
			 * @param int    $item_id Group id, 0 for the member's profile.
			 */
			do_action( 'buddy_builder/composer/fields', $bpb_object, $bpb_item_id );
			?>
			<button type="submit" class="bpb-composer__submit" name="aw-whats-new-submit"><?php echo esc_html( '' !== $attrs['button_label'] ? $attrs['button_label'] : __( 'Post', 'stax-buddy-builder' ) ); ?></button>
		</div>
		<p class="bpb-composer__status" role="status" aria-live="polite"></p>
	</div>
	<input type="hidden" name="whats-new-post-object" value="<?php echo esc_attr( 'group' === $bpb_object ? 'groups' : 'user' ); ?>" data-bpb-object="<?php echo esc_attr( $bpb_object ); ?>">
	<?php if ( 'group' === $bpb_object ) : ?>
		<input type="hidden" name="whats-new-post-in" value="<?php echo (int) $bpb_item_id; ?>">
	<?php endif; ?>
	<?php wp_nonce_field( 'post_update', '_wpnonce_post_update' ); ?>
</form>
<?php
/** This action is documented in BuddyPress' activity/post-form.php. */
do_action( 'bp_after_activity_post_form' );
