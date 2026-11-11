<?php

namespace Buddy_Builder\Widgets\ProfileGroup;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Leadership extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/group-leadership).
	 *
	 * @var string
	 */
	protected $bpb_element = 'group-leadership';

	public function get_name() {
		return 'bpb-profile-group-leadership';
	}

	public function get_title() {
		return esc_html__( 'Leadership', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-groups-leadership sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {
		do_action( 'buddy_builder/widget/profile-group/leadership/settings', $this );
	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'profile-group/leadership' );
		} else {
			$this->kit_render();
		}
	}

}
