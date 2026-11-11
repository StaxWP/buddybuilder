<?php
/**
 * Elementor integration: everything that needs Elementor loads from here, and only when Elementor is active.
 *
 * Keeps the 1.x classes (ElementorHooks, Library\Module, Template\Module, the 32 widgets in core/widgets) in place, so
 * class names, hook order and saved layouts stay the same; this class is the single switch that loads them.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Integrations\Elementor;

use Buddy_Builder\ElementorHooks;
use Buddy_Builder\Plugin;

defined( 'ABSPATH' ) || die();

/**
 * Elementor integration loader.
 */
final class Integration {

	/**
	 * Whether Elementor is active (any version).
	 *
	 * @return bool
	 */
	public static function is_available() {
		return defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Whether the active Elementor is a supported version (2.0: 3.20 or newer).
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return self::is_available() && version_compare( ELEMENTOR_VERSION, Plugin::$minimum_elementor_version, '>=' );
	}

	/**
	 * Load the Elementor hooks (document type, template library, widgets, editor assets, settings mirror).
	 */
	public static function boot() {
		include_once BPB_BASE_PATH . 'core/hooks/ElementorHooks.php';
		include_once BPB_BASE_PATH . 'core/hooks/CustomizerHooks.php';

		ElementorHooks::get_instance();
		\Buddy_Builder\CustomizerHooks::get_instance();
	}

	/**
	 * Whether an Elementor template post has content (1.x "populated" rule: non-empty _elementor_data).
	 *
	 * @param int $id Post id.
	 * @return bool
	 */
	public static function is_populated( $id ) {
		$content = get_post_meta( (int) $id, '_elementor_data', true );

		if ( ! is_string( $content ) || '' === $content ) {
			return false;
		}

		$content = json_decode( $content, true );

		return ! empty( $content );
	}

	/**
	 * Render an Elementor template by id (1.x behavior: the [elementor-template] shortcode).
	 *
	 * @param int $id Post id.
	 * @return string
	 */
	public static function render( $id ) {
		$shortcode = bpb_get_shortcode_str( (int) $id );

		return $shortcode ? do_shortcode( $shortcode ) : '';
	}
}
