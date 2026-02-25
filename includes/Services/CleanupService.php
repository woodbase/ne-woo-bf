<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

/**
 * Handles cleanup of test orders using CancelOrder API.
 */
class CleanupService
{
    private BeautyFortApiService $api;

    public function __construct()
    {
        $this->api = new BeautyFortApiService();
    }

    /**
     * Cancel a list of order references.
     *
     * @param int[] $orderReferences
     * @return array
     */
    public function cancel_orders(array $orderReferences): array
    {
        $results = [];

        foreach ($orderReferences as $orderRef) {

            $cancel = $this->api->cancel_order((int) $orderRef);

            $results[$orderRef] = [
                'success' => !is_wp_error($cancel) && !empty($cancel['success']),
                'result'  => $cancel,
            ];
        }

        return $results;
    }
}