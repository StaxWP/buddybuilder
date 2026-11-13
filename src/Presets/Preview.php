<?php
/**
 * Schematic SVG thumbnail of a preset layout (no screenshots shipped: drawn from the layout description).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Presets;

defined( 'ABSPATH' ) || die();

/**
 * Preset thumbnails.
 */
final class Preview {

	/**
	 * Height of one element box by element name (schematic units).
	 */
	const HEIGHTS = [
		'member-cover'    => 34,
		'group-cover'     => 30,
		'member-avatar'   => 22,
		'group-avatar'    => 22,
		'member-content'  => 44,
		'group-content'   => 44,
		'activity-stream' => 50,
		'members-listing' => 50,
		'groups-listing'  => 50,
	];

	/**
	 * Generic drawing of a page type, for layouts made by hand or imported from a starter kit (no preset).
	 *
	 * @param string $type Template type.
	 * @return string
	 */
	public static function generic( $type ) {
		$one = static function ( array $names ) {
			return [
				'columns' => [
					[
						'elements' => array_map(
							static function ( $name ) {
								return [ $name ];
							},
							$names
						),
					],
				],
			];
		};

		$map = [
			'member-profile'    => [ $one( [ 'member-cover', 'member-avatar', 'member-nav', 'member-content' ] ) ],
			'group-profile'     => [ $one( [ 'group-cover', 'group-avatar', 'group-nav', 'group-content' ] ) ],
			'members-directory' => [ $one( [ 'directory-nav', 'directory-filters', 'members-listing' ] ) ],
			'groups-directory'  => [ $one( [ 'directory-nav', 'directory-filters', 'groups-listing' ] ) ],
			'sitewide-activity' => [ $one( [ 'activity-form', 'activity-nav', 'activity-stream' ] ) ],
		];

		return self::svg( isset( $map[ $type ] ) ? $map[ $type ] : [ $one( [ 'member-content' ] ) ] );
	}

	/**
	 * SVG markup.
	 *
	 * @param array $layout Preset layout.
	 * @return string
	 */
	public static function svg( array $layout ) {
		$width = 200;
		$y     = 6;
		$boxes = '';

		foreach ( $layout as $row ) {
			$columns = isset( $row['columns'] ) ? $row['columns'] : [];
			$x       = 6;
			$row_h   = 0;
			$count   = max( 1, count( $columns ) );

			foreach ( $columns as $column ) {
				$col_w = ( $width - 12 - 4 * ( $count - 1 ) ) * ( isset( $column['width'] ) ? $column['width'] / 100 : 1 / $count );
				$cy    = $y;

				foreach ( $column['elements'] as $element ) {
					$h      = isset( self::HEIGHTS[ $element[0] ] ) ? self::HEIGHTS[ $element[0] ] : 8;
					$round  = false !== strpos( $element[0], 'avatar' );
					$boxes .= $round
						? sprintf( '<circle cx="%.1f" cy="%.1f" r="%.1f" class="e"/>', $x + min( $col_w, $h ) / 2, $cy + $h / 2, $h / 2 )
						: sprintf( '<rect x="%.1f" y="%.1f" width="%.1f" height="%d" rx="2" class="%s"/>', $x, $cy, $col_w, $h, $h > 20 ? 'b' : 'e' );
					$cy    += $h + 3;
				}

				$row_h = max( $row_h, $cy - $y );
				$x    += $col_w + 4;
			}

			$y += $row_h + 3;
		}

		return sprintf(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" role="img" focusable="false"><style>.b{fill:#c3c4c7}.e{fill:#dcdcde}</style>%3$s</svg>',
			$width,
			(int) max( 60, $y + 3 ),
			$boxes
		);
	}
}
