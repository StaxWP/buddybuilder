<?php

namespace Buddy_Builder\Widgets;

use Buddy_Builder\Plugin;

/**
 * Base of the BuddyBuilder Elementor widgets (BuddyBuilder Pro and third parties extend it too).
 *
 * 2.0: a widget that sets $bpb_element is a thin adapter: its front-end markup comes from the shared element
 * renderer (src/Elements/<name>/render.php), the same one the block uses. Its own controls stay unchanged, so saved
 * layouts keep their settings and styles.
 */
class Base extends \Elementor\Widget_Base {

	use \Buddy_Builder\Vendor\ElementKit\ElementorAdapter;

	/**
	 * Element rendered by this widget ('' = the widget renders itself, as in 1.x).
	 *
	 * @var string
	 */
	protected $bpb_element = '';

	/** Elementor category of the member and group header parts (element.json category buddybuilder-header-parts). */
	const HEADER_PARTS_CATEGORY = 'buddy-builder-header-parts';

	/** Settings of a header part inside a Member header split into parts (BuddyBuilder Pro): it prints the header's markup. */
	const SPLIT_PART = [ 'design' => 'header' ];

	/**
	 * Settings with which the widget's own Style tab settings do nothing ([] : none), e.g. self::SPLIT_PART for a 1.x
	 * header part: its Style tab targets the 1.x markup. Those settings hide then; alignment stays (it moves the part).
	 *
	 * @var array
	 */
	protected $bpb_style_hide_when = [];

	/**
	 * Kit element of this widget.
	 *
	 * @return \Buddy_Builder\Vendor\ElementKit\Element|null
	 */
	protected function kit_element() {
		return '' !== $this->bpb_element && class_exists( '\\Buddy_Builder\\Elements\\Elements' ) ? \Buddy_Builder\Elements\Elements::get( $this->bpb_element ) : null;
	}

	/**
	 * Register the controls, then show the basic settings first (U3; keys and defaults unchanged).
	 */
	protected function init_controls() {
		parent::init_controls();

		if ( class_exists( '\\Buddy_Builder\\Integrations\\Elementor\\BasicControls' ) ) {
			\Buddy_Builder\Integrations\Elementor\BasicControls::apply( $this, $this->kit_element() );
		}

		$this->bpb_part_of_section();
		$this->bpb_hide_when();
	}

	/**
	 * Settings that do nothing in some cases hide then: the element's attributes with "hideWhen" (element.json, as in
	 * the block editor; e.g. a header part's settings inside a Member header split into parts) and the widget's own
	 * Style tab settings with $bpb_style_hide_when (alignment stays). A section left without settings hides too. Keys
	 * and saved values stay; only conditions grow (responsive copies included). In the editor only (as BasicControls),
	 * so the page renders exactly as before.
	 */
	private function bpb_hide_when() {
		$element = $this->kit_element();
		$panel   = '\\Buddy_Builder\\Integrations\\Elementor\\BasicControls';

		if ( ! $element || ! is_admin() || ! class_exists( $panel ) ) {
			return;
		}

		$controls = $this->get_controls();
		$hide     = []; // JSON of the "when" => control ids.

		foreach ( \Buddy_Builder\Vendor\ElementKit\Schema::attributes( $element->schema() ) as $key => $def ) {
			if ( ! empty( $def['hideWhen'] ) && is_array( $def['hideWhen'] ) ) {
				foreach ( array_merge( [ $key ], preg_filter( '/^/', $key . '_', $panel::DEVICES ) ) as $id ) {
					$hide[ wp_json_encode( $def['hideWhen'] ) ][] = $id;
				}
			}
		}

		// The widget's own Style tab sections (Elementor's common ones start with "_").
		foreach ( $this->bpb_style_hide_when ? $controls : [] as $id => $control ) {
			$section = isset( $control['section'], $controls[ $control['section'] ] ) ? $controls[ $control['section'] ] : null;

			if ( $section && 0 !== strpos( $control['section'], '_' ) && isset( $section['tab'] ) && \Elementor\Controls_Manager::TAB_STYLE === $section['tab'] && ! preg_match( '/_align(_|$)/', $id ) ) {
				$hide[ wp_json_encode( $this->bpb_style_hide_when ) ][] = $id;
			}
		}

		foreach ( $hide as $when => $ids ) {
			$ids = array_intersect( $ids, array_keys( $controls ) );

			foreach ( array_unique( wp_list_pluck( array_intersect_key( $controls, array_flip( $ids ) ), 'section' ) ) as $section ) {
				if ( ! array_diff( array_keys( wp_list_filter( $controls, [ 'section' => $section ] ) ), $ids ) ) {
					$ids[] = $section;
				}
			}

			$panel::hide_when( $this, $ids, json_decode( $when, true ) );
		}
	}

