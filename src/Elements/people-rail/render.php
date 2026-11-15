<?php
/**
 * People and groups list (2.0 look element): a short list for side columns ("who to follow", "active groups").
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_items = [];
$bpb_order = in_array( $attrs['order'], [ 'active', 'newest', 'popular' ], true ) ? $attrs['order'] : 'active';

if ( 'groups' === $attrs['object'] ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}
	$bpb_list = groups_get_groups(
		[
			'type'        => $bpb_order,
			'per_page'    => (int) $attrs['count'],
			'show_hidden' => false,
		]
	);
	foreach ( isset( $bpb_list['groups'] ) ? $bpb_list['groups'] : [] as $bpb_group ) {
		$bpb_n       = (int) groups_get_total_member_count( $bpb_group->id );
		$bpb_items[] = [
			'url'    => bpb_group_url( $bpb_group ),
			'name'   => $bpb_group->name,
			'meta'   => sprintf( /* translators: %s: number */ _n( '%s member', '%s members', $bpb_n, 'stax-buddy-builder' ), number_format_i18n( $bpb_n ) ),
			'avatar' => bp_core_fetch_avatar(
				[
					'item_id' => $bpb_group->id,
					'object'  => 'group',
					'type'    => 'thumb',
					'width'   => 40,
					'height'  => 40,
					'alt'     => '',
				]
			),
		];
	}
} else {
	$bpb_list = bp_core_get_users(
		[
			'type'     => $bpb_order,
			'per_page' => (int) $attrs['count'],
			'exclude'  => array_filter( [ bp_displayed_user_id(), get_current_user_id() ] ),
		]
	);
	foreach ( isset( $bpb_list['users'] ) ? $bpb_list['users'] : [] as $bpb_user ) {
		$bpb_headline = bpb_xprofile_value( $bpb_user->ID, 'Headline' );
		$bpb_items[]  = [
			'url'    => bpb_user_url( $bpb_user->ID ),
			'name'   => bp_core_get_user_displayname( $bpb_user->ID ),
			'meta'   => '' !== $bpb_headline ? $bpb_headline : bp_get_last_activity( $bpb_user->ID ),
			'avatar' => bp_core_fetch_avatar(
				[
					'item_id' => $bpb_user->ID,
					'type'    => 'thumb',
					'width'   => 40,
					'height'  => 40,
					'alt'     => '',
				]
			),
		];
	}
}

if ( ! $bpb_items ) {
	return;
}

$bpb_default = 'groups' === $attrs['object'] ? __( 'Active groups', 'stax-buddy-builder' ) : __( 'People to know', 'stax-buddy-builder' );
?>
<section class="bpb-rail">
	<h2 class="bpb-rail__title"><?php echo esc_html( '' !== $attrs['title'] ? $attrs['title'] : $bpb_default ); ?></h2>
	<ul class="bpb-rail__list">
		<?php foreach ( $bpb_items as $bpb_item ) : ?>
			<li class="bpb-rail__item">
				<a href="<?php echo esc_url( $bpb_item['url'] ); ?>">
					<?php echo $bpb_item['avatar']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup. ?>
					<span class="bpb-rail__text"><strong><?php echo esc_html( $bpb_item['name'] ); ?></strong><span><?php echo esc_html( $bpb_item['meta'] ); ?></span></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
