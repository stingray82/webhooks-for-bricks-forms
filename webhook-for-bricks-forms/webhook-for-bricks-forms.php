<?php
/*
Plugin Name: Webhook for Bricks Forms
Description: Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options and response format selection.
Plugin URI: https://github.com/stingray82/webhooks-for-bricks-forms/
Version: 1.21
Author: Stingray82 & Reallyusefulplugins
Text Domain: webhook-for-bricks-forms
Author URI: https://Reallyusefulplugins.com
License: GPLv2 or later
Domain Path: /languages
*/

// Load plugin text domain for translations
add_action( 'plugins_loaded', 'rup_bhfbf_load_textdomain' );
function rup_bhfbf_load_textdomain() {
    load_plugin_textdomain( 'webhook-for-bricks-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Add submenu under the Bricks menu
add_action( 'admin_menu', 'rup_bhfbf_admin_menu', 20 );
function rup_bhfbf_admin_menu() {
    if ( ! class_exists( '\Bricks\Capabilities' ) || \Bricks\Capabilities::current_user_has_no_access() ) {
        return;
    }

    global $menu;
    $bricks_menu_exists = array_filter( $menu, function( $item ) {
        return in_array( 'bricks', $item );
    });

    if ( $bricks_menu_exists ) {
        add_submenu_page(
            'bricks',
            __( 'Webhook for Forms Settings', 'webhook-for-bricks-forms' ),
            __( 'Webhook for Forms', 'webhook-for-bricks-forms' ),
            'manage_options',
            'webhook_for_forms',
            'rup_bhfbf_render_webhook_for_forms_admin_page'
        );
    }
}

// Render admin page
function rup_bhfbf_render_webhook_for_forms_admin_page() {
    // Process deletion and then redirect
    if ( isset( $_GET['delete_form_id'] ) ) {
        $form_webhooks = get_option( 'rup_bhfbf_webhooks', [] );
        $delete_form_id = sanitize_text_field( wp_unslash( $_GET['delete_form_id'] ) );
        if ( isset( $form_webhooks[ $delete_form_id ] ) ) {
            unset( $form_webhooks[ $delete_form_id ] );
            update_option( 'rup_bhfbf_webhooks', $form_webhooks );
        }
        wp_redirect( admin_url( 'admin.php?page=webhook_for_forms' ) );
        exit;
    }

    // Load saved webhooks and debug mode
    $form_webhooks = get_option( 'rup_bhfbf_webhooks', [] );
    $debug_mode = get_option( 'rup_bhfbf_debug', false );

    // Check if we are editing an existing hook
    $edit_form_id = '';
    $edit_webhook  = [
        'url'    => '',
        'format' => 'json',
    ];
    if ( isset( $_GET['edit_form_id'] ) ) {
        $edit_form_id = sanitize_text_field( wp_unslash( $_GET['edit_form_id'] ) );
        if ( isset( $form_webhooks[ $edit_form_id ] ) ) {
            $edit_webhook = $form_webhooks[ $edit_form_id ];
        }
    }

    // Process form submission for both add and edit
    if ( isset( $_POST['rup_bhfbf_webhook_for_forms_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rup_bhfbf_webhook_for_forms_nonce'] ) ), 'rup_bhfbf_save_webhook_for_forms' ) ) {
        $form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
        $webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';
        $response_format = isset( $_POST['response_format'] ) ? sanitize_text_field( wp_unslash( $_POST['response_format'] ) ) : 'json';

        // If editing, use the original form ID if not changed
        if ( isset( $_POST['edit_form_id'] ) && ! empty( $_POST['edit_form_id'] ) ) {
            $original_form_id = sanitize_text_field( wp_unslash( $_POST['edit_form_id'] ) );
            // If the form ID was changed, remove the old entry
            if ( $original_form_id !== $form_id && isset( $form_webhooks[ $original_form_id ] ) ) {
                unset( $form_webhooks[ $original_form_id ] );
            }
        }

        if ( $form_id && $webhook_url ) {
            $form_webhooks[ $form_id ] = [
                'url'    => $webhook_url,
                'format' => $response_format,
            ];

            update_option( 'rup_bhfbf_webhooks', $form_webhooks );
            update_option( 'rup_bhfbf_debug', isset( $_POST['debug_mode'] ) );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved successfully.', 'webhook-for-bricks-forms' ) . '</p></div>';

            // Reset edit variables after saving
            $edit_form_id = '';
            $edit_webhook  = [
                'url'    => '',
                'format' => 'json',
            ];
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Webhook for Forms Settings', 'webhook-for-bricks-forms' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'rup_bhfbf_save_webhook_for_forms', 'rup_bhfbf_webhook_for_forms_nonce' ); ?>
            <?php
            // If editing, include a hidden field with the original form id.
            if ( ! empty( $edit_form_id ) ) {
                echo '<input type="hidden" name="edit_form_id" value="' . esc_attr( $edit_form_id ) . '">';
            }
            ?>
            <h2><?php esc_html_e( empty( $edit_form_id ) ? 'Add New Form Webhook' : 'Edit Form Webhook', 'webhook-for-bricks-forms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="form_id"><?php esc_html_e( 'Form ID', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td>
                        <input type="text" id="form_id" name="form_id" value="<?php echo esc_attr( $edit_form_id ? $edit_form_id : '' ); ?>" <?php echo ( ! empty( $edit_form_id ) ? 'readonly' : '' ); ?> />
                        <?php if ( ! empty( $edit_form_id ) ) : ?>
                            <p class="description"><?php esc_html_e( 'Form ID cannot be changed while editing.', 'webhook-for-bricks-forms' ); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td><input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( $edit_webhook['url'] ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="response_format"><?php esc_html_e( 'Response Format', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td>
                        <select id="response_format" name="response_format">
                            <option value="json" <?php selected( $edit_webhook['format'], 'json' ); ?>><?php esc_html_e( 'JSON', 'webhook-for-bricks-forms' ); ?></option>
                            <option value="formdata" <?php selected( $edit_webhook['format'], 'formdata' ); ?>><?php esc_html_e( 'FormData', 'webhook-for-bricks-forms' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Debug Options', 'webhook-for-bricks-forms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="debug_mode"><?php esc_html_e( 'Enable Debug Mode', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td><input type="checkbox" id="debug_mode" name="debug_mode" <?php checked( $debug_mode ); ?> /></td>
                </tr>
            </table>

            <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'webhook-for-bricks-forms' ); ?></button></p>
        </form>

        <h2><?php esc_html_e( 'Existing Form Webhooks', 'webhook-for-bricks-forms' ); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Form ID', 'webhook-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Webhook URL', 'webhook-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Response Format', 'webhook-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'webhook-for-bricks-forms' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $form_webhooks as $form_id => $webhook ) :
                    if ( is_string( $webhook ) ) {
                        $webhook = [
                            'url'    => $webhook,
                            'format' => 'json',
                        ];
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html( $form_id ); ?></td>
                        <td><?php echo esc_url( $webhook['url'] ); ?></td>
                        <td><?php echo esc_html( $webhook['format'] ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( 'edit_form_id', $form_id ) ); ?>" class="button"><?php esc_html_e( 'Edit', 'webhook-for-bricks-forms' ); ?></a>
                            <a href="<?php echo esc_url( add_query_arg( 'delete_form_id', $form_id ) ); ?>" class="button" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this webhook?', 'webhook-for-bricks-forms' ); ?>');"><?php esc_html_e( 'Delete', 'webhook-for-bricks-forms' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Hook into Bricks form submissions
add_action( 'bricks/form/custom_action', 'rup_bhfbf_trigger_webhook_on_bricks_form_submission' );
function rup_bhfbf_trigger_webhook_on_bricks_form_submission( $form ) {
    $form_webhooks = get_option( 'rup_bhfbf_webhooks', [] );
    $fields = $form->get_fields();
    $submitted_form_id = $fields['formId'] ?? null;

    if ( $submitted_form_id && isset( $form_webhooks[ $submitted_form_id ] ) ) {
        $webhook = $form_webhooks[ $submitted_form_id ];
        $url = is_array( $webhook ) ? $webhook['url'] : $webhook;
        $format = is_array( $webhook ) && isset( $webhook['format'] ) ? $webhook['format'] : 'json';

        $body = ( $format === 'formdata' ) ? http_build_query( $fields ) : wp_json_encode( $fields );
        $headers = [
            'Content-Type' => ( $format === 'formdata' ) ? 'application/x-www-form-urlencoded' : 'application/json',
        ];

        // Send the webhook request
        $response = wp_remote_post( $url, [
            'body'    => $body,
            'headers' => $headers,
        ] );

        // Debug logging if enabled
        $debug_mode = get_option( 'rup_bhfbf_debug', false );
        if ( $debug_mode ) {
            $log  = "Webhook Debug Log:\n";
            $log .= "Form ID: " . print_r( $submitted_form_id, true ) . "\n";
            $log .= "Webhook URL: " . print_r( $url, true ) . "\n";
            $log .= "Response Format: " . print_r( $format, true ) . "\n";
            $log .= "Request Body: " . print_r( $body, true ) . "\n";
            $log .= "Request Headers: " . print_r( $headers, true ) . "\n";
            if ( is_wp_error( $response ) ) {
                $log .= "Webhook Error: " . $response->get_error_message() . "\n";
            } else {
                $log .= "Webhook Response: " . print_r( $response, true ) . "\n";
            }
            error_log( $log );
        }
    }
}
