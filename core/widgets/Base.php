<?php

namespace Buddy_Builder\Widgets;

use Buddy_Builder\Library\Documents\BuddyPress;
use Buddy_Builder\Plugin;

class Base extends \Elementor\Widget_Base {

	/**
	 * Base constructor.
	 *
	 * @param array $data
	 * @param null $args
	 *
	 * @throws \Exception
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );

		wp_register_script(
			'bpb-grid-list-view',
			BPB_ASSETS_URL . 'js/grid-list-view.js',
			[ 'elementor-frontend' ],
			BPB_VERSION,
			true
		);
	}

	/**
	 * @return array|mixed|void
	 */
	public function get_script_depends() {
		return apply_filters( 'buddy_builder/' . $this->get_name() . '/widget_script_depends', [] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_name() {
	}

	/**
	 * @return bool
	 */
	public function show_in_panel() {

		global $post;

		$template_type = get_post_meta( $post->ID, BuddyPress::REMOTE_CATEGORY_META_KEY, true );
		$show          = false;

		if ( ! $template_type ) {
			return $show;
		}
		$elements = Plugin::get_instance()->get_elements();

		foreach ( $elements as $element ) {
			if ( $element['template'] === $template_type && $element['name'] === $this->get_name() ) {
				$show = true;
			}
		}

		return $show;
	}

}
