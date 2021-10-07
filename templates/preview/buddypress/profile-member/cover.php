<?php

if ( ! isset( $overlay_background ) || bpb_is_buddyboss() ) {
	$overlay_background = false;
}

?>

<?php if ( $overlay_background ) : ?>
	<div class="cover-bg-overlay"></div>
<?php endif; ?>
<div id="header-cover-image" style="background-image: url(' <?php echo esc_url( $cover_url ); ?> ') ">
</div>
