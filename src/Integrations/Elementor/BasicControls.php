<?php
/**
 * Elementor progressive disclosure for the 1.x widgets (U3, task 26): the 1 to 4 settings people change most move
 * into one "Settings" section at the top of the Content tab; everything else stays in its own section, collapsed
 * (Elementor opens only the first section of a tab), which is the "More settings" part.
 *
 * Only the panel changes: no control key is renamed or removed and no default changes, so saved layouts render the
 * same. A control only moves when its position cannot change the output: no CSS selectors, no prefix class (the
 * wrapper class order) and not frontend available (the data-settings order). Section controls left empty by the move
 * are removed (a section is not a setting). The 2.0 widgets (bpb-el-*) get the same two groups from element.json in
 * the element kit (Settings / More settings).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Integrations\Elementor;

defined( 'ABSPATH' ) || die();

/**
 * Basic settings section of the BuddyBuilder Elementor widgets.
 */
final class BasicControls {

	/** Section id of the basic settings. */
	const SECTION = 'bpb_basic';

	/** Device suffixes of responsive control copies (Elementor 3.x registers one control per device). */
	const DEVICES = [ 'widescreen', 'laptop', 'tablet_extra', 'tablet', 'mobile_extra', 'mobile' ];

	/**
	 * Basic settings of the 1.x widgets that are not (or not only) element.json basics: the settings that change what
	 * the widget shows, most used first. Widgets not listed use the basic attributes of their element.json.
	 */
	const WIDGETS = [
		// BuddyBuilder.
		'bpb-profile-member-buttons'       => [ 'button_display' ],
		'bpb-profile-group-buttons'        => [ 'button_display' ],
		'bpb-profile-member-username'      => [ 'username_type', 'username_tag' ],
		'bpb-groups-directory-navigation'  => [ 'link_to_menu' ],
		'bpb-general-members-list'         => [ 'card', 'columns', 'ml_member_number', 'ml_member_type' ],
		'bpb-general-groups-list'          => [ 'card', 'columns', 'gl_group_number', 'gl_group_filter' ],
		'bpb-general-activity-list'        => [ 'card', 'al_number', 'al_sort', 'al_object' ],
		'bpb-general-save-notification'    => [ 'type' ],
		// BuddyBuilder Pro.
		'bpb-profile-member-content'       => [ 'mp_content_current_editing' ],
		'bpb-profile-group-content'        => [ 'gp_content_current_editing' ],
		'bpb-groups-create-form'           => [ 'current_editing' ],
		'bpb-members-directory-item-name'  => [ 'name_type', 'name_tag' ],
		'bpb-register-form-submit'         => [ 'submit_btn_display_type' ],
		'bpb-common-members-list'          => [ 'per_page', 'member_columns' ],
		'bpb-common-groups-list'           => [ 'per_page', 'group_columns' ],
		'bpb-common-post-update-form'      => [ 'toggle_avatar' ],
	];

	/**
	 * Basic setting keys of a widget (at most 4).
	 *
	 * @param \Elementor\Controls_Stack                     $widget  Widget.
	 * @param \Buddy_Builder\Vendor\ElementKit\Element|null $element Element the widget renders, if any.
	 * @return string[]
	 */
	public static function keys( $widget, $element = null ) {
		$name = $widget->get_name();
		$keys = isset( self::WIDGETS[ $name ] ) ? self::WIDGETS[ $name ] : [];

		if ( ! $keys && $element ) {
			foreach ( $element->editor_definition()['controls'] as $control ) {
				if ( 'basic' === $control['panel'] ) {
					$keys[] = $control['key'];
				}
			}
		}

		/**
		 * Filters the settings a BuddyBuilder Elementor widget shows first, in its "Settings" section (at most 4).
		 * Moving a setting never changes its key or default.
		 *
		 * @since 2.0.0
		 *
		 * @param string[]                  $keys   Control keys.
		 * @param \Elementor\Controls_Stack $widget Widget.
		 */
		$keys = (array) apply_filters( 'buddy_builder/elementor/basic_controls', $keys, $widget );

		return array_slice( array_values( array_unique( array_filter( $keys, 'is_string' ) ) ), 0, 4 );
	}

