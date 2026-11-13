<?php
/**
 * Shell of the BuddyBuilder screens: WordPress-native title, version and the screen content.
 *
 * Kept 1.x hooks: bpb_submenu (Pro 1.x module tabs), bpb_before_panel_action, bpb_panel_action,
 * bpb_after_panel_action. Variables: $menu (screens), $wrapper_class.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

$bpb_page  = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
$bpb_title = __( 'BuddyBuilder', 'stax-buddy-builder' );

foreach ( $menu as $bpb_item ) {
	if ( isset( $bpb_item['query'] ) && $bpb_item['query'] === $bpb_page ) {
		$bpb_title = wp_strip_all_tags( $bpb_item['name'] );
	}
}
?>
<div class="wrap bpb-admin <?php echo esc_attr( implode( ' ', (array) $wrapper_class ) ); ?>">
	<h1 class="wp-heading-inline"><?php echo esc_html( $bpb_title ); ?></h1>
	<?php
	/**
	 * Fires next to the screen title (page title actions).
	 *
	 * @param string $page Screen slug.
	 */
	do_action( 'buddy_builder/admin/header_actions', $bpb_page );
	?>
	<span class="bpb-admin__version">
		<?php
		/* translators: %s: version */
		echo esc_html( sprintf( __( 'BuddyBuilder %s', 'stax-buddy-builder' ), BPB_VERSION ) );
		?>
	</span>
	<hr class="wp-header-end">

	<?php do_action( BPB_HOOK_PREFIX . 'submenu' ); ?>

	<?php do_action( BPB_HOOK_PREFIX . 'before_panel_action' ); ?>

	<?php do_action( BPB_HOOK_PREFIX . 'panel_action' ); ?>

	<?php do_action( BPB_HOOK_PREFIX . 'after_panel_action' ); ?>
</div>
