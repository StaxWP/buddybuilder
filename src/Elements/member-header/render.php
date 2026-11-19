<?php
/**
 * Member header (2.0 look element): cover, photo, name with badges, @username, headline, location and dates,
 * stats and the member buttons, in one header designed for the looks. Layouts: side, center, compact, card.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 * The parts' markup comes from the bpb_mh_*() helpers (look-helpers.php), shared with the header part elements.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

bpb_look_assets();

$bpb_uid = (int) bp_displayed_user_id();

if ( ! $bpb_uid ) {
	return;
}

$bpb_layout   = $attrs['layout'];
$bpb_cover    = $attrs['show_cover'] && 'compact' !== $bpb_layout;
$bpb_headline = $attrs['show_headline'] ? bpb_xprofile_value( $bpb_uid, $attrs['headline_field'] ) : '';
$bpb_classes  = 'bpb-mh bpb-mh--' . $bpb_layout . ( $bpb_cover ? ' bpb-mh--has-cover' : '' );

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- the bpb_mh_*() helpers escape their markup.
?>
<header class="<?php echo esc_attr( $bpb_classes ); ?>">
	<?php echo $bpb_cover ? bpb_mh_cover( $bpb_uid ) : ''; ?>

	<div class="bpb-mh__body">
		<?php echo bpb_mh_avatar( $bpb_uid ); ?>

		<div class="bpb-mh__main">
			<?php echo bpb_mh_name( $bpb_uid, false, $attrs['show_badges'] ? bpb_mh_badges( $bpb_uid ) : '' ); ?>
			<?php echo $attrs['show_handle'] ? bpb_mh_handle( $bpb_uid ) : ''; ?>
			<?php echo bpb_mh_headline( $bpb_headline ); ?>
			<?php echo $attrs['show_meta'] ? bpb_mh_meta( $bpb_uid, bpb_xprofile_value( $bpb_uid, $attrs['location_field'] ) ) : ''; ?>
		</div>

		<?php echo $attrs['show_stats'] ? bpb_mh_stats( bpb_member_stats( $bpb_uid ) ) : ''; ?>
		<?php echo $attrs['show_buttons'] ? bpb_mh_actions() : ''; ?>
	</div>
</header>
<?php
// phpcs:enable
