<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Elementor\Controls_Manager;

class Content extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-profile-member-content';
	}

	public function get_title() {
		return esc_html__( 'Content', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'sq-icon-bp_section sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {

		if ( ! function_exists( 'bpb_is_pro' ) ) {
			$this->start_controls_section(
				'go_pro_section',
				[
					'label' => __( 'Go PRO', 'stax-buddy-builder' )
				]
			);

			$this->add_control(
				'go_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw'  => $this->go_pro_template( [
						'title'    => __( 'BuddyBuilder PRO', 'stax-buddy-builder' ),
						'messages' => [
							__( 'Step up your game and customize your profile content with ease.', 'stax-buddy-builder' ),
						],
						'link'     => 'https://staxwp.com/go/buddybuilder-pro/',
					] ),
				]
			);

			$this->end_controls_section();
		}

		do_action( 'buddybuilder/widget/member-profile/content', $this );
	}

	public function go_pro_template( $texts ) {
		ob_start();

		?>
        <div class="elementor-nerd-box">
            <div class="elementor-nerd-box-title"><?php echo $texts['title']; ?></div>
			<?php foreach ( $texts['messages'] as $message ) { ?>
                <div class="elementor-nerd-box-message"><?php echo $message; ?></div>
			<?php }

			if ( $texts['link'] ) { ?>
                <a class="elementor-button elementor-panel-scheme-title" href="<?php echo $texts['link']; ?>"
                   target="_blank">
					<?php echo __( 'Go PRO', 'stax-buddy-builder' ); ?>
                </a>
			<?php }
			?>
        </div>
		<?php

		return ob_get_clean();
	}

	protected function render() {

		if ( bpb_is_elementor_editor() ) {
			ob_start();
			bpb_load_template( 'preview/profile-member/content' );
			echo apply_filters( 'buddybuilder/widget/member-profile/preview', ob_get_clean(), $this );
		} else {
			?>
            <div class="bp-wrap">
                <div id="item-body" class="item-body">
					<?php
					/*
					 * Returning a truthy value from the filter will effectively short-circuit the logic
					 */
					if ( apply_filters( 'buddybuilder/tpl/profile-member/content/render', true ) ) {
						bp_nouveau_member_template_part();
					}
					?>
                </div>
            </div>
			<?php
		}
	}

	public function render_plain_content() {
		return '';
	}

}
