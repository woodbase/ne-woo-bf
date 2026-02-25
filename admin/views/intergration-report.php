<?php
if (!defined('ABSPATH')) exit;

$report = is_array($report ?? null) ? $report : [];
?>

<div class="wrap">
    <h1><?php esc_html_e('Integration Test Report', 'nebf-mvc'); ?></h1>

    <div class="nebf-int-card">

        <table class="widefat striped nebf-int-table">
            <thead>
                <tr>
                    <th style="width:40%;"><?php esc_html_e('Test Case', 'nebf-mvc'); ?></th>
                    <th style="width:60%;"><?php esc_html_e('Result', 'nebf-mvc'); ?></th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($report as $key => $test): ?>

                <?php
                $success = !empty($test['success']);
                $rowClass = $success ? '' : 'nebf-row-fail';

                $errorMessage = '';

                if (!$success && !empty($test['result'])) {

                    // WP_Error case
                    if ($test['result'] instanceof \WP_Error) {
                        $errorMessage = $test['result']->get_error_message();
                    }

                    // Parsed API error
                    elseif (!empty($test['result']['errors'][0]['description'])) {
                        $errorMessage = $test['result']['errors'][0]['description'];
                    }

                    // Generic fallback
                    else {
                        $errorMessage = __('See Debug page for details.', 'nebf-mvc');
                    }
                }
                ?>

                <tr class="<?php echo esc_attr($rowClass); ?>">
                    <td>
                        <strong><?php echo esc_html($key); ?></strong>
                    </td>
                    <td>
                        <?php if ($success): ?>
                            <span class="nebf-status-badge nebf-pass">PASS</span>
                        <?php else: ?>
                            <span class="nebf-status-badge nebf-fail">FAIL</span>

                            <a href="<?php echo esc_url(admin_url('admin.php?page=nebf-mvc&tab=debug')); ?>"
                               class="nebf-debug-link">
                               <?php esc_html_e('Open Debug', 'nebf-mvc'); ?>
                            </a>

                            <?php if (!empty($errorMessage)): ?>
                                <div class="nebf-error-msg">
                                    <?php echo esc_html($errorMessage); ?>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    </div>
</div>