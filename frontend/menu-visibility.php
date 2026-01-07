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
/* Hide primary menu itself */
{$selector} .ast-primary-header-bar .main-header-menu,
{$selector} .ast-primary-header-bar .ast-builder-menu.ast-builder-menu-primary{
  display: none !important;
}

/* Remove the empty space left by the primary menu container */
{$selector} .ast-primary-header-bar .ast-builder-grid-row,
{$selector} .ast-primary-header-bar .ast-builder-grid-row-container,
{$selector} .ast-primary-header-bar .site-header-primary-section-center,
{$selector} .ast-primary-header-bar .site-header-primary-section-left,
{$selector} .ast-primary-header-bar .site-header-primary-section-right{
  min-height: 0 !important;
}

{$selector} .ast-primary-header-bar{
  padding-top: 0 !important;
  padding-bottom: 0 !important;
}

/* If center section exists only for menu, collapse it */
{$selector} .site-header-primary-section-center{
  height: 0 !important;
  overflow: hidden !important;
}
";
    }

    if ( hmde_should_hide_menu( 'mobile' ) ) {
        $css .= "
/* Hide Astra mobile menu trigger + drawer (multiple Astra variants) */
{$selector} .ast-mobile-menu-trigger-minimal,
{$selector} button.menu-toggle,
{$selector} .menu-toggle,
{$selector} .ast-button-wrap .menu-toggle{
  display: none !important;
}

/* Hide offcanvas / popup containers */
{$selector} .ast-mobile-popup-drawer,
{$selector} #ast-mobile-popup,
{$selector} .ast-mobile-popup-content,
{$selector} .ast-mobile-popup-inner,
{$selector} .ast-mobile-popup-overlay,
{$selector} .ast-mobile-header-content,
{$selector} .ast-mobile-nav-menu{
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
