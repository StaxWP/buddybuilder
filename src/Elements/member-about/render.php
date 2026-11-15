<?php
/**
 * Member about (2.0 look element): a card with chosen profile fields and the member's groups.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_uid = (int) bp_displayed_user_id();

if ( ! $bpb_uid ) {
	return;
}

$bpb_rows = [];
foreach ( array_filter( array_map( 'trim', explode( ',', (string) $attrs['fields'] ) ) ) as $bpb_field ) {
	$bpb_value = bpb_xprofile_value( $bpb_uid, $bpb_field );
	if ( '' !== $bpb_value ) {
		$bpb_rows[] = [ $bpb_field, $bpb_value ];
	}
}

$bpb_groups = [];
if ( $attrs['show_groups'] && bp_is_active( 'groups' ) ) {
	$bpb_list   = groups_get_groups(
		[
			'user_id'     => $bpb_uid,
			'per_page'    => 6,
			'show_hidden' => false,
			'type'        => 'active',
		]
	);
	$bpb_groups = isset( $bpb_list['groups'] ) ? $bpb_list['groups'] : [];
}

if ( ! $bpb_rows && ! $bpb_groups ) {
	return;
}
?>
<section class="bpb-about">
	<?php if ( '' !== $attrs['title'] ) : ?>
		<h2 class="bpb-about__title"><?php echo esc_html( $attrs['title'] ); ?></h2>
	<?php endif; ?>

	<?php if ( $bpb_rows ) : ?>
		<dl class="bpb-about__fields">
			<?php foreach ( $bpb_rows as $bpb_row ) : ?>
				<div class="bpb-about__row">
					<?php if ( $attrs['show_labels'] ) : ?>
						<dt><?php echo esc_html( $bpb_row[0] ); ?></dt>
					<?php endif; ?>
					<dd>
						<?php if ( wp_http_validate_url( $bpb_row[1] ) ) : ?>
							<a href="<?php echo esc_url( $bpb_row[1] ); ?>" rel="nofollow noopener"><?php echo esc_html( preg_replace( '#^https?://(www\.)?#', '', untrailingslashit( $bpb_row[1] ) ) ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $bpb_row[1] ); ?>
						<?php endif; ?>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $bpb_groups ) : ?>
		<h3 class="bpb-about__subtitle"><?php esc_html_e( 'Groups', 'stax-buddy-builder' ); ?></h3>
		<ul class="bpb-about__groups">
			<?php foreach ( $bpb_groups as $bpb_group ) : ?>
				<li>
					<a href="<?php echo esc_url( bpb_group_url( $bpb_group ) ); ?>">
						<?php
						echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup.
							[
								'item_id' => $bpb_group->id,
								'object'  => 'group',
								'type'    => 'thumb',
								'width'   => 28,
								'height'  => 28,
								'alt'     => '',
							]
						);
						?>
						<span><?php echo esc_html( $bpb_group->name ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
