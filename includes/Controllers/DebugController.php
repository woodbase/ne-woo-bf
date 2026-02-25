<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) exit;

class DebugController extends AbstractAdminController
{
    public function handle(): void
    {
        // Handle clear logs action
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_clear_debug']) &&
            check_admin_referer('nebf_clear_debug_action')
        ) {
            delete_option('nebf_last_create_order_trace');
            delete_option('nebf_last_add_order_item_trace');
            delete_option('nebf_last_edit_order_item_trace');
            delete_option('nebf_last_error_trace');
        }

        $this->render('debug', [
            'create_trace' => get_option('nebf_last_create_order_trace'),
            'add_trace'    => get_option('nebf_last_add_order_item_trace'),
            'edit_trace'   => get_option('nebf_last_edit_order_item_trace'),
            'error_trace'  => get_option('nebf_last_error_trace'),
        ]);
    }
}