	/**
	 * Header parts: a quiet, optional "Replace with Member header" at the end of the Content tab (a UI control, nothing
	 * is saved; replace-deprecated-elementor.js handles the click in the editor).
	 */
	private function bpb_part_of_section() {
		$use = '' !== $this->bpb_element && class_exists( '\\Buddy_Builder\\Elements\\Elements' ) ? \Buddy_Builder\Elements\Elements::part_of( $this->bpb_element ) : null;

		if ( ! $use ) {
			return;
		}

		/* translators: %s: name of the designed element, e.g. Member header */
		$label = sprintf( __( 'Replace with %s', 'stax-buddy-builder' ), $use->title() );

		// Not in a header split into parts (BuddyBuilder Pro marks its parts with design "header"; it offers Join there).
		$this->start_controls_section(
			'bpb_part_of',
			[
				'label'     => $label,
				'condition' => [ 'design!' => 'header' ],
			]
		);
		$this->add_control(
			'bpb_part_of_note',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				/* translators: %s: name of the designed element, e.g. Member header */
				'raw'             => esc_html( sprintf( __( 'Optional. This part keeps working as it is. One %s can show the header parts of this column in a style\'s design; it starts from its default settings, and Undo brings the parts back.', 'stax-buddy-builder' ), $use->title() ) ),
				'content_classes' => 'elementor-descriptor',
			]
		);
		$this->add_control(
			'bpb_part_of_replace',
			[
				'type'        => \Elementor\Controls_Manager::BUTTON,
				'text'        => esc_html__( 'Replace', 'stax-buddy-builder' ),
				'event'       => 'buddybuilder:replace',
				'label_block' => false,
				'show_label'  => false,
			]
		);
		$this->end_controls_section();
	}

	/**
	 * Header parts are named like their blocks (Member avatar, Group name, ...). The title only: widget names and
	 * settings stay, so saved templates keep working. Other widgets keep their own title.
	 *
	 * @return string
	 */
	public function get_title() {
		$element = $this->kit_element();

		return $element ? $element->title() : '';
	}

	/**
	 * Header parts sit in their own category; other widgets keep theirs.
	 *
	 * @return string[]
	 */
	public function get_categories() {
		return \Buddy_Builder\Elements\Elements::is_header_part( $this->kit_element() ) ? [ self::HEADER_PARTS_CATEGORY ] : parent::get_categories();
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
		// Deprecated (2.0): saved layouts keep the widget, new layouts use its replacement.
		if ( '' !== $this->bpb_element && class_exists( '\\Buddy_Builder\\Elements\\Elements' ) && '' !== \Buddy_Builder\Elements\Elements::deprecated_note( $this->bpb_element ) ) {
			return false;
		}

		$template_type = \Buddy_Builder\Integrations\Elementor\EditorPreview::edited_type();
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
	 * Render the shared element; in an Elementor editor without real data, a short placeholder instead (2.0: the
	 * 1.x static previews are gone).
	 *
	 * @param string $preview_filter 1.x filter of the widget's static editor preview (deprecated): in the editor,
	 *                               code still hooked to it gets the real preview markup.
	 */
	protected function bpb_render_element( $preview_filter = '' ) {
		if ( bpb_editor_placeholder( $this ) ) {
			return;
		}

		if ( '' === $preview_filter || ! bpb_is_elementor_editor() || ! has_filter( $preview_filter ) ) {
			$this->bpb_kit_render();
			return;
		}

		ob_start();
		$this->bpb_kit_render();
		$html = ob_get_clean();

		echo apply_filters_deprecated( $preview_filter, [ $html, $this ], '2.0.0', '', __( 'The Elementor editor shows BuddyBuilder templates with real data; the static preview templates were removed.', 'stax-buddy-builder' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- markup of render.php; 1.x hook names.
	}

	/**
	 * Widget markup. Not on the first view of the Elementor editor page of a BuddyBuilder template: the editor then
	 * asks for each widget in its real preview context (AJAX), where BuddyPress runs as on the front end.
	 */
	public function render_content() {
		if ( \Buddy_Builder\Integrations\Elementor\EditorPreview::is_first_view() ) {
			return;
		}

		parent::render_content();
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

		$defaults = \Buddy_Builder\Vendor\ElementKit\Schema::defaults( $schema );

		$this->start_controls_section( 'bpb_' . $key . '_section', [ 'label' => $label ] );
		$this->add_control( $key, self::kit_control( $element, $schema['attributes'][ $key ], $defaults[ $key ] ) );
		$this->end_controls_section();
	}

	/**
	 * Render
	 */
	protected function render() {
		buddy_builder()->register_front_css();
		wp_print_styles( [ 'stax-buddy-builder-front' ] );

		if ( '' !== $this->bpb_element && bpb_is_elementor_editor() && class_exists( '\\Buddy_Builder\\Elements\\Elements' ) ) {
			echo \Buddy_Builder\Elements\Elements::deprecated_note( $this->bpb_element ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in deprecated_note().
		}
	}

}
