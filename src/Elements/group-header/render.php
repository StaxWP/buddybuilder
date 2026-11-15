<?php
/**
 * Group header (2.0 look element): cover, photo, name, privacy, description, member avatars and the join button.
 * Layouts: side, center, compact.
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

$bpb_group = groups_get_current_group();

if ( ! $bpb_group || empty( $bpb_group->id ) ) {
	return;
}

$bpb_layout  = $attrs['layout'];
$bpb_cover   = $attrs['show_cover'] && 'compact' !== $bpb_layout ? bpb_cover_url( 'groups', $bpb_group->id ) : '';
$bpb_status  = [
	'public'  => [ 'globe', __( 'Public group', 'stax-buddy-builder' ) ],
	'private' => [ 'lock', __( 'Private group', 'stax-buddy-builder' ) ],
	'hidden'  => [ 'lock', __( 'Hidden group', 'stax-buddy-builder' ) ],
];
$bpb_status  = isset( $bpb_status[ $bpb_group->status ] ) ? $bpb_status[ $bpb_group->status ] : $bpb_status['public'];
$bpb_count   = (int) groups_get_total_member_count( $bpb_group->id );
$bpb_classes = 'bpb-gh bpb-gh--' . $bpb_layout . ( $attrs['show_cover'] && 'compact' !== $bpb_layout ? ' bpb-gh--has-cover' : '' );
?>
<header class="<?php echo esc_attr( $bpb_classes ); ?>">
	<?php if ( $attrs['show_cover'] && 'compact' !== $bpb_layout ) : ?>
		<div class="bpb-gh__cover<?php echo $bpb_cover ? '' : ' bpb-gh__cover--empty'; ?>"<?php echo $bpb_cover ? ' style="background-image:url(' . esc_url( $bpb_cover ) . ')"' : ''; ?>></div>
	<?php endif; ?>

	<div class="bpb-gh__body">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div class="bpb-gh__avatar">
				<?php
				echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup.
					[
						'item_id' => $bpb_group->id,
						'object'  => 'group',
						'type'    => 'full',
						'class'   => 'bpb-gh__photo',
						'alt'     => sprintf( /* translators: %s: group name */ __( 'Photo of %s', 'stax-buddy-builder' ), $bpb_group->name ),
					]
				);
				?>
			</div>
		<?php endif; ?>

		<div class="bpb-gh__main">
			<h1 class="bpb-gh__name"><?php echo esc_html( $bpb_group->name ); ?></h1>
			<ul class="bpb-gh__meta">
				<li><?php echo bpb_icon( $bpb_status[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $bpb_status[1] ); ?></li>
				<li><?php echo bpb_icon( 'users' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( sprintf( /* translators: %s: number of members */ _n( '%s member', '%s members', $bpb_count, 'stax-buddy-builder' ), number_format_i18n( $bpb_count ) ) ); ?></li>
				<li><?php echo bpb_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( sprintf( /* translators: %s: time since */ __( 'Active %s', 'stax-buddy-builder' ), bp_get_group_last_active( $bpb_group ) ) ); ?></li>
			</ul>
			<?php if ( $attrs['show_description'] && '' !== trim( $bpb_group->description ) ) : ?>
				<p class="bpb-gh__description"><?php echo esc_html( wp_strip_all_tags( $bpb_group->description ) ); ?></p>
			<?php endif; ?>
		</div>

		<div class="bpb-gh__side">
			<?php if ( $attrs['show_members'] && $bpb_count ) : ?>
				<?php
				$bpb_members = groups_get_group_members(
					[
						'group_id'            => $bpb_group->id,
						'per_page'            => 5,
						'exclude_admins_mods' => false,
					]
				);
				echo bpb_avatar_stack( wp_list_pluck( isset( $bpb_members['members'] ) ? $bpb_members['members'] : [], 'ID' ), $bpb_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
				?>
			<?php endif; ?>
			<?php if ( $attrs['show_buttons'] ) : ?>
				<div class="bpb-gh__actions">
					<?php
					bp_nouveau_group_header_buttons(
						[
							'container'         => 'ul',
							'button_element'    => 'button',
							'container_classes' => [ 'group-header-actions', 'bpb-actions' ],
						]
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</header>
