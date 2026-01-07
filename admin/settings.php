<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HMDE_SETTINGS_OPTION_KEY = 'hmde_settings';

function hmde_get_settings() {
    $defaults = array(
        'mode' => 'inherit', // inherit|force
    );
    $opt = get_option( HMDE_SETTINGS_OPTION_KEY, array() );
    if ( ! is_array( $opt ) ) {
        $opt = array();
    }
    return array_merge( $defaults, $opt );
}

function hmde_save_settings( array $settings ) {
    update_option( HMDE_SETTINGS_OPTION_KEY, $settings, false );
}

function hmde_handle_settings_save() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['hmde_action'] ) && $_POST['hmde_action'] === 'save_settings' ) {
        check_admin_referer( 'hmde_save_settings' );

        $mode = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'inherit';
        if ( ! in_array( $mode, array( 'inherit', 'force' ), true ) ) {
            $mode = 'inherit';
        }

        hmde_save_settings( array( 'mode' => $mode ) );

        wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-settings&saved=1' ) );
        exit;
    }
}
add_action( 'admin_init', 'hmde_handle_settings_save' );

function hmde_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $settings = hmde_get_settings();
    ?>
    <div class="wrap">
        <h1>Settings</h1>

        <?php if ( isset( $_GET['saved'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>
        <?php endif; ?>

        <form method="post">
            <?php wp_nonce_field( 'hmde_save_settings' ); ?>
            <input type="hidden" name="hmde_action" value="save_settings" />

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">CSS Mode</th>
                    <td>
                        <label style="display:block;margin-bottom:6px;">
                            <input type="radio" name="mode" value="inherit" <?php checked( $settings['mode'], 'inherit' ); ?> />
                            Inherit Mode (theme-friendly)
                        </label>
                        <label style="display:block;">
                            <input type="radio" name="mode" value="force" <?php checked( $settings['mode'], 'force' ); ?> />
                            Force Mode (adds !important where needed)
                        </label>
                        <p class="description">Use Force Mode for demos where the theme overrides colors aggressively.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save Settings' ); ?>
        </form>
    </div>
    <?php
}
