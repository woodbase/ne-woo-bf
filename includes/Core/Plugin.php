<?php

namespace NEBF\Core;

use NEBF\Controllers\AdminMenuController;

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
        // Register admin menu
        (new AdminMenuController())->register_hooks();
    }
}
