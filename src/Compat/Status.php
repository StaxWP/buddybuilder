<?php
/**
 * Status data shared by the Status screen and `wp buddybuilder status`.
 *
 * @package Buddy_Builder\Compat
 */

namespace Buddy_Builder\Compat;

use Buddy_Builder\Migrations\Runner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Collects versions, pairing, migration steps, template assignments and theme overrides.
 */
class Status {

	/**
	 * Everything the status screen shows.
	 *
	 * @return array
	 */
	public static function collect() {
		return [
			'versions'  => [
				'buddybuilder' => defined( 'BPB_VERSION' ) ? BPB_VERSION : '?',
				'api'          => defined( 'BPB_API_VERSION' ) ? (string) BPB_API_VERSION : '1',
				'pro'          => defined( 'BPB_PRO_VERSION' ) ? BPB_PRO_VERSION : '-',
				'db'           => (string) get_option( 'bpb_db_version', '-' ),
				'buddypress'   => function_exists( 'bp_get_version' ) ? bp_get_version() : '-',
				'elementor'    => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '-',
			],
			'pairing'   => Pairing::state(),
			'steps'     => Runner::status(),
			'templates' => self::templates(),
			'overrides' => self::overrides(),
		];
	}

	/**
	 * Assignment per template type: builder, id, title, populated.
	 *
	 * @return array[]
	 */
	public static function templates() {
		$types    = function_exists( 'bpb_get_template_types' ) ? bpb_get_template_types() : [];
		$settings = function_exists( 'bpb_get_settings' ) ? bpb_get_settings() : [];
		$registry = (array) get_option( 'bpb_templates', [] ); // Shape agreed with the 2.0 Registry: [ type => { builder, id } ].
		$rows     = [];

		foreach ( $types as $type => $info ) {
			if ( isset( $registry[ $type ] ) ) {
				$entry = (array) $registry[ $type ];
			} else {
				$entry = [
					'builder' => 'elementor',
					'id'      => isset( $settings['templates'][ $type ] ) ? (int) $settings['templates'][ $type ] : 0,
				];
			}

			$id     = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
			$rows[] = [
				'type'      => $type,
				'name'      => isset( $info['name'] ) ? $info['name'] : $type,
				'builder'   => $id ? ( isset( $entry['builder'] ) ? $entry['builder'] : 'elementor' ) : '-',
				'id'        => $id,
				'title'     => $id ? get_the_title( $id ) : '',
				'populated' => $id && function_exists( 'bpb_is_template_populated' ) && bpb_is_template_populated( $type ) ? 'yes' : 'no',
			];
		}

		return $rows;
	}

	/**
	 * Template overrides in the child and parent theme (`<theme>/buddybuilder/`): first in the template stack.
	 *
	 * @return string[] Relative paths with the theme folder.
	 */
	public static function overrides() {
		$out = [];

		foreach ( array_unique( [ get_stylesheet_directory(), get_template_directory() ] ) as $dir ) {
			$base = trailingslashit( $dir ) . 'buddybuilder';
			if ( ! is_dir( $base ) ) {
				continue;
			}
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $base, \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $file ) {
				if ( 'php' === $file->getExtension() ) {
					$out[] = basename( $dir ) . '/buddybuilder/' . ltrim( substr( $file->getPathname(), strlen( $base ) ), '/' );
				}
			}
		}

		sort( $out );

		return $out;
	}
}
