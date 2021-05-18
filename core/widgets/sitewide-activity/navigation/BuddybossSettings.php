<?php

namespace Buddy_Builder\Widgets\Sitewide\Navigation;

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
	 * BuddybossSettings constructor.
	 */
	public function __construct() {
		add_action( 'buddy_builder/widget/sitewide-activity/navigation/settings', [ $this, 'add_settings' ] );
	}

	/**
	 * Add settings
	 *
	 * @param \Buddy_Builder\Widgets\Base $widget
	 */
	public function add_settings( \Buddy_Builder\Widgets\Base $widget ) {
		$widget->start_controls_section(
			self::PREFIX . 'section_style',
			[
				'label' => __( 'Navigation', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_background_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'nav_shadow',
				'selector' => '{{WRAPPER}} nav',
			]
		);

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => self::PREFIX . 'nav_border',
				'selector'  => '#buddypress {{WRAPPER}} nav',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'#buddypress {{WRAPPER}} nav' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'#buddypress {{WRAPPER}} nav' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			self::PREFIX . 'section_item_style',
			[
				'label' => __( 'Navigation Item', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_align',
			[
				'label'     => __( 'Alignment', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} nav ul, {{WRAPPER}} #item-nav ul' => 'text-align: {{VALUE}}; display: block;',
				],
				'default'   => 'left',
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_display',
			[
				'label'     => __( 'Display', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'inline-block',
				'options'   => [
					'inline-block' => __( 'Inline', 'stax-buddy-builder' ),
					'block'        => __( 'Block', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'{{WRAPPER}} nav, {{WRAPPER}} #item-nav'                        => 'border-top: 0; border-bottom: 0; box-shadow: none;',
					'{{WRAPPER}} nav ul, {{WRAPPER}} #item-nav ul'                  => 'padding: 0; margin: 0; height: auto; display: block;',
					'{{WRAPPER}} nav ul li, {{WRAPPER}} #item-nav ul li'            => 'display: {{VALUE}}; float: none;',
					'{{WRAPPER}} nav ul li a, {{WRAPPER}} #item-nav ul li a'        => 'display: inline-block;',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_items_content_align',
			[
				'label'     => __( 'Content Align', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => __( 'Left', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'stax-buddy-builder' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} nav ul li a, {{WRAPPER}} #item-nav ul li a' => 'text-align: {{VALUE}};',
				],
				'default'   => 'center',
				'condition' => [
					self::PREFIX . 'nav_display' => 'block',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_h_spacing',
			[
				'label'     => __( 'Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} nav ul li, {{WRAPPER}} #item-nav ul li'                       => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} nav ul li:last-child, {{WRAPPER}} #item-nav ul li:last-child' => 'margin-right: 0;',
				],
				'condition' => [
					self::PREFIX . 'nav_display' => 'inline-block',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_width',
			[
				'label'     => __( 'Width', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'unit' => 'px',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} nav ul li a, {{WRAPPER}} #item-nav ul li a' => 'width: {{SIZE}}%;',
				],
				'condition' => [
					self::PREFIX . 'nav_display' => 'block',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_v_spacing',
			[
				'label'     => __( 'Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} nav ul li, {{WRAPPER}} #item-nav ul li'                       => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} nav ul li:last-child, {{WRAPPER}} #item-nav ul li:last-child' => 'margin-bottom: 0;',
				],
				'condition' => [
					self::PREFIX . 'nav_display' => 'block',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'label'    => __( 'Text Typography', 'stax-buddy-builder' ),
				'name'     => self::PREFIX . 'nav_item_typography',
				'selector' => '{{WRAPPER}} nav ul li a',
			]
		);

		$widget->start_controls_tabs( self::PREFIX . 'nav_item_style' );

		$widget->start_controls_tab(
			self::PREFIX . 'nav_item_normal',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} nav ul li a' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_background_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav ul li a' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'nav_item_box_shadow',
				'selector' => '{{WRAPPER}} nav ul li a',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'nav_item_hover',
			[
				'label' => __( 'Hover', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_hover_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} nav ul li a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_hover_background_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav ul li a:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_hover_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav ul li a:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'nav_item_hover_box_shadow',
				'selector' => '{{WRAPPER}} nav ul li a:hover',
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			self::PREFIX . 'nav_item_active',
			[
				'label' => __( 'Active', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_active_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} nav ul li.selected a' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_active_background_color',
			[
				'label'     => __( 'Background Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav ul li.selected a' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_active_border_color',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} nav ul li.selected a' => 'border-color: {{VALUE}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => self::PREFIX . 'nav_active_box_shadow',
				'selector' => '{{WRAPPER}} nav ul li.selected a',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => self::PREFIX . 'nav_item_border',
				'selector'  => '{{WRAPPER}} nav ul li a',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			self::PREFIX . 'nav_item_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} nav ul li a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_item_padding',
			[
				'label'      => __( 'Items Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} nav ul li a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			self::PREFIX . 'nav_item_margin',
			[
				'label'      => __( 'Items Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} nav ul li a' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();
	}

}

BuddybossSettings::get_instance();
