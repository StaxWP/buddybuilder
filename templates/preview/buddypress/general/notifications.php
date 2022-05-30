<aside class="bp-feedback bp-messages bp-template-notice <?php echo esc_attr( $type ); ?>">
	<span class="bp-icon" aria-hidden="true"></span>
	<?php if ( 'updated' === $type ) : ?>
		<p>Your settings have been saved.</p>
	<?php else : ?>
		<p>Error encountered while trying to save the changes.</p>
	<?php endif; ?>
</aside>
