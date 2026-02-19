<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Routes admin page requests to the correct controller.
 */
class AdminPageRouter {

    /**
     * Handle current admin request.
     */
    public function handle(): void {

        $tab = sanitize_key($_GET['tab'] ?? 'dashboard');

        echo $this->render_tabs($tab);

        switch ($tab) {

            case 'products':
                (new ProductsController())->handle();
                break;

            case 'settings':
                (new SettingsController())->handle();
                break;

            default:
                (new DashboardController())->handle();
        }
    }

    /**
     * Render navigation tabs.
     *
     * @param string $active
     * @return string
     */
    private function render_tabs(string $active): string {

        $tabs = [
            'dashboard' => __('Dashboard', 'nebf-mvc'),
            'products'  => __('Products', 'nebf-mvc'),
            'settings'  => __('Settings', 'nebf-mvc'),
        ];

        $html = '<h2 class="nav-tab-wrapper">';

        foreach ($tabs as $slug => $label) {

            $class = ($slug === $active)
                ? 'nav-tab nav-tab-active'
                : 'nav-tab';

            $url = add_query_arg([
                'page' => 'nebf-mvc',
                'tab'  => $slug,
            ], admin_url('admin.php'));

            $html .= sprintf(
                '<a href="%1$s" class="%2$s">%3$s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($label)
            );
        }

        $html .= '</h2>';

        return $html;
    }
}
