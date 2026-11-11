<?php
/**
 * Group cover: Cover image of the current group.
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! bp_get_current_group_id() ) {
	return;
}

if ( bpb_is_buddyboss() ) {
	bpb_render_buddyboss_group_cover();

	return;
}

$bpb_cover_url = null;

if ( $attrs['cover_advanced_settings'] ) {
	$bpb_cover_url = bp_attachments_get_attachment(
		'url',
		[
			'object_dir' => 'groups',
			'item_id'    => bp_get_current_group_id(),
		]
	);
}

if ( $attrs['overlay_background'] ) {
	echo '<div class="cover-bg-overlay"></div>';
}
?>
<div id="header-cover-image"></div>
<?php
bpb_print_cover_styles( $bpb_cover_url, 'background' );
