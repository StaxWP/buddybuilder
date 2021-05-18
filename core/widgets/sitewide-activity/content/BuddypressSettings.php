<?php

namespace Buddy_Builder\Widgets\Sitewide\Content;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Buddy_Builder\Singleton;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class BuddypressSettings extends Singleton {

	/**
	 * BuddypressSettings constructor.
	 */
	public function __construct() {
		add_action( 'buddy_builder/widget/sitewide-activity/content/settings', [ $this, 'add_settings' ] );
	}

	/**
	 * Add settings
	 *
	 * @param \Buddy_Builder\Widgets\Base $widget
	 */
	public function add_settings( \Buddy_Builder\Widgets\Base $widget ) {
		$widget->start_controls_section(
			'activity_item_container_section',
			[
				'label' => __( 'Activity Item Container', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'label'    => __( 'Background', 'stax-buddy-builder' ),
				'types'    => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li.activity-item',
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li.activity-item',
			]
		);

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .activity > ul.activity-list > li.activity-item',
			]
		);

		$widget->add_control(
			'container_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .activity > ul.activity-list > li.activity-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .activity > ul.activity-list > li.activity-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_control(
			'spacing_items',
			[
				'label'     => __( 'Spacing items', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .activity > ul.activity-list > li.activity-item'            => 'margin-top: 0; margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .activity > ul.activity-list > li.activity-item:last-child' => 'margin-bottom: 0;',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			'load_more_section',
			[
				'label'      => __( 'Load More Button', 'stax-buddy-builder' ),
				'tab'        => Controls_Manager::TAB_STYLE,
				'conditions' => [],
			]
		);

		$widget->add_control(
			'base_style',
			[
				'label'     => __( 'Base Style', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::HIDDEN,
				'default'   => '1',
				'selectors' => [
					'{{WRAPPER}} .load-more'   => 'background-color: transparent; border: none; margin: 0;',
					'{{WRAPPER}} .load-newest' => 'background-color: transparent; border: none; margin: 0;',
				],
			]
		);

		$widget->add_control(
			'load_more_btn_display_type',
			[
				'label'     => __( 'Display', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'block',
				'options'   => [
					'inline-block' => __( 'Inline', 'stax-buddy-builder' ),
					'block'        => __( 'Block', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'{{WRAPPER}} .load-more a' => 'display: block;',
				],
			]
		);

		$widget->add_responsive_control(
			'load_more_btn_align',
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
					'{{WRAPPER}} .load-more' => 'text-align: {{VALUE}};',
				],
				'condition' => [
					'load_more_btn_display_type' => 'inline-block',
				],
				'default'   => '',
			]
		);

		$widget->add_control(
			'load_more_btn_display_inline_block',
			[
				'label'     => __( 'Base Style', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::HIDDEN,
				'default'   => '1',
				'selectors' => [
					'{{WRAPPER}} .load-more a' => 'display: inline-block;',
				],
				'condition' => [
					'load_more_btn_display_type' => 'inline-block',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'load_more_btn_typography',
				'label'    => __( 'Typography', 'stax-buddy-builder' ),
				'selector' => '{{WRAPPER}} .load-more a, {{WRAPPER}} .load-newest a',
			]
		);

		$widget->start_controls_tabs( 'load_more_btn_style_tabs' );

		$widget->start_controls_tab(
			'load_more_btn_style_normal_tab',
			[
				'label' => __( 'Normal', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			'load_more_btn_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .load-more a'   => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .load-newest a' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			'load_more_btn_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .load-more a'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .load-newest a' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->end_controls_tab();

		$widget->start_controls_tab(
			'load_more_btn_style_hover_tab',
			[
				'label' => __( 'Hover', 'stax-buddy-builder' ),
			]
		);

		$widget->add_control(
			'load_more_btn_hover_background',
			[
				'label'     => __( 'Background', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .load-more a:hover'   => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .load-newest a:hover' => 'background-color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			'load_more_btn_hover_text_color',
			[
				'label'     => __( 'Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .load-more a:hover'   => 'color: {{VALUE}}',
					'{{WRAPPER}} .load-newest a:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$widget->add_control(
			'load_more_btn_hover_border',
			[
				'label'     => __( 'Border Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .load-more a:hover'   => 'border-color: {{VALUE}}',
					'{{WRAPPER}} .load-newest a:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'load_more_btn_hover_shadow',
				'selector' => '#buddypress {{WRAPPER}} .load-more a:hover, #buddypress {{WRAPPER}} .load-newest a:hover',
			]
		);

		$widget->end_controls_tab();

		$widget->end_controls_tabs();

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'load_more_btn_border',
				'selector'  => '{{WRAPPER}} .load-more a, {{WRAPPER}} .load-newest a',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'load_more_btn_border_radius',
			[
				'label'      => __( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .load-more a'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .load-newest a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'load_more_btn_shadow',
				'selector' => '#buddypress {{WRAPPER}} .load-more a, #buddypress {{WRAPPER}} .load-newest a',
			]
		);

		$widget->add_responsive_control(
			'load_more_btn_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .load-more a'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .load-newest a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();
	}

}

BuddypressSettings::get_instance();
