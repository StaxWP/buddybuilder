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
	 * On by default with KLEO 7 (hybrid block theme) and when Elementor is not active (nothing else to build with);
	 * otherwise opt-in through the "Blocks (beta)" toggle. Assigned block layouts always render on the front end.
	 *
	 * @return bool
	 */
	public static function blocks_enabled() {
		$option = get_option( self::BLOCKS_OPTION, null );

		if ( null === $option || false === $option || '' === $option ) {
			$enabled = self::is_kleo7() || ! defined( 'ELEMENTOR_VERSION' );
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
