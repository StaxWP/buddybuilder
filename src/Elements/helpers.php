<?php
/**
 * Render helpers shared by several element renderers (same markup as the 1.x widgets).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

if ( ! function_exists( 'bpb_print_cover_styles' ) ) {
	/**
	 * Inline styles of the cover elements when the uploaded cover is used as a background layer (1.x markup).
	 *
	 * @param string|null|false $cover_url Cover url; null when the option is off (nothing printed).
	 * @param string            $property  CSS property for the image: background-image (member) or background (group).
	 */
	function bpb_print_cover_styles( $cover_url, $property = 'background-image' ) {
		if ( null === $cover_url || bpb_is_buddyboss() ) {
			return;
		}
		?>
		<style>
			#header-cover-image {
				background-color: transparent !important;
			}
			#header-cover-image:before {
				content: "";
				position:  absolute;
				background-size: cover;
				top: 0;
				left: 0;
				bottom: 0;
				right: 0;
			}
		</style>
		<?php if ( $cover_url ) : ?>
			<style>
				#header-cover-image {
					position: relative;
					overflow: hidden;
					z-index: 0;
					background-image: none !important;
				}

				#header-cover-image:before {
					<?php echo 'background' === $property ? 'background' : 'background-image'; ?>: url(<?php echo esc_url( $cover_url ); ?>);
					z-index: -1;
				}
			</style>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'bpb_render_buddyboss_group_cover' ) ) {
	/**
	 * Group cover on BuddyBoss Platform (1.x markup, BuddyBoss classes and text domain).
	 */
	function bpb_render_buddyboss_group_cover() {
		$group_link               = bp_get_group_permalink();
		$admin_link               = trailingslashit( $group_link . 'admin' );
		$group_cover_link         = trailingslashit( $admin_link . 'group-cover-image' );
		$group_cover_image        = bp_attachments_get_attachment(
			'url',
			[
				'object_dir' => 'groups',
				'item_id'    => bp_get_group_id(),
			]
		);
		$group_cover_position     = '';
		$has_cover_image          = '';
		$has_cover_image_position = '';
		?>
		<div id="cover-image-container">
			<?php
			if ( ! empty( $group_cover_image ) ) {
				$group_cover_position = groups_get_groupmeta( bp_get_current_group_id(), 'bp_cover_position', true );
				$has_cover_image      = ' has-cover-image';
				if ( '' !== $group_cover_position ) {
					$has_cover_image_position = 'has-position';
				}
			}
			?>

			<div id="header-cover-image" class="<?php echo esc_attr( $has_cover_image_position . $has_cover_image ); ?>">
				<?php
				if ( bp_group_use_cover_image_header() ) {
					if ( ! empty( $group_cover_image ) ) {
						echo '<img class="header-cover-img" src="' . esc_url( $group_cover_image ) . '"' . ( '' !== $group_cover_position ? ' data-top="' . esc_attr( $group_cover_position ) . '"' : '' ) . ( '' !== $group_cover_position ? ' style="top: ' . esc_attr( $group_cover_position ) . 'px"' : '' ) . ' alt="" />';
					}
					?>
					<?php if ( bp_is_item_admin() ) { ?>
						<a href="<?php echo esc_url( $group_cover_link ); ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Change Cover Photo', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>">
							<i class="bb-icon-edit-thin"></i>
						</a>
					<?php } ?>

					<?php if ( ! empty( $group_cover_image ) && bp_is_item_admin() ) { ?>
						<a href="#" class="position-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php esc_attr_e( 'Reposition Cover Photo', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>">
							<i class="bb-icon-move"></i>
						</a>
						<div class="header-cover-reposition-wrap">
							<a href="#" class="button small cover-image-cancel"><?php esc_html_e( 'Cancel', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></a>
							<a href="#" class="button small cover-image-save"><?php esc_html_e( 'Save Changes', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></a>
							<span class="drag-element-helper"><i class="bb-icon-menu"></i><?php esc_html_e( 'Drag to move cover photo', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></span>
							<img src="<?php echo esc_url( $group_cover_image ); ?>" alt="<?php esc_attr_e( 'Cover photo', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>" />
						</div>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'bpb_render_general_loop' ) ) {
	/**
	 * Members, groups or activity loop outside the BuddyPress pages (the "General" elements), 1.x markup and hooks.
	 *
	 * @param string     $object   members|groups|activity.
	 * @param array      $attrs    Element attributes (columns for members and groups).
	 * @param array|null $settings Raw Elementor settings when rendered by the Elementor widget: passed to the
	 *                             1.x loop hooks unchanged (Pro reads its own keys from them, e.g. ml_template).
	 */
	function bpb_render_general_loop( $object, array $attrs, $settings = null ) {
		$payload = is_array( $settings ) ? $settings : $attrs;

		$current_component = static function () use ( $object ) {
			return $object;
		};

		$loop_classes = null;

		if ( 'activity' !== $object ) {
			$columns      = isset( $payload['columns'] ) ? $payload['columns'] : 3;
			$tablet       = isset( $payload['columns_tablet'] ) ? $payload['columns_tablet'] : $columns;
			$mobile       = isset( $payload['columns_mobile'] ) ? $payload['columns_mobile'] : $columns;
			$loop_classes = static function () use ( $object, $columns, $tablet, $mobile ) {
				return [
					'item-list',
					$object . '-list',
					'bp-list',
					'grid',
					bpb_get_column_class( $columns ),
					bpb_get_column_class( $tablet, 'tablet' ),
					bpb_get_column_class( $mobile, 'mobile' ),
				];
			};
		}

		$pagination = [
			'members' => [ 'bp_members_pagination_count', 'bp_get_members_pagination_links' ],
			'groups'  => [ 'bp_get_groups_pagination_count', 'bp_get_groups_pagination_links' ],
		];

		// The 1.x loop hooks receive the Elementor widget settings (BuddyBuilder Pro 1.x reads its own keys there);
		// block layouts fire the "/block" variants instead.
		$hook = is_array( $settings ) ? 'template' : 'block';

		add_filter( 'buddy_builder/has_template/pre', '__return_true' );
		add_filter( 'bp_current_component', $current_component );

		if ( $loop_classes ) {
			add_filter( 'bp_nouveau_get_loop_classes', $loop_classes );
		}

		apply_filters( 'buddy_builder/' . $object . '-loop/before/' . $hook, $payload ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		if ( isset( $pagination[ $object ] ) ) {
			foreach ( $pagination[ $object ] as $hook ) {
				add_filter( $hook, '__return_zero' );
			}
		}

		if ( 'activity' === $object ) {
			do_action( 'bp_before_directory_activity' );
			?>

			<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav activity">

				<?php bp_nouveau_before_activity_directory_content(); ?>

				<div class="screen-content">
					<?php bp_nouveau_activity_hook( 'before_directory', 'list' ); ?>

					<div id="activity-stream" class="activity" data-bp-list="">
						<?php bp_get_template_part( 'activity/activity-loop' ); ?>
					</div>

					<?php bp_nouveau_after_activity_directory_content(); ?>

				</div>
			</div>

			<?php
		} else {
			$before = 'members' === $object ? 'bp_nouveau_before_members_directory_content' : 'bp_nouveau_before_groups_directory_content';
			$after  = 'members' === $object ? 'bp_nouveau_after_members_directory_content' : 'bp_nouveau_after_groups_directory_content';
			?>

			<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav <?php echo esc_attr( $object ); ?>">
				<?php $before(); ?>

				<div class="screen-content">
					<div id="<?php echo esc_attr( $object ); ?>-dir-list" class="<?php echo esc_attr( $object ); ?> dir-list" data-bp-list="">
						<?php bp_get_template_part( $object . '/' . $object . '-loop' ); ?>
					</div>

					<?php $after(); ?>
				</div>
			</div>

			<?php
		}

		if ( $loop_classes ) {
			remove_filter( 'bp_nouveau_get_loop_classes', $loop_classes );
		}

		remove_filter( 'bp_current_component', $current_component );

		apply_filters( 'buddy_builder/' . $object . '-loop/after/' . $hook, $payload ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

		if ( isset( $pagination[ $object ] ) ) {
			foreach ( $pagination[ $object ] as $hook ) {
				remove_filter( $hook, '__return_zero' );
			}
		}

		remove_filter( 'buddy_builder/has_template/pre', '__return_true' );
	}
}
