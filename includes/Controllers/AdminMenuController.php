<?php
namespace NEBF\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenuController {

    public function register_hooks() {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu() {
        add_submenu_page(
            'woocommerce',
            __('Nordic Equilibro - BeautyFort integration', 'nebf-mvc'),
            __('Nordic Equilibro - BeautyFort', 'nebf-mvc'),
            'manage_options',
            'nebf-mvc',
            [$this, 'render']
        );
    }

    public function render() {
        (new AdminPageRouter())->handle();
    }
}

