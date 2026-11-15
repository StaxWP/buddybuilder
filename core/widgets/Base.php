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
	 * Whether this widget keeps its (empty) Elementor wrapper when the element prints nothing. 1.x widgets that
	 * printed whitespace around a missing value (latest update, meta, comments) kept the wrapper, which takes space
	 * in saved layouts; their adapters set this so the markup stays the same.
	 *
	 * @var bool
	 */
	protected $bpb_keep_wrapper = false;

	/**
	 * Render the shared element, keeping the empty wrapper when $bpb_keep_wrapper says so.
	 */
	protected function bpb_kit_render() {
		ob_start();
		$this->kit_render();
		$html = ob_get_clean();

		echo ( '' === $html && $this->bpb_keep_wrapper ) ? "\n" : $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in render.php.
	}

	/**
	 * A content section with one control generated from the element schema (2.0 attributes such as the list card
	 * style). New keys only: saved 1.x layouts get the schema default, which keeps their markup.
	 *
	 * @param string $key   Attribute key in element.json.
	 * @param string $label Section label.
	 */
	protected function bpb_schema_section( $key, $label ) {
		$element = $this->kit_element();
		$schema  = $element ? $element->schema() : [];

		if ( ! isset( $schema['attributes'][ $key ] ) ) {
			return;
		}

		$defaults = \Buddy_Builder\Kit\Schema::defaults( $schema );

		$this->start_controls_section( 'bpb_' . $key . '_section', [ 'label' => $label ] );
		$this->add_control( $key, self::kit_control( $element, $schema['attributes'][ $key ], $defaults[ $key ] ) );
		$this->end_controls_section();
	}

	/**
	 * Render
	 */
	protected function render() {
		wp_print_styles( [ 'stax-buddy-builder-front' ] );
	}

}
