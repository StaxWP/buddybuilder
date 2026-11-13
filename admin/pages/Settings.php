<?php

namespace Buddy_Builder;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

use Buddy_Builder\Features;
use Buddy_Builder\Admin\CommunityPages;
use Buddy_Builder\Templates\Registry;

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
     * Handle the 1.x settings form (kept for scripts and themes that post it): one value per template type,
     * "123" (Elementor template), "blocks:123" (block layout) or "0" (BuddyPress default).
     */
    public function handle_settings_request() {
        if ( $_SERVER['REQUEST_METHOD'] !== 'POST' || ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        $action   = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
        $did_save = '';

        if ( 'bpb_settings' === $action && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bpb_settings' ) ) {
            $settings = bpb_get_settings();

            foreach ( array_keys( $settings['templates'] ) as $type ) {
                if ( ! isset( $_POST[ $type ] ) ) {
                    continue;
                }

                $value   = sanitize_text_field( wp_unslash( $_POST[ $type ] ) );
                $builder = 0 === strpos( $value, 'blocks:' ) ? Registry::BLOCKS : Registry::ELEMENTOR;

                // Unchanged types are not written (assign() skips them).
                CommunityPages::assign( $type, $builder, absint( str_replace( 'blocks:', '', $value ) ) );
            }

            if ( Features::shows_blocks_toggle() ) {
                update_option( Features::BLOCKS_OPTION, empty( $_POST['bpb_blocks_enabled'] ) ? '0' : '1' );
            }

            $did_save = '&bpb_done=assign';
        }

        wp_redirect( admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . $this->current_slug . $did_save ) );
    }

    /**
     * Save notification of the 1.x form (the Community pages screen prints its own notices).
     */
    public function save_notification() {
    }

    /**
     * Panel: the Community pages screen.
     */
    public function panel_content() {
        CommunityPages::render();
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
            'name'     => __( 'Community pages', 'stax-buddy-builder' ),
            'link'     => admin_url( 'admin.php?page=' . BPB_ADMIN_PREFIX . $this->current_slug ),
            'query'    => BPB_ADMIN_PREFIX . $this->current_slug,
            'priority' => 1,
        ];

        return $menu;
    }


}

Settings::get_instance();
