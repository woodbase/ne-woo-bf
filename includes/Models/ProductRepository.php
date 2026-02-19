<?php

namespace NEBF\Models;

if (!defined('ABSPATH')) exit;

class ProductRepository
{
    /**
     * Get paginated products with search & filters
     */
    public function get_paginated(
        int $page,
        int $per_page,
        string $search = '',
        array $filters = []
    ): array {

        $all_products = get_option('nebf_beautyfort_products', []);

        if (!is_array($all_products)) {
            $all_products = [];
        }

        // Convert associative array (bf_id => product)
        // to indexed array to make array_slice work correctly
        $products = array_values($all_products);

        // ---------------------------
        // Apply search + filters
        // ---------------------------

        $products = array_filter($products, function ($product) use ($search, $filters) {

            // Brand filter
            if (!empty($filters['brand']) &&
                strcasecmp($product['brand'] ?? '', $filters['brand']) !== 0
            ) {
                return false;
            }

            // Collection filter
            if (!empty($filters['collection']) &&
                strcasecmp($product['collection'] ?? '', $filters['collection']) !== 0
            ) {
                return false;
            }

            // Search filter
            if (!empty($search)) {
                $haystack = strtolower(
                    ($product['fullname'] ?? '') . ' ' .
                    ($product['sku'] ?? '') . ' ' .
                    ($product['brand'] ?? '')
                );

                if (strpos($haystack, strtolower($search)) === false) {
                    return false;
                }
            }

            return true;
        });

        $products = array_values($products); // reindex after filter

        // ---------------------------
        // Pagination
        // ---------------------------

        $total_items = count($products);
        $total_pages = max(1, ceil($total_items / $per_page));

        if ($page < 1) {
            $page = 1;
        }

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $offset = ($page - 1) * $per_page;

        $items = array_slice($products, $offset, $per_page);

        // ---------------------------
        // Mark synced products
        // ---------------------------

        foreach ($items as &$item) {

            $existing = get_posts([
                'post_type'  => 'product',
                'meta_key'   => '_beautyfort_id',
                'meta_value' => $item['bf_id'] ?? '',
                'fields'     => 'ids',
                'numberposts' => 1,
            ]);

            $item['synced'] = !empty($existing);
        }

        unset($item);

        return [
            'items'       => $items,
            'page'        => $page,
            'total_pages' => $total_pages,
        ];
    }

    /**
     * Get product by BeautyFort ID
     */
    public function get_by_bf_id(string $bf_id): ?array
    {
        $all = get_option('nebf_beautyfort_products', []);

        if (!isset($all[$bf_id])) {
            return null;
        }

        return $all[$bf_id];
    }

    /**
     * Sync multiple products
     */
    public function sync_products(array $bf_ids): void
    {
        foreach ($bf_ids as $bf_id) {

            $product = $this->get_by_bf_id($bf_id);

            if (!$product) {
                continue;
            }

            nebf_sync_product_to_woo($product);
        }
    }
}
