<?php
/*
Plugin Name: Webhook for Bricks Forms
Description: Adds form ID and webhook URL pairs to trigger specific webhooks on Bricks form submissions, with debug options and response format selection.
Plugin URI: https://github.com/stingray82/webhooks-for-bricks-forms/
Version: 1.2
Author: Stingray82 & Reallyusefulplugins
Text Domain: webhooks-for-bricks-forms
Author URI: https://Reallyusefulplugins.com
License: GPLv2 or later
Domain Path: /languages
*/

// Load plugin text domain for translations
add_action( 'plugins_loaded', 'webhook_for_forms_load_textdomain' );
function webhook_for_forms_load_textdomain() {
    load_plugin_textdomain( 'webhooks-for-bricks-forms', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Add submenu under the Bricks menu
add_action( 'admin_menu', 'webhook_for_forms_admin_menu', 20 );
function webhook_for_forms_admin_menu() {
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
            __( 'Webhook for Forms Settings', 'webhooks-for-bricks-forms' ),
            __( 'Webhook for Forms', 'webhooks-for-bricks-forms' ),
            'manage_options',
            'webhook_for_forms',
            'render_webhook_for_forms_admin_page'
        );
    }
}

// Render admin page
function render_webhook_for_forms_admin_page() {
    if ( isset( $_POST['webhook_for_forms_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['webhook_for_forms_nonce'] ) ), 'save_webhook_for_forms' ) ) {
        $form_id = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) : '';
        $webhook_url = isset( $_POST['webhook_url'] ) ? esc_url_raw( wp_unslash( $_POST['webhook_url'] ) ) : '';
        $response_format = isset( $_POST['response_format'] ) ? sanitize_text_field( wp_unslash( $_POST['response_format'] ) ) : 'json';

        $form_webhooks = get_option( 'webhooks_for_forms_webhooks', [] );
        if ( ! is_array( $form_webhooks ) ) {
            $form_webhooks = [];
        }

        if ( $form_id && $webhook_url ) {
            $form_webhooks[ $form_id ] = [
                'url'    => $webhook_url,
                'format' => $response_format,
            ];

            update_option( 'webhooks_for_forms_webhooks', $form_webhooks );
            update_option( 'webhook_for_forms_debug', isset( $_POST['debug_mode'] ) );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved successfully.', 'webhooks-for-bricks-forms' ) . '</p></div>';
        }
    }

    if ( isset( $_GET['delete_form_id'] ) ) {
        $form_webhooks = get_option( 'webhooks_for_forms_webhooks', [] );
        $delete_form_id = sanitize_text_field( wp_unslash( $_GET['delete_form_id'] ) );
        unset( $form_webhooks[ $delete_form_id ] );
        update_option( 'webhooks_for_forms_webhooks', $form_webhooks );

        echo '<div class="updated"><p>' . esc_html__( 'Form Webhook deleted successfully.', 'webhooks-for-bricks-forms' ) . '</p></div>';
    }

    $form_webhooks = get_option( 'webhooks_for_forms_webhooks', [] );
    $debug_mode = get_option( 'webhook_for_forms_debug', false );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Webhook for Forms Settings', 'webhooks-for-bricks-forms' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'save_webhook_for_forms', 'webhook_for_forms_nonce' ); ?>
            <h2><?php esc_html_e( 'Add New Form Webhook', 'webhooks-for-bricks-forms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="form_id"><?php esc_html_e( 'Form ID', 'webhooks-for-bricks-forms' ); ?></label></th>
                    <td><input type="text" id="form_id" name="form_id" /></td>
                </tr>
                <tr>
                    <th><label for="webhook_url"><?php esc_html_e( 'Webhook URL', 'webhooks-for-bricks-forms' ); ?></label></th>
                    <td><input type="url" id="webhook_url" name="webhook_url" /></td>
                </tr>
                <tr>
                    <th><label for="response_format"><?php esc_html_e( 'Response Format', 'webhooks-for-bricks-forms' ); ?></label></th>
                    <td>
                        <select id="response_format" name="response_format">
                            <option value="json"><?php esc_html_e( 'JSON', 'webhooks-for-bricks-forms' ); ?></option>
                            <option value="formdata"><?php esc_html_e( 'FormData', 'webhooks-for-bricks-forms' ); ?></option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Debug Options', 'webhooks-for-bricks-forms' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="debug_mode"><?php esc_html_e( 'Enable Debug Mode', 'webhooks-for-bricks-forms' ); ?></label></th>
                    <td><input type="checkbox" id="debug_mode" name="debug_mode" <?php checked( $debug_mode ); ?> /></td>
                </tr>
            </table>

            <p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'webhooks-for-bricks-forms' ); ?></button></p>
        </form>

        <h2><?php esc_html_e( 'Existing Form Webhooks', 'webhooks-for-bricks-forms' ); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Form ID', 'webhooks-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Webhook URL', 'webhooks-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Response Format', 'webhooks-for-bricks-forms' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'webhooks-for-bricks-forms' ); ?></th>
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
                        <td><a href="<?php echo esc_url( add_query_arg( 'delete_form_id', $form_id ) ); ?>" class="button"><?php esc_html_e( 'Delete', 'webhooks-for-bricks-forms' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Hook into Bricks form submissions
add_action( 'bricks/form/custom_action', 'trigger_webhook_on_bricks_form_submission' );
function trigger_webhook_on_bricks_form_submission( $form ) {
    $form_webhooks = get_option( 'webhooks_for_forms_webhooks', [] );
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

        wp_remote_post( $url, [
            'body'    => $body,
            'headers' => $headers,
        ] );
    }
}
