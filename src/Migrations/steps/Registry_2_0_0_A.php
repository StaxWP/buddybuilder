<?php
/**
 * Step 2.0.0-a: template assignments to the builder-aware registry.
 *
 * @package Buddy_Builder\Migrations
 */

namespace Buddy_Builder\Migrations\Steps;

use Buddy_Builder\Migrations\Step;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `bpb_settings['templates'][ type ] = id` (1.x, Elementor only) becomes
 * `bpb_templates[ type ] = [ 'builder' => 'elementor', 'id' => id ]`.
 *
 * `bpb_settings` is not changed: 2.x keeps writing it for Pro 1.x and KLEO 6, so rolling back means deleting
 * (or restoring) `bpb_templates` only. Assignments already in `bpb_templates` (for example a block layout picked
 * before the step ran) are never overwritten.
 */
class Registry_2_0_0_A extends Step {

	/**
	 * {@inheritDoc}
	 */
	public function id() {
		return '2.0.0-a';
	}

	/**
	 * {@inheritDoc}
	 */
	public function description() {
		return 'Template assignments: bpb_settings[templates] to the builder-aware registry bpb_templates';
	}

	/**
	 * {@inheritDoc}
	 */
	public function options() {
		return [ 'bpb_templates' ];
	}

	/**
	 * Assignments of 1.x that point to an existing post.
	 *
	 * @return int[] [ type => id ]
	 */
	private function legacy() {
		$settings  = get_option( 'bpb_settings', [] );
		$templates = is_array( $settings ) && isset( $settings['templates'] ) && is_array( $settings['templates'] ) ? $settings['templates'] : [];
		$out       = [];

		foreach ( $templates as $type => $id ) {
			$id = (int) $id;
			if ( $id > 0 && get_post( $id ) ) {
				$out[ sanitize_key( $type ) ] = $id;
			}
		}

		return $out;
	}

	/**
	 * Registry as stored (not through the 2.0 Registry class, which falls back to bpb_settings when empty).
	 *
	 * @return array
	 */
	private function registry() {
		$value = get_option( 'bpb_templates', null );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * {@inheritDoc}
	 */
	public function check() {
		if ( ! is_array( get_option( 'bpb_templates', null ) ) ) {
			return false;
		}

		$registry = $this->registry();
		foreach ( array_keys( $this->legacy() ) as $type ) {
			if ( empty( $registry[ $type ]['builder'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Restores the registry from the backup, then puts back in `bpb_settings` the Elementor templates that block
	 * layouts replaced after the migration (1.9.2 only reads `bpb_settings`; a 0 there means BuddyPress default).
	 *
	 * @param array $backup Backup.
	 * @return true
	 */
	public function down( array $backup ) {
		parent::down( $backup );

		$replaced = get_option( 'bpb_replaced_elementor', [] );
		$settings = get_option( 'bpb_settings', [] );

		if ( is_array( $replaced ) && $replaced && is_array( $settings ) ) {
			foreach ( $replaced as $type => $id ) {
				if ( empty( $settings['templates'][ $type ] ) && get_post( (int) $id ) ) {
					$settings['templates'][ $type ] = (int) $id;
				}
			}

			update_option( 'bpb_settings', $settings );
		}

		delete_option( 'bpb_replaced_elementor' );

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function up( $dry_run = false ) {
		$registry = $this->registry();

		foreach ( $this->legacy() as $type => $id ) {
			if ( ! empty( $registry[ $type ]['builder'] ) ) {
				continue;
			}
			$registry[ $type ] = [
				'builder' => 'elementor',
				'id'      => $id,
			];
		}

		if ( ! $dry_run ) {
			update_option( 'bpb_templates', $registry, true );
		}

		return true;
	}
}
