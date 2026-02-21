<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performs one web-price lookup request and stores the result on the product.
 */
class WebPriceLookupService
{
    public function lookup_and_store(string $bf_id): bool
    {
        $bf_id = sanitize_text_field($bf_id);
        if ($bf_id === '') {
            return false;
        }

        $products = get_option('nebf_beautyfort_products', []);
        if (!is_array($products) || !isset($products[$bf_id]) || !is_array($products[$bf_id])) {
            return false;
        }

        $product = $products[$bf_id];
        $payload = [
            'bf_id' => $bf_id,
            'sku' => (string) ($product['sku'] ?? ''),
            'name' => (string) ($product['fullname'] ?? ''),
            'brand' => (string) ($product['brand'] ?? ''),
            'barcode' => (string) ($product['barcode'] ?? ''),
        ];

        // Integrators can provide a real online lookup implementation via this filter.
        $result = apply_filters('nebf_web_price_lookup_result', null, $payload, $product);
        $status = 'no_result';

        if (is_array($result)) {
            if (isset($result['price']) && is_numeric($result['price'])) {
                $product['web_price'] = (float) $result['price'];
                $status = 'ok';
            }

            if (!empty($result['currency'])) {
                $product['web_price_currency'] = sanitize_text_field((string) $result['currency']);
            }
            if (!empty($result['source'])) {
                $product['web_price_source'] = sanitize_text_field((string) $result['source']);
            }
            if (!empty($result['url'])) {
                $product['web_price_url'] = esc_url_raw((string) $result['url']);
            }
        }

        $product['web_price_lookup_status'] = $status;
        $product['web_price_lookup_updated_at'] = time();

        $products[$bf_id] = $product;
        update_option('nebf_beautyfort_products', $products, false);

        return true;
    }
}
