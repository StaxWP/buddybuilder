<?php
/**
 * Content area of a BuddyBuilder screen. Variables: $current_slug.
 *
 * Kept 1.x hooks: bpb_<slug>_page_container_before, bpb_<slug>_page_content_before, bpb_<slug>_page_content,
 * bpb_<slug>_page_content_after.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

do_action( BPB_HOOK_PREFIX . $current_slug . '_page_container_before' );
?>
<div class="bpb-admin__content bpb-admin__content--<?php echo esc_attr( $current_slug ); ?>">
	<?php do_action( BPB_HOOK_PREFIX . $current_slug . '_page_content_before' ); ?>

	<?php do_action( BPB_HOOK_PREFIX . $current_slug . '_page_content' ); ?>

	<?php do_action( BPB_HOOK_PREFIX . $current_slug . '_page_content_after' ); ?>
</div>
