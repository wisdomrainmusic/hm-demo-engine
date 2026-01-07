<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HMDE_PRESETS_OPTION_KEY = 'hmde_presets';

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    // Reliable gate: check the page slug directly
    $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

    if ( $page !== 'hm-demo-engine-presets' ) {
        return;
    }

    // WP color picker styles (sometimes not auto-enqueued)
    wp_enqueue_style( 'wp-color-picker' );

    // Safely compute plugin base URL (no constants required)
    $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

    wp_enqueue_style(
        'hmde-presets-admin',
        $plugin_url . 'admin/assets/presets-admin.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'hmde-presets-admin',
        $plugin_url . 'admin/assets/presets-admin.js',
        array( 'jquery', 'wp-color-picker' ),
        '1.0.0',
        true
    );
} );

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

                <?php
                $hmde_global_palettes = function_exists( 'hmde_get_global_palettes' ) ? hmde_get_global_palettes() : array();
                ?>

                <?php if ( ! empty( $hmde_global_palettes ) && is_array( $hmde_global_palettes ) ) : ?>
                    <tr>
                        <td colspan="2">
                            <div class="hmde-global-palettes">
                                <div class="hmde-global-palettes__header">
                                    <h2>Global Palettes</h2>
                                    <p class="description">Click-to-apply will be enabled in the next commit.</p>
                                </div>

                                <div class="hmde-global-palettes__grid">
                                    <?php foreach ( $hmde_global_palettes as $palette_key => $palette ) : ?>
                                        <?php
                                        $label  = isset( $palette['label'] ) ? (string) $palette['label'] : (string) $palette_key;
                                        $colors = isset( $palette['colors'] ) && is_array( $palette['colors'] ) ? $palette['colors'] : array();

                                        // Normalize expected keys (so UI is consistent)
                                        $primary = isset( $colors['primary'] ) ? (string) $colors['primary'] : '';
                                        $dark    = isset( $colors['dark'] ) ? (string) $colors['dark'] : '';
                                        $bg      = isset( $colors['background'] ) ? (string) $colors['background'] : '';
                                        $footer  = isset( $colors['footer'] ) ? (string) $colors['footer'] : '';
                                        $link    = isset( $colors['link'] ) ? (string) $colors['link'] : '';

                                        // Store colors for next commit (10.3 click-to-apply)
                                        $data_colors = wp_json_encode( array(
                                            'primary'    => $primary,
                                            'dark'       => $dark,
                                            'background' => $bg,
                                            'footer'     => $footer,
                                            'link'       => $link,
                                        ) );
                                        ?>
                                        <button
                                            type="button"
                                            class="hmde-palette-card"
                                            data-palette="<?php echo esc_attr( $palette_key ); ?>"
                                            data-colors="<?php echo esc_attr( $data_colors ); ?>"
                                        >
                                            <span class="hmde-palette-card__title"><?php echo esc_html( $label ); ?></span>

                                            <span class="hmde-palette-card__swatches" aria-hidden="true">
                                                <?php if ( $primary ) : ?><span class="hmde-swatch" style="background: <?php echo esc_attr( $primary ); ?>"></span><?php endif; ?>
                                                <?php if ( $dark ) : ?><span class="hmde-swatch" style="background: <?php echo esc_attr( $dark ); ?>"></span><?php endif; ?>
                                                <?php if ( $bg ) : ?><span class="hmde-swatch" style="background: <?php echo esc_attr( $bg ); ?>"></span><?php endif; ?>
                                                <?php if ( $footer ) : ?><span class="hmde-swatch" style="background: <?php echo esc_attr( $footer ); ?>"></span><?php endif; ?>
                                                <?php if ( $link ) : ?><span class="hmde-swatch" style="background: <?php echo esc_attr( $link ); ?>"></span><?php endif; ?>
                                            </span>

                                            <span class="hmde-palette-card__meta">
                                                <?php echo esc_html( trim( $primary . ' ' . $dark . ' ' . $bg ) ); ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td colspan="2">
                        <div class="hmde-current-palette-preview">
                            <span class="hmde-current-palette-label">Current Palette</span>

                            <div class="hmde-current-palette-swatches">
                                <span class="hmde-current-swatch" data-color-key="primary"></span>
                                <span class="hmde-current-swatch" data-color-key="dark"></span>
                                <span class="hmde-current-swatch" data-color-key="background"></span>
                                <span class="hmde-current-swatch" data-color-key="footer"></span>
                                <span class="hmde-current-swatch" data-color-key="link"></span>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr><th colspan="2"><h3>Colors</h3></th></tr>

                <tr>
                    <th scope="row"><label for="hmde_primary">Primary</label></th>
                    <td><input class="hmde-color-field" name="colors[primary]" id="hmde_primary" type="text" value="<?php echo esc_attr( $current['colors']['primary'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_dark">Dark</label></th>
                    <td><input class="hmde-color-field" name="colors[dark]" id="hmde_dark" type="text" value="<?php echo esc_attr( $current['colors']['dark'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_bg">Background</label></th>
                    <td><input class="hmde-color-field" name="colors[bg]" id="hmde_bg" type="text" value="<?php echo esc_attr( $current['colors']['bg'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_footer">Footer</label></th>
                    <td><input class="hmde-color-field" name="colors[footer]" id="hmde_footer" type="text" value="<?php echo esc_attr( $current['colors']['footer'] ); ?>" /></td>
                </tr>

                <tr>
                    <th scope="row"><label for="hmde_link">Link</label></th>
                    <td><input class="hmde-color-field" name="colors[link]" id="hmde_link" type="text" value="<?php echo esc_attr( $current['colors']['link'] ); ?>" /></td>
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
                    <th>Palette</th>
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
                        <td class="hmde-preset-palette-cell">
                            <?php
                            $p_primary = isset( $preset['colors']['primary'] ) ? (string) $preset['colors']['primary'] : '';
                            $p_dark    = isset( $preset['colors']['dark'] ) ? (string) $preset['colors']['dark'] : '';
                            $p_bg      = isset( $preset['colors']['bg'] ) ? (string) $preset['colors']['bg'] : '';
                            $p_footer  = isset( $preset['colors']['footer'] ) ? (string) $preset['colors']['footer'] : '';
                            $p_link    = isset( $preset['colors']['link'] ) ? (string) $preset['colors']['link'] : '';
                            if ( $p_bg === '' && isset( $preset['colors']['background'] ) ) {
                                $p_bg = (string) $preset['colors']['background'];
                            }
                            ?>

                            <span class="hmde-mini-palette" aria-hidden="true">
                                <?php if ( $p_primary ) : ?><span class="hmde-mini-swatch" style="background: <?php echo esc_attr( $p_primary ); ?>"></span><?php endif; ?>
                                <?php if ( $p_dark ) : ?><span class="hmde-mini-swatch" style="background: <?php echo esc_attr( $p_dark ); ?>"></span><?php endif; ?>
                                <?php if ( $p_bg ) : ?><span class="hmde-mini-swatch" style="background: <?php echo esc_attr( $p_bg ); ?>"></span><?php endif; ?>
                                <?php if ( $p_footer ) : ?><span class="hmde-mini-swatch" style="background: <?php echo esc_attr( $p_footer ); ?>"></span><?php endif; ?>
                                <?php if ( $p_link ) : ?><span class="hmde-mini-swatch" style="background: <?php echo esc_attr( $p_link ); ?>"></span><?php endif; ?>
                            </span>

                            <span class="hmde-mini-palette-codes">
                                <?php echo esc_html( trim( $p_primary . ' ' . $p_bg ) ); ?>
                            </span>
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
