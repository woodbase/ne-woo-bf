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
?>
<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . 'admin/js/admin.js'; ?>"></script>
<?php