<?php

if ( ! isset( $overlay_background ) ) {
	$overlay_background = false;
}

?>

<?php if ( $overlay_background ) : ?>
	<div class="cover-bg-overlay"></div>
<?php endif; ?>
<div id="header-cover-image" style="background-image: url(' <?php echo esc_url( $placeholder_image = ELEMENTOR_ASSETS_URL . 'images/placeholder.png' ); ?> ') ">
</div>
