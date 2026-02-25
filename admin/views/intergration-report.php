<?php
if (!defined('ABSPATH')) exit;

$report = is_array($report ?? null) ? $report : [];

// Calculate summary
$total = count($report);
$passed = 0;
$failed = 0;

foreach ($report as $test) {
    if (!empty($test['success'])) {
        $passed++;
    } else {
        $failed++;
    }
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Integration Tests', 'nebf-mvc'); ?></h1>

    <form method="post" class="nebf-run-btn">
        <?php wp_nonce_field('nebf_run_integration_tests'); ?>
        <button class="button button-primary">
            <?php esc_html_e('Run Integration Tests', 'nebf-mvc'); ?>
        </button>
    </form>

    <?php if (!empty($report)) : ?>

        <div class="nebf-int-wrapper">

            <div class="nebf-int-card">

                <div class="nebf-int-summary">
                    <div class="nebf-summary-box nebf-summary-pass">
                        <?php echo esc_html(sprintf(__('Passed: %d', 'nebf-mvc'), $passed)); ?>
                    </div>

                    <div class="nebf-summary-box nebf-summary-fail">
                        <?php echo esc_html(sprintf(__('Failed: %d', 'nebf-mvc'), $failed)); ?>
                    </div>

                    <div class="nebf-summary-box">
                        <?php echo esc_html(sprintf(__('Total: %d', 'nebf-mvc'), $total)); ?>
                    </div>
                </div>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th style="width:40%;"><?php esc_html_e('Test Case', 'nebf-mvc'); ?></th>
                            <th style="width:60%;"><?php esc_html_e('Result', 'nebf-mvc'); ?></th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($report as $key => $test) : ?>

                        <?php
                        $success = !empty($test['success']);
                        $rowClass = $success ? '' : 'nebf-row-fail';

                        $errorMessage = '';

                        if (!$success && !empty($test['result'])) {

                            if ($test['result'] instanceof \WP_Error) {
                                $errorMessage = $test['result']->get_error_message();
                            }

                            elseif (!empty($test['result']['errors'][0]['description'])) {
                                $errorMessage = $test['result']['errors'][0]['description'];
                            }

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
                                    <span class="nebf-status-badge nebf-pass">
                                        <?php esc_html_e('PASS', 'nebf-mvc'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="nebf-status-badge nebf-fail">
                                        <?php esc_html_e('FAIL', 'nebf-mvc'); ?>
                                    </span>

                                    <a href="<?php echo esc_url(admin_url('admin.php?page=nebf-mvc&tab=debug')); ?>"
                                       class="nebf-debug-link">
                                        <?php esc_html_e('Open Debug', 'nebf-mvc'); ?>
                                    </a>

                                    <?php if (!empty($errorMessage)) : ?>
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

    <?php endif; ?>

</div>