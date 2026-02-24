<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) exit;

/**
 * Handles admin menu registration.
 */
class AdminMenuController {

    public function register_hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        if (class_exists('WooCommerce')) {
            add_submenu_page(
                'woocommerce',
                __('Nordic Equilibro - BeautyFort integration', 'nebf-mvc'),
                __('Nordic Equilibro - BeautyFort', 'nebf-mvc'),
                'manage_options',
                'nebf-mvc',
                [$this, 'render']
            );

            add_submenu_page(
                'woocommerce',
                __('BeautyFort Test Order', 'nebf-mvc'),
                __('BeautyFort Test Order', 'nebf-mvc'),
                'manage_options',
                'nebf-mvc-test-order',
                [$this, 'render_test_order']
            );
        }
    }

    public function render(): void
    {
        (new AdminPageRouter())->handle();
    }

    public function render_test_order(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('BeautyFort Test Order', 'nebf-mvc') . '</h1>';
        echo '</div>';

        (new TestOrderController())->handle();
    }
}
