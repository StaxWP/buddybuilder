<?php
/**
 * BuddyBuilder elements: one element.json + render.php per element (src/Elements/<name>/), shared by the blocks
 * (registered by the element kit) and the 1.x Elementor widgets (thin adapters, see Widgets\Base).
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Elements;

use Buddy_Builder\Features;
use Buddy_Builder\Kit\Context;
use Buddy_Builder\Kit\Element;
use Buddy_Builder\Kit\Kit;
use Buddy_Builder\Templates\Dispatcher;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\PreviewContext;

defined( 'ABSPATH' ) || die();

/**
 * Element kit instance and BuddyBuilder's context rules.
 */
final class Elements {

	/**
	 * Kit instance.
	 *
	 * @var Kit|null
	 */
	private static $kit = null;

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		require_once __DIR__ . '/helpers.php';

		// Built on init (translated labels); the kit hooks its own init callback at priority 10.
		add_action(
			'init',
			static function () {
				Elements::kit()->register();
			},
			1
		);

		add_filter( 'allowed_block_types_all', [ __CLASS__, 'allowed_blocks' ], 10, 2 );
		add_filter( 'buddy_builder_kit_render', [ __CLASS__, 'preview_wrapper' ], 10, 4 );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'editor_assets' ] );
	}

	/**
	 * The kit (BuddyBuilder's copy, namespace Buddy_Builder\Kit).
	 *
	 * @return Kit
	 */
	public static function kit() {
		if ( null === self::$kit ) {
			self::$kit = new Kit(
				[
					'namespace'         => 'buddybuilder',
					'prefix'            => 'buddy_builder_kit',
					'css_prefix'        => 'bpb',
					'text_domain'       => 'stax-buddy-builder',
					'dir'               => __DIR__,
					'url'               => BPB_BASE_URL . 'src/Elements',
					'kit_url'           => BPB_BASE_URL . 'src/Kit',
					'version'           => BPB_VERSION,
					'js_global'         => 'buddyBuilderKit',
					'block_category'    => 'buddybuilder',
					'elementor'         => false, // The 1.x widget classes are the Elementor adapters.
					'elementor_wrap'    => 'widget',
					'labels'            => [
						'basic'    => __( 'Settings', 'stax-buddy-builder' ),
						'advanced' => __( 'More settings', 'stax-buddy-builder' ), // Not "Advanced": core already has an Advanced panel.
						'empty'    => __( 'Nothing to show for this preview.', 'stax-buddy-builder' ),
					],
					'editor_query_args' => [
						'bpb_type'          => PostType::META_TYPE,
						'bpb_preview_user'  => PostType::META_USER,
						'bpb_preview_group' => PostType::META_GROUP,
					],
					'context'           => [ __CLASS__, 'context' ],
					'empty_preview'     => [ __CLASS__, 'empty_preview' ],
				]
			);
		}

		return self::$kit;
	}

	/**
	 * One element.
	 *
	 * @param string $name Element name.
	 * @return Element|null
	 */
	public static function get( $name ) {
		return self::kit()->get( $name );
	}

	/**
	 * Legacy Elementor widget name => element name (from "legacy" in element.json).
	 *
	 * @return array
	 */
	public static function legacy_map() {
		$map = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			foreach ( isset( $schema['legacy']['elementor'] ) ? (array) $schema['legacy']['elementor'] : [] as $widget ) {
				$map[ $widget ] = $element->name();
			}
		}

		return $map;
	}

	/**
	 * Render context: the editor preview context (preview member or group), the layout being rendered, and the raw
	 * Elementor settings when an Elementor widget renders (1.x hooks receive them unchanged).
	 *
	 * @param Element $element Element.
	 * @param array   $attrs   Attributes.
	 * @param mixed   $source  WP_Block, Elementor widget or null.
	 * @param Context $base    Kit context (preview, post_id).
	 * @return Context
	 */
	public static function context( Element $element, array $attrs, $source, Context $base ) {
		$data = array_merge( Dispatcher::current_context(), PreviewContext::ctx() );

		if ( $base->is_preview() ) {
			$data['preview'] = true;
		}

		if ( $source instanceof \Elementor\Widget_Base ) {
			$data['elementor_settings'] = $source->get_settings_for_display();
		}

		return $base->with( $data );
	}

	/**
	 * Editor previews: BuddyPress styles are scoped to #buddypress / .buddypress-wrap, which the BuddyPress page
	 * provides on the front end. Previews get the same wrapper (preview markup only; the front end is unchanged).
	 *
	 * @param string  $html    Markup.
	 * @param Element $element Element.
	 * @param array   $attrs   Attributes.
	 * @param Context $ctx     Context.
	 * @return string
	 */
	public static function preview_wrapper( $html, $element, $attrs, $ctx ) {
		if ( ! $ctx instanceof Context || ! $ctx->is_preview() || ! \Buddy_Builder\Kit\Kit::is_block_preview() ) {
			return $html;
		}

		return '<div id="buddypress" class="buddypress-wrap bp-dir-hori-nav">' . $html . '</div>';
	}

	/**
	 * Editor placeholder when an element printed nothing.
	 *
	 * @param Element $element Element.
	 * @param Context $ctx     Context.
	 * @return string
	 */
	public static function empty_preview( Element $element, Context $ctx ) {
		$schema = $element->schema();
		$types  = isset( $schema['templateTypes'] ) ? (array) $schema['templateTypes'] : [];
		$type   = (string) $ctx->get( 'type', '' );

		if ( $types && $type && ! in_array( $type, $types, true ) ) {
			$text = __( 'This block is made for another page type. Change the template type of this layout, or remove the block.', 'stax-buddy-builder' );
		} elseif ( in_array( 'member-profile', $types, true ) ) {
			$text = __( 'Pick a preview member in the BuddyBuilder panel (or create the sample member).', 'stax-buddy-builder' );
		} elseif ( in_array( 'group-profile', $types, true ) ) {
			$text = __( 'Pick a preview group in the BuddyBuilder panel (or create the sample member and groups).', 'stax-buddy-builder' );
		} else {
			$text = __( 'Nothing to show for this preview.', 'stax-buddy-builder' );
		}

		return '<p class="bpb-el-empty">' . esc_html( $element->title() . ': ' . $text ) . '</p>';
	}

	/**
	 * Inserter: BuddyBuilder page blocks only in block layouts; the general blocks (members, groups and activity
	 * lists, notices) everywhere when block layouts are enabled.
	 *
	 * @param bool|string[]            $allowed Allowed block types.
	 * @param \WP_Block_Editor_Context $context Editor context.
	 * @return bool|string[]
	 */
	public static function allowed_blocks( $allowed, $context ) {
		$post_type = isset( $context->post ) && $context->post ? $context->post->post_type : '';

		if ( PostType::POST_TYPE === $post_type ) {
			return $allowed;
		}

		$remove = [];

		foreach ( self::kit()->elements() as $element ) {
			$schema = $element->schema();

			if ( ! Features::blocks_enabled() || ! empty( $schema['templateTypes'] ) ) {
				$remove[] = $element->block_name();
			}
		}

		if ( ! $remove ) {
			return $allowed;
		}

		if ( true === $allowed ) {
			$allowed = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );
		}

		return is_array( $allowed ) ? array_values( array_diff( $allowed, $remove ) ) : $allowed;
	}

	/**
	 * Block editor of a layout: BuddyPress styles in the canvas and the "BuddyBuilder" document panel.
	 */
	public static function editor_assets() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'bpb-layout-panel',
			BPB_BASE_URL . 'src/Elements/layout-panel.js',
			[ 'wp-plugins', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data', 'wp-i18n', 'wp-api-fetch', 'wp-url' ],
			BPB_VERSION,
			true
		);

		$types = [];

		foreach ( \Buddy_Builder\Templates\Types::for_blocks() as $slug => $type ) {
			$types[] = [
				'value' => $slug,
				'label' => $type['name'],
			];
		}

		wp_add_inline_script(
			'bpb-layout-panel',
			'window.bpbLayout = ' . wp_json_encode(
				[
					'types'        => $types,
					'sampleMember' => \Buddy_Builder\Templates\SampleMember::user_id(),
				]
			) . ';',
			'before'
		);
	}
}
