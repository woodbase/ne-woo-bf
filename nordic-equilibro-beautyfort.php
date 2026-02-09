<?php

/**
 * Plugin Name: Nordic Equilibro – Beauty Fort WooCommerce Integration
 * Description: Intern integration för att synka produkter från Beauty Fort till WooCommerce.
 * Version: 0.0.2
 * Author: Nordic Equilibro
 * Text Domain: nordic-equilibro-beautyfort
 */

if (!defined('ABSPATH')) exit;

add_action('woocommerce_loaded', function () {

    // Ladda admin-meny
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

    // Ladda övriga filer
    require_once plugin_dir_path(__FILE__) . 'includes/api-client.php';
    require_once plugin_dir_path(__FILE__) . 'includes/product-import.php';
    require_once plugin_dir_path(__FILE__) . 'includes/stock-price-sync.php';
    require_once plugin_dir_path(__FILE__) . 'includes/images.php';
    require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';
    require_once plugin_dir_path(__FILE__) . 'includes/cron.php';
    require_once plugin_dir_path(__FILE__) . 'admin/admin-control-page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/import-page.php';
});

add_action('admin_enqueue_scripts', function ($hook) {
    // Ladda bara på din Beautyfort-sida
    if ($hook !== 'woocommerce_page_nordic-equilibro-beautyfort') {
        error_log('NEBF hook not found!');
        return;
    }

    wp_enqueue_script(
        'nebf-admin-accordion',
        plugin_dir_url(__FILE__) . 'js/ne-beauty-woo-admin.js',
        ['jquery'], // viktigt i admin
        '1.0',
        true
    );
});