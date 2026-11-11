<?php
/**
 * Generates the synthetic "every widget" fixtures: one Elementor template per template type holding every
 * element registered for that type with default settings, plus "widgets-anywhere" (general and common widgets,
 * rendered on a normal Elementor page). Together with the real templates this covers all 64 widget names.
 *
 * Run on the 1.9.2 + Pro 1.7.0 baseline (it reads the legacy element registry):
 *   wp eval-file tests/compat/bin/build-synthetic-fixtures.php
 *
 * @package Buddy_Builder\Tests\Compat
 */

if ( ! function_exists( 'buddy_builder' ) ) {
	WP_CLI::error( 'BuddyBuilder is not active.' );
}

$bpb_out     = dirname( __DIR__ ) . '/fixtures';
$bpb_by_type = [];

foreach ( buddy_builder()->get_elements() as $bpb_element ) {
	$bpb_types = $bpb_element['template'];
	$bpb_types = ( '' === $bpb_types || null === $bpb_types ) ? [ 'anywhere' ] : (array) $bpb_types;

	foreach ( $bpb_types as $bpb_type ) {
		$bpb_by_type[ $bpb_type ][] = $bpb_element['name'];
	}
}

/**
 * Deterministic Elementor element id.
 *
 * @param string $seed Seed.
 * @return string
 */
function bpb_compat_eid( $seed ) {
	return substr( md5( $seed ), 0, 7 );
}

foreach ( $bpb_by_type as $bpb_type => $bpb_names ) {
	$bpb_sections = [];

	foreach ( $bpb_names as $bpb_name ) {
		$bpb_sections[] = [
			'id'       => bpb_compat_eid( $bpb_type . $bpb_name . 's' ),
			'elType'   => 'section',
			'settings' => new stdClass(),
			'isInner'  => false,
			'elements' => [
				[
					'id'       => bpb_compat_eid( $bpb_type . $bpb_name . 'c' ),
					'elType'   => 'column',
					'settings' => [ '_column_size' => 100 ],
					'isInner'  => false,
					'elements' => [
						[
							'id'         => bpb_compat_eid( $bpb_type . $bpb_name ),
							'elType'     => 'widget',
							'widgetType' => $bpb_name,
							'settings'   => new stdClass(),
							'elements'   => [],
							'isInner'    => false,
						],
					],
				],
			],
		];
	}

	$bpb_fixture = [
		'name'           => 'widgets-' . $bpb_type,
		'title'          => 'Every widget: ' . $bpb_type,
		'type'           => $bpb_type,
		'builder'        => 'elementor',
		'source'         => 'synthetic: every element registered for this type in 1.9.2 / Pro 1.7.0, default settings',
		'widgets'        => $bpb_names,
		'elementor_data' => $bpb_sections,
	];

	file_put_contents( $bpb_out . '/widgets-' . $bpb_type . '.json', wp_json_encode( $bpb_fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	WP_CLI::log( sprintf( 'widgets-%s: %d widgets', $bpb_type, count( $bpb_names ) ) );
}
