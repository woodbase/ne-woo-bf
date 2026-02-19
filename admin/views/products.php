<?php
if (!defined('ABSPATH')) exit;

/** @var array $products */
$products    = $products ?? [];
$items       = is_array($products['items'] ?? null) ? $products['items'] : [];
$page        = $page ?? 1;
$total_pages = $total_pages ?? 1;
$search_term = $search_term ?? '';
$filters     = $filters ?? ['brand' => '', 'collection' => '', 'status' => ''];

// --- Prepare dropdown options ---
$brands      = array_unique(array_filter(array_column($items, 'brand')));
sort($brands);
$collections = array_unique(array_filter(array_column($items, 'collection')));
sort($collections);
?>

<?php $this->notices->display(); ?>

<!-- ========================= -->
<!-- FILTER FORM -->
<form id="nebf-products-filter-form" method="get" style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
    <input type="hidden" name="page" value="nebf-mvc">
    <input type="hidden" name="tab" value="products">
    <input type="number" name="per_page" value="<?= esc_attr($_GET['per_page'] ?? 20) ?>" min="1" max="500">
    <input id="nebf-products-search" type="search" name="s" placeholder="<?php _e('Search name / SKU / brand', 'nebf'); ?>" value="<?= esc_attr($search_term); ?>">

    <select name="brand">
        <option value=""><?php _e('All brands', 'nebf'); ?></option>
        <?php foreach ($brands as $b): ?>
            <option value="<?= esc_attr($b); ?>" <?= selected($filters['brand'], $b, false); ?>><?= esc_html($b); ?></option>
        <?php endforeach; ?>
    </select>

    <select name="collection">
        <option value=""><?php _e('All collections', 'nebf'); ?></option>
        <?php foreach ($collections as $c): ?>
            <option value="<?= esc_attr($c); ?>" <?= selected($filters['collection'], $c, false); ?>><?= esc_html($c); ?></option>
        <?php endforeach; ?>
    </select>

    <select name="status">
        <option value=""><?php _e('All status', 'nebf'); ?></option>
        <option value="imported" <?= selected($filters['status'], 'imported', false); ?>><?php _e('Imported', 'nebf'); ?></option>
        <option value="not_imported" <?= selected($filters['status'], 'not_imported', false); ?>><?php _e('Not imported', 'nebf'); ?></option>
    </select>

    <button class="button button-primary"><?php _e('Update', 'nebf'); ?></button>
    <button id="nebf-select-all" type="button" class="button"><?php _e('Select all', 'nebf'); ?></button>
    <button id="nebf-reset-search" type="button" class="button"><?php _e('Reset search', 'nebf'); ?></button>
</form>

