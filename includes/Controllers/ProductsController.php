<?php

namespace NEBF\Controllers;

use NEBF\Models\ProductRepository;

if (!defined('ABSPATH')) exit;

/**
 * Controller for Products tab.
 */
class ProductsController extends AbstractAdminController {

    protected ProductRepository $repo;

    public function __construct() {
        $this->repo = new ProductRepository();
    }

    public function handle(): void
    {
        $page   = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
        $search = $_GET['s'] ?? '';
        $per_page = 20;

        $products = $this->repo->get_paginated($page, $per_page, $search);

        $this->render('products', [
            'products'    => $products['items'] ?? [],
            'page'        => $products['page'] ?? 1,
            'total_pages' => $products['total_pages'] ?? 1,
            'search_term' => $search,
        ]);
    }
}
