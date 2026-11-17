<?php
/**
 * Shell of the BuddyBuilder screens: the app (header, tabs, screen title) around the screen content.
 *
 * Kept 1.x hooks: bpb_submenu (Pro module navigation), bpb_before_panel_action, bpb_panel_action,
 * bpb_after_panel_action. Variables: $menu (screens), $wrapper_class.
 *
 * @package Buddy_Builder
 */

use Buddy_Builder\Admin\App;

defined( 'ABSPATH' ) || die();

App::open( [ 'wrapper_class' => (array) $wrapper_class ] );

do_action( BPB_HOOK_PREFIX . 'submenu' );

do_action( BPB_HOOK_PREFIX . 'before_panel_action' );

do_action( BPB_HOOK_PREFIX . 'panel_action' );

do_action( BPB_HOOK_PREFIX . 'after_panel_action' );

App::close();
