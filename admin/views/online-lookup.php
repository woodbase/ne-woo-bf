<?php
if (!defined('ABSPATH')) {
    exit;
}

$products = is_array($products ?? null) ? $products : [];
$overrides = is_array($overrides ?? null) ? $overrides : [];
$selected_bf_id = (string) ($selected_bf_id ?? '');
$lookup_result = is_array($lookup_result ?? null) ? $lookup_result : null;

$nebf_scalar = static function ($value): string {
    if (is_scalar($value)) {
        return (string) $value;
    }

    if ($value === null) {
        return '';
    }

    if (is_array($value)) {
        return wp_json_encode($value);
    }

    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }

    return '';
};
?>

<?php $this->notices->display(); ?>

<div class="nebf-settings">
    <h2><?php esc_html_e('Online Lookup', 'nebf-mvc'); ?></h2>
    <p>
        <?php esc_html_e('Set a custom product name that will be used as search input during online price lookup.', 'nebf-mvc'); ?>
    </p>
    <p>
        <?php esc_html_e('Current lookup payload fields: search name, brand, SKU, barcode.', 'nebf-mvc'); ?>
    </p>
    <p>
        <?php esc_html_e('Default provider: DuckDuckGo HTML search. For best results, set a precise search-name override (brand + product + size).', 'nebf-mvc'); ?>
    </p>

    <form method="post">
        <?php wp_nonce_field('nebf_save_lookup_override'); ?>
        <input type="hidden" name="nebf_save_lookup_override" value="1">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="bf_id"><?php esc_html_e('Product', 'nebf-mvc'); ?></label>
                    </th>
                    <td>
                        <select name="bf_id" id="bf_id" required>
                            <option value=""><?php esc_html_e('Select a product', 'nebf-mvc'); ?></option>
                            <?php foreach ($products as $bf_id => $product): ?>
                                <?php
                                $name = (string) ($product['fullname'] ?? '');
                                $sku = (string) ($product['sku'] ?? '');
                                ?>
                                <option value="<?php echo esc_attr((string) $bf_id); ?>" <?php selected($selected_bf_id, (string) $bf_id); ?>>
                                    <?php echo esc_html($name . ' (' . $sku . ' / ' . $bf_id . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="search_name_override"><?php esc_html_e('Search Name Override', 'nebf-mvc'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="search_name_override" name="search_name_override" class="regular-text" placeholder="<?php esc_attr_e('Example: Dior Sauvage Eau de Parfum 100ml', 'nebf-mvc'); ?>">
                        <p class="description">
                            <?php esc_html_e('Leave empty and save to remove override for selected product.', 'nebf-mvc'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Save Override', 'nebf-mvc'); ?></button>
        </p>
    </form>

    <hr>

    <h3><?php esc_html_e('Run Lookup Now', 'nebf-mvc'); ?></h3>
    <form method="post">
        <?php wp_nonce_field('nebf_run_lookup_now'); ?>
        <input type="hidden" name="nebf_run_lookup_now" value="1">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="bf_id_run_now"><?php esc_html_e('Product', 'nebf-mvc'); ?></label>
                    </th>
                    <td>
                        <select name="bf_id" id="bf_id_run_now" required>
                            <option value=""><?php esc_html_e('Select a product', 'nebf-mvc'); ?></option>
                            <?php foreach ($products as $bf_id => $product): ?>
                                <?php
                                $name = (string) ($product['fullname'] ?? '');
                                $sku = (string) ($product['sku'] ?? '');
                                ?>
                                <option value="<?php echo esc_attr((string) $bf_id); ?>" <?php selected($selected_bf_id, (string) $bf_id); ?>>
                                    <?php echo esc_html($name . ' (' . $sku . ' / ' . $bf_id . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Runs online lookup immediately for the selected product and stores result fields on the product.', 'nebf-mvc'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php esc_html_e('Run Lookup Now', 'nebf-mvc'); ?></button>
        </p>
    </form>

    <?php if ($lookup_result !== null && !empty($lookup_result['product']) && is_array($lookup_result['product'])): ?>
        <?php
        $result_product = $lookup_result['product'];
        $result_bf_id = (string) ($lookup_result['bf_id'] ?? '');
        $result_status = (string) ($result_product['web_price_lookup_status'] ?? '');
        $result_price = $result_product['web_price'] ?? null;
        $result_currency = strtoupper(trim((string) ($result_product['web_price_currency'] ?? get_option('nebf_currency', 'SEK'))));
        $result_query = (string) ($result_product['web_price_lookup_query'] ?? '');
        $result_search_url = (string) ($result_product['web_price_lookup_search_url'] ?? '');
        $result_source = (string) ($result_product['web_price_source'] ?? '');
        $result_debug = (string) ($result_product['web_price_lookup_debug'] ?? '');
        ?>
        <h3><?php esc_html_e('Latest Direct Lookup Result', 'nebf-mvc'); ?></h3>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <th><?php esc_html_e('BF ID', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result_bf_id !== '' ? $result_bf_id : '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Status', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result_status !== '' ? $result_status : '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Price', 'nebf-mvc'); ?></th>
                    <td>
                        <?php
                        if (is_numeric($result_price)) {
                            if (function_exists('wc_price')) {
                                echo wp_kses_post(wc_price((float) $result_price, ['currency' => $result_currency]));
                            } else {
                                echo esc_html(number_format((float) $result_price, 2, '.', '')) . ' ' . esc_html($result_currency);
                            }
                        } else {
                            echo '—';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Query', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result_query !== '' ? $result_query : '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Search URL', 'nebf-mvc'); ?></th>
                    <td>
                        <?php if ($result_search_url !== ''): ?>
                            <a href="<?php echo esc_url($result_search_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($result_search_url); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Source', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($result_source !== '' ? $result_source : '—'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Debug', 'nebf-mvc'); ?></th>
                    <td><?php echo esc_html($nebf_scalar($result_debug !== '' ? $result_debug : '—')); ?></td>
                </tr>
            </tbody>
        </table>
        <hr>
    <?php endif; ?>

    <h3><?php esc_html_e('Saved Overrides', 'nebf-mvc'); ?></h3>
    <?php if (empty($overrides)): ?>
        <p><?php esc_html_e('No overrides saved yet.', 'nebf-mvc'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('BF ID', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Product', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Search Name Override', 'nebf-mvc'); ?></th>
                    <th><?php esc_html_e('Actions', 'nebf-mvc'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overrides as $bf_id => $search_name): ?>
                    <?php
                    $product = $products[$bf_id] ?? [];
                    $product_name = (string) ($product['fullname'] ?? '');
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) $bf_id); ?></td>
                        <td><?php echo esc_html($product_name !== '' ? $product_name : '—'); ?></td>
                        <td><?php echo esc_html((string) $search_name); ?></td>
                        <td>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('nebf_run_lookup_now'); ?>
                                <input type="hidden" name="nebf_run_lookup_now" value="1">
                                <input type="hidden" name="bf_id" value="<?php echo esc_attr((string) $bf_id); ?>">
                                <button type="submit" class="button button-primary"><?php esc_html_e('Run Now', 'nebf-mvc'); ?></button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('nebf_queue_lookup_product'); ?>
                                <input type="hidden" name="nebf_queue_lookup_product" value="1">
                                <input type="hidden" name="bf_id" value="<?php echo esc_attr((string) $bf_id); ?>">
                                <button type="submit" class="button"><?php esc_html_e('Queue Lookup', 'nebf-mvc'); ?></button>
                            </form>
                            <form method="post" style="display:inline-block;">
                                <?php wp_nonce_field('nebf_delete_lookup_override'); ?>
                                <input type="hidden" name="nebf_delete_lookup_override" value="1">
                                <input type="hidden" name="bf_id" value="<?php echo esc_attr((string) $bf_id); ?>">
                                <button type="submit" class="button button-link-delete"><?php esc_html_e('Delete', 'nebf-mvc'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
