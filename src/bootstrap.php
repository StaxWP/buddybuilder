<?php
/**
 * BuddyBuilder 2.0 spike: block layouts next to Elementor. Loaded with BuddyPress, independent of Elementor.
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

require_once __DIR__ . '/Templates/PostType.php';
require_once __DIR__ . '/Templates/PreviewContext.php';
require_once __DIR__ . '/Templates/Dispatcher.php';
require_once __DIR__ . '/Elements/Renderer.php';

add_action( 'init', [ \Buddy_Builder\Templates\PostType::class, 'register' ] );
add_action( 'init', [ \Buddy_Builder\Elements\Renderer::class, 'register_blocks' ] );
add_filter( 'block_categories_all', [ \Buddy_Builder\Templates\PostType::class, 'category' ] );
add_action( 'enqueue_block_assets', [ \Buddy_Builder\Templates\PostType::class, 'editor_styles' ] );
add_filter( 'buddy_builder/settings', [ \Buddy_Builder\Templates\Dispatcher::class, 'filter_settings' ] );

/**
 * Render the template assigned to a BuddyPress template type (blocks or Elementor).
 *
 * @param string $type Template type.
 * @return string
 */
function bpb_render_template( $type ) {
	return \Buddy_Builder\Templates\Dispatcher::render( $type );
}

