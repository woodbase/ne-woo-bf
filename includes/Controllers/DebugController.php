<?php

namespace NEBF\Controllers;

use NEBF\Services\TraceService;

if (!defined('ABSPATH')) exit;

class DebugController extends AbstractAdminController
{
    public function handle(): void
    {
        $traceService = new TraceService();

        // Clear logs
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_clear_debug']) &&
            check_admin_referer('nebf_clear_debug_action')
        ) {
            $traceService->clear_all();
            $this->notices->add(__('Debug logs cleared.', 'nebf-mvc'), 'success');
        }

        $this->render('debug', [
            'traces' => $traceService->get_all(),
            'testmode' => get_option('nebf_api_testmode', '0') === '1',
        ]);
    }
}