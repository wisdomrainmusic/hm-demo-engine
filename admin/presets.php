<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HMDE_PRESETS_OPTION_KEY = 'hmde_presets';

/**
 * Admin assets (color picker)
 */
function hmde_admin_enqueue_assets( $hook ) {
    // Only on our presets page
    if ( strpos( $hook, 'hm-demo-engine_page_hm-demo-engine-presets' ) === false ) {
        return;
    }

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
    wp_add_inline_script( 'wp-color-picker', 'jQuery(function($){ $(".hmde-color").wpColorPicker(); });' );
}
add_action( 'admin_enqueue_scripts', 'hmde_admin_enqueue_assets' );

/**
 * Data helpers
 */
function hmde_get_presets() {
    $presets = get_option( HMDE_PRESETS_OPTION_KEY, array() );
    return is_array( $presets ) ? $presets : array();
}

function hmde_save_presets( array $presets ) {
    update_option( HMDE_PRESETS_OPTION_KEY, $presets, false );
}

function hmde_generate_id() {
    return substr( md5( uniqid( 'hmde_', true ) ), 0, 10 );
}

function hmde_font_choices() {
    // Lightweight list for v1. Expand later (Google Fonts API etc.)
    return array(
        'System Default'        => 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
        'Inter'                 => '"Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
        'Roboto'                => '"Roboto", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Open Sans'             => '"Open Sans", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Lato'                  => '"Lato", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Montserrat'            => '"Montserrat", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Playfair Display'      => '"Playfair Display", Georgia, serif',
        'Merriweather'          => '"Merriweather", Georgia, serif',
        'Poppins'               => '"Poppins", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
    );
}

/**
 * Sanitizers
 */
function hmde_sanitize_preset_payload( array $raw ) {
    $fonts = hmde_font_choices();

    $name = isset( $raw['name'] ) ? sanitize_text_field( $raw['name'] ) : '';
    if ( $name === '' ) {
        $name = 'Untitled Preset';
    }

    $colors   = isset( $raw['colors'] ) && is_array( $raw['colors'] ) ? $raw['colors'] : array();
    $fonts_in = isset( $raw['fonts'] ) && is_array( $raw['fonts'] ) ? $raw['fonts'] : array();

    $out = array(
        'name'   => $name,
        'colors' => array(
            'primary' => isset( $colors['primary'] ) ? sanitize_hex_color( $colors['primary'] ) : '',
            'dark'    => isset( $colors['dark'] ) ? sanitize_hex_color( $colors['dark'] ) : '',
            'bg'      => isset( $colors['bg'] ) ? sanitize_hex_color( $colors['bg'] ) : '',
            'footer'  => isset( $colors['footer'] ) ? sanitize_hex_color( $colors['footer'] ) : '',
            'link'    => isset( $colors['link'] ) ? sanitize_hex_color( $colors['link'] ) : '',
        ),
        'fonts'  => array(
            'body_font'    => isset( $fonts_in['body_font'] ) ? sanitize_text_field( $fonts_in['body_font'] ) : '',
            'heading_font' => isset( $fonts_in['heading_font'] ) ? sanitize_text_field( $fonts_in['heading_font'] ) : '',
        ),
    );

    // Hard-validate font values (must exist in choices)
    if ( ! isset( $fonts[ $out['fonts']['body_font'] ] ) ) {
        $out['fonts']['body_font'] = 'System Default';
    }
    if ( ! isset( $fonts[ $out['fonts']['heading_font'] ] ) ) {
        $out['fonts']['heading_font'] = 'System Default';
    }

    // Ensure empty colors become empty string (sanitize_hex_color returns null if invalid)
    foreach ( $out['colors'] as $k => $v ) {
        if ( $v === null ) {
            $out['colors'][ $k ] = '';
        }
    }

    return $out;
}

/**
 * Actions: save / delete
 */
function hmde_handle_presets_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Save preset
    if ( isset( $_POST['hmde_action'] ) && $_POST['hmde_action'] === 'save_preset' ) {
        check_admin_referer( 'hmde_save_preset' );

        $presets = hmde_get_presets();
        $id      = isset( $_POST['preset_id'] ) ? sanitize_text_field( $_POST['preset_id'] ) : '';

        $payload = hmde_sanitize_preset_payload( array(
            'name'   => $_POST['name'] ?? '',
            'colors' => $_POST['colors'] ?? array(),
            'fonts'  => $_POST['fonts'] ?? array(),
        ) );

        if ( $id === '' || ! isset( $presets[ $id ] ) ) {
            $id = hmde_generate_id();
        }

        $presets[ $id ] = array_merge(
            array( 'id' => $id ),
            $payload
        );

        hmde_save_presets( $presets );

        wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&updated=1' ) );
        exit;
    }

    // Delete preset
    if ( isset( $_GET['hmde_action'] ) && $_GET['hmde_action'] === 'delete_preset' ) {
        $id = isset( $_GET['preset_id'] ) ? sanitize_text_field( $_GET['preset_id'] ) : '';
        check_admin_referer( 'hmde_delete_preset_' . $id );

        $presets = hmde_get_presets();
        if ( $id !== '' && isset( $presets[ $id ] ) ) {
            unset( $presets[ $id ] );
            hmde_save_presets( $presets );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&deleted=1' ) );
        exit;
    }
}
add_action( 'admin_init', 'hmde_handle_presets_actions' );

