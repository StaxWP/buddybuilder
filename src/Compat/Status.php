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
		$types = function_exists( 'bpb_get_template_types' ) ? bpb_get_template_types() : [];
		// What renders (Registry::all(): the bpb_settings id wins over a stale registry entry).
		$all   = [];
		$rows  = [];

		if ( class_exists( '\\Buddy_Builder\\Templates\\Registry' ) ) {
			$all = \Buddy_Builder\Templates\Registry::all();
		} elseif ( function_exists( 'bpb_get_settings' ) ) {
			// 1.x files (the compat tests load this class by hand on 1.9.2): Elementor ids only.
			foreach ( (array) bpb_get_settings()['templates'] as $type => $id ) {
				$all[ $type ] = [
					'builder' => 'elementor',
					'id'      => (int) $id,
				];
			}
		}

		foreach ( $types as $type => $info ) {
			$entry = isset( $all[ $type ] ) ? (array) $all[ $type ] : [
				'builder' => 'elementor',
				'id'      => 0,
			];

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
