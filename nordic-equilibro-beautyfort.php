<?php
/**
 * Plugin Name: Nordic Equilibro – Beauty Fort WooCommerce Integration
 * Description: Intern integration för att synka produkter från Beauty Fort till WooCommerce.
 * Version: 0.0.4
 * Author: Nordic Equilibro
 * Text Domain: nordic-equilibro-beautyfort
 */

if (!defined('ABSPATH')) exit;

// ==========================
// LADDAR ALLA PHP-FILER
// ==========================
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
require_once $base_path . 'admin/settings-action.php';

// ==========================
// ADMIN-MENY
// ==========================
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

// ==========================
// ENQUEUE ADMIN JS + CSS
// ==========================
add_action('admin_enqueue_scripts', function ($hook) {

    if ($hook !== 'woocommerce_page_nordic-equilibro-beautyfort') return;

    $plugin_url = plugin_dir_url(__FILE__);

    wp_enqueue_script(
        'nebf-products-tab',
        $plugin_url . 'js/ne-beauty-woo-admin.js',
        ['jquery'],
        '1.1',
        true
    );

    wp_enqueue_style(
        'nebf-admin-css',
        $plugin_url . 'css/nebf-style.css',
        [],
        '1.0'
    );
});

// ==========================
// DEFAULT SETTINGS
// ==========================
register_activation_hook(__FILE__, 'nebf_set_default_pricing_settings');

function nebf_set_default_pricing_settings() {
    if (!get_option('nebf_pricing_settings')) {
        update_option('nebf_pricing_settings', [
            'default_type'  => 'percent',
            'default_value' => 30,
            'rounding'      => '99',
        ]);
    }
}
