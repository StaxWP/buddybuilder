<?php
/**
 * Activity stream: The site-wide activity stream (loaded by BuddyPress).
 *
 * Shared by the block and the Elementor widget. Variables: $attrs (schema types), $ctx (Context), $element.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_cards = \Buddy_Builder\Templates\Cards::wrapper_class( 'activity', $attrs['card'] );
\Buddy_Builder\Templates\Cards::push( 'activity', $attrs['card'] );

bp_nouveau_activity_hook( 'before_directory', 'list' );
?>
<div id="activity-stream" class="activity<?php echo $bpb_cards ? ' ' . esc_attr( $bpb_cards ) : ''; ?>" data-bp-list="activity">
	<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-activity-loading' ); ?></div>
</div>
<?php
bp_nouveau_after_activity_directory_content();
\Buddy_Builder\Templates\Cards::pop();
