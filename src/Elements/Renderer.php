<?php
/**
 * One renderer per element, shared by the block and the Elementor widget (2.0 spike, KLEO 7 spec 3.4 / 3.5).
 *
 * Each element lives in src/Elements/<name>/ with:
 * - block.json  attributes (content settings only) and "bpbControls" (labels for the generated editor UI);
 * - render.php  the only markup source. It receives $attrs (merged with the block.json defaults) and $ctx.
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Elements;

defined( 'ABSPATH' ) || die();

/**
 * Element renderer and the two adapters (block, Elementor).
 */
final class Renderer {

	/**
	 * Elements of the spike: element name => legacy Elementor widget name (for the CSS alias class).
	 *
	 * @var array
	 */
	const ELEMENTS = [
		'member-cover'    => 'bpb-profile-member-cover',
		'member-avatar'   => 'bpb-profile-member-avatar',
		'member-username' => 'bpb-profile-member-username',
		'member-meta'     => 'bpb-profile-member-meta',
		'member-buttons'  => 'bpb-profile-member-buttons',
		'member-nav'      => 'bpb-profile-member-navigation',
		'member-content'  => 'bpb-profile-member-content',
	];

	/**
	 * Parsed block.json files.
	 *
	 * @var array
	 */
	private static $meta = [];

	/**
	 * Path of an element folder.
	 *
	 * @param string $name Element name.
	 * @return string
	 */
	public static function dir( $name ) {
		return BPB_BASE_PATH . 'src/Elements/' . $name . '/';
	}

	/**
	 * block.json of an element.
	 *
	 * @param string $name Element name.
	 * @return array
	 */
	public static function meta( $name ) {
		if ( ! isset( self::$meta[ $name ] ) ) {
			$json               = file_get_contents( self::dir( $name ) . 'block.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			self::$meta[ $name ] = json_decode( (string) $json, true ) ?: [];
		}

		return self::$meta[ $name ];
	}

	/**
	 * Attribute defaults from block.json merged with the given values (unknown keys dropped, enums checked).
	 *
	 * @param string $name  Element name.
	 * @param array  $attrs Raw values (block attributes or Elementor settings, same keys).
	 * @return array
	 */
	public static function attrs( $name, array $attrs ) {
		$meta = self::meta( $name );
		$out  = [];

		foreach ( isset( $meta['attributes'] ) ? $meta['attributes'] : [] as $key => $def ) {
			$value = array_key_exists( $key, $attrs ) && '' !== $attrs[ $key ] && null !== $attrs[ $key ] ? $attrs[ $key ] : ( isset( $def['default'] ) ? $def['default'] : null );

			if ( isset( $def['enum'] ) && ! in_array( $value, $def['enum'], true ) ) {
				$value = isset( $def['default'] ) ? $def['default'] : reset( $def['enum'] );
			}

			$out[ $key ] = $value;
		}

		return $out;
	}

	/**
	 * Render an element: the single markup source for both builders.
	 *
	 * @param string $name  Element name.
	 * @param array  $attrs Attribute values.
	 * @param array  $ctx   Context: user_id (displayed member), preview (bool).
	 * @return string
	 */
	public static function render( $name, array $attrs, array $ctx = [] ) {
		if ( ! isset( self::ELEMENTS[ $name ] ) ) {
			return '';
		}

		$ctx   = array_merge(
			[
				'user_id' => function_exists( 'bp_displayed_user_id' ) ? bp_displayed_user_id() : 0,
				'preview' => false,
			],
			$ctx
		);
		$attrs = self::attrs( $name, $attrs );

		if ( ! $ctx['user_id'] ) {
			return '';
		}

		ob_start();
		include self::dir( $name ) . 'render.php';

		return (string) ob_get_clean();
	}

	/**
	 * Wrapper classes shared by both adapters (neutral class + legacy Elementor class as a CSS alias).
	 *
	 * @param string $name Element name.
	 * @return string
	 */
	public static function classes( $name ) {
		return 'bpb-el bpb-el-' . $name . ' elementor-widget-' . self::ELEMENTS[ $name ];
	}

	/**
	 * Block adapter: register every element as a dynamic block.
	 */
	public static function register_blocks() {
		wp_register_script(
			'bpb-blocks-editor',
			BPB_BASE_URL . 'src/Elements/editor.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n', 'wp-data', 'wp-plugins', 'wp-editor', 'wp-core-data' ],
			BPB_VERSION,
			true
		);

		$editor = [];

		foreach ( array_keys( self::ELEMENTS ) as $name ) {
			register_block_type(
				self::dir( $name ),
				[
					'render_callback' => static function ( $attributes ) use ( $name ) {
						return Renderer::render_block( $name, (array) $attributes );
					},
				]
			);

			$meta            = self::meta( $name );
			$editor[ $name ] = [
				'name'     => $meta['name'],
				'controls' => isset( $meta['bpbControls'] ) ? $meta['bpbControls'] : [],
			];
		}

		wp_add_inline_script( 'bpb-blocks-editor', 'window.bpbBlocks = ' . wp_json_encode( array_values( $editor ) ) . ';', 'before' );
	}

	/**
	 * Block adapter: render with block wrapper attributes.
	 *
	 * @param string $name       Element name.
	 * @param array  $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( $name, array $attributes ) {
		$ctx  = \Buddy_Builder\Templates\PreviewContext::ctx();
		$html = self::render( $name, $attributes, $ctx );

		if ( '' === $html ) {
			return ! empty( $ctx['preview'] ) ? '<div class="bpb-el-empty">' . esc_html__( 'Pick a preview member in the template settings.', 'stax-buddy-builder' ) . '</div>' : '';
		}

		return '<div ' . get_block_wrapper_attributes( [ 'class' => self::classes( $name ) ] ) . '>' . $html . '</div>';
	}

	/**
	 * Elementor adapter: same renderer, Elementor keeps its own wrapper and style controls.
	 *
	 * @param string $name     Element name.
	 * @param array  $settings Elementor settings for display.
	 */
	public static function render_elementor( $name, array $settings ) {
		echo '<div class="bpb-el bpb-el-' . esc_attr( $name ) . '">' . self::render( $name, $settings ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
