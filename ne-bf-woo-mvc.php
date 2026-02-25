<?php
/**
 * Plugin Name: NE BeautyFort Woo
 * Description: BeautyFort WooCommerce integration.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
 * WC requires at least: 7.0
 * Author: Nordic Equilibro
 * Text Domain: nebf-mvc
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Avoid session warnings (only start if needed and not in CLI)
 */
if (!session_id() && !wp_doing_cron() && php_sapi_name() !== 'cli') {
    session_start();
}

/**
 * Core constants
 */
define('NEBF_MVC_PATH', plugin_dir_path(__FILE__));
define('NEBF_MVC_URL', plugin_dir_url(__FILE__));
define('NEBF_MVC_VERSION', '1.0.0'); // MUST match plugin header

/**
 * Load translations
 */
function nebf_mvc_load_textdomain() {
    load_plugin_textdomain(
        'nebf-mvc',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'nebf_mvc_load_textdomain');

/**
 * Load core files
 */
require_once NEBF_MVC_PATH . 'includes/Core/Autoloader.php';
require_once NEBF_MVC_PATH . 'includes/Support/helpers.php';

/**
 * Bootstrap plugin
 */
function nebf_mvc_boot() {

    // Init main plugin
    $plugin = new NEBF\Core\Plugin();
    $plugin->init();

    // Init GitHub updater (native WP updates + RC ignore)
    if (is_admin()) {
        new NEBF\Updater\GitHubUpdater(__FILE__);
    }
}

nebf_mvc_boot();