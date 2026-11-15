<?php
/**
 * Members directory list: The members list of the members directory (loaded by BuddyPress).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_cards = \Buddy_Builder\Templates\Cards::wrapper_class( 'members', $attrs['card'] );
\Buddy_Builder\Templates\Cards::push( 'members', $attrs['card'] );

?>
<div id="members-dir-list" class="members dir-list<?php echo $bpb_cards ? ' ' . esc_attr( $bpb_cards ) : ''; ?>" data-bp-list="members">
	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-members-loading' ); ?></div>
</div>
<?php
\Buddy_Builder\Templates\Cards::pop();
