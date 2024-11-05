<?php
/*
Plugin Name: Webhook for Bricks Forms
Description: Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options.
Version: 1.1 
Author: Stingray82
*/

// Add a submenu under the Bricks menu, ensuring it loads after Bricks menu registration
add_action( 'admin_menu', 'webhook_for_forms_admin_menu', 20 );

function webhook_for_forms_admin_menu() {
    if ( ! class_exists('\Bricks\Capabilities') || \Bricks\Capabilities::current_user_has_no_access() ) {
        return;
    }

    global $menu;
    $bricks_menu_exists = array_filter( $menu, function( $item ) {
        return in_array( 'bricks', $item );
    });

    if ( $bricks_menu_exists ) {
        add_submenu_page(
            'bricks',                             // Parent slug (Bricks menu)
            'Webhook for Forms Settings',         // Page title
            'Webhook for Forms',                  // Menu title
            'manage_options',                     // Capability
            'webhook_for_forms',                  // Menu slug
            'render_webhook_for_forms_admin_page' // Callback function to render the page
        );
    }
}
function render_webhook_for_forms_admin_page() {
    // Check for form submission to save new form-webhook pairs and settings
    if ( isset( $_POST['webhook_for_forms_nonce'] ) && wp_verify_nonce( $_POST['webhook_for_forms_nonce'], 'save_webhook_for_forms' ) ) {
        $form_id = sanitize_text_field( $_POST['form_id'] );
        $webhook_url = esc_url_raw( $_POST['webhook_url'] );
        $response_format = !empty($_POST['response_format']) ? sanitize_text_field( $_POST['response_format'] ) : 'json';

        // Retrieve or initialize the form webhooks array
        $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
        if ( ! is_array( $form_webhooks ) ) {
            $form_webhooks = []; // Ensure it's an array
            update_option( 'webhook_for_forms_webhooks', $form_webhooks );
        }

        // Save the form data, with the format and URL for each form ID
        $form_webhooks[$form_id] = [
            'url' => $webhook_url,
            'format' => $response_format
        ];
        update_option( 'webhook_for_forms_webhooks', $form_webhooks );

        update_option( 'webhook_for_forms_debug', isset( $_POST['debug_mode'] ) );
        update_option( 'webhook_for_forms_log_next_submission', isset( $_POST['log_next_submission'] ) );

        echo '<div class="updated"><p>Settings saved successfully.</p></div>';
    }

    // Handle deletion of form-webhook pairs
    if ( isset( $_GET['delete_form_id'] ) ) {
        $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
        if ( ! is_array( $form_webhooks ) ) {
            $form_webhooks = []; // Ensure it's an array
            update_option( 'webhook_for_forms_webhooks', $form_webhooks );
        }

        unset( $form_webhooks[ $_GET['delete_form_id'] ] );
        update_option( 'webhook_for_forms_webhooks', $form_webhooks );

        echo '<div class="updated"><p>Form Webhook deleted successfully.</p></div>';
    }

    // Retrieve form-webhook pairs and settings
    $form_webhooks = get_option( 'webhook_for_forms_webhooks', [] );
    if ( ! is_array( $form_webhooks ) ) {
        $form_webhooks = []; // Ensure it's an array
    }
    $debug_mode = get_option( 'webhook_for_forms_debug', false );
    $log_next_submission = get_option( 'webhook_for_forms_log_next_submission', false );

    // Render the HTML for the admin page
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
            <tr>
                <th scope="row"><label for="response_format">Select Response Format</label></th>
                <td>
                    <select id="response_format" name="response_format">
                        <option value="json">JSON</option>
                        <option value="formdata">FormData</option>
                    </select>
                </td>
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
                <th>Response Format</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
    foreach ( $form_webhooks as $form_id => $webhook ) :
    // Check if the $webhook entry is a string (backward-compatible) or array
    if ( is_string( $webhook ) ) {
        // If the entry is a string, treat it as a URL and default format to JSON
        $url = $webhook;
        $format = 'json';
    } elseif ( is_array( $webhook ) && isset( $webhook['url'] ) ) {
        // If the entry is an array, use the URL and format keys
        $url = $webhook['url'];
        $format = $webhook['format'] ?? 'json';
    } else {
        // Skip malformed entries
        continue;
    }
            ?>
            <tr>
                <td><?php echo esc_html( $form_id ); ?></td>
                <td><?php echo esc_url( $url ); ?></td>
                <td><?php echo esc_html( $format ); ?></td>
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

        update_option( 'webhook_for_forms_log_next_submission', false );
    }

    // Check if the submitted form ID has a corresponding webhook URL
    if ( $submitted_form_id && isset( $form_webhooks[ $submitted_form_id ] ) ) {
        $webhook = $form_webhooks[ $submitted_form_id ];
        
        // Backward compatibility check: if $webhook is a string, treat it as a URL and default to JSON format
        if ( is_string( $webhook ) ) {
            $url = $webhook;
            $format = 'json';
        } elseif ( is_array( $webhook ) && isset( $webhook['url'] ) ) {
            // New format: array with 'url' and 'format' keys
            $url = $webhook['url'];
            $format = $webhook['format'] ?? 'json';
        } else {
            // Invalid entry format; skip processing
            return;
        }

        // Log for debugging purposes
        if ( $debug_mode ) {
            error_log( 'Triggering webhook for form ID ' . $submitted_form_id );
            error_log( 'Fields Data: ' . print_r( $fields, true ) );
            error_log( 'Webhook URL: ' . $url );
            error_log( 'Format: ' . $format );
        }

        // Send data to the custom webhook with the determined URL and format
        $response = send_data_to_webhook( $url, $fields, $format );

        if ( $debug_mode ) {
            error_log( 'Webhook Response: ' . print_r( $response, true ) );
        }
    } elseif ( $debug_mode ) {
        error_log( 'No webhook found for submitted form ID: ' . $submitted_form_id );
    }
}


/**
 * Send form data to a custom webhook using the selected format.
 *
 * @param string $url  The webhook URL.
 * @param array  $data The data to send.
 * @param string $response_format The response format to use (json or formdata).
 * @return array|WP_Error The response or WP_Error on failure.
 */
function send_data_to_webhook( $url, $data, $response_format = 'json' ) {
    // Determine the appropriate headers and body based on the response format
    if ( $response_format === 'formdata' ) {
        $body = http_build_query( $data );
        $headers = [ 'Content-Type' => 'application/x-www-form-urlencoded' ];
    } else {
        $body = json_encode( $data );
        $headers = [ 'Content-Type' => 'application/json' ];
    }

    // Perform the request
    $response = wp_remote_post( $url, [
        'body'    => $body,
        'headers' => $headers,
    ]);

    // Log error if the request fails
    if ( is_wp_error( $response ) ) {
        error_log( 'Error posting data: ' . $response->get_error_message() );
    } else {
        error_log( 'Data posted successfully: ' . print_r( wp_remote_retrieve_body( $response ), true ) );
    }

    return $response;
}