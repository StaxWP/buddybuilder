<?php

namespace Buddy_Builder;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Buddy_Builder\Features;
use Buddy_Builder\Templates\PostType;
use Buddy_Builder\Templates\Registry;
use WP_Query;

/**
 * Class Settings
 *
 * @package Buddy_Builder
 */
class Settings extends Base {

    /**
     * Settings constructor.
     */
    public function __construct() {
        $this->current_slug = 'settings';

        if ( Helpers::get_instance()->is_current_page( $this->current_slug ) ) {
            add_filter( BPB_HOOK_PREFIX . 'current_slug', [ $this, 'set_page_slug' ] );
            add_filter( BPB_HOOK_PREFIX . 'welcome_wrapper_class', [ $this, 'set_wrapper_classes' ] );

            add_action( BPB_HOOK_PREFIX . $this->current_slug . '_page_content_before', [
                $this,
                'save_notification'
            ] );
            add_action( BPB_HOOK_PREFIX . $this->current_slug . '_page_content', [ $this, 'panel_content' ] );
        }

        add_filter( BPB_HOOK_PREFIX . 'admin_menu', [ $this, 'add_menu_item' ] );
        add_action( 'admin_post_bpb_settings', [ $this, 'handle_settings_request' ] );
    }

    /**
     * Handle settings save
     */
    public function handle_settings_request() {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        $action   = sanitize_text_field( $_POST['action'] );
        $did_save = '';

        if ( ! empty( $action ) && 'bpb_settings' === $action && isset( $_POST['_wpnonce'] )
             && wp_verify_nonce( $_POST['_wpnonce'], 'bpb_settings' ) ) {
            $settings = bpb_get_settings();

            foreach ( array_keys( $settings['templates'] ) as $type ) {
                if ( ! isset( $_POST[ $type ] ) ) {
                    continue;
                }

                // Values: "123" (Elementor template, 1.x format), "blocks:123" (block layout) or "0" (BuddyPress default).
                $value   = sanitize_text_field( wp_unslash( $_POST[ $type ] ) );
                $builder = 0 === strpos( $value, 'blocks:' ) ? Registry::BLOCKS : Registry::ELEMENTOR;
                $id      = absint( str_replace( 'blocks:', '', $value ) );

                Registry::set( $type, $builder, $id );

                if ( $id && Registry::ELEMENTOR === $builder && class_exists( '\\Buddy_Builder\\ElementorHooks' ) ) {
                    ElementorHooks::get_instance()->save_buddypress_options( $id, null );
                }
            }

            if ( Features::shows_blocks_toggle() ) {
                update_option( Features::BLOCKS_OPTION, empty( $_POST['bpb_blocks_enabled'] ) ? '0' : '1' );
            }

            $did_save = '&did_save';
        }

        wp_redirect( admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . $this->current_slug . $did_save ) );
    }

    /**
     * Save settings notification
     */
    public function save_notification() {
        if ( isset( $_GET['did_save'] ) ) {
            Helpers::load_template(
                'admin/pages/templates/parts/notification-success',
                [
                    'message' => __( 'YEY! Your settings have been saved successfully!', 'stax-buddy-builder' ),
                ]
            );
        }
    }

    /**
     * Panel template
     */
    public function panel_content() {
        $settings = bpb_get_settings();

        $templates = [
            'member-profile'    => [
                'title' => __( 'Member Profile', 'stax-buddy-builder' ),
                'order' => 2,
            ],
            'members-directory' => [
                'title' => __( 'Members Directory', 'stax-buddy-builder' ),
                'order' => 1,
            ],
            'group-profile'     => [
                'title' => __( 'Group Profile', 'stax-buddy-builder' ),
                'order' => 4,
            ],
            'groups-directory'  => [
                'title' => __( 'Groups Directory', 'stax-buddy-builder' ),
                'order' => 3,
            ],
            'sitewide-activity' => [
                'title' => __( 'Site-Wide Activity', 'stax-buddy-builder' ),
                'order' => 5,
            ],
        ];

        $templates = apply_filters( 'buddy_builder/admin/templates_list', $templates );

        // Set active template
        foreach ( $templates as $key => $item ) {
            if ( isset( $settings['templates'][ $key ] ) ) {
                $templates[ $key ]['template'] = (int) $settings['templates'][ $key ];
            }
        }

        $data = [];

        foreach ( $templates as $slug => $item ) {
            $entry = Registry::get( $slug );
            $posts = [];

            foreach ( $this->get_layouts( $slug ) as $post ) {
                $builder = Registry::builder_of( $post->ID );
                $posts[] = [
                    'id'      => $post->ID,
                    'title'   => $post->post_title ? $post->post_title : __( '(no title)', 'stax-buddy-builder' ),
                    'builder' => $builder,
                    'value'   => Registry::BLOCKS === $builder ? 'blocks:' . $post->ID : (string) $post->ID,
                    'status'  => $post->ID === $entry['id'] && $builder === $entry['builder'],
                ];
            }

            $data[ $slug ] = [
                'title' => $item['title'],
                'pro'   => isset( $item['pro'] ) && $item['pro'],
                'order' => $item['order'],
                'posts' => $posts,
            ];
        }

        // Restructure templates.
        $new_data = [];

        $new_data['members'] = [
            'inner' => [
                'members-directory' => $data['members-directory'],
            ],
            'order' => $data['members-directory']['order'],
        ];

        $new_data['groups'] = [
            'inner' => [
                'groups-directory' => $data['groups-directory'],
            ],
            'order' => $data['groups-directory']['order'],
        ];

        $new_data['sitewide'] = [
            'inner' => [
                'sitewide-activity' => $data['sitewide-activity'],
            ],
            'order' => $data['sitewide-activity']['order'],
        ];

        unset( $data['members-directory'], $data['groups-directory'], $data['sitewide-activity'] );

        $new_data = apply_filters( 'buddy_builder/admin/templates_list/after_restructure', array_merge( $new_data, $data ) );

        uasort(
            $new_data,
            static function ( $a, $b ) {
                return $a['order'] - $b['order'];
            }
        );

        Helpers::load_template(
            'admin/pages/templates/' . $this->current_slug,
            [
                'data'          => $new_data,
                'has_elementor' => defined( 'ELEMENTOR_VERSION' ),
                'has_blocks'    => Features::blocks_enabled(),
                'blocks_toggle' => Features::shows_blocks_toggle(),
            ]
        );
    }

    /**
     * Layouts that can be assigned to a type: Elementor templates (when Elementor is active) and block layouts.
     *
     * @param string $type Template type.
     * @return \WP_Post[]
     */
    private function get_layouts( $type ) {
        $posts = [];

        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            $query = new WP_Query(
                [
                    'post_type'      => 'elementor_library',
                    'posts_per_page' => 100,
                    'no_found_rows'  => true,
                    'meta_query'     => [
                        [
                            'key'   => '_elementor_template_type',
                            'value' => 'bpb-buddypress',
                        ],
                        [
                            'key'   => '_bpb_page',
                            'value' => $type,
                        ],
                    ],
                ]
            );

            $posts = $query->posts;
        }

        $blocks = new WP_Query(
            [
                'post_type'      => PostType::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => 100,
                'no_found_rows'  => true,
                'meta_query'     => [
                    [
                        'key'   => PostType::META_TYPE,
                        'value' => $type,
                    ],
                ],
            ]
        );

        return array_merge( $posts, $blocks->posts );
    }

    /**
     * Panel menu item
     *
     * @param $menu
     *
     * @return array
     */
    public function add_menu_item( $menu ) {
        $menu[] = [
            'slug'     => $this->current_slug,
            'name'     => __( 'Settings', 'stax-buddy-builder' ),
            'link'     => admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . $this->current_slug ),
            'query'    => BPB_ADMIN_PREFIX . $this->current_slug,
            'priority' => 1,
        ];

        return $menu;
    }


}

Settings::get_instance();
