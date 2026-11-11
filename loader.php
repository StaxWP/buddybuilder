<?php
/**
 * Plugin Name: BuddyBuilder - BuddyPress Builder
 * Description: Build and customize your BuddyPress community pages with the block editor or Elementor.
 * Plugin URI: https://staxwp.com/go/buddybuilder/
 * Author: StaxWP
 * Author URI: https://staxwp.com
 * Version: 2.0.0-dev
 * Requires at least: 6.6
 * Requires PHP: 7.4
 *
 * Elementor tested up to: 4.4.0
 * Elementor Pro tested up to: 4.4.0
 *
 * Text Domain: stax-buddy-builder
 */

define( 'BPB_VERSION', '2.0.0-dev' );

/**
 * Integer API version checked by BuddyBuilder Pro and other add-ons (not the marketing version).
 */
define( 'BPB_API_VERSION', 2 );
define( 'BPB_HOOK_PREFIX', 'bpb_' );
define( 'BPB_ADMIN_PREFIX', 'buddy-builder-' );

define( 'BPB_FILE', __FILE__ );
define( 'BPB_BASE_URL', plugins_url( '/', BPB_FILE ) );
define( 'BPB_BASE_PATH', plugin_dir_path( BPB_FILE ) );
define( 'BPB_ASSETS_URL', BPB_BASE_URL . 'assets/' );
define( 'BPB_ADMIN_ASSETS_URL', BPB_BASE_URL . 'admin/assets/' );

require_once BPB_BASE_PATH . 'src/autoload.php';
require_once BPB_BASE_PATH . 'core/Plugin.php';

// Compatibility layer: migrations, public surface shims, Free/Pro pairing, WP-CLI, status screen.
if ( file_exists( BPB_BASE_PATH . 'src/Compat/bootstrap.php' ) ) {
	require_once BPB_BASE_PATH . 'src/Compat/bootstrap.php';
}

/**
 * Returns the Plugin application instance.
 *
 * @return \Buddy_Builder\Plugin
 * @since 1.0.0
 */
function buddy_builder() {
	return \Buddy_Builder\Plugin::get_instance();
}

/**
 * Plugin localization
 *
 * @return void
 */
function buddybuilder_load_plugin_textdomain() {
	load_plugin_textdomain( 'stax-buddy-builder', false, basename( __DIR__ ) . '/languages/' );
}
add_action( 'init', 'buddybuilder_load_plugin_textdomain', 0 );

/**
 * Initializes the Plugin application.
 *
 * @since 1.0.0
 */
buddy_builder();
