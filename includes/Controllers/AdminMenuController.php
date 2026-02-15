<?php

namespace NEBF\Controllers;
    use NEBF\Models\ProductRepository;
    use NEBF\Services\ProductSyncService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin menu registration.
 */
class AdminMenuController {

    /**
     * Register WordPress hooks.
     */
    public function register_hooks() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /**
     * Register WooCommerce submenu page.
     */
    public function register_menu() {
        // Ensure WooCommerce menu exists
        if (class_exists('WooCommerce')) {
            add_submenu_page(
                'woocommerce', // parent slug (WooCommerce top menu)
                __('Nordic Equilibro - BeautyFort integration', 'nebf-mvc'), // page title
                __('Nordic Equilibro - BeautyFort', 'nebf-mvc'),       // menu title
                'manage_options',                  // capability
                'nebf-mvc',                        // menu slug
                [$this, 'render_dashboard']        // callback
            );
        }
    }

    /**
     * Render dashboard view.
     */
public function render_dashboard() {
    $repo = new ProductRepository();

    // Uncomment to generate mock products once
    // $repo->generate_mock_products(50);

    $search_term = $_GET['s'] ?? '';
    $page = max(1, intval($_GET['paged'] ?? 1));
    $per_page = 10;

    $paginated = $repo->get_paginated($page, $per_page, $search_term);
    $products = $paginated['items'];
    $total = $paginated['total'];
    $total_pages = ceil($total / $per_page);
if (
    isset($_POST['nebf_sync_all']) &&
    check_admin_referer('nebf_sync_products')
) {
    $sync = new ProductSyncService();
    $sync->sync_multiple($repo->get_all());

    echo '<div class="notice notice-success"><p>'
        . esc_html__('Products synced successfully.', 'nebf-mvc')
        . '</p></div>';
}
    include NEBF_MVC_PATH . 'admin/views/dashboard.php';
}
}
