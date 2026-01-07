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
    $ver        = defined( 'HM_DEMO_ENGINE_VERSION' ) ? HM_DEMO_ENGINE_VERSION : time();

    wp_enqueue_style(
        'hmde-presets-admin',
        $plugin_url . 'admin/assets/presets-admin.css',
        array(),
        $ver
    );

    wp_enqueue_script(
        'hmde-presets-admin',
        $plugin_url . 'admin/assets/presets-admin.js',
        array( 'jquery', 'wp-color-picker' ),
        $ver,
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

/**
 * CSV helpers
 */
function hmde_strip_utf8_bom( $text ) {
    if ( ! is_string( $text ) || $text === '' ) {
        return $text;
    }
    // UTF-8 BOM: EF BB BF
    if ( substr( $text, 0, 3 ) === "\xEF\xBB\xBF" ) {
        return substr( $text, 3 );
    }
    return $text;
}

function hmde_normalize_csv_header_key( $key ) {
    $key = strtolower( trim( (string) $key ) );
    $key = preg_replace( '/\s+/', '_', $key );

    // Synonyms
    if ( $key === 'background' ) {
        $key = 'bg';
    }

    return $key;
}

function hmde_csv_row_to_preset_payload( array $row ) {
    // Expect normalized keys: name, primary, dark, bg, footer, link, body_font, heading_font
    $name = isset( $row['name'] ) ? (string) $row['name'] : '';

    return hmde_sanitize_preset_payload( array(
        'name'   => $name,
        'colors' => array(
            'primary' => $row['primary'] ?? '',
            'dark'    => $row['dark'] ?? '',
            'bg'      => $row['bg'] ?? '',
            'footer'  => $row['footer'] ?? '',
            'link'    => $row['link'] ?? '',
        ),
        'fonts'  => array(
            'body_font'    => $row['body_font'] ?? 'System Default',
            'heading_font' => $row['heading_font'] ?? 'System Default',
        ),
    ) );
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

    // Download CSV template
    if ( isset( $_GET['hmde_action'] ) && $_GET['hmde_action'] === 'download_csv_template' ) {
        check_admin_referer( 'hmde_download_csv_template' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'hm-demo-engine' ) );
        }

        $filename = 'hmde-presets-template.csv';

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        $out = fopen( 'php://output', 'w' );

        // Header
        fputcsv( $out, array(
            'name',
            'primary',
            'dark',
            'bg',
            'footer',
            'link',
            'body_font',
            'heading_font',
        ) );

        // Example row
        fputcsv( $out, array(
            'Women Fashion – W01',
            '#E91E63',
            '#111827',
            '#FFFFFF',
            '#0B1220',
            '#2563EB',
            'Inter',
            'Poppins',
        ) );

        fclose( $out );
        exit;
    }

    // Import presets from CSV (UI added in prior commit)
    if ( isset( $_POST['hmde_action'] ) && $_POST['hmde_action'] === 'import_csv' ) {
        check_admin_referer( 'hmde_import_csv' );

        $mode = isset( $_POST['hmde_import_mode'] ) ? sanitize_text_field( $_POST['hmde_import_mode'] ) : 'update';
        $mode = in_array( $mode, array( 'update', 'create' ), true ) ? $mode : 'update';

        if ( empty( $_FILES['hmde_csv'] ) || ! isset( $_FILES['hmde_csv']['tmp_name'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=missing_file' ) );
            exit;
        }

        $file_name = isset( $_FILES['hmde_csv']['name'] ) ? (string) $_FILES['hmde_csv']['name'] : '';
        $tmp_name  = (string) $_FILES['hmde_csv']['tmp_name'];

        // Basic extension check
        $ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
        if ( $ext !== 'csv' ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=invalid_type' ) );
            exit;
        }

        if ( ! is_uploaded_file( $tmp_name ) || ! file_exists( $tmp_name ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=upload_failed' ) );
            exit;
        }

        $handle = fopen( $tmp_name, 'r' );
        if ( ! $handle ) {
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=cannot_read' ) );
            exit;
        }

        $presets        = hmde_get_presets();
        $imported_count = 0;
        $updated_count  = 0;
        $skipped_count  = 0;

        // Read header
        $header = fgetcsv( $handle );
        if ( ! is_array( $header ) || empty( $header ) ) {
            fclose( $handle );
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=missing_header' ) );
            exit;
        }

        // Normalize header keys
        $header = array_map( 'hmde_strip_utf8_bom', $header );
        $keys   = array();
        foreach ( $header as $h ) {
            $keys[] = hmde_normalize_csv_header_key( $h );
        }

        // Minimal requirement: at least a name column
        if ( ! in_array( 'name', $keys, true ) ) {
            fclose( $handle );
            wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-presets&csv_import_error=missing_name_column' ) );
            exit;
        }

        // Build a lookup for update-by-name (case-insensitive)
        $name_to_id = array();
        foreach ( $presets as $pid => $preset ) {
            $pname = isset( $preset['name'] ) ? strtolower( trim( (string) $preset['name'] ) ) : '';
            if ( $pname !== '' ) {
                $name_to_id[ $pname ] = $pid;
            }
        }

        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            if ( ! is_array( $data ) ) {
                $skipped_count++;
                continue;
            }

            // Skip fully empty rows
            $all_empty = true;
            foreach ( $data as $cell ) {
                if ( trim( (string) $cell ) !== '' ) {
                    $all_empty = false;
                    break;
                }
            }
            if ( $all_empty ) {
                continue;
            }

            // Map row to associative array using header keys
            $row = array();
            foreach ( $keys as $idx => $key ) {
                if ( $key === '' ) {
                    continue;
                }
                $row[ $key ] = isset( $data[ $idx ] ) ? trim( (string) $data[ $idx ] ) : '';
            }

            $name_raw = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
            if ( $name_raw === '' ) {
                $skipped_count++;
                continue;
            }

            $payload  = hmde_csv_row_to_preset_payload( $row );
            $name_key = strtolower( trim( (string) $payload['name'] ) );

            if ( $mode === 'update' && $name_key !== '' && isset( $name_to_id[ $name_key ] ) ) {
                $existing_id = $name_to_id[ $name_key ];
                $presets[ $existing_id ] = array_merge( array( 'id' => $existing_id ), $payload );
                $updated_count++;
            } else {
                $new_id = hmde_generate_id();
                $presets[ $new_id ] = array_merge( array( 'id' => $new_id ), $payload );
                $imported_count++;

                // Keep lookup fresh for subsequent rows
                if ( $mode === 'update' && $name_key !== '' ) {
                    $name_to_id[ $name_key ] = $new_id;
                }
            }
        }

        fclose( $handle );

        hmde_save_presets( $presets );

        $redirect = add_query_arg(
            array(
                'page'         => 'hm-demo-engine-presets',
                'csv_imported' => $imported_count,
                'csv_updated'  => $updated_count,
                'csv_skipped'  => $skipped_count,
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $redirect );
        exit;
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

    // CSV import notices (success)
    $csv_imported = isset( $_GET['csv_imported'] ) ? intval( $_GET['csv_imported'] ) : null;
    $csv_updated  = isset( $_GET['csv_updated'] ) ? intval( $_GET['csv_updated'] ) : null;
    $csv_skipped  = isset( $_GET['csv_skipped'] ) ? intval( $_GET['csv_skipped'] ) : null;

    if ( $csv_imported !== null || $csv_updated !== null || $csv_skipped !== null ) {
        $imported = max( 0, (int) $csv_imported );
        $updated  = max( 0, (int) $csv_updated );
        $skipped  = max( 0, (int) $csv_skipped );

        $msg = sprintf(
            /* translators: 1: imported count, 2: updated count, 3: skipped count */
            __( 'CSV import completed. Imported: %1$d, Updated: %2$d, Skipped: %3$d', 'hm-demo-engine' ),
            $imported,
            $updated,
            $skipped
        );

        $class = ( $skipped > 0 ) ? 'notice notice-warning is-dismissible' : 'notice notice-success is-dismissible';
        echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $msg ) . '</p></div>';
    }

    // CSV import notices (errors)
    if ( isset( $_GET['csv_import_error'] ) ) {
        $code = sanitize_text_field( (string) $_GET['csv_import_error'] );

        $map = array(
            'missing_file'        => __( 'CSV import failed: no file was uploaded.', 'hm-demo-engine' ),
            'invalid_type'        => __( 'CSV import failed: invalid file type. Please upload a .csv file.', 'hm-demo-engine' ),
            'upload_failed'       => __( 'CSV import failed: upload validation failed.', 'hm-demo-engine' ),
            'cannot_read'         => __( 'CSV import failed: could not read the uploaded file.', 'hm-demo-engine' ),
            'missing_header'      => __( 'CSV import failed: missing header row.', 'hm-demo-engine' ),
            'missing_name_column' => __( 'CSV import failed: the CSV must include a "name" column.', 'hm-demo-engine' ),
        );

        $msg = isset( $map[ $code ] ) ? $map[ $code ] : __( 'CSV import failed: unknown error.', 'hm-demo-engine' );
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
    }

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
                                    <p class="description">Click a palette to apply its colors to the preset fields.</p>
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

        <h2><?php esc_html_e( 'Import Presets from CSV', 'hm-demo-engine' ); ?></h2>
        <?php
        $template_url = wp_nonce_url(
            admin_url( 'admin.php?page=hm-demo-engine-presets&hmde_action=download_csv_template' ),
            'hmde_download_csv_template'
        );
        ?>
        <p>
            <a href="<?php echo esc_url( $template_url ); ?>" class="button">
                <?php esc_html_e( 'Download CSV Template', 'hm-demo-engine' ); ?>
            </a>
        </p>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'hmde_import_csv' ); ?>
            <input type="hidden" name="hmde_action" value="import_csv" />

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="hmde_csv"><?php esc_html_e( 'CSV File', 'hm-demo-engine' ); ?></label>
                    </th>
                    <td>
                        <input type="file" name="hmde_csv" id="hmde_csv" accept=".csv,text/csv" required />
                        <p class="description">
                            <?php esc_html_e( 'CSV columns:', 'hm-demo-engine' ); ?>
                            <code>name, primary, dark, bg, footer, link, body_font, heading_font</code>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Import Mode', 'hm-demo-engine' ); ?>
                    </th>
                    <td>
                        <label>
                            <input type="radio" name="hmde_import_mode" value="update" checked />
                            <?php esc_html_e( 'Update if name matches (recommended)', 'hm-demo-engine' ); ?>
                        </label>
                        <br />
                        <label>
                            <input type="radio" name="hmde_import_mode" value="create" />
                            <?php esc_html_e( 'Always create new presets', 'hm-demo-engine' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button( __( 'Import CSV', 'hm-demo-engine' ), 'secondary' ); ?>
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