<!-- ========================= -->
<!-- SYNC FORM -->
<form method="POST">
    <?php wp_nonce_field('nebf_sync_selected_products'); ?>
    <button type="submit" name="nebf_sync_selected" class="button button-primary">
        <?php _e('Sync to WooCommerce', 'nebf'); ?>
    </button>

    <!-- ========================= -->
    <!-- PRODUCTS TABLE -->
    <table class="widefat striped nebf-products-table">
        <thead>
            <tr>
                <th><?php _e('Select', 'nebf'); ?></th>
                <th><?php _e('Status', 'nebf'); ?></th>
                <th><?php _e('Image', 'nebf'); ?></th>
                <th><?php _e('Name', 'nebf'); ?></th>
                <th><?php _e('SKU', 'nebf'); ?></th>
                <th><?php _e('Brand', 'nebf'); ?></th>
                <th><?php _e('Collection', 'nebf'); ?></th>
                <th><?php _e('Cost price', 'nebf'); ?></th>
                <th><?php _e('Sale price', 'nebf'); ?></th>
                <th><?php _e('Margin %', 'nebf'); ?></th>
                <th><?php _e('Stock', 'nebf'); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $product):
                $accordion_id = 'acc-' . $i;

                $cost_price = floatval($product['price'] ?? 0);
                $sale_price = floatval($product['sale_price'] ?? $cost_price); // fallback
                $calc_margin = $cost_price > 0 ? round((($sale_price - $cost_price) / $cost_price) * 100, 2) : 0;

                $is_imported = !empty($product['synced'] ?? false);
            ?>
                <tr class="product-row" data-accordion="<?= esc_attr($accordion_id); ?>">
                    <td>
                        <input type="checkbox" name="selected_products[]" value="<?= esc_attr($product['bf_id']); ?>">
                        <input type="hidden" name="sale_prices[<?= esc_attr($product['bf_id']); ?>]" value="<?= esc_attr($sale_price); ?>">
                    </td>
                    <td><?php echo $is_imported ? '✅' : '❌'; ?></td>
                    <td><?= !empty($product['thumbnail_url']) ? '<img src="' . esc_url($product['thumbnail_url']) . '" width="50">' : '—'; ?></td>
                    <td><?= esc_html($product['fullname'] ?? '—'); ?></td>
                    <td><?= esc_html($product['sku'] ?? '—'); ?></td>
                    <td><?= esc_html($product['brand'] ?? '—'); ?></td>
                    <td><?= esc_html($product['collection'] ?? '—'); ?></td>
                    <td><?= function_exists('wc_price') ? wc_price($cost_price) : esc_html($cost_price); ?></td>
                    <td><?= function_exists('wc_price') ? wc_price($sale_price) : esc_html($sale_price); ?></td>
                    <td><?= esc_html($calc_margin); ?>%</td>
                    <td><?= esc_html($product['stock_level'] ?? '—'); ?></td>
                    <td class="nebf-expand"><span class="dashicons dashicons-arrow-down-alt2"></span></td>
                </tr>

                <!-- Accordion row -->
                <tr id="<?= esc_attr($accordion_id); ?>" class="accordion-row" style="display:none;">
                    <td colspan="12" style="background:#f6f7f7; padding:16px;">
                        <table class="widefat striped" style="margin:0;">
                            <tbody>
                                <tr>
                                    <th><?php _e('Description', 'nebf'); ?></th>
                                    <td><?= esc_html($product['description'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Barcode', 'nebf'); ?></th>
                                    <td><?= esc_html($product['barcode'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Size', 'nebf'); ?></th>
                                    <td><?= esc_html($product['size'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Type', 'nebf'); ?></th>
                                    <td><?= esc_html($product['type'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('BF ID', 'nebf'); ?></th>
                                    <td><?= esc_html($product['bf_id'] ?? ''); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit" name="nebf_sync_selected" class="button button-primary">
        <?php _e('Sync to WooCommerce', 'nebf'); ?>
    </button>
</form>

<!-- PAGINATION -->
<?php if ($total_pages > 1): ?>
    <div class="tablenav">
        <div class="tablenav-pages">
            <?= paginate_links([
                'base' => add_query_arg(array_merge($_GET, ['paged' => '%#%'])),
                'format' => '',
                'current' => $page,
                'total' => $total_pages,
                'prev_text' => '«',
                'next_text' => '»'
            ]) ?>
        </div>
    </div>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('nebf-products-filter-form');
        var searchInput = document.getElementById('nebf-products-search');
        var selectAllButton = document.getElementById('nebf-select-all');
        var resetSearchButton = document.getElementById('nebf-reset-search');
        var table = document.querySelector('.nebf-products-table');
        var defaultSelectAllText = <?php echo wp_json_encode(__('Select all', 'nebf')); ?>;
        var defaultUnselectAllText = <?php echo wp_json_encode(__('Unselect all', 'nebf')); ?>;

        if (!form || !searchInput) {
            return;
        }

        var debounceTimer = null;

        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                form.submit();
            }, 350);
        });

        if (selectAllButton && table) {
            selectAllButton.addEventListener('click', function() {
                var checkboxes = table.querySelectorAll('input[type="checkbox"][name="selected_products[]"]');
                if (!checkboxes.length) {
                    return;
                }

                var allChecked = true;
                checkboxes.forEach(function(checkbox) {
                    if (!checkbox.checked) {
                        allChecked = false;
                    }
                });

                var shouldCheck = !allChecked;
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = shouldCheck;
                });

                selectAllButton.textContent = shouldCheck ? defaultUnselectAllText : defaultSelectAllText;
            });
        }

        if (resetSearchButton) {
            resetSearchButton.addEventListener('click', function() {
                form.querySelectorAll('input[name="s"], select[name="brand"], select[name="collection"], select[name="status"]').forEach(function(field) {
                    field.value = '';
                });
                form.submit();
            });
        }
    });
</script>