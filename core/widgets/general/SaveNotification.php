<?php

namespace Buddy_Builder\Widgets\General;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class SaveNotification extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-general-save-notification';
	}

	public function get_title() {
		return esc_html__( 'Status Notification', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-status-message sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-general-elements' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Preview', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'type',
			[
				'label'   => __( 'Type', 'stax-buddy-builder' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'updated',
				'options' => [
					'updated' => __( 'Success', 'stax-buddy-builder' ),
					'error'   => __( 'Error', 'stax-buddy-builder' ),
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_success_container_style',
			[
				'label'     => __( 'Success Container', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'updated',
				],
			]
		);

		$this->add_control(
			'success_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated' => 'background-color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'success_border',
				'selector'  => '.buddypress-wrap {{WRAPPER}} .bp-feedback.updated, .buddypress-wrap {{WRAPPER}} .bp-feedback.success',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'success_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'success_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_success_text_style',
			[
				'label'     => __( 'Success Text', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'updated',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'success_text_typography',
				'selector' => '.buddypress-wrap {{WRAPPER}} .bp-feedback.updated p, .buddypress-wrap {{WRAPPER}} .bp-feedback.success p',
			]
		);

		$this->add_control(
			'success_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated p' => 'color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'success_text_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_success_icon_style',
			[
				'label'     => __( 'Success Icon', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'updated',
				],
			]
		);

		$this->add_control(
			'success_icon_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated .bp-icon' => 'color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success .bp-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'success_icon_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated .bp-icon' => 'background-color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success .bp-icon' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'success_icon_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'separator'  => 'before',
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated .bp-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success .bp-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'success_icon_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated .bp-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success .bp-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'success_icon_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.updated .bp-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.success .bp-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_error_container_style',
			[
				'label'     => __( 'Error Container', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'error',
				],
			]
		);

		$this->add_control(
			'error_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error' => 'background-color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'error_border',
				'selector'  => '.buddypress-wrap {{WRAPPER}} .bp-feedback.error, .buddypress-wrap {{WRAPPER}} .bp-feedback.warning',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'error_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'error_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_error_text_style',
			[
				'label'     => __( 'Error Text', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'error',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'error_text_typography',
				'selector' => '.buddypress-wrap {{WRAPPER}} .bp-feedback.error p, .buddypress-wrap {{WRAPPER}} .bp-feedback.warning p',
			]
		);

		$this->add_control(
			'error_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error p' => 'color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'error_text_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning p' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_error_icon_style',
			[
				'label'     => __( 'Error Icon', 'stax-buddy-builder' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => [
					'type' => 'error',
				],
			]
		);

		$this->add_control(
			'error_icon_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error .bp-icon' => 'color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning .bp-icon' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_icon_bg_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error .bp-icon' => 'background-color: {{VALUE}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning .bp-icon' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'error_icon_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'separator'  => 'before',
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error .bp-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning .bp-icon' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'error_icon_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error .bp-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning .bp-icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'error_icon_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.error .bp-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'.buddypress-wrap {{WRAPPER}} .bp-feedback.warning .bp-icon' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		parent::render();
		$settings = $this->get_settings_for_display();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'general/notifications', [ 'type' => $settings['type'] ] );
		} else {
			bp_nouveau_template_notices();
		}
	}

}
