<?php
/**
 * Directory header (2.0 look element): title, intro and count of the members, groups or activity directory.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_type = (string) $ctx->get( 'type', '' );

if ( '' === $bpb_type ) {
	$bpb_type = bp_is_groups_directory() ? 'groups-directory' : ( bp_is_activity_directory() ? 'sitewide-activity' : 'members-directory' );
}

$bpb_titles = [
	'members-directory' => __( 'Members', 'stax-buddy-builder' ),
	'groups-directory'  => __( 'Groups', 'stax-buddy-builder' ),
	'sitewide-activity' => __( 'Activity', 'stax-buddy-builder' ),
];
$bpb_title  = '' !== $attrs['title'] ? $attrs['title'] : ( isset( $bpb_titles[ $bpb_type ] ) ? $bpb_titles[ $bpb_type ] : '' );
$bpb_count  = '';

if ( $attrs['show_count'] ) {
	if ( 'members-directory' === $bpb_type ) {
		$bpb_n     = (int) bp_get_total_member_count();
		$bpb_count = sprintf( /* translators: %s: number */ _n( '%s member', '%s members', $bpb_n, 'stax-buddy-builder' ), number_format_i18n( $bpb_n ) );
	} elseif ( 'groups-directory' === $bpb_type && bp_is_active( 'groups' ) ) {
		$bpb_n     = (int) groups_get_total_group_count();
		$bpb_count = sprintf( /* translators: %s: number */ _n( '%s group', '%s groups', $bpb_n, 'stax-buddy-builder' ), number_format_i18n( $bpb_n ) );
	}
}
?>
<div class="bpb-dh">
	<div class="bpb-dh__text">
		<h1 class="bpb-dh__title"><?php echo esc_html( $bpb_title ); ?></h1>
		<?php if ( '' !== $attrs['intro'] ) : ?>
			<p class="bpb-dh__intro"><?php echo esc_html( $attrs['intro'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( '' !== $bpb_count ) : ?>
		<p class="bpb-dh__count"><?php echo esc_html( $bpb_count ); ?></p>
	<?php endif; ?>
</div>
