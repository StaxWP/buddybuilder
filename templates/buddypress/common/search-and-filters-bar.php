<?php
/**
 * BP Nouveau Search & filters bar
 *
 * @since 3.0.0
 * @version 3.1.0
 */
?>
<div class="subnav-filters filters no-ajax" id="subnav-filters">
	<?php if ( in_array( bp_current_component(), [ 'members', 'groups', 'friends' ] ) ): ?>
		<?php
		$current_component = bp_current_component();

		if ( $current_component === 'friends' ) {
			$current_component = 'members';
		}

		$listing_type = 'grid';

		if ( isset( $_COOKIE[ $current_component ] ) && in_array( $_COOKIE[ $current_component ], [
				'list',
				'grid'
			] ) ) {
			$listing_type = $_COOKIE[ $current_component ];
		}

		$list_class = $listing_type === 'list' ? 'bpb-active' : '';
		$grid_class = $listing_type === 'grid' ? 'bpb-active' : '';
		?>
        <div class="bpb-listing-type" data-component="<?php echo esc_attr( $current_component ); ?>">
            <span class="bpb-list-mode <?php echo esc_attr( $list_class ); ?>">
                <span class="dashicons dashicons-list-view"></span>
            </span>
            <span class="bpb-grid-mode <?php echo esc_attr( $grid_class ); ?>">
                <span class="dashicons dashicons-grid-view"></span>
            </span>
        </div>
	<?php endif; ?>

	<?php if ( 'friends' !== bp_current_component() ) : ?>
        <div class="subnav-search clearfix">

			<?php if ( 'activity' === bp_current_component() ) : ?>
                <div class="feed">
                    <a href="<?php bp_sitewide_activity_feed_link(); ?>" class="bp-tooltip"
                       data-bp-tooltip="<?php esc_attr_e( 'RSS Feed', 'buddypress' ); ?>">
                        <span class="bp-screen-reader-text">
                            <?php esc_html_e( 'RSS', 'buddypress' ); ?>
                        </span>
                    </a>
                </div>
			<?php endif; ?>

			<?php bp_nouveau_search_form(); ?>

        </div>
	<?php endif; ?>

	<?php if ( bp_is_user() && ! bp_is_current_action( 'requests' ) ) : ?>
		<?php bp_get_template_part( 'common/filters/user-screens-filters' ); ?>
	<?php elseif ( 'groups' === bp_current_component() ) : ?>
		<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>
	<?php else : ?>
		<?php bp_get_template_part( 'common/filters/directory-filters' ); ?>
	<?php endif; ?>

</div><!-- search & filters -->
