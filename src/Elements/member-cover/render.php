<?php
/**
 * Member cover. Variables: $attrs, $ctx.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_cover_url = '';

if ( 'yes' === $attrs['cover_advanced_settings'] && function_exists( 'bp_attachments_get_attachment' ) ) {
	$bpb_cover_url = (string) bp_attachments_get_attachment( 'url', [ 'item_id' => $ctx['user_id'] ] );
}

if ( 'yes' === $attrs['overlay_background'] && ! bpb_is_buddyboss() ) {
	echo '<div class="cover-bg-overlay"></div>';
}
?>
<div id="header-cover-image"<?php echo $bpb_cover_url ? ' style="background-image:url(' . esc_url( $bpb_cover_url ) . ');background-size:cover;background-position:center"' : ''; ?>></div>
