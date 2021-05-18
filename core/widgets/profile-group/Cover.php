<?php

namespace Buddy_Builder\Widgets\ProfileGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class Cover extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-profile-group-cover';
	}

	public function get_title() {
		return esc_html__( 'Cover', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-groups-profile-cover sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Cover', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'#buddypress {{WRAPPER}} #header-cover-image' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_position',
			[
				'label'     => __( 'Image position', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'separator' => 'before',
				'default'   => 'center center',
				'options'   => [
					''              => __( 'Default', 'Background Control', 'stax-buddy-builder' ),
					'top left'      => __( 'Top Left', 'Background Control', 'stax-buddy-builder' ),
					'top center'    => __( 'Top Center', 'Background Control', 'stax-buddy-builder' ),
					'top right'     => __( 'Top Right', 'Background Control', 'stax-buddy-builder' ),
					'center left'   => __( 'Center Left', 'Background Control', 'stax-buddy-builder' ),
					'center center' => __( 'Center Center', 'Background Control', 'stax-buddy-builder' ),
					'center right'  => __( 'Center Right', 'Background Control', 'stax-buddy-builder' ),
					'bottom left'   => __( 'Bottom Left', 'Background Control', 'stax-buddy-builder' ),
					'bottom center' => __( 'Bottom Center', 'Background Control', 'stax-buddy-builder' ),
					'bottom right'  => __( 'Bottom Right', 'Background Control', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'#buddypress {{WRAPPER}} #header-cover-image' => 'background-position: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_repeat',
			[
				'label'     => __( 'Image repeat', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'no-repeat',
				'options'   => [
					''          => __( 'Default', 'Background Control', 'stax-buddy-builder' ),
					'no-repeat' => __( 'No-repeat', 'Background Control', 'stax-buddy-builder' ),
					'repeat'    => __( 'Repeat', 'Background Control', 'stax-buddy-builder' ),
					'repeat-x'  => __( 'Repeat-x', 'Background Control', 'stax-buddy-builder' ),
					'repeat-y'  => __( 'Repeat-y', 'Background Control', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'#buddypress {{WRAPPER}} #header-cover-image' => 'background-repeat: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'image_size',
			[
				'label'     => __( 'Image size', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => [
					''        => __( 'Default', 'Background Control', 'stax-buddy-builder' ),
					'auto'    => __( 'Auto', 'Background Control', 'stax-buddy-builder' ),
					'cover'   => __( 'Cover', 'Background Control', 'stax-buddy-builder' ),
					'contain' => __( 'Contain', 'Background Control', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'#buddypress {{WRAPPER}} #header-cover-image' => 'background-size: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'position',
			[
				'label'              => __( 'Position', 'elementor' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'absolute',
				'options'            => [
					''         => __( 'Default', 'elementor' ),
					'absolute' => __( 'Absolute', 'elementor' ),
					'fixed'    => __( 'Fixed', 'elementor' ),
				],
				'prefix_class'       => 'elementor-',
				'frontend_available' => true,
			]
		);

		$start = is_rtl() ? __( 'Right', 'elementor' ) : __( 'Left', 'elementor' );
		$end   = ! is_rtl() ? __( 'Right', 'elementor' ) : __( 'Left', 'elementor' );

		$this->add_control(
			'offset_orientation_h',
			[
				'label'       => __( 'Horizontal Orientation', 'elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'toggle'      => false,
				'default'     => 'start',
				'options'     => [
					'start' => [
						'title' => $start,
						'icon'  => 'eicon-h-align-left',
					],
					'end'   => [
						'title' => $end,
						'icon'  => 'eicon-h-align-right',
					],
				],
				'classes'     => 'elementor-control-start-end',
				'render_type' => 'ui',
				'condition'   => [
					'position!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'offset_x',
			[
				'label'      => __( 'Offset', 'elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => - 200,
						'max' => 200,
					],
					'vw' => [
						'min' => - 200,
						'max' => 200,
					],
					'vh' => [
						'min' => - 200,
						'max' => 200,
					],
				],
				'default'    => [
					'size' => '0',
				],
				'size_units' => [ 'px', '%', 'vw', 'vh' ],
				'selectors'  => [
					'body:not(.rtl) {{WRAPPER}}' => 'left: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}}'       => 'right: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'offset_orientation_h!' => 'end',
					'position!'             => '',
				],
			]
		);

		$this->add_responsive_control(
			'offset_x_end',
			[
				'label'      => __( 'Offset', 'elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 0.1,
					],
					'%'  => [
						'min' => - 200,
						'max' => 200,
					],
					'vw' => [
						'min' => - 200,
						'max' => 200,
					],
					'vh' => [
						'min' => - 200,
						'max' => 200,
					],
				],
				'default'    => [
					'size' => '0',
				],
				'size_units' => [ 'px', '%', 'vw', 'vh' ],
				'selectors'  => [
					'body:not(.rtl) {{WRAPPER}}' => 'right: {{SIZE}}{{UNIT}}',
					'body.rtl {{WRAPPER}}'       => 'left: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'offset_orientation_h' => 'end',
					'position!'            => '',
				],
			]
		);

		$this->add_control(
			'offset_orientation_v',
			[
				'label'       => __( 'Vertical Orientation', 'elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'toggle'      => false,
				'default'     => 'start',
				'options'     => [
					'start' => [
						'title' => __( 'Top', 'elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'end'   => [
						'title' => __( 'Bottom', 'elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'render_type' => 'ui',
				'condition'   => [
					'position!' => '',
				],
			]
		);

		$this->add_responsive_control(
			'offset_y',
			[
				'label'      => __( 'Offset', 'elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => - 200,
						'max' => 200,
					],
					'vh' => [
						'min' => - 200,
						'max' => 200,
					],
					'vw' => [
						'min' => - 200,
						'max' => 200,
					],
				],
				'size_units' => [ 'px', '%', 'vh', 'vw' ],
				'default'    => [
					'size' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}}' => 'top: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'offset_orientation_v!' => 'end',
					'position!'             => '',
				],
			]
		);

		$this->add_responsive_control(
			'offset_y_end',
			[
				'label'      => __( 'Offset', 'elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'range'      => [
					'px' => [
						'min'  => - 1000,
						'max'  => 1000,
						'step' => 1,
					],
					'%'  => [
						'min' => - 200,
						'max' => 200,
					],
					'vh' => [
						'min' => - 200,
						'max' => 200,
					],
					'vw' => [
						'min' => - 200,
						'max' => 200,
					],
				],
				'size_units' => [ 'px', '%', 'vh', 'vw' ],
				'default'    => [
					'size' => '0',
				],
				'selectors'  => [
					'{{WRAPPER}}' => 'bottom: {{SIZE}}{{UNIT}}',
				],
				'condition'  => [
					'offset_orientation_v' => 'end',
					'position!'            => '',
				],
			]
		);

		$this->add_control(
			'cover-height',
			[
				'label'       => __( 'Height', 'stax-buddy-builder' ),
				'type'        => Controls_Manager::SLIDER,
				'description' => __( 'This works only when your Cover position is not set to Absolute', 'stax-buddy-builder' ),
				'size_units'  => [ 'px', 'vh', 'em' ],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => '525',
				],
				'separator'   => 'before',
				'selectors'   => [
					'#buddypress {{WRAPPER}} #header-cover-image' => 'height: {{SIZE}}{{UNIT}};',
					'#buddypress {{WRAPPER}} .cover-bg-overlay'   => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'   => [
					'position!' => 'absolute',
				],
			]
		);

		$this->start_controls_tabs( 'tab_cover_style' );

		$this->start_controls_tab(
			'tab_cover_normal',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'cover_box_shadow',
				'selector' => '{{WRAPPER}} #header-cover-image',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_cover_hover',
			[
				'label' => __( 'Hover', 'stax-buddy-builder' ),
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'cover_box_shadow_hover',
				'selector' => '{{WRAPPER}} #header-cover-image:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'cover_border',
				'selector'  => '{{WRAPPER}} #header-cover-image',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'cover_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #header-cover-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'overlay_style',
			[
				'label' => __( 'Overlay', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'overlay_background',
			[
				'label'   => __( 'Overlay', 'stax-buddy-builder' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'           => 'cover_background',
				'types'          => [ 'classic', 'gradient' ],
				'exclude'        => [ 'image' ],
				'selector'       => '{{WRAPPER}} .cover-bg-overlay',
				'fields_options' => [
					'background' => [
						'label' => __( 'Overlay type', 'stax-buddy-builder' ),
					],
				],
				'condition'      => [
					'overlay_background' => 'yes',
				],
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		parent::render();

		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( 'header-cover-bg-overlay', [ 'class' => 'cover-bg-overlay' ] );

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'profile-group/cover', $settings );
		} else {
			if ( bpb_is_buddyboss() ) {
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

						<div id="header-cover-image" class="
							<?php
							echo $has_cover_image_position;
							echo $has_cover_image;
							?>
							">
									<?php
									if ( bp_group_use_cover_image_header() ) {

										if ( ! empty( $group_cover_image ) ) {
											echo '<img class="header-cover-img" src="' . esc_url( $group_cover_image ) . '"' . ( '' !== $group_cover_position ? ' data-top="' . $group_cover_position . '"' : '' ) . ( '' !== $group_cover_position ? ' style="top: ' . $group_cover_position . 'px"' : '' ) . ' alt="" />';
										}
										?>
										<?php if ( bp_is_item_admin() ) { ?>
									<a href="<?php echo $group_cover_link; ?>" class="link-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php _e( 'Change Cover Photo', 'buddyboss' ); ?>">
										<i class="bb-icon-edit-thin"></i>
									</a>
								<?php } ?>
								
										<?php if ( ! empty( $group_cover_image ) && bp_is_item_admin() ) { ?>
									<a href="#" class="position-change-cover-image bp-tooltip" data-bp-tooltip-pos="right" data-bp-tooltip="<?php _e( 'Reposition Cover Photo', 'buddyboss' ); ?>">
										<i class="bb-icon-move"></i>
									</a>
									<div class="header-cover-reposition-wrap">
										<a href="#" class="button small cover-image-cancel"><?php _e( 'Cancel', 'buddyboss' ); ?></a>
										<a href="#" class="button small cover-image-save"><?php _e( 'Save Changes', 'buddyboss' ); ?></a>
										<span class="drag-element-helper"><i class="bb-icon-menu"></i><?php _e( 'Drag to move cover photo', 'buddyboss' ); ?></span>
										<img src="<?php echo esc_url( $group_cover_image ); ?>" alt="<?php _e( 'Cover photo', 'buddyboss' ); ?>" />
									</div>
								<?php } ?>
									<?php } ?>
						</div>
					</div>
				<?php
			} else {
				?>
				<?php if ( $settings['overlay_background'] ) : ?>
					<div <?php echo $this->get_render_attribute_string( 'header-cover-bg-overlay' ); ?>></div>
				<?php endif; ?>
				<div id="header-cover-image"></div>
				<?php
			}
		}
	}

}
