<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;

class LastActivity extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/member-latest-update).
	 *
	 * @var string
	 */
	protected $bpb_element = 'member-latest-update';

	public function get_name() {
		return 'bpb-profile-member-last-activity';
	}

	public function get_title() {
		return esc_html__( 'Last Activity', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-members-last-activity sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Activity Status', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'activity_text_align',
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
					'{{WRAPPER}} #item-meta #latest-update' => 'text-align: {{VALUE}};',
				],
				'default'   => '',
			]
		);

		$this->add_control(
			'activity_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #item-meta #latest-update' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'activity_typography',
				'selector' => '{{WRAPPER}} #item-meta #latest-update',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_link_style',
			[
				'label' => __( 'Link', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'activity_link_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} #item-meta #latest-update a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'activity_link_typography',
				'selector' => '{{WRAPPER}} #item-meta #latest-update a',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'activity_link_shadow',
				'selector' => '{{WRAPPER}} #item-meta #latest-update a',
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'profile-member/activity-status' );
		} else {
			$this->kit_render();
		}
	}

}
