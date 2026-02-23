<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * POC default online lookup provider using DuckDuckGo HTML search.
 */
class DefaultWebPriceLookupProvider
{
    public function register_hooks(): void
    {
        add_filter('nebf_web_price_lookup_result', [$this, 'lookup_result'], 10, 3);
    }

    /**
     * @param mixed $result Existing lookup result from earlier filters.
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $product
     *
     * @return mixed
     */
    public function lookup_result($result, array $payload, array $product)
    {
        if (is_array($result) && isset($result['price']) && is_numeric($result['price'])) {
            return $result;
        }

        $query = sanitize_text_field((string) ($payload['search_query'] ?? ''));
        if ($query === '') {
            return [
                'source' => 'duckduckgo-html',
                'debug' => [
                    'stage' => 'empty_query',
                ],
            ];
        }

        $search_url = add_query_arg([
            'q' => $query,
            'kl' => 'se-sv',
            'kp' => '-2',
        ], 'https://html.duckduckgo.com/html/');

        $response = wp_remote_get($search_url, [
            'timeout' => 20,
            'redirection' => 5,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; NEBF-PriceLookup/1.0; +https://wordpress.org)',
                'Accept-Language' => 'sv-SE,sv;q=0.9,en;q=0.8',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'source' => 'duckduckgo-html',
                'url' => $search_url,
                'debug' => [
                    'stage' => 'http_error',
                    'error' => sanitize_text_field($response->get_error_message()),
                ],
            ];
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $html = (string) wp_remote_retrieve_body($response);

        if ($html === '') {
            return [
                'source' => 'duckduckgo-html',
                'url' => $search_url,
                'debug' => [
                    'stage' => 'empty_body',
                    'http_code' => $http_code,
                ],
            ];
        }

        $found = $this->extract_first_price($html);
        if ($found === null) {
            return [
                'source' => 'duckduckgo-html',
                'url' => $search_url,
                'debug' => [
                    'stage' => 'price_not_found',
                    'http_code' => $http_code,
                    'body_head' => sanitize_text_field(substr(wp_strip_all_tags($html), 0, 200)),
                ],
            ];
        }

        return [
            'price' => $found['price'],
            'currency' => $found['currency'],
            'source' => 'duckduckgo-html',
            'url' => $search_url,
            'debug' => [
                'stage' => 'ok',
                'http_code' => $http_code,
                'match' => $found['match'],
            ],
        ];
    }

    /**
     * @return array{price: float, currency: string, match: string}|null
     */
    private function extract_first_price(string $html): ?array
    {
        $text = wp_strip_all_tags($html);
        $normalized = preg_replace('/\s+/u', ' ', $text);
        if (!is_string($normalized) || $normalized === '') {
            return null;
        }

        $patterns = [
            '/(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\s*(kr|sek)\b/iu' => 'SEK',
            '/(kr|sek)\s*(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\b/iu' => 'SEK_PREFIX',
            '/(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\s*(€|eur)\b/iu' => 'EUR',
            '/(€|eur)\s*(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\b/iu' => 'EUR_PREFIX',
            '/(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\s*(\$|usd)\b/iu' => 'USD',
            '/(\$|usd)\s*(\d{1,3}(?:[ .]\d{3})*(?:[\.,]\d{2})?|\d+)\b/iu' => 'USD_PREFIX',
        ];

        foreach ($patterns as $regex => $currencyMode) {
            if (!preg_match($regex, $normalized, $matches)) {
                continue;
            }

            $raw = (string) ($matches[1] ?? '');
            $currency = $currencyMode;
            if (str_ends_with($currencyMode, '_PREFIX')) {
                $raw = (string) ($matches[2] ?? '');
                $currency = str_replace('_PREFIX', '', $currencyMode);
            }

            $price = $this->normalize_price($raw);
            if ($price === null || $price <= 0) {
                continue;
            }

            return [
                'price' => $price,
                'currency' => $currency,
                'match' => sanitize_text_field((string) ($matches[0] ?? '')),
            ];
        }

        return null;
    }

    private function normalize_price(string $raw): ?float
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = str_replace(' ', '', $raw);

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $last_comma = strrpos($raw, ',');
            $last_dot = strrpos($raw, '.');
            if ($last_comma !== false && $last_dot !== false) {
                if ($last_comma > $last_dot) {
                    $raw = str_replace('.', '', $raw);
                    $raw = str_replace(',', '.', $raw);
                } else {
                    $raw = str_replace(',', '', $raw);
                }
            }
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        if (!is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }
}
