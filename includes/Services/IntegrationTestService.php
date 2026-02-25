<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

class IntegrationTestService
{
    private BeautyFortApiService $api;
    private CleanupService $cleanup;

    public function __construct()
    {
        $this->api = new BeautyFortApiService();
        $this->cleanup = new CleanupService();
    }

    /**
     * Run full integration suite.
     */
    public function run(): array
    {
        if (get_option('nebf_api_testmode', '0') !== '1') {
            return [
                'success' => false,
                'error'   => 'Integration tests require TestMode enabled.'
            ];
        }

        $createdOrders = [];
        $report = [];

        /*
        |--------------------------------------------------------------------------
        | Test 1 – Create Order (Direct Dispatch for cancel support)
        |--------------------------------------------------------------------------
        */
        $create = $this->api->create_order('Direct Dispatch');

        $report['create_order'] = [
            'success' => !is_wp_error($create) && !empty($create['success']),
            'result'  => $create,
        ];

        if (!is_wp_error($create) && !empty($create['order_reference'])) {
            $createdOrders[] = $create['order_reference'];
        }

        /*
        |--------------------------------------------------------------------------
        | Test 2 – Add Multiple Items
        |--------------------------------------------------------------------------
        */
        if (!empty($create['order_reference'])) {

            $orderRef = $create['order_reference'];

            // Replace with real test SKUs that exist in testmode
            $item1 = $this->api->add_order_item($orderRef, 'P407231', 2);
            $item2 = $this->api->add_order_item($orderRef, 'I822692', 3);

            $report['add_multiple'] = [
                'success' =>
                    !is_wp_error($item1) &&
                    !is_wp_error($item2) &&
                    !empty($item1['success']) &&
                    !empty($item2['success']),
                'results' => [$item1, $item2],
            ];
        } else {
            $report['add_multiple'] = ['success' => false];
        }

        /*
        |--------------------------------------------------------------------------
        | Test 3 – Edit Quantity
        |--------------------------------------------------------------------------
        */
        if (!empty($create['order_reference'])) {

            $orderRef = $create['order_reference'];

            $add = $this->api->add_order_item($orderRef, 'TESTSKU1', 1);

            $itemRef = $add['order_item_reference'] ?? null;

            if (!is_wp_error($add) && !empty($add['success']) && $itemRef) {

                $edit = $this->api->edit_order_item($orderRef, $itemRef, 5);

                $report['edit_quantity'] = [
                    'success' => !is_wp_error($edit) && !empty($edit['success']),
                    'result'  => $edit,
                ];

            } else {
                $report['edit_quantity'] = ['success' => false];
            }

        } else {
            $report['edit_quantity'] = ['success' => false];
        }

        /*
        |--------------------------------------------------------------------------
        | Test 4 – Invalid Product
        |--------------------------------------------------------------------------
        */
        if (!empty($create['order_reference'])) {

            $orderRef = $create['order_reference'];

            $invalid = $this->api->add_order_item($orderRef, 'INVALIDSKU123', 1);

            $report['invalid_product'] = [
                'success' => is_wp_error($invalid) || empty($invalid['success']),
                'result'  => $invalid,
            ];
        } else {
            $report['invalid_product'] = ['success' => false];
        }

        /*
        |--------------------------------------------------------------------------
        | CLEANUP – Cancel all created orders
        |--------------------------------------------------------------------------
        */
        if (!empty($createdOrders)) {

            $cleanupResults = $this->cleanup->cancel_orders($createdOrders);

            $report['cleanup'] = $cleanupResults;

        } else {
            $report['cleanup'] = ['success' => false];
        }

        /*
        |--------------------------------------------------------------------------
        | Overall Success
        |--------------------------------------------------------------------------
        */
        $testResults = [];

        foreach ($report as $key => $value) {
            if ($key === 'cleanup') continue;
            if (isset($value['success'])) {
                $testResults[] = $value['success'];
            }
        }

        $report['success'] = !in_array(false, $testResults, true);

        return $report;
    }
}