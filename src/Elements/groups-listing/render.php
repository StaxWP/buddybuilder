<?php
/**
 * Groups directory list: The groups list of the groups directory (loaded by BuddyPress).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_cards = \Buddy_Builder\Templates\Cards::wrapper_class( 'groups', $attrs['card'] );
\Buddy_Builder\Templates\Cards::push( 'groups', $attrs['card'] );

?>
<div id="groups-dir-list" class="groups dir-list<?php echo $bpb_cards ? ' ' . esc_attr( $bpb_cards ) : ''; ?>" data-bp-list="groups">
	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-groups-loading' ); ?></div>
</div>
<?php
\Buddy_Builder\Templates\Cards::pop();
