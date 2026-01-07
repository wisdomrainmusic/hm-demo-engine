<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HMDE_PAGE_GROUP_META_KEY = '_hmde_group_id';

/**
 * Register meta (optional but clean)
 */
function hmde_register_page_group_meta() {
    register_post_meta(
        'page',
        HMDE_PAGE_GROUP_META_KEY,
        array(
            'type'              => 'string',
            'single'            => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback'     => function () {
                return current_user_can( 'edit_pages' );
            },
            'show_in_rest'      => false,
        )
    );
}
add_action( 'init', 'hmde_register_page_group_meta' );

/**
 * Add metabox to Pages
 */
function hmde_add_page_group_metabox() {
    add_meta_box(
        'hmde_page_group_metabox',
        'Demo Group',
        'hmde_render_page_group_metabox',
        'page',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'hmde_add_page_group_metabox' );

/**
 * Render metabox
 */
function hmde_render_page_group_metabox( $post ) {
    if ( ! current_user_can( 'edit_post', $post->ID ) ) {
        return;
    }

    wp_nonce_field( 'hmde_save_page_group', 'hmde_page_group_nonce' );

    $groups   = function_exists( 'hmde_get_groups' ) ? hmde_get_groups() : array();
    $selected = get_post_meta( $post->ID, HMDE_PAGE_GROUP_META_KEY, true );

    echo '<p style="margin-top:0;">Assign this page to a Demo Group.</p>';

    echo '<label class="screen-reader-text" for="hmde_group_id">Demo Group</label>';
    echo '<select name="hmde_group_id" id="hmde_group_id" style="width:100%;">';
    echo '<option value="">— None —</option>';

    if ( ! empty( $groups ) ) {
        foreach ( $groups as $id => $group ) {
            $name = isset( $group['name'] ) ? $group['name'] : $id;
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr( $id ),
                selected( $selected, $id, false ),
                esc_html( $name )
            );
        }
    }

    echo '</select>';

    echo '<p class="description" style="margin-top:8px;">This mapping will be used to apply the group preset on the frontend (next commits).</p>';
}

/**
 * Save metabox value
 */
function hmde_save_page_group_metabox( $post_id ) {

    // Only pages
    if ( get_post_type( $post_id ) !== 'page' ) {
        return;
    }

    // Nonce
    if ( ! isset( $_POST['hmde_page_group_nonce'] ) || ! wp_verify_nonce( $_POST['hmde_page_group_nonce'], 'hmde_save_page_group' ) ) {
        return;
    }

    // Autosave / revision
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Capability
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $group_id = isset( $_POST['hmde_group_id'] ) ? sanitize_text_field( $_POST['hmde_group_id'] ) : '';

    // Validate group exists (or allow empty)
    if ( $group_id !== '' && function_exists( 'hmde_get_groups' ) ) {
        $groups = hmde_get_groups();
        if ( ! isset( $groups[ $group_id ] ) ) {
            $group_id = '';
        }
    }

    if ( $group_id === '' ) {
        delete_post_meta( $post_id, HMDE_PAGE_GROUP_META_KEY );
    } else {
        update_post_meta( $post_id, HMDE_PAGE_GROUP_META_KEY, $group_id );
    }
}
add_action( 'save_post', 'hmde_save_page_group_metabox' );
