<?php
/**
 * Error notice. Variables: $message.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( isset( $message ) ) : ?>
	<div class="notice notice-error"><p><?php echo esc_html( $message ); ?></p></div>
<?php endif; ?>
