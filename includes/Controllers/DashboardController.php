<?php

namespace NEBF\Controllers;

use NEBF\Models\ProductRepository;

if (!defined('ABSPATH')) exit;

/**
 * Dashboard controller
 */
class DashboardController extends AbstractAdminController {

    public function handle(): void
    {
        $repo = new ProductRepository();

        // Handle full sync from BeautyFort API
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nebf_sync_all']) && check_admin_referer('nebf_sync_products')) {

            $api_products = $this->fetch_products_from_api();

            // Respect "Separate Brand" setting
            $separate = (bool) get_option('nebf_separate_brand', 0);

            foreach ($api_products as &$product) {
                $brand = $product['brand'] ?? '';
                $name  = $product['name'] ?? '';

                if ($separate && !empty($brand) && stripos($name, $brand) === 0) {
                    $name = trim(substr($name, strlen($brand))); // remove brand from name
                }

                $product['brand'] = $brand;
                $product['name']  = $name;
            }

            // Save products
            $repo->save_products($api_products);

            // Update last sync timestamp
            update_option('nebf_last_sync', current_time('mysql'));
        }

        // Count total / synced / unsynced products
        $all_products = $repo->get_all();
        $total_products = count($all_products);
        $synced = 0;

        foreach ($all_products as $product) {
            if (!empty($product['sku']) && wc_get_product_id_by_sku($product['sku'])) {
                $synced++;
            }
        }

        $this->render('dashboard', [
            'total_products'   => $total_products,
            'synced_products'  => $synced,
            'unsynced_products'=> $total_products - $synced,
            'last_sync'        => get_option('nebf_last_sync'),
        ]);
    }

    /**
     * Fetch products from BeautyFort API
     *
     * @return array
     */
    private function fetch_products_from_api(): array
    {
        $username = get_option('nebf_username', '');
        $api_key  = get_option('nebf_api_key', '');

        if (!$username || !$api_key) {
            return [];
        }

        // Example: replace with real API call
        $response = wp_remote_get('https://api.beautyfort.com/products', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$username}:{$api_key}")
            ],
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return is_array($data) ? $data : [];
    }
}
