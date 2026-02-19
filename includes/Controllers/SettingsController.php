<?php

namespace NEBF\Controllers;

if (!defined('ABSPATH')) exit;

/**
 * Controller for Settings tab
 */
class SettingsController extends AbstractAdminController {

    public function handle(): void
    {
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('nebf_save_settings')) {

            if (isset($_POST['nebf_username'])) {
                update_option('nebf_username', sanitize_text_field($_POST['nebf_username']));
            }

            if (isset($_POST['nebf_api_key'])) {
                update_option('nebf_api_key', sanitize_text_field($_POST['nebf_api_key']));
            }

            // Checkbox may be unset if unchecked
            $separate = isset($_POST['nebf_separate_brand']) ? 1 : 0;
            update_option('nebf_separate_brand', $separate);

            // Feedback notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>'
                     . esc_html__('Settings saved successfully.', 'nebf-mvc') . '</p></div>';
            });
        }

        $this->render('settings');
    }
}
