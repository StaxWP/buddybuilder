<?php

/**
 * Admin class.
 *
 * @package Buddy_Builder
 * @since 1.0.0
 */

namespace Buddy_Builder;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Admin
 *
 * @package Buddy_Builder
 */
final class Admin extends Singleton {

	/**
	 * @var string
	 */
	public static $plugin_path;

	/**
	 * @var string
	 */
	private $current_slug = '';

	/*
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init' ] );

		if ( empty( self::$plugin_path ) ) {
			self::$plugin_path = trailingslashit( plugin_dir_path( BPB_FILE ) );
		}

		// Admin pages.
		require_once self::$plugin_path . '/admin/pages/Base.php';
		// Starter template kits are Elementor imports.
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			require_once self::$plugin_path . '/admin/pages/Templates.php';
		}
		require_once self::$plugin_path . '/admin/pages/Settings.php';
	}

	/**
	 * Init
	 */
	public function init() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 10 );
		add_action( 'admin_menu', [ $this, 'admin_menu_change_name' ], 200 );
		add_filter( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( BPB_HOOK_PREFIX . 'panel_action', [ $this, 'main_panel' ] );
		add_action( 'buddy_builder/admin/header_actions', [ $this, 'header_actions' ] );
	}

	/**
	 * Title buttons of the Community pages screen.
	 *
	 * @param string $page Screen slug.
	 */
	public function header_actions( $page ) {
		if ( Helpers::get_instance()->get_slug() === $page ) {
			\Buddy_Builder\Admin\CommunityPages::header_actions();
		}
	}

	/**
	 * Adds the menu item in the admin panel
	 */
	public function register_menu() {
		add_menu_page(
			__( 'BuddyBuilder - Community pages', 'stax-buddy-builder' ),
			__( 'BuddyBuilder', 'stax-buddy-builder' ),
			'manage_options',
			Helpers::get_instance()->get_slug(),
			[ $this, 'settings_template' ],
			\Buddy_Builder\Admin\App::LOGO,
			'58.9'
		);

		// Submenus, in the order of their priority (Community pages, Starter kits, Pro modules, Settings).
		$sub_menus = apply_filters( BPB_HOOK_PREFIX . 'admin_menu', [] );

		if ( ! empty( $sub_menus ) ) {
			usort(
				$sub_menus,
				static function ( $a, $b ) {
					return ( isset( $a['priority'] ) ? $a['priority'] : 10 ) - ( isset( $b['priority'] ) ? $b['priority'] : 10 );
				}
			);

			foreach ( $sub_menus as $submenu ) {
				$submenu_slug = 'buddy-builder-' . $submenu['slug'];

				if ( $submenu_slug === Helpers::get_instance()->get_slug() ) {
					continue;
				}
				add_submenu_page(
					Helpers::get_instance()->get_slug(),
					/* translators: %s: screen name */
					sprintf( __( 'BuddyBuilder - %s', 'stax-buddy-builder' ), wp_strip_all_tags( $submenu['name'] ) ),
					$submenu['name'],
					'manage_options',
					$submenu_slug,
					[ $this, 'settings_template' ]
				);
			}
		}
	}

	/**
	 * Change fist menu item name
	 */
	public function admin_menu_change_name() {
		global $submenu;

		if ( isset( $submenu[ Helpers::get_instance()->get_slug() ] ) ) {
			$submenu[ Helpers::get_instance()->get_slug() ][0][0] = __( 'Community pages', 'stax-buddy-builder' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	/**
	 * Settings template
	 */
	public function settings_template() {
		$site_url      = apply_filters( BPB_HOOK_PREFIX . 'admin_site_url', 'https://staxwp.com' );
		$wrapper_class = apply_filters( BPB_HOOK_PREFIX . 'welcome_wrapper_class', [ $this->current_slug ] );
		$menu          = apply_filters( BPB_HOOK_PREFIX . 'admin_menu', [] );

		if ( ! empty( $menu ) ) {
			usort(
				$menu,
				static function ( $a, $b ) {
					return $a['priority'] - $b['priority'];
				}
			);
		}

		Helpers::load_template(
			'admin/layout',
			[
				'site_url'      => $site_url,
				'wrapper_class' => $wrapper_class,
				'menu'          => $menu,
			]
		);
	}

	/**
	 * Check if plugin is PRO
	 *
	 * @return boolean
	 */
	public function has_pro() {
		return defined( 'BPB_PRO_VERSION' ) || function_exists( 'bpb_is_pro' );
	}

	/**
	 * Add body class when on plugin's settings page
	 *
	 * @param $classes
	 *
	 * @return string
	 */
	public function add_admin_body_class( $classes ) {
		if ( isset( $_GET['page'] ) && strpos( sanitize_text_field( $_GET['page'] ), 'buddy-builder' ) !== false ) {
			$classes .= ' stax-buddybuilder-admin-page';
		}

		return $classes;
	}

	/**
	 * Main template actions
	 */
	public function main_panel() {
		$current_slug = apply_filters( BPB_HOOK_PREFIX . 'current_slug', $this->current_slug );

		Helpers::load_template(
			'admin/actions',
			[
				'current_slug' => $current_slug,
			]
		);
	}

	/**
	 * Load scripts & styles
	 */
	public function admin_scripts() {
		$min = '.min';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$min = '';
		}

		// The 1.x Tailwind styles are only needed by BuddyBuilder Pro 1.x module screens (2.0 screens are native).
		if ( isset( $_GET['page'] ) && BPB_ADMIN_PREFIX . 'modules' === $_GET['page'] && defined( 'BPB_PRO_VERSION' ) && version_compare( BPB_PRO_VERSION, '2.0.0-dev', '<' ) ) {
			wp_register_style(
				'stax-buddy-addons-tw',
				BPB_ADMIN_ASSETS_URL . 'css/admin' . $min . '.css',
				[],
				BPB_VERSION,
				'all'
			);

			wp_enqueue_style( 'stax-buddy-addons-tw' );
		}

		wp_register_style(
			'stax-buddy-notice',
			BPB_ADMIN_ASSETS_URL . 'css/notice' . $min . '.css',
			[],
			BPB_VERSION,
			'all'
		);

		wp_enqueue_style( 'stax-buddy-notice' );

		wp_enqueue_script(
			'stax-buddy-admin',
			BPB_ADMIN_ASSETS_URL . 'js/admin.js',
			[ 'jquery' ],
			BPB_VERSION,
			true
		);
	}

}

Admin::get_instance();
