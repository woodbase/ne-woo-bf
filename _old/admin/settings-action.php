<?php
if (!defined('ABSPATH')) exit;

/**
 * Handles POST actions for the settings page
 */
add_action('admin_init', 'nebf_handle_settings_actions');

function nebf_handle_settings_actions()
{
    if (!isset($_POST['nebf_action'])) return;

    // ==============================
    // IMPORTERA PRODUKTER
    // ==============================
    if ($_POST['nebf_action'] === 'import_products') {

        check_admin_referer('nebf_import_nonce');

        $result = nebf_import_products();

        if (is_wp_error($result)) {

            $message = $result->get_error_message();

            wp_redirect(add_query_arg([
                'page'        => 'nordic-equilibro-beautyfort',
                'nebf_notice' => 'error',
                'message'     => urlencode($message)
            ], admin_url('admin.php')));
            exit;
        } else {

            $last_fetch       = current_time('mysql');
            $last_fetch_count = $result['total'];

            update_option('nebf_last_fetch', $last_fetch);
            update_option('nebf_last_fetch_count', $last_fetch_count);

            $message = sprintf(
                /* translators: %d = antal importerade produkter */
                __('%d produkter importerade som utkast.', 'ne-bf-woo'),
                $last_fetch_count
            );

            wp_redirect(add_query_arg([
                'page'        => 'nordic-equilibro-beautyfort',
                'nebf_notice' => 'success',
                'message'     => urlencode($message)
            ], admin_url('admin.php')));
            exit;
        }
    }

    // ==============================
    // TEST API
    // ==============================
    if ($_POST['nebf_action'] === 'test_api') {

        check_admin_referer('nebf_test_api_nonce');

        $result = nebf_test_api_connection();

        $message = is_wp_error($result)
            ? $result->get_error_message()
            : __('API-anslutning OK', 'ne-bf-woo');

        wp_redirect(add_query_arg([
            'page'        => 'nordic-equilibro-beautyfort',
            'nebf_notice' => is_wp_error($result) ? 'error' : 'success',
            'message'     => urlencode($message)
        ], admin_url('admin.php')));
        exit;
    }
}
