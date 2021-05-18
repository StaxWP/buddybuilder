<?php

namespace Buddy_Builder\Widgets\ProfileGroup;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Leadership extends \Buddy_Builder\Widgets\Base {

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

	protected function _register_controls() {
		do_action( 'buddy_builder/widget/profile-group/leadership/settings', $this );
	}

	protected function render() {
		parent::render();
		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'profile-group/leadership' );
		} else {
			bp_get_template_part( 'groups/single/parts/header-item-actions' );
		}
	}

}
