<?php

namespace NEBF\Controllers;

use NEBF\Services\BeautyFortApiService;
use NEBF\Services\IntegrationTestService;
use NEBF\Services\TraceService;

if (!defined('ABSPATH')) exit;

/**
 * Controller for BeautyFort manual test orders + integration tests.
 */
class TestOrderController extends AbstractAdminController
{
    public function handle(): void
    {
        $result = null;
        $feedback = null;
        $integrationReport = null;

        $api = new BeautyFortApiService();
        $traceService = new TraceService();

        /*
        |--------------------------------------------------------------------------
        | Manual Create Order (Existing functionality)
        |--------------------------------------------------------------------------
        */
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_create_test_order']) &&
            check_admin_referer('nebf_create_test_order')
        ) {

            $type = isset($_POST['nebf_order_type'])
                ? sanitize_text_field((string) $_POST['nebf_order_type'])
                : '';

            $yourOrderReference = isset($_POST['nebf_order_reference'])
                ? sanitize_text_field((string) $_POST['nebf_order_reference'])
                : '';

            $result = $api->create_order($type, $yourOrderReference);

            if (is_wp_error($result)) {
                $feedback = [
                    'type' => 'error',
                    'message' => sprintf(
                        __('CreateOrder failed: %s', 'nebf-mvc'),
                        $result->get_error_message()
                    ),
                ];
            } elseif (!empty($result['success'])) {
                $feedback = [
                    'type' => 'success',
                    'message' => sprintf(
                        __('Test order created. OrderReference: %d', 'nebf-mvc'),
                        (int) ($result['order_reference'] ?? 0)
                    ),
                ];
            } else {
                $feedback = [
                    'type' => 'warning',
                    'message' => __('CreateOrder returned validation errors. Check details below.', 'nebf-mvc'),
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Integration Tests (NEW serious mode)
        |--------------------------------------------------------------------------
        */
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_run_integration_tests']) &&
            check_admin_referer('nebf_run_integration_tests_action')
        ) {

            if (get_option('nebf_api_testmode', '0') !== '1') {

                $feedback = [
                    'type' => 'error',
                    'message' => __('Integration tests require TestMode enabled in Settings.', 'nebf-mvc'),
                ];

            } else {

                $integrationService = new IntegrationTestService();
                $integrationReport = $integrationService->run();

                if (!empty($integrationReport['success'])) {
                    $feedback = [
                        'type' => 'success',
                        'message' => __('Integration tests completed successfully.', 'nebf-mvc'),
                    ];
                } else {
                    $feedback = [
                        'type' => 'warning',
                        'message' => __('Integration tests completed with failures. Review the report below.', 'nebf-mvc'),
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Get latest trace via TraceService (NEW architecture)
        |--------------------------------------------------------------------------
        */
        $trace = $traceService->get('create');

        $this->render('test-order', [
            'result'            => $result,
            'trace'             => $trace,
            'feedback'          => $feedback,
            'integrationReport' => $integrationReport,
        ]);
    }
}