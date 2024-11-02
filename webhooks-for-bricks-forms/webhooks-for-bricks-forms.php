<?php
/*
Plugin Name: Webhook for Bricks Forms
Description: Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options.
Version: 1.0
Author: Stingray82
*/

// Add a submenu under the Bricks menu, ensuring it loads after Bricks menu registration
add_action( 'admin_menu', 'webhook_for_forms_admin_menu', 20 );

function webhook_for_forms_admin_menu() {
    // Check if Bricks and the necessary capabilities class exist
    if ( ! class_exists('\Bricks\Capabilities') ) {
        return;
    }

    // Check if the current user has access to Bricks
    if ( \Bricks\Capabilities::current_user_has_no_access() ) {
        return;
    }

    // Confirm that the Bricks menu is registered in the global $menu array
    global $menu;
    $bricks_menu_exists = false;

    foreach ( $menu as $item ) {
        if ( in_array( 'bricks', $item ) ) {
            $bricks_menu_exists = true;
            break;
        }
    }

    // Only add the submenu if the Bricks menu exists
    if ( $bricks_menu_exists ) {
        add_submenu_page(
            'bricks',                            // Parent slug (Bricks menu)
            'Webhook for Forms Settings',        // Page title
            'Webhook for Forms',                 // Menu title
            'manage_options',                    // Capability
            'webhook_for_forms',                 // Menu slug
            'render_webhook_for_forms_admin_page'// Callback function to render the page
        );
    }
}

// Render the admin page for managing form-webhook pairs and debug options
function render_webhook_for_forms_admin_page() {
    // Handle form submission for saving new form-webhook pairs and debug settings
    if ( isset( $_POST['webhook_for_forms_nonce'] ) && wp_verify_nonce( $_POST['webhook_for_forms_nonce'], 'save_webhook_for_forms' ) ) {
        // Save form ID and webhook URL pairs
        $form_id = sanitize_text_field( $_POST['form_id'] );
        $webhook_url = esc_url_raw( $_POST['webhook_url'] );
        $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
        $form_webhooks[$form_id] = $webhook_url;
        update_option( 'webhook_for_forms_webhooks', $form_webhooks );

        // Save debug options
        update_option( 'webhook_for_forms_debug', isset( $_POST['debug_mode'] ) );
        update_option( 'webhook_for_forms_log_next_submission', isset( $_POST['log_next_submission'] ) );

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Handle deletion of form-webhook pairs
    if ( isset( $_GET['delete_form_id'] ) ) {
        $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
        unset( $form_webhooks[ $_GET['delete_form_id'] ] );
        update_option( 'webhook_for_forms_webhooks', $form_webhooks );

        echo '<div class="updated"><p>Form Webhook deleted successfully.</p></div>';
    }

    // Retrieve saved form-webhook pairs and debug options
    $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
    $debug_mode = get_option( 'webhook_for_forms_debug', false );
    $log_next_submission = get_option( 'webhook_for_forms_log_next_submission', false );

    // Output the admin page HTML
    ?>
    <div class="wrap">
        <h1>Webhook for Forms Settings</h1>
        <form method="post">
            <?php wp_nonce_field( 'save_webhook_for_forms', 'webhook_for_forms_nonce' ); ?>
            <h2>Add New Form Webhook</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="form_id">Form ID</label></th>
                    <td><input type="text" id="form_id" name="form_id" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="webhook_url">Webhook URL</label></th>
                    <td><input type="url" id="webhook_url" name="webhook_url" /></td>
                </tr>
            </table>

            <h2>Debug Options</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="debug_mode">Enable Debug Mode</label></th>
                    <td><input type="checkbox" id="debug_mode" name="debug_mode" <?php checked( $debug_mode ); ?> /></td>
                </tr>                
            </table>

            <p class="submit"><button type="submit" class="button button-primary">Save Settings</button></p>
        </form>

        <h2>Existing Form Webhooks</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Form ID</th>
                    <th>Webhook URL</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $form_webhooks as $form_id => $webhook_url ) : ?>
                    <tr>
                        <td><?php echo esc_html( $form_id ); ?></td>
                        <td><?php echo esc_url( $webhook_url ); ?></td>
                        <td><a href="<?php echo add_query_arg( 'delete_form_id', $form_id ); ?>" class="button">Delete</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Hook into the Bricks form custom action
add_action( 'bricks/form/custom_action', 'trigger_webhook_on_bricks_form_submission' );

/**
 * Function to trigger webhook on specific Bricks form submissions.
 *
 * @param object $form The Bricks form object.
 */
function trigger_webhook_on_bricks_form_submission( $form ) {
    // Retrieve form-webhook pairs from the options table
    $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );

    // Get the submitted form ID
    $fields = $form->get_fields();
    $submitted_form_id = $fields['formId'] ?? null;

    // Debugging options
    $debug_mode = get_option( 'webhook_for_forms_debug', false );
    $log_next_submission = get_option( 'webhook_for_forms_log_next_submission', false );

    // Log specific fields for the next form submission if enabled
    if ( $log_next_submission ) {
        $log_data = [
            'form_fields' => array_filter($fields, function ($key) {
                return strpos($key, 'form-field') === 0;
            }, ARRAY_FILTER_USE_KEY),
            'postId' => $fields['postId'] ?? null,
            'formId' => $fields['formId'] ?? null,
            'referrer' => $fields['referrer'] ?? null,
        ];
        error_log( 'Logging next form submission for debugging:' );
        error_log( 'Submission Data: ' . print_r( $log_data, true ) );

        // Disable the "Log Next Form Submission" option after logging
        update_option( 'webhook_for_forms_log_next_submission', false );
    }

    // Check if the submitted form ID has a corresponding webhook URL
    if ( $submitted_form_id && isset( $form_webhooks[ $submitted_form_id ] ) ) {
        $webhook_url = $form_webhooks[ $submitted_form_id ];

        // Send data to the custom webhook
        $response = send_data_to_webhook( $webhook_url, $fields );

        // Log webhook response if debugging is enabled
        if ( $debug_mode ) {
            error_log( 'Webhook triggered for form ID ' . $submitted_form_id );
            error_log( 'Webhook Response: ' . print_r( $response, true ) );
        }
    } elseif ( $debug_mode ) {
        // Log if no webhook is found for the submitted form ID
        error_log( 'No webhook found for submitted form ID: ' . $submitted_form_id );
    }
}

/**
 * Send form data to a custom webhook.
 *
 * @param string $url  The webhook URL.
 * @param array  $data The data to send.
 * @return array|WP_Error The response or WP_Error on failure.
 */
function send_data_to_webhook( $url, $data ) {
    $response = wp_remote_post( $url, [
        'body'    => json_encode( $data ),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
    ]);

    return $response;
}
