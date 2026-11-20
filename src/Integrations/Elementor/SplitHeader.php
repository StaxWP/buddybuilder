<?php
/**
 * Elementor: a Member header split into parts (BuddyBuilder Pro) is made of Flexbox Containers that carry the header's
 * classes (frame bpb-mh--parts, then bpb-mh__body, bpb-mh__main, bpb-mh__name) and hold the part widgets. Elementor's
 * container classes (e-con, e-flex, e-con-full, ...) bring their own display, padding, gap, width and flex rules, which
 * would win over the header's. These containers drop them, on the page (here) and in the editor preview
 * (split-header-preview.js), so they are plain boxes with the header's classes and the header's CSS lays them out
 * exactly like the one-element header (base.css, the same rules as the block groups). The part widgets' wrappers take
 * no box there (display: contents).
 *
 * Here and not in Pro: a split header keeps its layout when Pro is off (its free parts still render).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Integrations\Elementor;

defined( 'ABSPATH' ) || die();

/**
 * Split Member header containers.
 */
final class SplitHeader {

	/** Classes of the split header's containers. */
	const BOXES = [ 'bpb-mh--parts', 'bpb-mh__body', 'bpb-mh__main', 'bpb-mh__name' ];

	/** Elementor's container layout classes, dropped on those containers (e-con, e-con-full, e-con--column, e-flex, ...). */
	const LAYOUT = '^e-(con|flex|grid|child|parent)(-|$)';

	/**
	 * Ids of the containers being printed with their output held (see open()).
	 *
	 * @var string[]
	 */
	private static $open = [];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_filter( 'elementor/frontend/container/should_render', [ __CLASS__, 'open' ], 10, 2 );
		add_action( 'elementor/frontend/container/after_render', [ __CLASS__, 'close' ] );
		add_action( 'elementor/preview/enqueue_scripts', [ __CLASS__, 'preview' ] );
		add_action( 'elementor/element/after_section_end', [ __CLASS__, 'box_controls' ], 10, 2 );
	}

	/** Settings that mark the split header's containers (set by Pro's Split into parts). */
	const MARK = [ 'design' => 'header' ];

	/**
	 * Container controls that do nothing on these containers, besides the Layout tab's Container section (width, height,
	 * direction, alignment, gap, wrap): margin, padding, z-index and corner radius. Elementor applies them through its
	 * container classes and their CSS variables, which these containers drop. Responsive copies follow.
	 */
	const IGNORED = [ 'margin', 'padding', 'z_index', 'border_radius', 'border_radius_hover' ];

	/**
	 * Editor panel of a split header's containers: the settings that do nothing there hide (as each section that holds
	 * them is registered), and a short note in the Layout tab says why (background, border, shadow and responsive
	 * visibility still apply). Other containers are unchanged.
	 *
	 * @param \Elementor\Controls_Stack $element    Element.
	 * @param string                    $section_id Section that ended.
	 */
	public static function box_controls( $element, $section_id ) {
		if ( 'container' !== $element->get_name() ) {
			return;
		}

		$ids     = [];
		$devices = '/_(' . implode( '|', BasicControls::DEVICES ) . ')$/';

		foreach ( $element->get_controls() as $id => $control ) {
			if ( isset( $control['section'] ) && $section_id === $control['section'] && ( 'section_layout_container' === $section_id || in_array( preg_replace( $devices, '', $id ), self::IGNORED, true ) ) ) {
				$ids[] = $id;
			}
		}

		BasicControls::hide_when( $element, 'section_layout_container' === $section_id ? array_merge( $ids, [ $section_id ] ) : $ids, self::MARK );

		if ( 'section_layout_container' !== $section_id ) {
			return;
		}

		$element->start_controls_section(
			'bpb_split_box',
			[
				'label'     => __( 'Member header (parts)', 'stax-buddy-builder' ),
				'tab'       => \Elementor\Controls_Manager::TAB_LAYOUT,
				'condition' => self::MARK,
			]
		);
		$element->add_control(
			'bpb_split_box_note',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'This box is part of a Member header split into parts: the header\'s design lays it out, so its layout, size, margin, padding and corner settings are hidden. Background, border, shadow and responsive visibility still apply.', 'stax-buddy-builder' ),
				'content_classes' => 'elementor-descriptor',
			]
		);
		$element->end_controls_section();
	}

	/**
	 * Whether a container is one of a split header's (from its CSS classes setting).
	 *
	 * @param string $classes CSS classes setting.
	 * @return bool
	 */
	public static function is_box( $classes ) {
		return (bool) array_intersect( self::BOXES, preg_split( '/\s+/', (string) $classes, -1, PREG_SPLIT_NO_EMPTY ) );
	}

	/**
	 * Page: hold the output of a split header's container (its children are printed already), so close() can drop
	 * the layout classes Elementor gives its opening tag (it adds e-con after every attribute hook).
	 *
	 * @param bool                    $should_render Whether Elementor prints the container.
	 * @param \Elementor\Element_Base $element       Container.
	 * @return bool
	 */
	public static function open( $should_render, $element ) {
		if ( $should_render && self::is_box( $element->get_settings( 'css_classes' ) ) ) {
			ob_start();
			self::$open[] = $element->get_id();
		}

		return $should_render;
	}

	/**
	 * Page: print a held container without Elementor's layout classes on its opening tag.
	 *
	 * @param \Elementor\Element_Base $element Container.
	 */
	public static function close( $element ) {
		if ( ! self::$open || end( self::$open ) !== $element->get_id() ) {
			return;
		}

		array_pop( self::$open );
		echo self::strip( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor's own markup.
	}

	/**
	 * Drop Elementor's layout classes from the first tag of a container's markup.
	 *
	 * @param string $html Markup.
	 * @return string
	 */
	public static function strip( $html ) {
		return (string) preg_replace_callback(
			'/^(\s*<[a-z]+\b[^>]*?\bclass=")([^"]*)"/i',
			static function ( $m ) {
				return $m[1] . implode( ' ', preg_grep( '/' . self::LAYOUT . '/', preg_split( '/\s+/', $m[2], -1, PREG_SPLIT_NO_EMPTY ), PREG_GREP_INVERT ) ) . '"';
			},
			$html,
			1
		);
	}

	/**
	 * Editor preview: the same, as the editor draws the containers. A part with nothing to show for the preview member
	 * shows its hint as in the block editor (small, dashed), not in the style of the header row it sits in.
	 */
	public static function preview() {
		wp_register_style( 'bpb-split-header-preview', false, [], BPB_VERSION );
		wp_enqueue_style( 'bpb-split-header-preview' );
		wp_add_inline_style( 'bpb-split-header-preview', '.bpb-mh--parts .bpb-el-empty{margin:0;padding:.5em 1em;border:1px dashed currentColor;border-radius:4px;font:400 13px/1.4 -apple-system,BlinkMacSystemFont,sans-serif;letter-spacing:0;text-transform:none;opacity:.7}' );

		wp_enqueue_script( 'bpb-split-header-preview', BPB_BASE_URL . 'src/Integrations/Elementor/split-header-preview.js', [], BPB_VERSION, true );
		wp_add_inline_script(
			'bpb-split-header-preview',
			'window.bpbSplitHeaderPreview = ' . wp_json_encode(
				[
					'boxes'  => self::BOXES,
					'layout' => self::LAYOUT,
				]
			) . ';',
			'before'
		);
	}
}
