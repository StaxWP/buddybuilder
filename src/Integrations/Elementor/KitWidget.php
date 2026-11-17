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
	 * Widget markup, except on the first view of the Elementor editor page (see EditorPreview::is_first_view()).
	 */
	public function render_content() {
		if ( EditorPreview::is_first_view() ) {
			return;
		}

		parent::render_content();
	}
}
