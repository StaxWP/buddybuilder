<?php
/**
 * BuddyPress - Groups Members
 *
 * @since 3.0.0
 * @version 3.0.0
 */

$current_component = 'members';
$listing_type      = 'grid';

if ( isset( $_COOKIE[ $current_component ] ) && in_array( $_COOKIE[ $current_component ], [
		'list',
		'grid'
	] ) ) {
	$listing_type = $_COOKIE[ $current_component ];
}

$list_class = $listing_type === 'list' ? 'bpb-active' : '';
$grid_class = $listing_type === 'grid' ? 'bpb-active' : '';

?>

<div class="subnav-filters filters clearfix no-subnav">
    <div class="bpb-listing-type" data-component="<?php echo esc_attr( $current_component ); ?>">
        <span class="bpb-list-mode <?php echo esc_attr( $list_class ); ?>">
            <span class="dashicons dashicons-list-view"></span>
        </span>
        <span class="bpb-grid-mode <?php echo esc_attr( $grid_class ); ?>">
            <span class="dashicons dashicons-grid-view"></span>
        </span>
    </div>

	<?php bp_nouveau_search_form(); ?>

	<?php bp_get_template_part( 'common/filters/groups-screens-filters' ); ?>

</div>

<h2 class="bp-screen-title">
	<?php esc_html_e( 'Membership List', 'buddypress' ); ?>
</h2>


<div id="members-group-list" class="group_members dir-list" data-bp-list="group_members">

    <div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'group-members-loading' ); ?></div>

</div><!-- .group_members.dir-list -->
