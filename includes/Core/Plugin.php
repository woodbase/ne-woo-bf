<?php

namespace NEBF\Core;

use NEBF\Controllers\AdminMenuController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class.
 */
class Plugin {

    /**
     * Initialize plugin components.
     */
    public function init() {
        $this->register_controllers();
    }

    /**
     * Register all controllers.
     */
    private function register_controllers() {
        $adminMenu = new AdminMenuController();
        $adminMenu->register_hooks();
    }
}
