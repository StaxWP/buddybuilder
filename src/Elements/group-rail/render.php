<?php
/**
 * Group rail (2.0 element): a narrow column of group photos, your groups first (or the most active groups for
 * visitors), the current group marked. On phones it scrolls sideways.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

if ( ! bp_is_active( 'groups' ) ) {
	return;
}

$bpb_args = [
	'per_page'    => (int) $attrs['count'],
	'type'        => 'active',
	'show_hidden' => false,
];

if ( is_user_logged_in() ) {
	$bpb_args['user_id'] = get_current_user_id();
}

$bpb_list = groups_get_groups( $bpb_args );

if ( empty( $bpb_list['groups'] ) && isset( $bpb_args['user_id'] ) ) {
	unset( $bpb_args['user_id'] );
	$bpb_list = groups_get_groups( $bpb_args );
}

if ( empty( $bpb_list['groups'] ) ) {
	return;
}

$bpb_current = bp_is_group() ? (int) bp_get_current_group_id() : 0;
?>
<nav class="bpb-grail" aria-label="<?php esc_attr_e( 'Groups', 'stax-buddy-builder' ); ?>">
	<a class="bpb-grail__home<?php echo $bpb_current ? '' : ' is-current'; ?>" href="<?php echo esc_url( bpb_groups_directory_url() ); ?>" title="<?php esc_attr_e( 'All groups', 'stax-buddy-builder' ); ?>">
		<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><path d="M3 10l9-6 9 6v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
		<span class="screen-reader-text"><?php esc_html_e( 'All groups', 'stax-buddy-builder' ); ?></span>
	</a>
	<ul class="bpb-grail__list">
		<?php foreach ( $bpb_list['groups'] as $bpb_group ) : ?>
			<li class="bpb-grail__item<?php echo (int) $bpb_group->id === $bpb_current ? ' is-current' : ''; ?>">
				<a href="<?php echo esc_url( bpb_group_url( $bpb_group ) ); ?>" title="<?php echo esc_attr( $bpb_group->name ); ?>"<?php echo (int) $bpb_group->id === $bpb_current ? ' aria-current="page"' : ''; ?>>
					<?php echo bp_core_fetch_avatar( [ 'item_id' => $bpb_group->id, 'object' => 'group', 'type' => 'thumb', 'width' => 44, 'height' => 44, 'alt' => $bpb_group->name ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup. ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
