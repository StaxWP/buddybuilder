<?php

namespace Buddy_Builder\Widgets\ProfileGroup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class TypeList extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-profile-group-type-list';
	}

	public function get_title() {
		return esc_html__( 'Type List', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'eicon-photo-library';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {

	}

	protected function render() {
		parent::render();
		if ( bpb_is_elementor_editor() ) {
			bpb_load_template( 'preview/profile-group/type' );
		} else {
			echo bp_nouveau_group_meta()->group_type_list;
		}
	}

}
