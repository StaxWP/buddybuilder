<?php
/**
 * BuddyBuilder 2.0 core: builder-agnostic parts, loaded with BuddyPress (no Elementor needed).
 *
 * @package Buddy_Builder
 */

defined( 'ABSPATH' ) || die();

use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\PreviewContext;

PostType::hooks();
PreviewContext::hooks();
\Buddy_Builder\Templates\LayoutPreview::hooks();
\Buddy_Builder\Templates\SampleMember::hooks();

add_filter( 'buddy_builder/settings', [ Dispatcher::class, 'filter_settings' ] );
add_shortcode( Dispatcher::SHORTCODE, [ Dispatcher::class, 'shortcode' ] );

\Buddy_Builder\Elements\Elements::hooks();
\Buddy_Builder\Settings\Mirror::hooks();
\Buddy_Builder\Assets\Assets::hooks();
\Buddy_Builder\Presets\Presets::hooks();
\Buddy_Builder\Admin\Screens::hooks();
\Buddy_Builder\Abilities\Abilities::hooks();
