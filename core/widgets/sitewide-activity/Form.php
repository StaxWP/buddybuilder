<?php

namespace Buddy_Builder\Widgets\Sitewide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Form extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/activity-form).
	 *
	 * @var string
	 */
	protected $bpb_element = 'activity-form';

	public function get_name() {
		return 'bpb-sitewide-form';
	}

	public function get_title() {
		return esc_html__( 'Form', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-form sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {
		do_action( 'buddy_builder/widget/sitewide-activity/form/settings', $this );
	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'sitewide-activity/form' );
		} else {
			$this->kit_render();
		}
	}

}
