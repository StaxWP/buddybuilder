<?php
/**
 * Success notice. Variables: $message.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( isset( $message ) ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
<?php endif; ?>
