<?php
/**
 * Member list (2.0 element): members with their online status, Online first then Offline. In a group: the group's
 * members; elsewhere: the most recently active members. On phones it can fold into a drawer.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_count = (int) $attrs['count'];
$bpb_ids   = [];

if ( bp_is_active( 'groups' ) && bp_is_group() ) {
	$bpb_members = groups_get_group_members(
		[
			'group_id'            => bp_get_current_group_id(),
			'per_page'            => $bpb_count,
			'exclude_admins_mods' => false,
		]
	);
	$bpb_ids     = wp_list_pluck( isset( $bpb_members['members'] ) ? $bpb_members['members'] : [], 'ID' );
} else {
	$bpb_users = bp_core_get_users(
		[
			'type'     => 'active',
			'per_page' => $bpb_count,
		]
	);
	$bpb_ids   = wp_list_pluck( isset( $bpb_users['users'] ) ? $bpb_users['users'] : [], 'ID' );
}

if ( ! $bpb_ids ) {
	return;
}

$bpb_groups = [
	'online'  => [],
	'offline' => [],
];

foreach ( $bpb_ids as $bpb_id ) {
	$bpb_groups[ bpb_member_is_online( (int) $bpb_id ) ? 'online' : 'offline' ][] = (int) $bpb_id;
}

$bpb_labels = [
	'online'  => __( 'Online', 'stax-buddy-builder' ),
	'offline' => __( 'Offline', 'stax-buddy-builder' ),
];
$bpb_id     = 'bpb-mlist-' . wp_unique_id();
?>
<aside class="bpb-mlist<?php echo $attrs['drawer'] ? ' bpb-mlist--drawer' : ''; ?>" aria-label="<?php esc_attr_e( 'Members', 'stax-buddy-builder' ); ?>">
	<?php if ( $attrs['drawer'] ) : ?>
		<input type="checkbox" class="bpb-drawer__toggle" id="<?php echo esc_attr( $bpb_id ); ?>">
		<label class="bpb-drawer__button" for="<?php echo esc_attr( $bpb_id ); ?>">
			<?php
			/* translators: %s: number of members */
			echo esc_html( sprintf( __( 'Members (%s)', 'stax-buddy-builder' ), number_format_i18n( count( $bpb_ids ) ) ) );
			?>
		</label>
	<?php endif; ?>
	<div class="bpb-mlist__body">
		<?php foreach ( $bpb_groups as $bpb_key => $bpb_list ) : ?>
			<?php if ( $bpb_list ) : ?>
				<h2 class="bpb-mlist__title">
					<?php
					/* translators: 1: Online or Offline, 2: count */
					echo esc_html( sprintf( __( '%1$s, %2$s', 'stax-buddy-builder' ), $bpb_labels[ $bpb_key ], number_format_i18n( count( $bpb_list ) ) ) );
					?>
				</h2>
				<ul class="bpb-mlist__list bpb-mlist__list--<?php echo esc_attr( $bpb_key ); ?>">
					<?php foreach ( $bpb_list as $bpb_member ) : ?>
						<li class="bpb-mlist__item">
							<a href="<?php echo esc_url( bpb_user_url( $bpb_member ) ); ?>">
								<span class="bpb-mlist__avatar"><?php echo bp_core_fetch_avatar( [ 'item_id' => $bpb_member, 'type' => 'thumb', 'width' => 32, 'height' => 32, 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup. ?><span class="bpb-mlist__dot" aria-hidden="true"></span></span>
								<span class="bpb-mlist__name"><?php echo esc_html( bp_core_get_user_displayname( $bpb_member ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</aside>
