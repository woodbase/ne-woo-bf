<?php

namespace NEBF\Core;

use NEBF\Controllers\AdminMenuController;
use NEBF\Services\WebPriceLookupQueueService;

if (!defined('ABSPATH')) exit;

/**
 * Main plugin class.
 */
class Plugin {

    /**
     * Initialize plugin.
     */
    public function init(): void
    {
        // Register plugin admin menu and page routes.
        $admin_menu = new AdminMenuController();
        $admin_menu->register_hooks();

        // Register background queue hooks.
        $web_price_lookup_queue = new WebPriceLookupQueueService();
        $web_price_lookup_queue->register_hooks();

        // Enqueue admin assets (CSS/JS) only when needed.
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue CSS/JS for admin pages
     */
    public function enqueue_admin_assets(): void
    {
        $screen = get_current_screen();

        // Only load assets on this plugin's admin screens.
        if ($screen && str_contains($screen->id, 'nebf-mvc')) {
        wp_enqueue_style(
            'nebf-admin',
            NEBF_MVC_URL . 'assets/css/admin.css',
            [],
            NEBF_MVC_VERSION
        );

            // If needed later, enqueue JS here as well.
            // wp_enqueue_script(
            //     'nebf-admin-js',
            //     NEBF_MVC_URL . 'admin/assets/js/admin.js',
            //     ['jquery'],
            //     NEBF_MVC_VERSION,
            //     true
            // );
        }
    }
}
