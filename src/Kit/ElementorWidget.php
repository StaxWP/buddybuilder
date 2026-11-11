<?php
/**
 * Generic Elementor widget for every kit element (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 * Loaded only when Elementor is active (autoloaded from Kit::elementor_widgets()).
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@33c7c19 (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * One class for all elements. The element name travels in the Elementor default args
 * (the pattern Elementor uses for its WordPress widgets), so no PHP class per element.
 */
class ElementorWidget extends \Elementor\Widget_Base {

	use ElementorAdapter;

	/**
	 * Kit of this copy (one kit per namespace).
	 *
	 * @var Kit|null
	 */
	public static $kit;

	/**
	 * Widget name: <elementor_prefix><element>.
	 *
	 * @return string
	 */
	public function get_name() {
		$args = $this->get_default_args();

		if ( is_array( $args ) && ! empty( $args['kit_element'] ) && self::$kit ) {
			return self::$kit->elementor_name( $args['kit_element'] );
		}

		return (string) $this->get_data( 'widgetType' );
	}

	/**
	 * Element behind this widget.
	 *
	 * @return Element|null
	 */
	protected function kit_element() {
		if ( ! self::$kit ) {
			return null;
		}

		$prefix = (string) self::$kit->config( 'elementor_prefix' );
		$name   = $this->get_name();

		return self::$kit->get( 0 === strpos( $name, $prefix ) ? substr( $name, strlen( $prefix ) ) : $name );
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		$element = $this->kit_element();

		return $element ? $element->title() : $this->get_name();
	}

	/**
	 * Panel icon (element.json "elementorIcon", default eicon-apps).
	 *
	 * @return string
	 */
	public function get_icon() {
		$element = $this->kit_element();

		return $element && ! empty( $element->schema()['elementorIcon'] ) ? $element->schema()['elementorIcon'] : 'eicon-apps';
	}

	/**
	 * Category.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return array( self::$kit ? self::$kit->elementor_category_slug() : 'general' );
	}

	/**
	 * Search keywords.
	 *
	 * @return string[]
	 */
	public function get_keywords() {
		$element = $this->kit_element();

		return $element && isset( $element->schema()['keywords'] ) ? array_map( array( $element->kit(), 't' ), (array) $element->schema()['keywords'] ) : array();
	}

	/**
	 * Element stylesheet.
	 *
	 * @return string[]
	 */
	public function get_style_depends(): array {
		return $this->kit_style_depends();
	}

	/**
	 * Output depends on site data (members online, menus): never cached by Elementor.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Controls from element.json.
	 */
	protected function register_controls() {
		$this->kit_register_controls();
	}

	/**
	 * Classes on the widget wrapper in "widget" wrap mode.
	 */
	protected function add_render_attributes() {
		parent::add_render_attributes();
		$this->kit_add_wrapper_classes();
	}

	/**
	 * Markup from the element renderer.
	 */
	protected function render() {
		$this->kit_render();
	}
}
