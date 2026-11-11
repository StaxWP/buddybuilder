<?php
/**
 * Assignment box of one template type: Elementor templates and block layouts.
 *
 * Variables: $key (type), $item (title, posts), $has_elementor, $has_blocks.
 *
 * @package Buddy_Builder
 */

use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Types;

$bpb_active      = null;
$bpb_by_builder  = [
	'elementor' => [],
	'blocks'    => [],
];
$bpb_block_types = Types::for_blocks();

foreach ( isset( $item['posts'] ) ? $item['posts'] : [] as $bpb_post ) {
	$bpb_builder                      = isset( $bpb_post['builder'] ) ? $bpb_post['builder'] : 'elementor';
	$bpb_by_builder[ $bpb_builder ][] = $bpb_post;

	if ( $bpb_post['status'] ) {
		$bpb_active = $bpb_post;
	}
}

$bpb_option = static function ( $post ) {
	$value = isset( $post['value'] ) ? $post['value'] : (string) $post['id'];

	printf(
		'<option value="%1$s"%2$s>%3$s</option>',
		esc_attr( $value ),
		$post['status'] ? ' selected' : '',
		esc_html( $post['title'] )
	);
};
?>

<select name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" class="ste-w-full">
	<option value="0"><?php esc_html_e( '-- BuddyPress Default --', 'stax-buddy-builder' ); ?></option>

	<?php if ( ! empty( $bpb_by_builder['blocks'] ) ) : ?>
		<optgroup label="<?php esc_attr_e( 'Block layouts', 'stax-buddy-builder' ); ?>">
			<?php array_map( $bpb_option, $bpb_by_builder['blocks'] ); ?>
		</optgroup>
	<?php endif; ?>

	<?php if ( ! empty( $bpb_by_builder['elementor'] ) ) : ?>
		<optgroup label="<?php esc_attr_e( 'Elementor templates', 'stax-buddy-builder' ); ?>">
			<?php array_map( $bpb_option, $bpb_by_builder['elementor'] ); ?>
		</optgroup>
	<?php endif; ?>
</select>
<div class="ste-flex ste-flex-wrap ste-justify-between ste-w-full">
	<div class="ste-mt-4 ste-flex ste-leading-none ste-gap-4">
		<?php if ( $has_blocks && isset( $bpb_block_types[ $key ] ) ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( [ 'action' => 'bpb_new_layout', 'type' => $key ], admin_url( 'admin-post.php' ) ), 'bpb_new_layout' ) ); ?>" class="ste-text-xs ste-text-blue-500 ste-no-underline focus:ste-shadow-none ste-flex ste-items-center">
				<?php esc_html_e( '+ New block layout', 'stax-buddy-builder' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $has_elementor ) : ?>
			<a href="
			<?php
			echo esc_url(
				add_query_arg(
					[
						'action'             => 'elementor_new_post',
						'post_type'          => 'elementor_library',
						'template_type'      => 'bpb-buddypress',
						'_bpb_page'          => $key,
						'_bpb_set_as_active' => 1,
						'_wpnonce'           => wp_create_nonce( 'elementor_action_new_post' ),
					],
					admin_url( 'edit.php' )
				)
			);
			?>
			" class="ste-text-xs ste-text-blue-500 ste-no-underline focus:ste-shadow-none ste-flex ste-items-center">
				<i class="eicon-plus-circle-o ste-mr-1"></i> <?php esc_html_e( 'New Elementor template', 'stax-buddy-builder' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<?php if ( $bpb_active ) : ?>
		<div class="ste-mt-4 ste-flex ste-leading-none">
			<?php
			$bpb_edit = 'blocks' === $bpb_active['builder']
				? get_edit_post_link( $bpb_active['id'], 'raw' )
				: add_query_arg(
					[
						'post'   => $bpb_active['id'],
						'action' => 'elementor',
					],
					admin_url( 'post.php' )
				);
			?>
			<a href="<?php echo esc_url( $bpb_edit ); ?>" class="ste-text-xs ste-text-blue-500 ste-no-underline focus:ste-shadow-none ste-flex ste-items-center">
				<i class="eicon-edit ste-mr-1"></i> <?php esc_html_e( 'Edit current', 'stax-buddy-builder' ); ?>
			</a>
		</div>
	<?php endif; ?>
</div>
