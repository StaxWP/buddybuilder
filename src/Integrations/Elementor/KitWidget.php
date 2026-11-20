<?php
/**
 * The element kit's generic Elementor widget (bpb-el-<element>), as BuddyBuilder registers it: same controls and
 * markup, plus BuddyBuilder's editor rules shared with the 1.x widgets (core/widgets/Base.php).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Integrations\Elementor;

defined( 'ABSPATH' ) || die();

/**
 * Generic element widget.
 */
class KitWidget extends \Buddy_Builder\Kit\ElementorWidget {

	/**
	 * Page types the element is made for ([] : any page).
	 *
	 * @return string[]
	 */
	private function page_types() {
		$element = $this->kit_element();
		$schema  = $element ? $element->schema() : [];

		return isset( $schema['templateTypes'] ) ? (array) $schema['templateTypes'] : [];
	}

	/**
	 * In a BuddyBuilder template the panel offers the elements of its page and the ones for any page, as the block
	 * inserter does (templates that already use another page's element keep it).
	 *
	 * @return bool
	 */
	public function show_in_panel() {
		$types = $this->page_types();
		$page  = EditorPreview::edited_type();

		return ! $types || '' === $page || in_array( $page, $types, true );
	}

	/**
	 * The page category ("BuddyBuilder Member Profile") holds the elements made for pages; elements for any page go
	 * to "BuddyBuilder General"; header parts (BuddyBuilder Pro's Member stats, headline, badges) join the 1.x parts
	 * in "BuddyBuilder Header parts".
	 *
	 * @return string[]
	 */
	public function get_categories() {
		if ( \Buddy_Builder\Elements\Elements::is_header_part( $this->kit_element() ) ) {
			return [ \Buddy_Builder\Widgets\Base::HEADER_PARTS_CATEGORY ];
		}

		return $this->page_types() ? parent::get_categories() : [ 'buddy-builder-general-elements' ];
	}

	/**
	 * Widget markup, except on the first view of the Elementor editor page (see EditorPreview::is_first_view()).
	 */
	public function render_content() {
		if ( EditorPreview::is_first_view() ) {
			return;
		}

		parent::render_content();
	}
}
