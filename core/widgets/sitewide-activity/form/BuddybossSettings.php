<?php

namespace Buddy_Builder\Widgets\Sitewide\Form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Buddy_Builder\Singleton;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class BuddybossSettings extends Singleton {

	const PREFIX = 'boss_';

	/**
	 * BuddypressSettings constructor.
	 */
	public function __construct() {
		add_action( 'buddy_builder/widget/sitewide-activity/form/settings', [ $this, 'add_settings' ] );
	}

	/**
	 * Add settings
	 *
	 * @param \Buddy_Builder\Widgets\Base $widget
	 */
	public function add_settings( \Buddy_Builder\Widgets\Base $widget ) {
		$widget->start_controls_section(
			self::PREFIX . 'container_section',
			[
				'label' => __( 'Form Container', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_control(
			self::PREFIX . 'container_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-form' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'container_state_style' );

		$widget->start_controls_tab(
			self::PREFIX . 'container_normal_style',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'container_box_shadow',
				'selector' => '{{WRAPPER}} #whats-new-form',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'container_focus_style',
			[
				'label' => __( 'Focus', 'stax-buddy-builder' ),
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'container_focus_box_shadow',
				'selector' => '{{WRAPPER}} #whats-new-form.focus-in',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => self::PREFIX . 'container_border',
				'selector'  => '{{WRAPPER}} #whats-new-form',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			self::PREFIX . 'container_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'separator'  => 'before',
				'selectors'  => [
					'{{WRAPPER}} #whats-new-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'container_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'container_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-form' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		if ( function_exists( 'bpb_elementor_heading' ) ) {
			bpb_elementor_heading( $widget, self::PREFIX . 'container_header', __( 'Form Header', 'stax-buddy-builder' ) );
		}

		$widget->add_control(
			self::PREFIX . 'container_header_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-avatar' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'container_header_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-avatar' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'container_header_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		if ( function_exists( 'bpb_elementor_heading' ) ) {
			bpb_elementor_heading( $widget, self::PREFIX . 'container_footer', __( 'Form Footer', 'stax-buddy-builder' ) );
		}
		$widget->add_control(
			self::PREFIX . 'container_footer_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'container_footer_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #activity-form-submit-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'container_footer_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #activity-form-submit-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		if ( function_exists( 'bpb_elementor_heading' ) ) {
			bpb_elementor_heading( $widget, self::PREFIX . 'container_dividers', __( 'Form Dividers', 'stax-buddy-builder' ) );
		}

		$widget->add_control(
			self::PREFIX . 'container_dividers_border_width',
			[
				'label'     => __( 'Border Width', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 10,
					],
				],
				'selectors' => [
					'{{WRAPPER}} #whats-new-avatar'             => 'border-bottom-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} #activity-form-submit-wrapper' => 'border-top-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'container_dividers_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-avatar'             => 'border-color: {{VALUE}}',
					'{{WRAPPER}} #activity-form-submit-wrapper' => 'border-color: {{VALUE}}',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'avatar_section',
			[
				'label' => __( 'Avatar', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'avatar_box_shadow',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #whats-new-avatar img',
			]
		);

		$widget->add_control(
			self::PREFIX . 'avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #bp-nouveau-activity-form #whats-new-avatar img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'user_name_section',
			[
				'label' => __( 'User Name', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'user_name_state_style' );

		$widget->start_controls_tab(
			self::PREFIX . 'user_name_normal_style',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'user_name_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .activity-post-avatar .user-name' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'user_name_hover_style',
			[
				'label' => __( 'Focus', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'user_name_hover_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .activity-post-avatar:hover .user-name' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'textarea_section',
			[
				'label' => __( 'Textarea', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_control(
			self::PREFIX . 'textarea_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-textarea .bp-suggestions' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'textarea_text',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-textarea .bp-suggestions' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'textarea_placeholder',
			[
				'label'     => __( 'Placeholder Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-textarea [contenteditable="true"]:empty:before' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => self::PREFIX . 'textarea_border',
				'selector' => '{{WRAPPER}} #whats-new-textarea .bp-suggestions',
			]
		);

		$widget->add_control(
			self::PREFIX . 'textarea_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-textarea .bp-suggestions' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'textarea_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #whats-new-textarea .bp-suggestions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'post_in_section',
			[
				'label' => __( 'Post In Option', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'label'    => __( 'Text Typography', 'stax-buddy-builder' ),
				'name'     => self::PREFIX . 'post_in_select_typography',
				'selector' => '{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete, {{WRAPPER}} #activity-form-submit-wrapper select',
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'post_in_select_state_style' );

		$widget->start_controls_tab(
			self::PREFIX . 'post_in_select_normal_style',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} #activity-form-submit-wrapper select'                => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete' => 'color: {{VALUE}};',
					'{{WRAPPER}} #activity-form-submit-wrapper select'                => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_placeholder_color',
			[
				'label'     => __( 'Placeholder Input Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete::placeholder'           => 'color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:-ms-input-placeholder'  => 'color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete::-ms-input-placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_arrow_color',
			[
				'label'     => __( 'Select Arrow Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper select' => 'background-image: linear-gradient(45deg, transparent 50%, {{VALUE}} 50%), linear-gradient(135deg, {{VALUE}} 50%, transparent 50%);',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'post_in_select_shadow',
				'selector' => '{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete, {{WRAPPER}} #activity-form-submit-wrapper select',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'post_in_select_focus_style',
			[
				'label' => __( 'Focus', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_focus_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper select:focus'                => 'background-color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_focus_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper select:focus'                => 'color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_placeholder_focus_color',
			[
				'label'     => __( 'Placeholder Input Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus::placeholder'           => 'color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus:-ms-input-placeholder'  => 'color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus::-ms-input-placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_arrow_focus_color',
			[
				'label'     => __( 'Select Arrow Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper select:focus' => 'background-image: linear-gradient(45deg, transparent 50%, {{VALUE}} 50%), linear-gradient(135deg, {{VALUE}} 50%, transparent 50%);',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_focus_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #activity-form-submit-wrapper select:focus'                => 'border-color: {{VALUE}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'post_in_select_focus_shadow',
				'selector' => '{{WRAPPER}} #whats-new-post-in-box:focus select, {{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete:focus',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => self::PREFIX . 'post_in_select_border',
				'selector'  => '{{WRAPPER}} #activity-form-submit-wrapper select, {{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_in_select_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #activity-form-submit-wrapper select'                => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'post_in_select_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #activity-form-submit-wrapper select'                            => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'#buddypress {{WRAPPER}} #whats-new-post-in-box-items #activity-autocomplete' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'post_btn_section',
			[
				'label' => __( 'Post Button', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => self::PREFIX . 'post_btn_typography',
				'label'    => __( 'Typography', 'stax-buddy-builder' ),
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit',
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'post_btn_style_tabs' );

		$widget->start_controls_tab(
			self::PREFIX . 'post_btn_style_normal_tab',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'post_btn_box_shadow',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'post_btn_style_hover_tab',
			[
				'label' => __( 'Hover', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_hover_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_text_hover_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_hover_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'post_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit:hover',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => self::PREFIX . 'post_btn_border',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit',
			]
		);

		$widget->add_control(
			self::PREFIX . 'post_btn_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'post_btn_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'cancel_btn_section',
			[
				'label' => __( 'Cancel Button', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => self::PREFIX . 'cancel_btn_typography',
				'label'    => __( 'Typography', 'stax-buddy-builder' ),
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset',
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'cancel_btn_style_tabs' );

		$widget->start_controls_tab(
			self::PREFIX . 'cancel_btn_style_normal_tab',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'cancel_btn_box_shadow',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'cancel_btn_style_hover_tab',
			[
				'label' => __( 'Hover', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_hover_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_text_hover_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_hover_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'cancel_btn_hover_box_shadow',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset:hover',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => self::PREFIX . 'cancel_btn_border',
				'selector' => '{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset',
			]
		);

		$widget->add_control(
			self::PREFIX . 'cancel_btn_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'cancel_btn_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} #bp-nouveau-activity-form #aw-whats-new-reset' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();
	}

}

BuddybossSettings::get_instance();
