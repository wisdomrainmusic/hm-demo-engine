<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function hmde_get_current_group_config() {
    if ( ! function_exists( 'hmde_current_group_id' ) ) {
        return null;
    }

    $gid = hmde_current_group_id();
    if ( ! $gid ) {
        return null;
    }

    $groups = get_option( 'hmde_groups', array() );
    if ( ! is_array( $groups ) || ! isset( $groups[ $gid ] ) ) {
        return null;
    }

    return $groups[ $gid ];
}

function hmde_should_hide_menu( $key ) {
    $group = hmde_get_current_group_config();
    if ( ! $group ) {
        return false;
    }

    if ( empty( $group['menu_hide'] ) || ! is_array( $group['menu_hide'] ) ) {
        return false;
    }

    return ! empty( $group['menu_hide'][ $key ] );
}

/**
 * Attempt to block Astra menus at render-time
 * (Works when theme_location matches)
 */
function hmde_filter_nav_menu_args( $args ) {
    if ( is_admin() ) {
        return $args;
    }

    // Primary menu
    if ( hmde_should_hide_menu( 'primary' ) ) {
        if ( isset( $args['theme_location'] ) && in_array( $args['theme_location'], array( 'primary', 'astra-primary-menu' ), true ) ) {
            $args['echo'] = false;
            $args['items_wrap'] = '<!-- HMDE: primary menu hidden -->';
        }
    }

    // Secondary / above header
    if ( hmde_should_hide_menu( 'secondary' ) ) {
        if ( isset( $args['theme_location'] ) && in_array( $args['theme_location'], array( 'secondary', 'astra-secondary-menu' ), true ) ) {
            $args['echo'] = false;
            $args['items_wrap'] = '<!-- HMDE: secondary menu hidden -->';
        }
    }

    return $args;
}
add_filter( 'wp_nav_menu_args', 'hmde_filter_nav_menu_args', 20 );

/**
 * CSS fallback for Astra builder wrappers (covers cases where theme_location differs)
 */
function hmde_print_menu_hide_css() {
    if ( is_admin() ) {
        return;
    }
    if ( ! function_exists( 'hmde_current_group_body_selector' ) ) {
        return;
    }

    $selector = hmde_current_group_body_selector();
    if ( ! $selector ) {
        return;
    }

    $css = '';

    if ( hmde_should_hide_menu( 'primary' ) ) {
        $css .= "
{$selector} .ast-primary-header-bar .main-header-menu,
{$selector} .ast-primary-header-bar .ast-builder-menu.ast-builder-menu-primary{
  display: none !important;
}
";
    }

    if ( hmde_should_hide_menu( 'mobile' ) ) {
        $css .= "
{$selector} .ast-mobile-header-wrap .main-header-menu,
{$selector} .ast-mobile-header-wrap .ast-builder-menu-primary,
{$selector} .ast-mobile-popup-drawer,
{$selector} #ast-mobile-popup{
  display: none !important;
}
";
        $css .= "
{$selector} .ast-mobile-menu-trigger-minimal{
  display: none !important;
}
";
    }

    if ( hmde_should_hide_menu( 'secondary' ) ) {
        $css .= "
{$selector} .ast-above-header,
{$selector} .ast-above-header-bar{
  display: none !important;
}
";
    }

    if ( trim( $css ) === '' ) {
        return;
    }

    echo "\n<!-- HM Demo Engine (Menu Visibility) -->\n";
    echo "<style id=\"hmde-menu-visibility\">\n" . trim( $css ) . "\n</style>\n";
}
add_action( 'wp_head', 'hmde_print_menu_hide_css', 60 );
