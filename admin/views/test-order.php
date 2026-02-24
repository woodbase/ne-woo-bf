<?php

if (!defined('ABSPATH')) exit;

/** @var array<string,mixed>|\WP_Error|null $result */
$result = $result ?? null;
?>

<div class="wrap nebf-settings">
    <p><?php esc_html_e('Create a test order directly against BeautyFort CreateOrder API.', 'nebf-mvc'); ?></p>

    <form method="post">
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
    <?php endif; ?>
</div>
