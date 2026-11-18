<?php
/**
 * Feature switches.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder;

defined( 'ABSPATH' ) || die();

/**
 * Feature switches: block layouts on/off.
 */
final class Features {

	/**
	 * Option of the "Blocks (beta)" toggle on non-KLEO 7 sites ('1' on, '0' off, missing = default).
	 */
	const BLOCKS_OPTION = 'bpb_blocks_enabled';

	/**
	 * Whether the block layouts UI (Block layouts screen, block inserter, presets for blocks) is enabled.
	 *
	 * On by default with KLEO 7 (hybrid block theme), when Elementor is not active (nothing else to build with) and
	 * when a page uses a block layout (a site set up with the block editor keeps it when Elementor is activated later);
	 * otherwise opt-in through the "Blocks (beta)" toggle. Assigned block layouts always render on the front end.
	 *
	 * @return bool
	 */
	public static function blocks_enabled() {
		$option = get_option( self::BLOCKS_OPTION, null );

		if ( null === $option || false === $option || '' === $option ) {
			$enabled = self::is_kleo7() || ! defined( 'ELEMENTOR_VERSION' ) || self::uses_block_layouts();
		} else {
			$enabled = '1' === (string) $option;
		}

		/**
		 * Filters whether block layouts are enabled.
		 *
		 * @param bool $enabled Enabled.
		 */
		return (bool) apply_filters( 'buddy_builder/blocks_enabled', $enabled );
	}

	/**
	 * Whether a page is assigned a block layout (the stored registry, read directly: no Registry cache or fallbacks).
	 *
	 * @return bool
	 */
	private static function uses_block_layouts() {
		foreach ( (array) get_option( \Buddy_Builder\Templates\Registry::OPTION, [] ) as $entry ) {
			if ( is_array( $entry ) && isset( $entry['builder'] ) && \Buddy_Builder\Templates\Registry::BLOCKS === $entry['builder'] && ! empty( $entry['id'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether the "Blocks (beta)" toggle is shown (it is not needed on KLEO 7, where blocks are the default).
	 *
	 * @return bool
	 */
	public static function shows_blocks_toggle() {
		return ! self::is_kleo7();
	}

	/**
	 * KLEO 7 or newer is the parent theme.
	 *
	 * @return bool
	 */
	public static function is_kleo7() {
		$theme = wp_get_theme( get_template() );

		return 'kleo' === strtolower( (string) $theme->get( 'Name' ) ) && version_compare( (string) $theme->get( 'Version' ), '7.0.0-dev', '>=' );
	}
}
