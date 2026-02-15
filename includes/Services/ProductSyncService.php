<?php

namespace NEBF\Services;

use WC_Product_Simple;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles syncing products to WooCommerce.
 */
class ProductSyncService {

    /**
     * Sync a single product to WooCommerce.
     *
     * @param array $data
     * @return int WooCommerce product ID
     */
    public function sync(array $data): int {

        // Check if product already exists by SKU
        $existing_id = wc_get_product_id_by_sku($data['sku']);

        if ($existing_id) {
            $product = wc_get_product($existing_id);
        } else {
            $product = new WC_Product_Simple();
        }

        $product->set_name($data['name']);
        $product->set_sku($data['sku']);
        $product->set_regular_price($data['price']);
        $product->set_catalog_visibility(
            $data['visible'] ? 'visible' : 'hidden'
        );

        return $product->save();
    }

    /**
     * Sync multiple products.
     *
     * @param array $products
     */
    public function sync_multiple(array $products) {
        foreach ($products as $product) {
            $this->sync($product);
        }
    }
}