/**
 * Renderer
 */
function hmde_render_presets_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $presets = hmde_get_presets();
    $fonts   = hmde_font_choices();

    $edit_id = isset( $_GET['edit'] ) ? sanitize_text_field( $_GET['edit'] ) : '';
    $editing = ( $edit_id !== '' && isset( $presets[ $edit_id ] ) );

    $current = $editing ? $presets[ $edit_id ] : array(
        'id'     => '',
        'name'   => '',
        'colors' => array(
            'primary' => '',
            'dark'    => '',
            'bg'      => '',
            'footer'  => '',
            'link'    => '',
        ),
        'fonts'  => array(
            'body_font'    => 'System Default',
            'heading_font' => 'System Default',
        ),
    );

    ?>
    <div class="wrap">
        <h1>Presets</h1>

        <?php if ( isset( $_GET['updated'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Preset saved.</p></div>
        <?php endif; ?>

        <?php if ( isset( $_GET['deleted'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Preset deleted.</p></div>
        <?php endif; ?>

        <h2><?php echo $editing ? 'Edit Preset' : 'Add New Preset'; ?></h2>

        <form method="post">
            <?php wp_nonce_field( 'hmde_save_preset' ); ?>
            <input type="hidden" name="hmde_action" value="save_preset" />
            <input type="hidden" name="preset_id" value="<?php echo esc_attr( $current['id'] ); ?>" />

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="hmde_name">Name</label></th>
                    <td>
                        <input name="name" id="hmde_name" type="text" class="regular-text" value="<?php echo esc_attr( $current['name'] ); ?>" required />
                    </td>
                </tr>

                <tr><th colspan="2"><h3>Colors</h3></th></tr>

                <tr>
                    <th scope="row"><label for="hmde_primary">Primary</label></th>
                    <td><input class="hmde-color" name="colors[primary]" id="hmde_primary" type="text" value="<?php echo esc_attr( $current['colors']['primary'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_dark">Dark</label></th>
                    <td><input class="hmde-color" name="colors[dark]" id="hmde_dark" type="text" value="<?php echo esc_attr( $current['colors']['dark'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_bg">Background</label></th>
                    <td><input class="hmde-color" name="colors[bg]" id="hmde_bg" type="text" value="<?php echo esc_attr( $current['colors']['bg'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_footer">Footer</label></th>
                    <td><input class="hmde-color" name="colors[footer]" id="hmde_footer" type="text" value="<?php echo esc_attr( $current['colors']['footer'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_link">Link</label></th>
                    <td><input class="hmde-color" name="colors[link]" id="hmde_link" type="text" value="<?php echo esc_attr( $current['colors']['link'] ); ?>" /></td>
                </tr>

                <tr><th colspan="2"><h3>Fonts</h3></th></tr>

                <tr>
                    <th scope="row"><label for="hmde_body_font">Body Font</label></th>
                    <td>
                        <select name="fonts[body_font]" id="hmde_body_font">
                            <?php foreach ( $fonts as $label => $css_stack ) : ?>
                                <option value="<?php echo esc_attr( $label ); ?>" <?php selected( $current['fonts']['body_font'], $label ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Font loading will be handled in a later commit. For now we store the selection.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_heading_font">Heading Font</label></th>
                    <td>
                        <select name="fonts[heading_font]" id="hmde_heading_font">
                            <?php foreach ( $fonts as $label => $css_stack ) : ?>
                                <option value="<?php echo esc_attr( $label ); ?>" <?php selected( $current['fonts']['heading_font'], $label ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button( $editing ? 'Update Preset' : 'Create Preset' ); ?>
        </form>

        <hr />

        <h2>Preset List</h2>

        <?php if ( empty( $presets ) ) : ?>
            <p>No presets yet.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Primary</th>
                    <th>Background</th>
                    <th>Fonts</th>
                    <th style="width: 220px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $presets as $id => $preset ) : ?>
                    <tr>
                        <td><?php echo esc_html( $preset['name'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $preset['colors']['primary'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $preset['colors']['bg'] ?? '' ); ?></td>
                        <td>
                            <?php
                            $bf = $preset['fonts']['body_font'] ?? 'System Default';
                            $hf = $preset['fonts']['heading_font'] ?? 'System Default';
                            echo esc_html( $bf . ' / ' . $hf );
                            ?>
                        </td>
                        <td>
                            <?php
                            $edit_url = admin_url( 'admin.php?page=hm-demo-engine-presets&edit=' . urlencode( $id ) );
                            $del_url  = wp_nonce_url(
                                admin_url( 'admin.php?page=hm-demo-engine-presets&hmde_action=delete_preset&preset_id=' . urlencode( $id ) ),
                                'hmde_delete_preset_' . $id
                            );
                            ?>
                            <a class="button" href="<?php echo esc_url( $edit_url ); ?>">Edit</a>
                            <a class="button button-link-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('Delete this preset?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
    <?php
}
