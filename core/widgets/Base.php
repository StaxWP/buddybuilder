<?php

namespace Buddy_Builder\Widgets;

use Buddy_Builder\Library\Documents\BuddyPress;
use Buddy_Builder\Plugin;

/**
 * Base of the BuddyBuilder Elementor widgets (BuddyBuilder Pro and third parties extend it too).
 *
 * 2.0: a widget that sets $bpb_element is a thin adapter: its front-end markup comes from the shared element
 * renderer (src/Elements/<name>/render.php), the same one the block uses. Its own controls stay unchanged, so saved
 * layouts keep their settings and styles.
 */
class Base extends \Elementor\Widget_Base {

	use \Buddy_Builder\Kit\ElementorAdapter;

	/**
	 * Element rendered by this widget ('' = the widget renders itself, as in 1.x).
	 *
	 * @var string
	 */
	protected $bpb_element = '';

	/**
	 * Kit element of this widget.
	 *
	 * @return \Buddy_Builder\Kit\Element|null
	 */
	protected function kit_element() {
		return '' !== $this->bpb_element && class_exists( '\\Buddy_Builder\\Elements\\Elements' ) ? \Buddy_Builder\Elements\Elements::get( $this->bpb_element ) : null;
	}

	/**
	 * Wrapper attributes: Elementor's own, plus the neutral "bpb-el bpb-el-<name>" classes shared with the blocks.
	 */
	protected function add_render_attributes() {
		parent::add_render_attributes();

		$this->kit_add_wrapper_classes();
	}

	/**
	 * Element stylesheet (loaded only when the widget renders).
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array_merge( parent::get_style_depends(), $this->kit_style_depends() );
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

		foreach ( buddy_builder()->get_elements() as $element ) {
			if ( $element['name'] === $this->get_name() ) {
				if ( ! isset( $element['template'] ) ) {
					$show = true;
				} elseif ( isset( $element['template'] ) && $template_type ) {
					if ( is_array( $element['template'] ) && in_array( $template_type, $element['template'], true ) ) {
						$show = true;
					} elseif ( $element['template'] === $template_type ) {
						$show = true;
					}
				}
			}
		}

		return $show;
	}

	/**
	 * Render
	 */
	protected function render() {
		wp_print_styles( [ 'stax-buddy-builder-front' ] );
	}

}
