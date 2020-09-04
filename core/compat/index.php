<?php
defined( 'ABSPATH' ) || die();

global $buddyboss_platform_plugin_file;
if ( isset( $buddyboss_platform_plugin_file ) ) {
	require_once BPB_BASE_PATH . 'core/compat/platform.php';
}
