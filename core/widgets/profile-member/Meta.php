<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;

class Meta extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/member-meta).
	 *
	 * @var string
	 */
	protected $bpb_element = 'member-meta';

	/**
	 * Style tab settings that hide inside a Member header split into parts (they target the 1.x markup).
	 *
	 * @var array
	 */
	protected $bpb_style_hide_when = self::SPLIT_PART;

	public function get_name() {
		return 'bpb-profile-member-meta';
	}

	public function get_icon() {
		return 'bbl-members-meta sq-widget-label';
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Meta', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'meta_align',
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
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$this->add_control(
			'meta_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .activity' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'meta_typography',
				'selector' => '{{WRAPPER}} .activity',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'meta_text_shadow',
				'selector' => '{{WRAPPER}} .activity',
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		parent::render();

		$this->bpb_render_element();
	}

}
