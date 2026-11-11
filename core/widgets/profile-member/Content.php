<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Buddy_Builder\Plugin;
use Elementor\Controls_Manager;

class Content extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/member-content).
	 *
	 * @var string
	 */
	protected $bpb_element = 'member-content';

	public function get_name() {
		return 'bpb-profile-member-content';
	}

	public function get_title() {
		return esc_html__( 'Content', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-members-content sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {

		if ( ! function_exists( 'bpb_is_pro' ) ) {
			$this->start_controls_section(
				'go_pro_section',
				[
					'label' => __( 'Go PRO', 'stax-buddy-builder' ),
				]
			);

			$this->add_control(
				'go_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw'  => buddy_builder()->go_pro_template(
						[
							'title'    => __( 'BuddyBuilder PRO', 'stax-buddy-builder' ),
							'messages' => [
								__( 'Step up your game and customize your profile content with ease.', 'stax-buddy-builder' ),
							],
							'link'     => 'https://staxwp.com/go/buddybuilder-pro/',
						]
					),
				]
			);

			$this->end_controls_section();
		}

		do_action( 'buddy_builder/widget/member-profile/settings', $this );
	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			$template = bpb_load_preview_template( 'profile-member/content', [], false );
			echo apply_filters( 'buddy_builder/widget/member-profile/preview', $template, $this ); // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			$this->kit_render();
		}
	}

	public function render_plain_content() {
		return '';
	}

}
