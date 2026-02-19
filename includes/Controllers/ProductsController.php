<?php

namespace NEBF\Controllers;

use NEBF\Models\ProductRepository;

if (!defined('ABSPATH')) exit;

class ProductsController extends AbstractAdminController {

    protected ProductRepository $repo;

    public function __construct() {
        $this->repo = new ProductRepository();
    }

    public function handle(): void {
        $page       = (int) ($_GET['paged'] ?? 1);
        $per_page   = 20;
        $search     = $_GET['s'] ?? '';
        $filters    = [
            'brand'      => $_GET['brand'] ?? '',
            'collection' => $_GET['collection'] ?? '',
            'status'     => $_GET['status'] ?? '',
        ];

        $products = $this->repo->get_paginated($page, $per_page, $search, $filters);

        $this->render('products', [
            'products'    => $products,
            'page'        => $products['page'] ?? $page,
            'total_pages' => $products['total_pages'] ?? 1,
            'search_term' => $search,
            'filters'     => $filters,
        ]);
    }

    public function sync_selected(array $bf_ids): void {
        $this->repo->sync_products($bf_ids);

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>Selected products synced to WooCommerce successfully!</p></div>';
        });
    }
}
