<?php

namespace Buddy_Builder\Widgets\Sitewide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Navigation extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/activity-nav).
	 *
	 * @var string
	 */
	protected $bpb_element = 'activity-nav';

	public function get_name() {
		return 'bpb-sitewide-navigation';
	}

	public function get_title() {
		return esc_html__( 'Navigation', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-activity-navigation sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {
		do_action( 'buddy_builder/widget/sitewide-activity/navigation/settings', $this );
	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'sitewide-activity/navigation' );
		} else {
			$this->kit_render();
		}
	}

}
