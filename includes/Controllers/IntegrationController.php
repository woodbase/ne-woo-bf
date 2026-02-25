<?php

namespace NEBF\Controllers;

use NEBF\Services\IntegrationTestService;

if (!defined('ABSPATH')) exit;

class IntegrationController extends AbstractAdminController
{
    public function handle(): void
    {
        $report = null;

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            check_admin_referer('nebf_run_integration_tests')
        ) {
            $service = new IntegrationTestService();
            $report = $service->run();
        }

        $this->render('integration-report', [
            'report' => $report,
        ]);
    }
}