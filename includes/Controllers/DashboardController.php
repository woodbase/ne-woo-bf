<?php
namespace NEBF\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

class DashboardController {

    public function handle() {
        include NEBF_MVC_PATH . 'admin/views/dashboard.php';
    }
}
