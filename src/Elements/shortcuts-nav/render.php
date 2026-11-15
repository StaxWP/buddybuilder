<?php
/**
 * Community shortcuts (2.0 element): your profile, Activity, Members, Groups, Messages, Notifications. A side list or
 * "channels" column on large screens; on phones, optionally a bottom tab bar.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_uid   = get_current_user_id();
$bpb_items = [];

if ( bp_is_active( 'activity' ) ) {
	$bpb_items['activity'] = [ __( 'Activity', 'stax-buddy-builder' ), bp_get_activity_directory_permalink(), bp_is_activity_directory(), 'feed' ];
}

$bpb_items['members'] = [ __( 'Members', 'stax-buddy-builder' ), bp_get_members_directory_permalink(), bp_is_members_directory(), 'users' ];

if ( bp_is_active( 'groups' ) ) {
	$bpb_items['groups'] = [ __( 'Groups', 'stax-buddy-builder' ), bpb_groups_directory_url(), bp_is_groups_directory() || bp_is_group(), 'groups' ];
}

if ( $bpb_uid && bp_is_active( 'messages' ) ) {
	$bpb_items['messages'] = [ __( 'Messages', 'stax-buddy-builder' ), bpb_user_url( $bpb_uid, bp_get_messages_slug() ), bp_is_user_messages(), 'mail' ];
}

if ( $bpb_uid && bp_is_active( 'notifications' ) ) {
	$bpb_items['notifications'] = [ __( 'Notifications', 'stax-buddy-builder' ), bpb_user_url( $bpb_uid, bp_get_notifications_slug() ), bp_is_user_notifications(), 'bell' ];
}

$bpb_icons = [
	'feed'   => 'M4 5h16M4 12h16M4 19h10',
	'users'  => 'M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM20 20v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.3a3.5 3.5 0 0 1 0 6.4',
	'groups' => 'M3 10l9-6 9 6v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
	'mail'   => 'M3 6h18v12H3zM3 7l9 6 9-6',
	'bell'   => 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4zM10 20a2 2 0 0 0 4 0',
	'user'   => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
];
$bpb_svg   = static function ( $name ) use ( $bpb_icons ) {
	return '<svg class="bpb-shortcuts__icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="' . esc_attr( $bpb_icons[ $name ] ) . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
};
$bpb_class = 'bpb-shortcuts bpb-shortcuts--' . $attrs['layout'] . ( $attrs['mobile_bar'] ? ' bpb-shortcuts--bar' : '' );
?>
<nav class="<?php echo esc_attr( $bpb_class ); ?>" aria-label="<?php esc_attr_e( 'Community', 'stax-buddy-builder' ); ?>">
	<ul class="bpb-shortcuts__list">
		<?php if ( $attrs['show_user'] && $bpb_uid ) : ?>
			<li class="bpb-shortcuts__item bpb-shortcuts__item--me<?php echo bp_is_my_profile() ? ' is-current' : ''; ?>">
				<a href="<?php echo esc_url( bpb_user_url( $bpb_uid ) ); ?>">
					<?php echo bp_core_fetch_avatar( [ 'item_id' => $bpb_uid, 'type' => 'thumb', 'width' => 28, 'height' => 28, 'alt' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup. ?>
					<span class="bpb-shortcuts__label"><?php echo esc_html( bp_core_get_user_displayname( $bpb_uid ) ); ?></span>
				</a>
			</li>
		<?php endif; ?>
		<?php foreach ( $bpb_items as $bpb_key => $bpb_item ) : ?>
			<li class="bpb-shortcuts__item bpb-shortcuts__item--<?php echo esc_attr( $bpb_key ); ?><?php echo $bpb_item[2] ? ' is-current' : ''; ?>">
				<a href="<?php echo esc_url( $bpb_item[1] ); ?>"<?php echo $bpb_item[2] ? ' aria-current="page"' : ''; ?>>
					<?php echo $bpb_svg( $bpb_item[3] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above. ?>
					<span class="bpb-shortcuts__label"><?php echo esc_html( $bpb_item[0] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
