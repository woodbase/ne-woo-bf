<?php

/**
 * Plugin Name: Nordic Equilibro – Beauty Fort WooCommerce Integration
 * Description: Intern integration för att synka produkter från Beauty Fort till WooCommerce.
 * Version: 0.0.3
 * Author: Nordic Equilibro
 * Text Domain: nordic-equilibro-beautyfort
 */

if (!defined('ABSPATH')) exit;

/* ======================================================
   ADMIN-MENY
   ====================================================== */
add_action('woocommerce_loaded', function () {

    // Admin-submenu under WooCommerce
    add_action('admin_menu', function () {
        add_submenu_page(
            'woocommerce',
            'Nordic Equilibro – Beauty Fort',
            'Nordic Equilibro',
            'manage_woocommerce',
            'nordic-equilibro-beautyfort',
            'nebf_admin_page'
        );
    });

    /* =========================
       LADDAR ALLA PHP-FILER
       ========================= */
    $base_path = plugin_dir_path(__FILE__);

    require_once $base_path . 'includes/helpers.php';
    require_once $base_path . 'includes/api-client.php';
    require_once $base_path . 'includes/product-import.php';
    require_once $base_path . 'includes/stock-price-sync.php';
    require_once $base_path . 'includes/images.php';
    require_once $base_path . 'includes/cron.php';
    require_once $base_path . 'admin/admin-control-page.php';
    require_once $base_path . 'admin/import-page.php';
    require_once $base_path . 'admin/products-page.php';
});

/* ======================================================
   ENQUEUE ADMIN JS + CSS
   ====================================================== */
add_action('admin_enqueue_scripts', function ($hook) {

    // Endast på Beautyfort-sidan
    if ($hook !== 'woocommerce_page_nordic-equilibro-beautyfort') return;

    $plugin_url = plugin_dir_url(__FILE__);

    // jQuery-baserad admin JS
    wp_enqueue_script(
        'nebf-products-tab',
        $plugin_url . 'js/ne-beauty-woo-admin.js',
        ['jquery'],
        '1.1',
        true
    );

    // Admin CSS
    wp_enqueue_style(
        'nebf-admin-css',
        $plugin_url . 'css/admin.css',
        [],
        '1.0'
    );
});
