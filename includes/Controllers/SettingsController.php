<?php
namespace NEBF\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

class SettingsController {

    public function handle() {
        include NEBF_MVC_PATH . 'admin/views/settings.php';
    }
}
