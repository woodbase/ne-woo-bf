<?php

namespace NEBF\Models;

if (!defined('ABSPATH')) exit;

class ProductRepository {

    /**
     * Get all products from stored option
     */
    public function get_all(): array
    {
        $products = get_option('nebf_all_products', []);
        return is_array($products) ? $products : [];
    }

    /**
     * Paginated products with optional search
     */
    public function get_paginated(int $page = 1, int $per_page = 20, string $search = ''): array
    {
        $all_products = $this->get_all();

        if ($search !== '') {
            $all_products = array_filter($all_products, function ($p) use ($search) {
                return stripos($p['name'], $search) !== false || stripos($p['sku'], $search) !== false;
            });
        }

        $total_items = count($all_products);
        $total_pages = (int) ceil($total_items / $per_page);
        $page = max(1, min($page, $total_pages));

        $offset = ($page - 1) * $per_page;
        $items = array_slice($all_products, $offset, $per_page);

        // Apply separate brand setting
        $separate = (bool) get_option('nebf_separate_brand', 0);

        if ($separate) {
            foreach ($items as &$item) {
                if (!empty($item['brand']) && !empty($item['name'])) {
                    // Remove brand prefix from name if present
                    if (stripos($item['name'], $item['brand']) === 0) {
                        $item['name'] = trim(substr($item['name'], strlen($item['brand'])));
                    }
                }
            }
        }

        return [
            'items'       => $items,
            'page'        => $page,
            'total_pages' => $total_pages,
        ];
    }

    /**
     * Save or update products (from API)
     */
    public function save_products(array $products): void
    {
        update_option('nebf_all_products', $products);
    }
}
