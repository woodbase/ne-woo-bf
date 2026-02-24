<?php

namespace NEBF\Controllers;

use NEBF\Services\BeautyFortApiService;

if (!defined('ABSPATH')) exit;

/**
 * Controller for sending BeautyFort test orders from WP admin.
 */
class TestOrderController extends AbstractAdminController
{
    public function handle(): void
    {
        $result = null;
        $trace = [];
        $feedback = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nebf_create_test_order')) {
            $type = isset($_POST['nebf_order_type']) ? sanitize_text_field((string) $_POST['nebf_order_type']) : '';
            $yourOrderReference = isset($_POST['nebf_order_reference']) ? sanitize_text_field((string) $_POST['nebf_order_reference']) : '';

            $service = new BeautyFortApiService();
            $result = $service->create_order($type, $yourOrderReference);

            if (is_wp_error($result)) {
                $feedback = [
                    'type' => 'error',
                    'message' => sprintf(__('CreateOrder failed: %s', 'nebf-mvc'), $result->get_error_message()),
                ];
            } elseif (!empty($result['success'])) {
                $feedback = [
                    'type' => 'success',
                    'message' => sprintf(__('Test order created. OrderReference: %d', 'nebf-mvc'), (int) ($result['order_reference'] ?? 0)),
                ];
            } else {
                $feedback = [
                    'type' => 'warning',
                    'message' => __('CreateOrder returned validation errors. Check details below.', 'nebf-mvc'),
                ];
            }
        }

        $trace = (new BeautyFortApiService())->get_last_create_order_trace();

        $this->render('test-order', [
            'result' => $result,
            'trace' => $trace,
            'feedback' => $feedback,
        ]);
    }
}
