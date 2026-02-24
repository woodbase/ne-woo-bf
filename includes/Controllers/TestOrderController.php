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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nebf_create_test_order')) {
            $type = isset($_POST['nebf_order_type']) ? sanitize_text_field((string) $_POST['nebf_order_type']) : '';
            $yourOrderReference = isset($_POST['nebf_order_reference']) ? sanitize_text_field((string) $_POST['nebf_order_reference']) : '';

            $service = new BeautyFortApiService();
            $result = $service->create_order($type, $yourOrderReference);

            if (is_wp_error($result)) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-error is-dismissible"><p>'
                        . esc_html(sprintf(__('CreateOrder failed: %s', 'nebf-mvc'), $result->get_error_message()))
                        . '</p></div>';
                });
            } elseif (!empty($result['success'])) {
                add_action('admin_notices', function () use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>'
                        . esc_html(sprintf(__('Test order created. OrderReference: %d', 'nebf-mvc'), (int) ($result['order_reference'] ?? 0)))
                        . '</p></div>';
                });
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-warning is-dismissible"><p>'
                        . esc_html__('CreateOrder returned validation errors. Check details below.', 'nebf-mvc')
                        . '</p></div>';
                });
            }
        }

        $this->render('test-order', [
            'result' => $result,
        ]);
    }
}
