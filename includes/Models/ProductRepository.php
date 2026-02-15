<?php

namespace NEBF\Models;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles product data storage and retrieval.
 */
class ProductRepository {

    private $option_key = 'nebf_products';

    public function get_all(): array {
        $products = get_option($this->option_key, []);
        return is_array($products) ? $products : [];
    }

    public function save_all(array $products): bool {
        return update_option($this->option_key, $products);
    }

    public function get_by_id($id): ?array {
        $all = $this->get_all();
        return $all[$id] ?? null;
    }

    /**
     * Generate some mock products for testing.
     */
    public function generate_mock_products(int $count = 10) {
        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $products[$i] = [
                'id' => $i,
                'name' => "Product $i",
                'sku' => "SKU$i",
                'price' => rand(50, 500),
                'visible' => $i % 2 === 0, // mock visibility flag
            ];
        }
        $this->save_all($products);
    }

    /**
     * Search products by term in name or SKU.
     * Searches all products, not just visible ones.
     *
     * @param string $term
     * @return array
     */
    public function search(string $term): array {
        $all = $this->get_all();
        $term = strtolower($term);

        return array_filter($all, function($product) use ($term) {
            return strpos(strtolower($product['name']), $term) !== false
                || strpos(strtolower($product['sku']), $term) !== false;
        });
    }

    /**
 * Get paginated products.
 *
 * @param int $page Current page number.
 * @param int $per_page Items per page.
 * @param string $search Search term (optional).
 * @return array [ 'items' => array, 'total' => int ]
 */
public function get_paginated(int $page = 1, int $per_page = 10, string $search = ''): array {
    $all = $search ? $this->search($search) : $this->get_all();

    $total = count($all);
    $offset = ($page - 1) * $per_page;
    $items = array_slice($all, $offset, $per_page, true);

    return [
        'items' => $items,
        'total' => $total,
    ];
}

}
