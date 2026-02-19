<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) exit;

/**
 * Routes current admin page request to the correct controller.
 */
class AdminPageRouter {

    public function handle(): void
    {
        $tab = $_GET['tab'] ?? 'dashboard';

        // Render shared header + tabs
        $active_tab = $tab;
        include NEBF_MVC_PATH . 'admin/views/partials/page-header-tabs.php';

        // Dispatch to the correct controller
        switch ($tab) {
            case 'products':
                (new ProductsController())->handle();
                break;
            case 'settings':
                (new SettingsController())->handle();
                break;
            default:
                (new DashboardController())->handle();
                break;
        }
    }
}
