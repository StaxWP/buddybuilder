<?php
/**
 * Member header (2.0 look element): cover, photo, name with badges, @username, headline, location and dates,
 * stats and the member buttons, in one header designed for the looks. Layouts: side, center, compact, card.
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

$bpb_layout   = $attrs['layout'];
$bpb_cover    = $attrs['show_cover'] && 'compact' !== $bpb_layout ? bpb_cover_url( 'members', $bpb_uid ) : '';
$bpb_headline = $attrs['show_headline'] ? bpb_xprofile_value( $bpb_uid, $attrs['headline_field'] ) : '';
$bpb_location = $attrs['show_meta'] ? bpb_xprofile_value( $bpb_uid, $attrs['location_field'] ) : '';
$bpb_user     = get_userdata( $bpb_uid );
$bpb_classes  = 'bpb-mh bpb-mh--' . $bpb_layout . ( $attrs['show_cover'] && 'compact' !== $bpb_layout ? ' bpb-mh--has-cover' : '' );
?>
<header class="<?php echo esc_attr( $bpb_classes ); ?>">
	<?php if ( $attrs['show_cover'] && 'compact' !== $bpb_layout ) : ?>
		<div class="bpb-mh__cover<?php echo $bpb_cover ? '' : ' bpb-mh__cover--empty'; ?>"<?php echo $bpb_cover ? ' style="background-image:url(' . esc_url( $bpb_cover ) . ')"' : ''; ?>></div>
	<?php endif; ?>

	<div class="bpb-mh__body">
		<div class="bpb-mh__avatar">
			<a href="<?php echo esc_url( bpb_user_url( $bpb_uid ) ); ?>">
				<?php
				echo bp_core_fetch_avatar( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- BuddyPress markup.
					[
						'item_id' => $bpb_uid,
						'type'    => 'full',
						'class'   => 'bpb-mh__photo',
						'alt'     => sprintf( /* translators: %s: member name */ __( 'Profile photo of %s', 'stax-buddy-builder' ), bp_core_get_user_displayname( $bpb_uid ) ),
					]
				);
				?>
			</a>
			<?php if ( bpb_member_is_online( $bpb_uid ) ) : ?>
				<span class="bpb-mh__online" title="<?php esc_attr_e( 'Online', 'stax-buddy-builder' ); ?>"><span class="screen-reader-text"><?php esc_html_e( 'Online', 'stax-buddy-builder' ); ?></span></span>
			<?php endif; ?>
		</div>

		<div class="bpb-mh__main">
			<h1 class="bpb-mh__name">
				<?php echo esc_html( bp_core_get_user_displayname( $bpb_uid ) ); ?>
				<?php if ( $attrs['show_badges'] ) : ?>
					<?php foreach ( bpb_member_badges( $bpb_uid ) as $bpb_badge ) : ?>
						<span class="bpb-badge"><?php echo esc_html( $bpb_badge ); ?></span>
					<?php endforeach; ?>
				<?php endif; ?>
			</h1>

			<?php if ( $attrs['show_handle'] && bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
				<p class="bpb-mh__handle">@<?php echo esc_html( bp_activity_get_user_mentionname( $bpb_uid ) ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== $bpb_headline ) : ?>
				<p class="bpb-mh__headline"><?php echo esc_html( $bpb_headline ); ?></p>
			<?php endif; ?>

			<?php if ( $attrs['show_meta'] ) : ?>
				<ul class="bpb-mh__meta">
					<?php if ( '' !== $bpb_location ) : ?>
						<li><?php echo bpb_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( $bpb_location ); ?></li>
					<?php endif; ?>
					<?php if ( $bpb_user ) : ?>
						<li><?php echo bpb_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( sprintf( /* translators: %s: month and year */ __( 'Joined %s', 'stax-buddy-builder' ), date_i18n( 'F Y', strtotime( $bpb_user->user_registered ) ) ) ); ?></li>
					<?php endif; ?>
					<li><?php echo bpb_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( bp_get_last_activity( $bpb_uid ) ); ?></li>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( $attrs['show_stats'] ) : ?>
			<ul class="bpb-mh__stats">
				<?php foreach ( bpb_member_stats( $bpb_uid ) as $bpb_stat ) : ?>
					<li class="bpb-mh__stat bpb-mh__stat--<?php echo esc_attr( $bpb_stat[0] ); ?>">
						<a href="<?php echo esc_url( $bpb_stat[3] ); ?>"><strong><?php echo esc_html( number_format_i18n( $bpb_stat[1] ) ); ?></strong> <span><?php echo esc_html( $bpb_stat[2] ); ?></span></a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $attrs['show_buttons'] ) : ?>
			<div class="bpb-mh__actions">
				<?php
				bp_nouveau_member_header_buttons(
					[
						'container'         => 'ul',
						'button_element'    => 'button',
						'container_classes' => [ 'member-header-actions', 'bpb-actions' ],
					]
				);
				?>
			</div>
		<?php endif; ?>
	</div>
</header>
