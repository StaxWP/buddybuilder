<?php
/**
 * Settings mirror (E8): a few element settings are BuddyPress options, not only markup. Both builders write them
 * through one function when the assigned layout is saved, and the Customizer writes them back into the layouts.
 *
 * - members/groups directory list columns => option bpb_listings (members_directory, groups_directory)
 * - member/group navigation "Home tab"     => option bp_nouveau_appearance (user_front_page, group_front_page)
 *
 * @package Buddy_Builder
 */

namespace Buddy_Builder\Settings;

use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;

defined( 'ABSPATH' ) || die();

/**
 * Settings mirror shared by the Elementor and block adapters.
 */
final class Mirror {

	/**
	 * Element or legacy widget name => mirrored setting.
	 */
	const MAP = [
		'bpb-members-directory-list'    => 'members_directory',
		'members-listing'               => 'members_directory',
		'bpb-groups-directory-list'     => 'groups_directory',
		'groups-listing'                => 'groups_directory',
		'bpb-profile-member-navigation' => 'user_front_page',
		'member-nav'                    => 'user_front_page',
		'bpb-profile-group-navigation'  => 'group_front_page',
		'group-nav'                     => 'group_front_page',
	];

	/**
	 * Register hooks.
	 */
	public static function hooks() {
		add_action( 'save_post_' . PostType::POST_TYPE, [ __CLASS__, 'on_block_layout_save' ], 20, 2 );
		add_action( 'buddy_builder/registry/changed', [ __CLASS__, 'on_assignment' ], 10, 3 );
		add_action( 'customize_save_after', [ __CLASS__, 'customizer_to_block_layouts' ] );
	}

	/**
	 * Mirror one element's settings into the BuddyPress options.
	 *
	 * @param string $name     Element name or legacy Elementor widget name.
	 * @param array  $settings Raw settings (Elementor 'yes'/'' and '3', or block attributes true/false and 3).
	 *                         Missing keys mean "not set": columns fall back to the 1.x defaults, the Home tab is
	 *                         left unchanged.
	 */
	public static function apply( $name, array $settings ) {
		if ( ! isset( self::MAP[ $name ] ) ) {
			return;
		}

		$target = self::MAP[ $name ];

		if ( in_array( $target, [ 'members_directory', 'groups_directory' ], true ) ) {
			$columns            = bpb_get_listing_columns();
			$columns[ $target ] = [
				'desktop' => self::column( $settings, 'columns', '3' ),
				'tablet'  => self::column( $settings, 'columns_tablet', '2' ),
				'mobile'  => self::column( $settings, 'columns_mobile', '1' ),
			];

			bpb_update_listing_columns( $columns );

			return;
		}

		if ( ! array_key_exists( 'show_home_tab', $settings ) ) {
			return;
		}

		$appearance            = bpb_get_appearance();
		$appearance            = is_array( $appearance ) ? $appearance : [];
		$appearance[ $target ] = self::truthy( $settings['show_home_tab'] ) ? 1 : 0;

		bpb_update_appearance( $appearance );
	}

	/**
	 * Block layout saved: mirror its elements when it is the assigned layout of its type.
	 *
	 * @param int      $post_id Post id.
	 * @param \WP_Post $post    Post.
	 */
	public static function on_block_layout_save( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || 'publish' !== $post->post_status ) {
			return;
		}

		$entry = Registry::get( PostType::type_of( $post_id ) );

		if ( Registry::BLOCKS === $entry['builder'] && (int) $post_id === $entry['id'] ) {
			self::from_blocks( $post->post_content );
		}
	}

	/**
	 * A layout was assigned: mirror it (a block layout here; Elementor layouts through the 1.x Settings page path).
	 *
	 * @param string $type Template type.
	 * @param array  $old  Previous assignment.
	 * @param array  $new  New assignment.
	 */
	public static function on_assignment( $type, $old, $new ) {
		if ( Registry::BLOCKS === $new['builder'] && $new['id'] ) {
			$post = get_post( $new['id'] );

			if ( $post ) {
				self::from_blocks( $post->post_content );
			}
		}
	}

	/**
	 * Mirror every BuddyBuilder block of a layout.
	 *
	 * @param string $content Block markup.
	 */
	public static function from_blocks( $content ) {
		foreach ( self::find_blocks( parse_blocks( (string) $content ) ) as $block ) {
			self::apply( substr( $block['blockName'], strlen( 'buddybuilder/' ) ), (array) $block['attrs'] );
		}
	}

	/**
	 * Customizer saved: write the Home tab choice back into the assigned block layouts (Elementor layouts are
	 * updated by CustomizerHooks, unchanged from 1.x).
	 */
	public static function customizer_to_block_layouts() {
		$appearance = bpb_get_appearance();

		foreach ( [ 'member-profile' => 'user_front_page', 'group-profile' => 'group_front_page' ] as $type => $key ) {
			$entry = Registry::get( $type );

			if ( Registry::BLOCKS !== $entry['builder'] || ! $entry['id'] || ! isset( $appearance[ $key ] ) ) {
				continue;
			}

			$post = get_post( $entry['id'] );

			if ( ! $post ) {
				continue;
			}

			$name    = 'member-profile' === $type ? 'buddybuilder/member-nav' : 'buddybuilder/group-nav';
			$value   = (bool) $appearance[ $key ];
			$blocks  = parse_blocks( $post->post_content );
			$changed = self::set_attribute( $blocks, $name, 'show_home_tab', $value );

			if ( $changed ) {
				remove_action( 'save_post_' . PostType::POST_TYPE, [ __CLASS__, 'on_block_layout_save' ], 20 );
				wp_update_post(
					[
						'ID'           => $post->ID,
						'post_content' => serialize_blocks( $blocks ),
					]
				);
				add_action( 'save_post_' . PostType::POST_TYPE, [ __CLASS__, 'on_block_layout_save' ], 20, 2 );
			}
		}
	}

	/**
	 * BuddyBuilder blocks of a block tree, nested ones included.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array
	 */
	private static function find_blocks( array $blocks ) {
		$found = [];

		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'buddybuilder/' ) ) {
				$found[] = $block;
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = array_merge( $found, self::find_blocks( $block['innerBlocks'] ) );
			}
		}

		return $found;
	}

	/**
	 * Set an attribute on every block with that name.
	 *
	 * @param array  $blocks Parsed blocks (by reference).
	 * @param string $name   Block name.
	 * @param string $key    Attribute.
	 * @param mixed  $value  Value.
	 * @return bool Whether anything changed.
	 */
	private static function set_attribute( array &$blocks, $name, $key, $value ) {
		$changed = false;

		foreach ( $blocks as &$block ) {
			if ( $name === $block['blockName'] && ( ! isset( $block['attrs'][ $key ] ) || $block['attrs'][ $key ] !== $value ) ) {
				$block['attrs'][ $key ] = $value;
				$changed                = true;
			}

			if ( ! empty( $block['innerBlocks'] ) && self::set_attribute( $block['innerBlocks'], $name, $key, $value ) ) {
				$changed = true;
			}
		}

		unset( $block );

		return $changed;
	}

	/**
	 * Column value as the 1.x string.
	 *
	 * @param array  $settings Settings.
	 * @param string $key      Key.
	 * @param string $default  Default.
	 * @return string
	 */
	private static function column( array $settings, $key, $default ) {
		return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? (string) $settings[ $key ] : $default;
	}

	/**
	 * Elementor switcher ('yes') or block boolean.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function truthy( $value ) {
		return true === $value || 'yes' === $value || 1 === $value || '1' === $value;
	}
}
