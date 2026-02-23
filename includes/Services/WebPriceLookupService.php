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
    private const OPTION_NAME_OVERRIDES = 'nebf_web_price_lookup_name_overrides';
    /** @var array<string,string>|null */
    private ?array $name_overrides_cache = null;

    public function lookup_and_store(string $bf_id): bool
    {
        $products = get_option('nebf_beautyfort_products', []);
        if (!is_array($products)) {
            $products = [];
        }

        $ok = $this->lookup_and_apply($bf_id, $products);
        if ($ok) {
            update_option('nebf_beautyfort_products', $products, false);
        }

        return $ok;
    }

    /**
     * Apply lookup result to the provided product array in memory.
     *
     * @param array<string, array<string, mixed>> $products
     */
    public function lookup_and_apply(string $bf_id, array &$products): bool
    {
        $bf_id = sanitize_text_field($bf_id);
        if ($bf_id === '') {
            return false;
        }
        if (!isset($products[$bf_id]) || !is_array($products[$bf_id])) {
            return false;
        }

        $product = $products[$bf_id];
        $name_override = $this->get_name_override($bf_id);
        $original_name = (string) ($product['fullname'] ?? '');
        $lookup_name = $name_override !== '' ? $name_override : $original_name;
        $brand = (string) ($product['brand'] ?? '');
        $sku = (string) ($product['sku'] ?? '');
        $barcode = (string) ($product['barcode'] ?? '');
        $search_query = trim(implode(' ', array_filter([$lookup_name, $brand, $barcode])));
        $search_url = $this->build_search_url($search_query);

        $payload = [
            'bf_id' => $bf_id,
            'sku' => $sku,
            'name' => $lookup_name,
            'original_name' => $original_name,
            'brand' => $brand,
            'barcode' => $barcode,
            'search_name_override' => $name_override,
            'search_query' => $search_query,
            'search_url' => $search_url,
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
            if (!empty($result['debug'])) {
                if (is_array($result['debug'])) {
                    $product['web_price_lookup_debug'] = wp_json_encode($result['debug']);
                } else {
                    $product['web_price_lookup_debug'] = sanitize_text_field((string) $result['debug']);
                }
            }
        }

        $product['web_price_lookup_status'] = $status;
        $product['web_price_lookup_updated_at'] = time();
        $product['web_price_lookup_query'] = $search_query;
        if ($search_url !== '') {
            $product['web_price_lookup_search_url'] = $search_url;
        }

        $products[$bf_id] = $product;

        return true;
    }

    private function build_search_url(string $search_query): string
    {
        $query = sanitize_text_field($search_query);
        if ($query === '') {
            return '';
        }

        return (string) add_query_arg([
            'q' => $query,
            'kl' => 'se-sv',
            'kp' => '-2',
        ], 'https://html.duckduckgo.com/html/');
    }

    private function get_name_override(string $bf_id): string
    {
        if ($this->name_overrides_cache === null) {
            $overrides = get_option(self::OPTION_NAME_OVERRIDES, []);
            $this->name_overrides_cache = is_array($overrides) ? $overrides : [];
        }

        return sanitize_text_field((string) ($this->name_overrides_cache[$bf_id] ?? ''));
    }
}
