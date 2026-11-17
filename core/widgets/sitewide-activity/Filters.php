<?php

namespace Buddy_Builder\Widgets\Sitewide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Filters extends \Buddy_Builder\Widgets\Base {

	/**
	 * Element rendered by this widget (src/Elements/activity-filters).
	 *
	 * @var string
	 */
	protected $bpb_element = 'activity-filters';

	public function get_name() {
		return 'bpb-sitewide-filters';
	}

	public function get_title() {
		return esc_html__( 'Filters', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-activity-filter sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function register_controls() {
		do_action( 'buddy_builder/widget/sitewide-activity/filters/settings', $this );
	}

	protected function render() {
		parent::render();

		$this->bpb_render_element();
	}

}
