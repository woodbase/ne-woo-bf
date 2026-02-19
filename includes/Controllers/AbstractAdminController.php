<?php

namespace NEBF\Controllers;

use NEBF\Services\NoticeService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base controller for all admin pages.
 *
 * Provides:
 * - View rendering
 * - Access to NoticeService
 * - Shared helper utilities
 */
abstract class AbstractAdminController {

    /**
     * Notice service instance.
     *
     * @var NoticeService
     */
    protected NoticeService $notices;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->notices = new NoticeService();
    }

    /**
     * Render a view file.
     *
     * @param string $view View filename without extension.
     * @param array  $data Data passed to view.
     */
    protected function render(string $view, array $data = []): void {
        extract($data);
        include NEBF_MVC_PATH . 'admin/views/' . $view . '.php';
    }

    /**
     * Each controller must implement handle().
     *
     * Responsible for processing input and rendering output.
     */
    abstract public function handle(): void;
}
