<?php
/**
 * Member cover: Cover image of the displayed member.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_displayed_user_id() ) {
	return;
}

$bpb_cover_url = null;

if ( $attrs['cover_advanced_settings'] ) {
	$bpb_cover_url = bp_attachments_get_attachment( 'url', [ 'item_id' => bp_displayed_user_id() ] );
}

if ( $attrs['overlay_background'] && ! bpb_is_buddyboss() ) {
	echo '<div class="cover-bg-overlay"></div>';
}
?>
<div id="header-cover-image"></div>
<?php
bpb_print_cover_styles( $bpb_cover_url );
