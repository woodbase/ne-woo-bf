<?php
if (!defined('ABSPATH')) exit;

function nebf_sync_product_to_woo($product)
{

    if (!class_exists('WC_Product_Simple')) {
        return new WP_Error('no_wc', 'WooCommerce is not active');
    }

    // --- 1. Hämta data ---
    $sku        = sanitize_text_field($product['sku']);
    $name       = sanitize_text_field($product['name']);
    $cost_price = floatval($product['price']); // BeautyFort inköpspris

    // --- 2. Räkna ut säljpris ---
    $margin_percent = floatval(get_option('nebf_margin_percent', 30)); // ex 30%
    $sale_price = $cost_price * (1 + ($margin_percent / 100));
    $sale_price = round($sale_price, 2);

    // --- 3. Finns produkt redan? ---
    $existing_id = wc_get_product_id_by_sku($sku);

    if ($existing_id) {
        $wc_product = wc_get_product($existing_id);
    } else {
        $wc_product = new WC_Product_Simple();
    }

    // --- 4. Sätt data ---
    $wc_product->set_name($name);
    $wc_product->set_sku($sku);
    $wc_product->set_regular_price($sale_price);
    $wc_product->set_price($sale_price);
    $wc_product->set_manage_stock(false);
    $wc_product->set_stock_status('instock');
    $wc_product->set_catalog_visibility('visible');

    $wc_product->save();

    return true;
}
