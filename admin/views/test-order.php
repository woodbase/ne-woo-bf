<?php

if (!defined('ABSPATH')) exit;

/** @var array<string,mixed>|\WP_Error|null $result */
$result = $result ?? null;
/** @var array<string,mixed> $trace */
$trace = is_array($trace ?? null) ? $trace : [];
/** @var array<string,string>|null $feedback */
$feedback = is_array($feedback ?? null) ? $feedback : null;
?>

<div class="wrap nebf-settings">
    <p><?php esc_html_e('Create a test order directly against BeautyFort CreateOrder API.', 'nebf-mvc'); ?></p>

    <?php if (!empty($feedback['message'])) : ?>
        <div class="notice notice-<?php echo esc_attr((string) ($feedback['type'] ?? 'info')); ?>"><p><?php echo esc_html((string) $feedback['message']); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=nebf-mvc&tab=test-order')); ?>">
        <?php wp_nonce_field('nebf_create_test_order'); ?>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="nebf_order_type"><?php esc_html_e('Order Type', 'nebf-mvc'); ?></label></th>
                    <td>
                        <select name="nebf_order_type" id="nebf_order_type">
                            <option value="Wholesale"><?php esc_html_e('Wholesale', 'nebf-mvc'); ?></option>
                            <option value="Direct Dispatch"><?php esc_html_e('Direct Dispatch', 'nebf-mvc'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nebf_order_reference"><?php esc_html_e('YourOrderReference', 'nebf-mvc'); ?></label></th>
                    <td>
                        <input type="text" name="nebf_order_reference" id="nebf_order_reference" class="regular-text"
                               value="<?php echo esc_attr('TEST-' . gmdate('Ymd-His')); ?>">
                        <p class="description"><?php esc_html_e('Must be unique across BeautyFort test and live for your account.', 'nebf-mvc'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Create Test Order', 'nebf-mvc'); ?></button>
        </p>
    </form>

    <?php if (is_array($result)) : ?>
        <p>
            <strong><?php esc_html_e('Result:', 'nebf-mvc'); ?></strong>
            <?php echo !empty($result['success'])
                ? esc_html__('Order created successfully in BeautyFort.', 'nebf-mvc')
                : esc_html__('Order was not created. See Errors below.', 'nebf-mvc'); ?>
        </p>
        <h2><?php esc_html_e('CreateOrder Response', 'nebf-mvc'); ?></h2>
        <table class="widefat striped" style="max-width:900px;">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Success', 'nebf-mvc'); ?></th>
                    <td><?php echo !empty($result['success']) ? 'true' : 'false'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('TestMode', 'nebf-mvc'); ?></th>
                    <td><?php echo !empty($result['test_mode']) ? 'true' : 'false'; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('OrderReference', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html((string) ($result['order_reference'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('YourOrderReference', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html((string) ($result['your_order_reference'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Errors', 'nebf-mvc'); ?></th>
                    <td><pre style="margin:0;"><?php echo esc_html(wp_json_encode($result['errors'] ?? [], JSON_PRETTY_PRINT)); ?></pre></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Warnings', 'nebf-mvc'); ?></th>
                    <td><pre style="margin:0;"><?php echo esc_html(wp_json_encode($result['warnings'] ?? [], JSON_PRETTY_PRINT)); ?></pre></td>
                </tr>
            </tbody>
        </table>
    <?php elseif ($result instanceof \WP_Error) : ?>
        <h2><?php esc_html_e('CreateOrder Error', 'nebf-mvc'); ?></h2>
        <table class="widefat striped" style="max-width:900px;">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Error code', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result->get_error_code()); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Error message', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result->get_error_message()); ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <?php
    $steps = [];
    if (is_array($result) && !empty($result['steps']) && is_array($result['steps'])) {
        $steps = $result['steps'];
    } elseif (is_array($trace['parsed'] ?? null) && !empty($trace['parsed']['steps']) && is_array($trace['parsed']['steps'])) {
        $steps = $trace['parsed']['steps'];
    }
    ?>

    <?php if (!empty($steps)) : ?>
        <h2><?php esc_html_e('Step-by-step report', 'nebf-mvc'); ?></h2>
        <table class="widefat striped" style="max-width:1100px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Step', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Status', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Details', 'nebf-mvc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($steps as $step) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($step['label'] ?? '')); ?></td>
                        <td><?php echo esc_html(strtoupper((string) ($step['status'] ?? ''))); ?></td>
                        <td><?php echo esc_html((string) ($step['details'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($trace)) : ?>
        <h2><?php esc_html_e('Last API trace', 'nebf-mvc'); ?></h2>
        <table class="widefat striped" style="max-width:1100px;">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Time', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html((string) ($trace['time'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Endpoint', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html((string) ($trace['endpoint'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('HTTP status', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html((string) ($trace['http_code'] ?? '')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Request XML (sent)', 'nebf-mvc'); ?></th>
                    <td><pre style="margin:0; white-space:pre-wrap;"><?php echo esc_html((string) ($trace['request_xml'] ?? '')); ?></pre></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Response body (received)', 'nebf-mvc'); ?></th>
                    <td><pre style="margin:0; white-space:pre-wrap;"><?php echo esc_html((string) ($trace['response_body'] ?? '')); ?></pre></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

</div>
