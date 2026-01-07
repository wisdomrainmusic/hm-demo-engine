<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Slugify helper for CSS class
 */
function hmde_slugify( $text ) {
    $text = (string) $text;
    $text = strtolower( $text );
    $text = preg_replace( '/[^a-z0-9\-_]+/', '-', $text );
    $text = trim( $text, '-' );
    if ( $text === '' ) {
        $text = 'group';
    }
    return $text;
}

/**
 * Get current group id for current page
 */
function hmde_current_group_id() {
    if ( is_admin() ) {
        return '';
    }

    if ( ! is_singular( 'page' ) ) {
        return '';
    }

    $page_id = get_queried_object_id();
    if ( ! $page_id ) {
        return '';
    }

    $meta_key = defined( 'HMDE_PAGE_GROUP_META_KEY' ) ? HMDE_PAGE_GROUP_META_KEY : '_hmde_group_id';
    $gid      = get_post_meta( $page_id, $meta_key, true );

    return is_string( $gid ) ? $gid : '';
}

/**
 * Add body class based on group
 */
function hmde_add_body_class( $classes ) {
    $gid = hmde_current_group_id();
    if ( ! $gid ) {
        return $classes;
    }

    // Prefer group name for nicer class, fallback to id
    $groups = get_option( 'hmde_groups', array() );
    $label  = $gid;

    if ( is_array( $groups ) && isset( $groups[ $gid ]['name'] ) ) {
        $label = $groups[ $gid ]['name'];
    }

    $slug = hmde_slugify( $label );

    $classes[] = 'hm-demo-group';
    $classes[] = 'hm-demo-group-' . $slug;

    return $classes;
}
add_filter( 'body_class', 'hmde_add_body_class' );

/**
 * Utility: get current body class selector (for css-engine)
 */
function hmde_current_group_body_selector() {
    $gid = hmde_current_group_id();
    if ( ! $gid ) {
        return '';
    }

    $groups = get_option( 'hmde_groups', array() );
    $label  = $gid;

    if ( is_array( $groups ) && isset( $groups[ $gid ]['name'] ) ) {
        $label = $groups[ $gid ]['name'];
    }

    $slug = hmde_slugify( $label );

    return 'body.hm-demo-group-' . $slug;
}
