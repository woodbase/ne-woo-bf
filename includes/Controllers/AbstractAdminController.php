<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) exit;

/**
 * Base controller for all admin pages.
 */
abstract class AbstractAdminController {

    protected function render(string $view_name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $view_file = NEBF_MVC_PATH . "admin/views/{$view_name}.php";

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo esc_html__("View not found: {$view_name}", 'nebf-mvc');
        }
    }

    abstract public function handle(): void;
}
