<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend CSS Variable Engine
 *
 * Commit 7:
 * - Apply variables to current group's body class selector
 * - Requires frontend/body-class.php
 */

function hmde_is_supported_frontend_context() {
    if ( is_admin() ) {
        return false;
    }
    return is_singular( 'page' );
}

function hmde_get_current_page_id() {
    $id = get_queried_object_id();
    return $id ? absint( $id ) : 0;
}

function hmde_get_group_id_for_page( $page_id ) {
    if ( ! $page_id ) {
        return '';
    }

    if ( defined( 'HMDE_PAGE_GROUP_META_KEY' ) ) {
        $gid = get_post_meta( $page_id, HMDE_PAGE_GROUP_META_KEY, true );
        return is_string( $gid ) ? $gid : '';
    }

    $gid = get_post_meta( $page_id, '_hmde_group_id', true );
    return is_string( $gid ) ? $gid : '';
}

function hmde_get_group_by_id( $group_id ) {
    if ( ! $group_id ) {
        return null;
    }

    $groups = get_option( 'hmde_groups', array() );
    if ( ! is_array( $groups ) ) {
        return null;
    }

    return isset( $groups[ $group_id ] ) && is_array( $groups[ $group_id ] )
        ? $groups[ $group_id ]
        : null;
}

function hmde_get_preset_by_id( $preset_id ) {
    if ( ! $preset_id ) {
        return null;
    }

    $presets = get_option( 'hmde_presets', array() );
    if ( ! is_array( $presets ) ) {
        return null;
    }

    return isset( $presets[ $preset_id ] ) && is_array( $presets[ $preset_id ] )
        ? $presets[ $preset_id ]
        : null;
}

function hmde_build_css_variables_from_preset( array $preset ) {
    $colors = isset( $preset['colors'] ) && is_array( $preset['colors'] ) ? $preset['colors'] : array();
    $fonts  = isset( $preset['fonts'] ) && is_array( $preset['fonts'] ) ? $preset['fonts'] : array();

    $font_map = array(
        'System Default'   => 'system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
        'Inter'            => '"Inter", system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif',
        'Roboto'           => '"Roboto", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Open Sans'        => '"Open Sans", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Lato'             => '"Lato", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Montserrat'       => '"Montserrat", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
        'Playfair Display' => '"Playfair Display", Georgia, serif',
        'Merriweather'     => '"Merriweather", Georgia, serif',
        'Poppins'          => '"Poppins", system-ui, -apple-system, Segoe UI, Arial, sans-serif',
    );

    $body_label    = isset( $fonts['body_font'] ) ? (string) $fonts['body_font'] : 'System Default';
    $heading_label = isset( $fonts['heading_font'] ) ? (string) $fonts['heading_font'] : 'System Default';

    $body_stack    = isset( $font_map[ $body_label ] ) ? $font_map[ $body_label ] : $font_map['System Default'];
    $heading_stack = isset( $font_map[ $heading_label ] ) ? $font_map[ $heading_label ] : $font_map['System Default'];

    $vars = array(
        '--hm-primary'      => isset( $colors['primary'] ) ? (string) $colors['primary'] : '',
        '--hm-dark'         => isset( $colors['dark'] ) ? (string) $colors['dark'] : '',
        '--hm-bg'           => isset( $colors['bg'] ) ? (string) $colors['bg'] : '',
        '--hm-footer'       => isset( $colors['footer'] ) ? (string) $colors['footer'] : '',
        '--hm-link'         => isset( $colors['link'] ) ? (string) $colors['link'] : '',
        '--hm-font-body'    => $body_stack,
        '--hm-font-heading' => $heading_stack,
    );

    $out = array();
    foreach ( $vars as $k => $v ) {
        if ( $k === '--hm-font-body' || $k === '--hm-font-heading' ) {
            $out[ $k ] = $v;
            continue;
        }
        if ( is_string( $v ) && $v !== '' ) {
            $out[ $k ] = $v;
        }
    }

    return $out;
}

function hmde_print_css_variables() {
    if ( ! hmde_is_supported_frontend_context() ) {
        return;
    }

    $page_id  = hmde_get_current_page_id();
    $group_id = hmde_get_group_id_for_page( $page_id );

    if ( ! $group_id ) {
        return;
    }

    $group = hmde_get_group_by_id( $group_id );
    if ( ! $group || empty( $group['preset_id'] ) ) {
        return;
    }

    $preset_id = (string) $group['preset_id'];
    $preset    = hmde_get_preset_by_id( $preset_id );

    if ( ! $preset ) {
        return;
    }

    $selector = function_exists( 'hmde_current_group_body_selector' )
        ? hmde_current_group_body_selector()
        : '';

    if ( ! $selector ) {
        // Fallback to :root
        $selector = ':root';
    }

    $vars = hmde_build_css_variables_from_preset( $preset );
    if ( empty( $vars ) ) {
        return;
    }

    echo "\n<!-- HM Demo Engine (Commit 7) -->\n";
    echo "<style id=\"hmde-css-vars\">\n";
    echo "{$selector}{\n";
    foreach ( $vars as $k => $v ) {
        $k = preg_replace( '/[^a-zA-Z0-9\-\_]/', '', $k );
        $v = str_replace( array( "\n", "\r" ), '', $v );
        echo "  {$k}: {$v};\n";
    }
    echo "}\n";
    echo "</style>\n";
}
add_action( 'wp_head', 'hmde_print_css_variables', 20 );
