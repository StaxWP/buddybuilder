<?php
/**
 * Turns a preset layout description into block markup or Elementor data.
 *
 * Layout: list of rows. Row: [ 'columns' => [ column, ... ], 'gap' => '1rem' (blocks), 'align' => 'center' ].
 * Column: [ 'width' => 33 (percent, optional), 'elements' => [ element, ... ] ].
 * Element: [ element name, attributes (element.json keys), style (optional) ].
 * Style keys (both builders): minHeight, padding, margin, radius, background (theme color slug), text (theme color
 * slug), fontSize (CSS value), textAlign.
 *
 * Blocks get a root constrained core/group (S4: without it the layout runs edge to edge). Elementor gets one
 * section per row with one column per column, and the widgets of the elements (legacy widget names).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

use Buddy_Builder\Elements\Elements;

defined( 'ABSPATH' ) || die();

/**
 * Preset layout serializers.
 */
final class Builder {

	/**
	 * Block markup.
	 *
	 * @param array $layout Rows.
	 * @return string
	 */
	public static function to_blocks( array $layout ) {
		$rows = '';

		foreach ( $layout as $row ) {
			$columns = isset( $row['columns'] ) ? $row['columns'] : [];

			if ( 1 === count( $columns ) && empty( $columns[0]['width'] ) ) {
				$rows .= self::elements_to_blocks( $columns[0]['elements'] );
				continue;
			}

			$inner = '';

			foreach ( $columns as $column ) {
				$width  = isset( $column['width'] ) ? (float) $column['width'] : 0;
				$attrs  = $width ? [ 'width' => $width . '%' ] : [];
				$style  = $width ? ' style="flex-basis:' . $width . '%"' : '';
				$inner .= '<!-- wp:column ' . ( $attrs ? wp_json_encode( $attrs ) . ' ' : '' ) . '-->' . "\n"
					. '<div class="wp-block-column"' . $style . '>' . self::elements_to_blocks( $column['elements'] ) . '</div>' . "\n"
					. '<!-- /wp:column -->' . "\n";
			}

			$columns_attrs = isset( $row['align'] ) ? [ 'verticalAlignment' => $row['align'] ] : [];
			$columns_class = isset( $row['align'] ) ? ' are-vertically-aligned-' . $row['align'] : '';

			$rows .= '<!-- wp:columns ' . ( $columns_attrs ? wp_json_encode( $columns_attrs ) . ' ' : '' ) . '-->' . "\n"
				. '<div class="wp-block-columns' . $columns_class . '">' . $inner . '</div>' . "\n"
				. '<!-- /wp:columns -->' . "\n";
		}

		return '<!-- wp:group {"layout":{"type":"constrained"},"metadata":{"name":"BuddyBuilder layout"}} -->' . "\n"
			. '<div class="wp-block-group">' . $rows . '</div>' . "\n"
			. '<!-- /wp:group -->';
	}

	/**
	 * Self-closing BuddyBuilder blocks (dynamic, no saved markup to validate).
	 *
	 * @param array $elements Elements.
	 * @return string
	 */
	private static function elements_to_blocks( array $elements ) {
		$out = "\n";

		foreach ( $elements as $element ) {
			$attrs = isset( $element[1] ) ? (array) $element[1] : [];
			$style = isset( $element[2] ) ? self::block_style( (array) $element[2] ) : [];
			$attrs = array_merge( $attrs, $style );

			$out .= '<!-- wp:buddybuilder/' . $element[0] . ' ' . ( $attrs ? wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . ' ' : '' ) . '/-->' . "\n";
		}

		return $out;
	}

	/**
	 * Style keys => block support attributes.
	 *
	 * @param array $style Style.
	 * @return array
	 */
	private static function block_style( array $style ) {
		$attrs = [];

		if ( isset( $style['background'] ) ) {
			$attrs['backgroundColor'] = $style['background'];
		}

		if ( isset( $style['text'] ) ) {
			$attrs['textColor'] = $style['text'];
		}

		if ( isset( $style['minHeight'] ) ) {
			$attrs['style']['dimensions']['minHeight'] = $style['minHeight'];
		}

		foreach ( [ 'padding', 'margin' ] as $key ) {
			if ( isset( $style[ $key ] ) ) {
				$attrs['style']['spacing'][ $key ] = self::sides( $style[ $key ] );
			}
		}

		if ( isset( $style['radius'] ) ) {
			$attrs['style']['border']['radius'] = $style['radius'];
		}

		if ( isset( $style['fontSize'] ) ) {
			$attrs['style']['typography']['fontSize'] = $style['fontSize'];
		}

		if ( isset( $style['textAlign'] ) ) {
			$attrs['style']['typography']['textAlign'] = $style['textAlign'];
		}

		return $attrs;
	}

