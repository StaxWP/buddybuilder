<?php

namespace Buddy_Builder\Widgets\ProfileGroup\Leadership;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Buddy_Builder\Singleton;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class BuddypressSettings extends Singleton {

	/**
	 * BuddypressSettings constructor.
	 */
	public function __construct() {
		add_action( 'buddy_builder/widget/profile-group/leadership/settings', [ $this, 'add_settings' ] );
	}

	/**
	 * Add settings
	 *
	 * @param \Buddy_Builder\Widgets\Base $widget
	 */
	public function add_settings( \Buddy_Builder\Widgets\Base $widget ) {
		$widget->start_controls_section(
			'section_content_style',
			[
				'label' => __( 'Leadership Content', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_responsive_control(
			'align_titles',
			[
				'label'     => __( 'Align Titles', 'stax-buddy-builder' ),
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
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$widget->add_responsive_control(
			'display',
			[
				'label'     => __( 'Display mods', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'block',
				'options'   => [
					'inline-block' => __( 'Inline', 'stax-buddy-builder' ),
					'block'        => __( 'Block', 'stax-buddy-builder' ),
				],
				'selectors' => [
					'{{WRAPPER}} dl' => 'display: {{VALUE}}',
				],
			]
		);

		$widget->add_responsive_control(
			'spacing_inline_block',
			[
				'label'     => __( 'Inline Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} dl' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$widget->add_responsive_control(
			'spacing_block',
			[
				'label'     => __( 'Block Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} dl'            => 'margin-bottom: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} dl:last-child' => 'margin-bottom: 0;',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			'section_moderators_style',
			[
				'label' => __( 'Moderators lists', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_control(
			'moderators_background_color',
			[
				'label'     => __( 'Moderators BG Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .moderators-lists' => 'background-color: {{VALUE}};',
				],
			]
		);

		$widget->add_responsive_control(
			'moderators_padding',
			[
				'label'      => __( 'Padding', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .moderators-lists' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'moderators_border',
				'selector'  => '{{WRAPPER}} .moderators-lists',
				'separator' => 'before',
			]
		);

		$widget->add_control(
			'moderators_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .moderators-lists' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Title', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}} dl dt',
			]
		);

		$widget->add_control(
			'title_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} dl dt' => 'color: {{VALUE}};',
				],
			]
		);

		$widget->add_control(
			'title_spacing_block',
			[
				'label'     => __( 'Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .moderators-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();

		$widget->start_controls_section(
			'section_avatar_style',
			[
				'label' => __( 'Avatars', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$widget->add_group_control(
			Group_Control_Border::get_type(),
			[
				'label'    => __( 'Border', 'stax-buddy-builder' ),
				'name'     => 'avatar_img_border',
				'selector' => '{{WRAPPER}} .avatar',
			]
		);

		$widget->add_control(
			'avatar_img_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .avatar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

        $widget->add_control(
            'avatars_size',
            [
                'label'     => __( 'Size', 'stax-buddy-builder' ),
                'type'      => Controls_Manager::SLIDER,
                'range'     => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .avatar' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

		$widget->add_control(
			'avatars_spacing',
			[
				'label'     => __( 'Spacing', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .moderators-lists .user-list > ul > li' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$widget->end_controls_section();
	}

}

BuddypressSettings::get_instance();
