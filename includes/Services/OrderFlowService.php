<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

class OrderFlowService
{
    private BeautyFortApiService $api;

    public function __construct()
    {
        $this->api = new BeautyFortApiService();
    }

    public function create_full_order(string $type, array $items)
    {
        $create = $this->api->create_order($type);

        if (is_wp_error($create) || empty($create['success'])) {
            return $create;
        }

        $orderReference = $create['order_reference'] ?? null;

        if (!$orderReference) {
            return new \WP_Error('nebf_missing_order_ref', 'Missing order reference.');
        }

        foreach ($items as $item) {
            $add = $this->api->add_order_item(
                $orderReference,
                $item['stock_code'],
                $item['quantity']
            );

            if (is_wp_error($add) || empty($add['success'])) {
                return new \WP_Error('nebf_add_failed', 'Order item failed.');
            }
        }

        return [
            'success' => true,
            'order_reference' => $orderReference,
        ];
    }
}