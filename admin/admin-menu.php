<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register admin menu + submenus
 */
function hm_demo_engine_register_admin_menu() {

    add_menu_page(
        'Demo Engine',
        'Demo Engine',
        'manage_options',
        'hm-demo-engine',
        'hm_demo_engine_dashboard_page',
        'dashicons-admin-customizer',
        58
    );

    add_submenu_page(
        'hm-demo-engine',
        'Presets',
        'Presets',
        'manage_options',
        'hm-demo-engine-presets',
        'hm_demo_engine_presets_page'
    );

    add_submenu_page(
        'hm-demo-engine',
        'Groups',
        'Groups',
        'manage_options',
        'hm-demo-engine-groups',
        'hm_demo_engine_groups_page'
    );
}
add_action( 'admin_menu', 'hm_demo_engine_register_admin_menu' );

function hm_demo_engine_dashboard_page() {
    ?>
    <div class="wrap">
        <h1>HM Demo Engine</h1>
        <p>Plugin is active. Create Presets and Groups from the submenu.</p>
    </div>
    <?php
}

function hm_demo_engine_presets_page() {
    if ( function_exists( 'hmde_render_presets_page' ) ) {
        hmde_render_presets_page();
        return;
    }
    echo '<div class="wrap"><h1>Presets</h1><p>presets.php not loaded.</p></div>';
}

function hm_demo_engine_groups_page() {
    if ( function_exists( 'hmde_render_groups_page' ) ) {
        hmde_render_groups_page();
        return;
    }
    echo '<div class="wrap"><h1>Groups</h1><p>groups.php not loaded.</p></div>';
}
