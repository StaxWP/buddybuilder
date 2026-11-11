<?php
/**
 * Block adapter: element.json to a dynamic block (element kit, shared with BuddyBuilder).
 *
 * KIT RULES: PHP 7.4 syntax, WordPress 6.6 APIs, no plugin-specific names.
 *
 * @package Buddy_Builder\Kit
 *
 * Synced from k-elements@1cfbcd7 (src/Kit) by bin/sync-kit.sh, do not edit.
 */

namespace Buddy_Builder\Kit;

defined( 'ABSPATH' ) || exit;

/**
 * Registers each element as a dynamic block. There is no block.json on disk: the block
 * metadata is generated from element.json, and core sends it to the editor with the other
 * server-registered blocks. The generic editor.js only adds edit() and save().
 */
final class BlockAdapter {

	/**
	 * Register the block of an element.
	 *
	 * @param Element $element Element.
	 * @return \WP_Block_Type|false
	 */
	public static function register( Element $element ) {
		if ( \WP_Block_Type_Registry::get_instance()->is_registered( $element->block_name() ) ) {
			return false;
		}

		return register_block_type( $element->block_name(), self::args( $element ) );
	}

	/**
	 * Arguments for register_block_type() generated from element.json.
	 *
	 * @param Element $element Element.
	 * @return array
	 */
	public static function args( Element $element ) {
		$kit    = $element->kit();
		$schema = $element->schema();

		$args = array(
			'api_version'           => 3,
			'title'                 => $element->title(),
			'description'           => isset( $schema['description'] ) ? $kit->t( $schema['description'] ) : '',
			'category'              => isset( $schema['category'] ) ? $schema['category'] : $kit->config( 'block_category' ),
			'icon'                  => isset( $schema['icon'] ) ? $schema['icon'] : 'admin-generic',
			'keywords'              => isset( $schema['keywords'] ) ? array_map( array( $kit, 't' ), (array) $schema['keywords'] ) : array(),
			'textdomain'            => $kit->config( 'text_domain' ),
			'attributes'            => self::attributes( $schema ),
			'supports'              => isset( $schema['supports'] ) ? (array) $schema['supports'] : array( 'html' => false ),
			'uses_context'          => array( 'postId', 'postType' ),
			'editor_script_handles' => array( $kit->editor_handle() ),
			'render_callback'       => static function ( $attributes, $content, $block ) use ( $element ) {
				return BlockAdapter::render( $element, (array) $attributes, $block );
			},
		);

		if ( $element->style_handle() ) {
			$args['style_handles'] = array( $element->style_handle() );
		}
		foreach ( array( 'example', 'variations', 'parent', 'ancestor' ) as $key ) {
			if ( isset( $schema[ $key ] ) ) {
				$args[ $key ] = $schema[ $key ];
			}
		}

		return $args;
	}

	/**
	 * Block attributes from the kit attributes (block support attributes are added by core).
	 *
	 * @param array $schema Schema.
	 * @return array
	 */
	public static function attributes( array $schema ) {
		$defaults = Schema::defaults( $schema );
		$out      = array();

		foreach ( Schema::attributes( $schema ) as $key => $def ) {
			$out[ $key ] = array(
				'type'    => $def['type'],
				'default' => $defaults[ $key ],
			);
			if ( 'array' === $def['type'] ) {
				$out[ $key ]['items'] = array( 'type' => 'string' );
			}
		}

		return $out;
	}

	/**
	 * Render callback: element markup inside the block wrapper.
	 *
	 * @param Element        $element    Element.
	 * @param array          $attributes Block attributes.
	 * @param \WP_Block|null $block      Block instance.
	 * @return string
	 */
	public static function render( Element $element, array $attributes, $block = null ) {
		$attrs = $element->normalize( $attributes );
		$ctx   = $element->kit()->context( $element, $attrs, $block );
		$html  = $element->render( $attrs, $ctx );

		if ( '' === $html ) {
			if ( ! $ctx->is_preview() ) {
				return '';
			}
			$html = $element->kit()->empty_preview( $element, $ctx );
		}

		return '<div ' . get_block_wrapper_attributes( array( 'class' => $element->kit()->classes( $element ) ) ) . '>' . $html . '</div>';
	}
}
