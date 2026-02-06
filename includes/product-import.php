<?php

function nebf_import_products()
{

    if (!class_exists('WooCommerce')) {
        return new WP_Error('no_wc', 'WooCommerce är inte aktivt');
    }

    $rows = nebf_api_request_stockfile();

    if (is_wp_error($rows)) {
        return $rows;
    }

    $imported = 0;
    $updated  = 0;
    $skipped  = 0;

    if ($rows == null) return 'Inga produkter inläst!';

    foreach ($rows as $row) {

        // 1. Validera
        if (empty($row['sku'])) {
            $skipped++;
            continue;
        }
        debug_log('Importerar produkt: ' . json_encode($row));
        $sku   = sanitize_text_field($row['sku']);
        $stock = intval($row['stock'] ?? 0);
        $name  = sanitize_text_field($row['name'] ?? $sku);

        // 2. Hitta produkt via SKU
        $product_id = wc_get_product_id_by_sku($sku);

        if ($product_id) {
            $product = wc_get_product($product_id);
            $updated++;
        } else {
            $product = new WC_Product_Simple();
            $product->set_sku($sku);
            $product->set_name($name);
            $product->set_status('draft');
            $imported++;
        }

        // 3. Lager
        $product->set_manage_stock(true);
        $product->set_stock_quantity($stock);
        $product->set_stock_status(
            $stock > 0 ? 'instock' : 'outofstock'
        );

        // 4. Spara
        $product->save();
    }

    try {
        return [
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'total'    => count($rows)
        ];
    } catch (Exception $e) {
        return ['error' => $e];
    }
}
