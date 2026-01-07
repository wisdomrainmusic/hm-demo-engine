<?php
/**
 * Plugin Name: HM Demo Engine
 * Plugin URI:  https://github.com/wisdomrainmusic/hm-demo-engine
 * Description: Page group based color & typography preset engine for demo websites.
 * Version:     0.8.1
 * Author:      Wisdom Rain
 * Author URI:  https://wisdomrainmusic.com
 * License:     GPL v2 or later
 * Text Domain: hm-demo-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HM_DEMO_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'HM_DEMO_ENGINE_URL', plugin_dir_url( __FILE__ ) );
define( 'HM_DEMO_ENGINE_VERSION', '0.8.1' );

if ( is_admin() ) {
    require_once HM_DEMO_ENGINE_PATH . 'admin/admin-menu.php';
    require_once HM_DEMO_ENGINE_PATH . 'admin/presets.php';
    require_once HM_DEMO_ENGINE_PATH . 'admin/groups.php';
    require_once HM_DEMO_ENGINE_PATH . 'admin/page-group-metabox.php';
}

require_once HM_DEMO_ENGINE_PATH . 'frontend/body-class.php';
require_once HM_DEMO_ENGINE_PATH . 'frontend/css-engine.php';
require_once HM_DEMO_ENGINE_PATH . 'frontend/astra-mapping.php';