	/**
	 * Elementor data (sections, columns, widgets).
	 *
	 * @param array $layout Rows.
	 * @return array
	 */
	public static function to_elementor( array $layout ) {
		$widgets  = array_flip( Elements::legacy_map() );
		$sections = [];

		foreach ( $layout as $row ) {
			$columns = [];
			$list    = isset( $row['columns'] ) ? $row['columns'] : [];
			$count   = max( 1, count( $list ) );

			foreach ( $list as $column ) {
				$width   = isset( $column['width'] ) ? (float) $column['width'] : round( 100 / $count, 3 );
				$content = [];

				foreach ( $column['elements'] as $element ) {
					if ( ! isset( $widgets[ $element[0] ] ) ) {
						continue;
					}

					$content[] = [
						'id'         => self::id(),
						'elType'     => 'widget',
						'widgetType' => $widgets[ $element[0] ],
						'settings'   => array_merge(
							self::elementor_settings( isset( $element[1] ) ? (array) $element[1] : [] ),
							self::elementor_style( isset( $element[2] ) ? (array) $element[2] : [] )
						),
						'elements'   => [],
					];
				}

				$columns[] = [
					'id'       => self::id(),
					'elType'   => 'column',
					'settings' => [
						'_column_size' => (int) round( $width ),
						'_inline_size' => $width,
					],
					'elements' => $content,
				];
			}

			$settings = [];

			if ( isset( $row['align'] ) ) {
				$settings['content_position'] = 'center' === $row['align'] ? 'middle' : $row['align'];
			}

			$sections[] = [
				'id'       => self::id(),
				'elType'   => 'section',
				'settings' => $settings,
				'elements' => $columns,
			];
		}

		return $sections;
	}

	/**
	 * Element attributes => Elementor setting values (booleans become switcher values, numbers strings).
	 *
	 * @param array $attrs Attributes.
	 * @return array
	 */
	private static function elementor_settings( array $attrs ) {
		$out = [];

		foreach ( $attrs as $key => $value ) {
			if ( is_bool( $value ) ) {
				$out[ $key ] = $value ? 'yes' : '';
			} elseif ( is_int( $value ) || is_float( $value ) ) {
				$out[ $key ] = (string) $value;
			} else {
				$out[ $key ] = $value;
			}
		}

		return $out;
	}

	/**
	 * Style keys => Elementor common (Advanced tab) settings.
	 *
	 * @param array $style Style.
	 * @return array
	 */
	private static function elementor_style( array $style ) {
		$out = [];

		foreach ( [ 'padding' => '_padding', 'margin' => '_margin' ] as $key => $control ) {
			if ( isset( $style[ $key ] ) ) {
				$sides           = self::sides( $style[ $key ] );
				$out[ $control ] = [
					'unit'     => 'px',
					'top'      => (string) (int) $sides['top'],
					'right'    => (string) (int) $sides['right'],
					'bottom'   => (string) (int) $sides['bottom'],
					'left'     => (string) (int) $sides['left'],
					'isLinked' => false,
				];
			}
		}

		if ( isset( $style['background'] ) ) {
			$out['_background_background'] = 'classic';
			$out['_background_color']      = 'var(--wp--preset--color--' . sanitize_key( $style['background'] ) . ')';
		}

		if ( isset( $style['radius'] ) ) {
			$radius               = (string) (int) $style['radius'];
			$out['_border_radius'] = [
				'unit'     => 'px',
				'top'      => $radius,
				'right'    => $radius,
				'bottom'   => $radius,
				'left'     => $radius,
				'isLinked' => true,
			];
		}

		// minHeight: Elementor widgets have no height control; the cover keeps the BuddyPress cover height there.
		return $out;
	}

	/**
	 * "10px" or [ top, right, bottom, left ] => sides.
	 *
	 * @param string|array $value Value.
	 * @return array
	 */
	private static function sides( $value ) {
		if ( is_array( $value ) ) {
			return [
				'top'    => isset( $value['top'] ) ? $value['top'] : '0',
				'right'  => isset( $value['right'] ) ? $value['right'] : '0',
				'bottom' => isset( $value['bottom'] ) ? $value['bottom'] : '0',
				'left'   => isset( $value['left'] ) ? $value['left'] : '0',
			];
		}

		return [
			'top'    => $value,
			'right'  => $value,
			'bottom' => $value,
			'left'   => $value,
		];
	}

	/**
	 * Elementor element id (7 hex characters).
	 *
	 * @return string
	 */
	private static function id() {
		return substr( md5( wp_generate_uuid4() ), 0, 7 );
	}
}
