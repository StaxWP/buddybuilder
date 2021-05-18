<?php

namespace Buddy_Builder\Widgets\ProfileMember;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

use Buddy_Builder\Plugin;
use Elementor\Controls_Manager;

class Content extends \Buddy_Builder\Widgets\Base {

	public function get_name() {
		return 'bpb-profile-member-content';
	}

	public function get_title() {
		return esc_html__( 'Content', 'stax-buddy-builder' );
	}

	public function get_icon() {
		return 'bbl-members-content sq-widget-label';
	}

	public function get_categories() {
		return [ 'buddy-builder-elements' ];
	}

	protected function _register_controls() {

		if ( ! function_exists( 'bpb_is_pro' ) ) {
			$this->start_controls_section(
				'go_pro_section',
				[
					'label' => __( 'Go PRO', 'stax-buddy-builder' ),
				]
			);

			$this->add_control(
				'go_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw'  => Plugin::get_instance()->go_pro_template(
						[
							'title'    => __( 'BuddyBuilder PRO', 'stax-buddy-builder' ),
							'messages' => [
								__( 'Step up your game and customize your profile content with ease.', 'stax-buddy-builder' ),
							],
							'link'     => 'https://staxwp.com/go/buddybuilder-pro/',
						]
					),
				]
			);

			$this->end_controls_section();
		}

		do_action( 'buddy_builder/widget/member-profile/settings', $this );
	}

	protected function render() {
		parent::render();

		if ( bpb_is_elementor_editor() ) {
			$template = bpb_load_preview_template( 'profile-member/content', [], false );
			echo apply_filters( 'buddy_builder/widget/member-profile/preview', $template, $this );
		} else {
			?>
			<div class="bp-wrap">
				<div id="item-body" class="item-body">
					<?php
					/*
					 * Returning a truthy value from the filter will effectively short-circuit the logic
					 */
					if ( apply_filters( 'buddy_builder/tpl/profile-member/content/render', true ) ) {

						?>
						<?php if ( bp_nouveau_has_nav( [ 'type' => 'secondary' ] ) ) : ?>
							<nav class="<?php bp_nouveau_single_item_subnav_classes(); ?>" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss' ); ?>">
								<ul class="subnav">
								<?php
								while ( bp_nouveau_nav_items() ) :
									bp_nouveau_nav_item();
									?>
									<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?>>
										<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
											<?php bp_nouveau_nav_link_text(); ?>

											<?php if ( bp_nouveau_nav_has_count() ) : ?>
											<span class="count"><?php bp_nouveau_nav_count(); ?></span>
											<?php endif; ?>
										</a>
									</li>

								<?php endwhile; ?>
								</ul>
							</nav>
						<?php endif; ?>
						
						<div class="bpb-settings-container">
							<?php bp_nouveau_member_template_part(); ?>
						</div>

						<?php
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
