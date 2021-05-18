<?php

namespace Buddy_Builder\Widgets\Sitewide;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Content extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-sitewide-content';
	}

	public function get_title() {
		return esc_html__( 'Content', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-activity-content sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {
		do_action( 'buddy_builder/widget/sitewide-activity/content/settings', $this );
	}

	protected function render() {
		parent::render();
		if ( bpb_is_elementor_editor() ) {
			bpb_load_preview_template( 'sitewide-activity/content' );
		} else {
			bp_nouveau_activity_hook( 'before_directory', 'list' );

			?>
			<div id="activity-stream" class="activity" data-bp-list="activity">
				<div id="bp-ajax-loader"><?php bp_nouveau_user_feedback( 'directory-activity-loading' ); ?></div>
			</div>
			<?php

			bp_nouveau_after_activity_directory_content();
		}
	}

	public function render_plain_content() {
		return '';
	}

}