	/**
	 * Move the basic settings of a widget into the "Settings" section at the top of its Content tab. Runs in the
	 * admin only (the editor and its AJAX requests): the front end never reads sections.
	 *
	 * @param \Elementor\Controls_Stack                     $widget  Widget, its controls just registered.
	 * @param \Buddy_Builder\Vendor\ElementKit\Element|null $element Element the widget renders, if any.
	 */
	public static function apply( $widget, $element = null ) {
		if ( ! is_admin() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return;
		}

		$manager  = \Elementor\Plugin::$instance->controls_manager;
		$controls = $widget->get_controls();
		$moved    = [];
		$emptied  = [];

		foreach ( self::keys( $widget, $element ) as $key ) {
			$group = self::control_group( $controls, $key );

			if ( ! $group ) {
				continue;
			}

			foreach ( $group as $id => $control ) {
				$emptied[ $control['section'] ] = true;
				$moved[ $id ]                   = $control;
				$widget->remove_control( $id );
			}
		}

		if ( ! $moved ) {
			return;
		}

		$index = self::first_content_section( $widget->get_controls() );

		$manager->add_control_to_stack(
			$widget,
			self::SECTION,
			[
				'type'  => \Elementor\Controls_Manager::SECTION,
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
				'label' => \Buddy_Builder\Elements\Elements::kit()->config( 'labels' )['basic'],
			],
			[ 'index' => $index ]
		);

		foreach ( $moved as $id => $control ) {
			$control['section'] = self::SECTION;
			$control['tab']     = \Elementor\Controls_Manager::TAB_CONTENT;
			unset( $control['name'] );

			$manager->add_control_to_stack( $widget, $id, $control, [ 'index' => ++$index ] );
		}

		// Sections the move left without controls.
		$left = [];
		foreach ( $widget->get_controls() as $id => $control ) {
			if ( isset( $control['section'] ) && \Elementor\Controls_Manager::SECTION !== $control['type'] ) {
				$left[ $control['section'] ] = true;
			}
		}

		$labels = \Buddy_Builder\Elements\Elements::kit()->config( 'labels' );

		foreach ( array_keys( $emptied ) as $section ) {
			if ( self::SECTION === $section ) {
				continue;
			}

			if ( ! isset( $left[ $section ] ) ) {
				$widget->remove_control( $section );
			} elseif ( isset( $controls[ $section ]['label'] ) && $controls[ $section ]['label'] === $labels['basic'] ) {
				// A 1.x section also called "Settings" keeps the rest of its controls as "More settings".
				$manager->update_control_in_stack( $widget, $section, [ 'label' => $labels['advanced'] ] );
			}
		}
	}

	/**
	 * Hide controls while other settings have these values (a condition "key!" => value is added to each control that
	 * exists; keys and saved values stay). For settings that do nothing then.
	 *
	 * @param \Elementor\Controls_Stack $stack Widget or element.
	 * @param string[]                  $ids   Control ids (sections too).
	 * @param array                     $when  Setting => value.
	 */
	public static function hide_when( $stack, array $ids, array $when ) {
		$hide = [];

		foreach ( $when as $key => $value ) {
			$hide[ $key . '!' ] = $value;
		}

		foreach ( $hide ? $ids : [] as $id ) {
			$control = $stack->get_controls( $id );

			if ( $control ) {
				self::condition_keys( $stack, array_keys( $when ), $control );
				$stack->update_control( $id, [ 'condition' => array_merge( isset( $control['condition'] ) ? (array) $control['condition'] : [], $hide ) ] );
			}
		}
	}

	/**
	 * Register the settings a condition reads that the stack does not have, as hidden settings with an empty default.
	 * Elementor (Controls_Stack::is_control_visible()) counts a condition on an unset setting as false, "key!" ones
	 * too: without this, "design!" => "header" hides the control, and drops its classes and CSS, on every element
	 * without a "design" setting (plain Flexbox Containers, the 1.x header parts). Saved values stay.
	 *
	 * @param \Elementor\Controls_Stack $stack Widget or element.
	 * @param string[]                  $keys  Setting keys.
	 * @param array                     $near  Control (or section) the hidden settings go next to: its section and tab.
	 */
	public static function condition_keys( $stack, array $keys, array $near ) {
		foreach ( $keys as $key ) {
			if ( null !== $stack->get_controls( $key ) ) {
				continue;
			}

			\Elementor\Plugin::$instance->controls_manager->add_control_to_stack(
				$stack,
				$key,
				[
					'type'    => \Elementor\Controls_Manager::HIDDEN,
					'default' => '',
					'section' => \Elementor\Controls_Manager::SECTION === $near['type'] ? $near['name'] : $near['section'],
					'tab'     => isset( $near['tab'] ) ? $near['tab'] : \Elementor\Controls_Manager::TAB_CONTENT,
				]
			);
		}
	}

	/**
	 * A control and its device copies, when moving them cannot change the output; [] otherwise.
	 *
	 * @param array  $controls Controls of the widget, in order.
	 * @param string $key      Control key.
	 * @return array id => control
	 */
	private static function control_group( array $controls, $key ) {
		if ( ! isset( $controls[ $key ] ) || empty( $controls[ $key ]['section'] ) || \Elementor\Controls_Manager::SECTION === $controls[ $key ]['type'] || ! empty( $controls[ $key ]['groupType'] ) || ! empty( $controls[ $key ]['groupPrefix'] ) ) {
			return [];
		}

		$group = [ $key => $controls[ $key ] ];

		if ( isset( $controls[ $key ]['responsive'] ) ) {
			foreach ( self::DEVICES as $device ) {
				if ( isset( $controls[ $key . '_' . $device ]['responsive'] ) ) {
					$group[ $key . '_' . $device ] = $controls[ $key . '_' . $device ];
				}
			}
		}

		foreach ( $group as $control ) {
			if ( ! empty( $control['selectors'] ) || ! empty( $control['prefix_class'] ) || ! empty( $control['frontend_available'] ) ) {
				return [];
			}
		}

		return $group;
	}

	/**
	 * Stack index of the first Content tab section (where the "Settings" section goes).
	 *
	 * @param array $controls Controls, in order.
	 * @return int
	 */
	private static function first_content_section( array $controls ) {
		$index = 0;

		foreach ( $controls as $control ) {
			if ( \Elementor\Controls_Manager::SECTION === $control['type'] && ( ! isset( $control['tab'] ) || \Elementor\Controls_Manager::TAB_CONTENT === $control['tab'] ) ) {
				return $index;
			}
			++$index;
		}

		return 0;
	}
}
