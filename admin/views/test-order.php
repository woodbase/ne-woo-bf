<?php

if (!defined('ABSPATH')) exit;

$result = $result ?? null;
$trace = is_array($trace ?? null) ? $trace : [];
$feedback = is_array($feedback ?? null) ? $feedback : null;
$integrationReport = is_array($integrationReport ?? null) ? $integrationReport : null;
$testmode = get_option('nebf_api_testmode', '0') === '1';
?>

<div class="wrap nebf-settings">

    <h1><?php esc_html_e('BeautyFort Test Console', 'nebf-mvc'); ?></h1>

    <?php if (!empty($feedback['message'])) : ?>
        <div class="notice notice-<?php echo esc_attr((string) ($feedback['type'] ?? 'info')); ?>">
            <p><?php echo esc_html((string) $feedback['message']); ?></p>
        </div>
    <?php endif; ?>

    <!-- ============================================================= -->
    <!-- MANUAL CREATE ORDER -->
    <!-- ============================================================= -->

    <h2><?php esc_html_e('Manual CreateOrder Test', 'nebf-mvc'); ?></h2>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nebf-mvc&tab=test-order')); ?>">
        <?php wp_nonce_field('nebf_create_test_order'); ?>

        <input type="hidden" name="nebf_create_test_order" value="1">

        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Order Type', 'nebf-mvc'); ?></th>
                    <td>
                        <select name="nebf_order_type">
                            <option value="Wholesale">Wholesale</option>
                            <option value="Direct Dispatch">Direct Dispatch</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('YourOrderReference', 'nebf-mvc'); ?></th>
                    <td>
                        <input type="text"
                               name="nebf_order_reference"
                               class="regular-text"
                               value="<?php echo esc_attr('TEST-' . gmdate('Ymd-His')); ?>">
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Create Test Order', 'nebf-mvc'); ?>
            </button>
        </p>
    </form>

    <!-- RESULT TABLE -->
    <?php if (is_array($result)) : ?>
        <h3><?php esc_html_e('CreateOrder Response', 'nebf-mvc'); ?></h3>

        <table class="widefat striped" style="max-width:900px;">
            <tbody>
                <tr><th>Success</th><td><?php echo !empty($result['success']) ? 'true' : 'false'; ?></td></tr>
                <tr><th>OrderReference</th><td><?php echo esc_html((string) ($result['order_reference'] ?? '')); ?></td></tr>
                <tr><th>Errors</th><td><pre><?php echo esc_html(wp_json_encode($result['errors'] ?? [], JSON_PRETTY_PRINT)); ?></pre></td></tr>
                <tr><th>Warnings</th><td><pre><?php echo esc_html(wp_json_encode($result['warnings'] ?? [], JSON_PRETTY_PRINT)); ?></pre></td></tr>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- ============================================================= -->
    <!-- INTEGRATION TESTS -->
    <!-- ============================================================= -->

    <hr style="margin:40px 0;">

    <h2><?php esc_html_e('Integration Tests', 'nebf-mvc'); ?></h2>

    <?php if (!$testmode): ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Integration tests require TestMode enabled in Settings.', 'nebf-mvc'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <?php wp_nonce_field('nebf_run_integration_tests_action'); ?>
        <input type="hidden" name="nebf_run_integration_tests" value="1">

        <button type="submit"
                class="button button-secondary"
                <?php disabled(!$testmode); ?>>
            <?php esc_html_e('Run Integration Tests', 'nebf-mvc'); ?>
        </button>
    </form>

    <?php if (!empty($integrationReport)) : ?>
        <h3 style="margin-top:20px;"><?php esc_html_e('Integration Report', 'nebf-mvc'); ?></h3>

        <table class="widefat striped" style="max-width:1000px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Test Case', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Status', 'nebf-mvc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($integrationReport as $testName => $testData) :
                    if ($testName === 'success') continue;
                    $success = !empty($testData['success']);
                ?>
                    <tr>
                        <td><?php echo esc_html($testName); ?></td>
                        <td>
                            <?php if ($success): ?>
                                <span style="color:green;font-weight:bold;">PASS</span>
                            <?php else: ?>
                                <span style="color:red;font-weight:bold;">FAIL</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h4 style="margin-top:15px;"><?php esc_html_e('Raw Report', 'nebf-mvc'); ?></h4>
        <pre style="background:#111;color:#0f0;padding:15px;max-height:400px;overflow:auto;">
<?php echo esc_html(print_r($integrationReport, true)); ?>
        </pre>

    <?php endif; ?>

    <!-- ============================================================= -->
    <!-- LAST TRACE -->
    <!-- ============================================================= -->

    <?php if (!empty($trace)) : ?>
        <hr style="margin:40px 0;">
        <h2><?php esc_html_e('Last API Trace', 'nebf-mvc'); ?></h2>

        <table class="widefat striped" style="max-width:1100px;">
            <tbody>
                <tr><th>Time</th><td><?php echo esc_html((string) ($trace['time'] ?? '')); ?></td></tr>
                <tr><th>Endpoint</th><td><?php echo esc_html((string) ($trace['endpoint'] ?? '')); ?></td></tr>
                <tr><th>HTTP Status</th><td><?php echo esc_html((string) ($trace['http_code'] ?? '')); ?></td></tr>
                <tr>
                    <th>Request XML</th>
                    <td><pre style="white-space:pre-wrap;"><?php echo esc_html((string) ($trace['request_xml'] ?? '')); ?></pre></td>
                </tr>
                <tr>
                    <th>Response Body</th>
                    <td><pre style="white-space:pre-wrap;"><?php echo esc_html((string) ($trace['response_body'] ?? '')); ?></pre></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

</div>