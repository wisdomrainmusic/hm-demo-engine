<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const HMDE_GROUPS_OPTION_KEY = 'hmde_groups';

/**
 * Helpers
 */
function hmde_get_groups() {
    $groups = get_option( HMDE_GROUPS_OPTION_KEY, array() );
    return is_array( $groups ) ? $groups : array();
}

function hmde_save_groups( array $groups ) {
    update_option( HMDE_GROUPS_OPTION_KEY, $groups, false );
}

function hmde_generate_group_id() {
    return substr( md5( uniqid( 'hmde_group_', true ) ), 0, 10 );
}

function hmde_sanitize_group_payload( array $raw ) {
    $name = isset( $raw['name'] ) ? sanitize_text_field( $raw['name'] ) : '';
    if ( $name === '' ) {
        $name = 'Untitled Group';
    }

    $description = isset( $raw['description'] ) ? sanitize_textarea_field( $raw['description'] ) : '';

    return array(
        'name'        => $name,
        'description' => $description,
    );
}

/**
 * Actions: add / delete
 */
function hmde_handle_groups_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Add group
    if ( isset( $_POST['hmde_action'] ) && $_POST['hmde_action'] === 'add_group' ) {
        check_admin_referer( 'hmde_add_group' );

        $groups  = hmde_get_groups();
        $payload = hmde_sanitize_group_payload( array(
            'name'        => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
        ) );

        $id = hmde_generate_group_id();

        $groups[ $id ] = array_merge(
            array( 'id' => $id ),
            $payload
        );

        hmde_save_groups( $groups );

        wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-groups&created=1' ) );
        exit;
    }

    // Delete group
    if ( isset( $_GET['hmde_action'] ) && $_GET['hmde_action'] === 'delete_group' ) {
        $id = isset( $_GET['group_id'] ) ? sanitize_text_field( $_GET['group_id'] ) : '';
        check_admin_referer( 'hmde_delete_group_' . $id );

        $groups = hmde_get_groups();
        if ( $id !== '' && isset( $groups[ $id ] ) ) {
            unset( $groups[ $id ] );
            hmde_save_groups( $groups );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=hm-demo-engine-groups&deleted=1' ) );
        exit;
    }
}
add_action( 'admin_init', 'hmde_handle_groups_actions' );

/**
 * Renderer
 */
function hmde_render_groups_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $groups = hmde_get_groups();
    ?>
    <div class="wrap">
        <h1>Groups</h1>

        <?php if ( isset( $_GET['created'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Group created.</p></div>
        <?php endif; ?>

        <?php if ( isset( $_GET['deleted'] ) ) : ?>
            <div class="notice notice-success is-dismissible"><p>Group deleted.</p></div>
        <?php endif; ?>

        <h2>Add New Group</h2>

        <form method="post">
            <?php wp_nonce_field( 'hmde_add_group' ); ?>
            <input type="hidden" name="hmde_action" value="add_group" />

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="hmde_group_name">Name</label></th>
                    <td>
                        <input name="name" id="hmde_group_name" type="text" class="regular-text" required />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="hmde_group_desc">Description</label></th>
                    <td>
                        <textarea name="description" id="hmde_group_desc" class="large-text" rows="3"></textarea>
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Create Group' ); ?>
        </form>

        <hr />

        <h2>Group List</h2>

        <?php if ( empty( $groups ) ) : ?>
            <p>No groups yet.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th style="width: 160px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ( $groups as $id => $group ) : ?>
                    <tr>
                        <td><?php echo esc_html( $group['name'] ?? '' ); ?></td>
                        <td><?php echo esc_html( $group['description'] ?? '' ); ?></td>
                        <td>
                            <?php
                            $del_url = wp_nonce_url(
                                admin_url( 'admin.php?page=hm-demo-engine-groups&hmde_action=delete_group&group_id=' . urlencode( $id ) ),
                                'hmde_delete_group_' . $id
                            );
                            ?>
                            <a class="button button-link-delete" href="<?php echo esc_url( $del_url ); ?>" onclick="return confirm('Delete this group?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p class="description" style="margin-top:12px;">
            Next commits will connect Groups to Presets and Pages.
        </p>
    </div>
    <?php
}
