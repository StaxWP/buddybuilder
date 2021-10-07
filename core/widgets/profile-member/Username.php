<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;

class Username extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-profile-member-username';
	}

	public function get_title() {
		return esc_html__( 'Name/Username', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-members-name sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {

		$this->start_controls_section(
			'section_content',
			[
				'label' => __( 'Name', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'username_type',
			[
				'label'   => __( 'What to generate', 'stax-buddy-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'username'  => '@Username',
					'full_name' => 'Name',
				],
				'default' => 'username',
			]
		);

		$this->add_control(
			'username_tag',
			[
				'label'   => __( 'HTML Tag', 'stax-buddy-builder' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'h1'  => 'H1',
					'h2'  => 'H2',
					'h3'  => 'H3',
					'h4'  => 'H4',
					'h5'  => 'H5',
					'h6'  => 'H6',
					'div' => 'div',
				],
				'default' => 'h2',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Name', 'stax-buddy-builder' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'username_align',
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
			'username_text_color',
			[
				'label'     => __( 'Text Color', 'stax-buddy-builder' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} .user-nicename' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'username_typography',
				'selector' => '{{WRAPPER}} .user-nicename',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name'     => 'username_text_shadow',
				'selector' => '{{WRAPPER}} .user-nicename',
			]
		);

		$this->add_responsive_control(
			'username_margin',
			[
				'label'      => __( 'Margin', 'stax-buddy-builder' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'separator'  => 'before',
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .user-nicename' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

	}

	protected function render() {
		parent::render();

		$settings = $this->get_settings_for_display();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'profile-member/username', $settings );
		} else { ?>

            <<?php echo esc_attr( $settings['username_tag'] ); ?> class="user-nicename">
                <?php if ( $settings['username_type'] === 'full_name' ) : ?>
                    <?php bp_displayed_user_fullname(); ?>
                <?php elseif ( bp_is_active( 'activity' ) && bp_activity_do_mentions() ) : ?>
                    @<?php bp_displayed_user_mentionname(); ?>
                <?php endif; ?>
            </<?php echo esc_attr( $settings['username_tag'] ); ?>>

			<?php
		}
	}

}
