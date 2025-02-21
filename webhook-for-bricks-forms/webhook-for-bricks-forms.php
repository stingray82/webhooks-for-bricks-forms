<?php
/*
Plugin Name: Webhook for Bricks Forms
Description: Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options and response format selection.
Plugin URI: https://github.com/stingray82/webhook-for-bricks-forms/
Version: 1.31
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
    $form_webhooks = get_option( 'rup_bhfbf_webhooks', [] );
    $debug_mode    = get_option( 'rup_bhfbf_debug', false );

    // Determine the base URL depending on multisite/network admin
    $base_url = is_network_admin() ? network_admin_url( 'admin.php?page=webhook_for_forms' ) : admin_url( 'admin.php?page=webhook_for_forms' );

    // Helper function for redirection
    function rup_bhfbf_redirect( $url ) {
        if ( ! headers_sent() ) {
            wp_safe_redirect( $url );
            exit;
        } else {
            echo '<script>window.location.href="' . esc_url( $url ) . '";</script>';
            exit;
        }
    }

    // Check if we are editing an existing hook
    $editing = false;
    $edit_form_id = '';
    $edit_webhook_url = '';
    $edit_response_format = 'json';
    if ( isset( $_GET['edit_form_id'] ) ) {
        $edit_form_id = sanitize_text_field( wp_unslash( $_GET['edit_form_id'] ) );
        if ( isset( $form_webhooks[ $edit_form_id ] ) ) {
            $editing = true;
            $edit_webhook_url = $form_webhooks[ $edit_form_id ]['url'];
            $edit_response_format = $form_webhooks[ $edit_form_id ]['format'];
        }
    }

    // Process form submission
    if ( isset( $_POST['webhook_for_forms_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['webhook_for_forms_nonce'] ) ), 'save_webhook_for_forms' ) ) {

        // Always update the debug log option on save
        update_option( 'rup_bhfbf_debug', isset( $_POST['debug_mode'] ) );

        $form_id         = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
        $webhook_url     = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';
        $response_format = isset( $_POST['response_format'] ) ? sanitize_text_field( wp_unslash( $_POST['response_format'] ) ) : 'json';

        // Check if we're editing an existing hook
        if ( isset( $_POST['editing'] ) && ! empty( $_POST['editing'] ) ) {
            $original_form_id = sanitize_text_field( wp_unslash( $_POST['editing'] ) );
            // If the Form ID was changed, remove the old key
            if ( $original_form_id !== $form_id && isset( $form_webhooks[ $original_form_id ] ) ) {
                unset( $form_webhooks[ $original_form_id ] );
            }
            if ( $form_id && $webhook_url ) {
                $form_webhooks[ $form_id ] = [
                    'url'    => $webhook_url,
                    'format' => $response_format,
                ];
                update_option( 'rup_bhfbf_webhooks', $form_webhooks );
                rup_bhfbf_redirect( $base_url );
            }
        } else {
            // Adding a new webhook
            if ( $form_id && $webhook_url ) {
                $form_webhooks[ $form_id ] = [
                    'url'    => $webhook_url,
                    'format' => $response_format,
                ];
                update_option( 'rup_bhfbf_webhooks', $form_webhooks );
                rup_bhfbf_redirect( $base_url );
            }
        }
    }

    // Process deletion
    if ( isset( $_GET['delete_form_id'] ) ) {
        $delete_form_id = sanitize_text_field( wp_unslash( $_GET['delete_form_id'] ) );
        if ( isset( $form_webhooks[ $delete_form_id ] ) ) {
            unset( $form_webhooks[ $delete_form_id ] );
            update_option( 'rup_bhfbf_webhooks', $form_webhooks );
            rup_bhfbf_redirect( $base_url );
        }
    }

    // Reload options after any changes.
    $form_webhooks = get_option( 'rup_bhfbf_webhooks', [] );
    $debug_mode    = get_option( 'rup_bhfbf_debug', false );
    
    // Prepare static translation strings
    $heading_text = $editing ? __( 'Edit Form Webhook', 'webhook-for-bricks-forms' ) : __( 'Add New Form Webhook', 'webhook-for-bricks-forms' );
    $button_text  = $editing ? __( 'Update Webhook', 'webhook-for-bricks-forms' ) : __( 'Save Settings', 'webhook-for-bricks-forms' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Webhook for Forms Settings', 'webhook-for-bricks-forms' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'save_webhook_for_forms', 'webhook_for_forms_nonce' ); ?>
            <h2><?php echo esc_html( $heading_text ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="form_id"><?php esc_html_e( 'Form ID', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td>
                        <input type="text" id="form_id" name="form_id" value="<?php echo esc_attr( $editing ? $edit_form_id : '' ); ?>" <?php echo $editing ? 'readonly' : ''; ?> />
                        <?php if ( $editing ) : ?>
                            <input type="hidden" name="editing" value="<?php echo esc_attr( $edit_form_id ); ?>" />
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td><input type="url" id="webhook_url" name="webhook_url" value="<?php echo esc_url( $editing ? $edit_webhook_url : '' ); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="response_format"><?php esc_html_e( 'Response Format', 'webhook-for-bricks-forms' ); ?></label></th>
                    <td>
                        <select id="response_format" name="response_format">
                            <option value="json" <?php selected( $editing ? $edit_response_format : 'json', 'json' ); ?>><?php esc_html_e( 'JSON', 'webhook-for-bricks-forms' ); ?></option>
                            <option value="formdata" <?php selected( $editing ? $edit_response_format : '', 'formdata' ); ?>><?php esc_html_e( 'FormData', 'webhook-for-bricks-forms' ); ?></option>
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

            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php echo esc_html( $button_text ); ?>
                </button>
            </p>
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
                            <a href="<?php echo esc_url( add_query_arg( [ 'edit_form_id' => $form_id ] ) ); ?>" class="button">
                                <?php esc_html_e( 'Edit', 'webhook-for-bricks-forms' ); ?>
                            </a>
                            <a href="<?php echo esc_url( add_query_arg( 'delete_form_id', $form_id ) ); ?>" class="button" onclick="return confirm('<?php esc_html_e( 'Are you sure you want to delete this webhook?', 'webhook-for-bricks-forms' ); ?>');">
                                <?php esc_html_e( 'Delete', 'webhook-for-bricks-forms' ); ?>
                            </a>
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
            $log .= "Form ID: " . var_export( $submitted_form_id, true ) . "\n";
            $log .= "Webhook URL: " . var_export( $url, true ) . "\n";
            $log .= "Response Format: " . var_export( $format, true ) . "\n";
            $log .= "Request Body: " . var_export( $body, true ) . "\n";
            $log .= "Request Headers: " . var_export( $headers, true ) . "\n";
            if ( is_wp_error( $response ) ) {
                $log .= "Webhook Error: " . $response->get_error_message() . "\n";
            } else {
                $log .= "Webhook Response: " . var_export( $response, true ) . "\n";
            }
            error_log( $log );
        }
    }
}