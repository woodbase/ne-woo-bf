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
    private const MAX_CRAWL_RESULTS = 5;

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

        $result_urls = $this->extract_result_urls($html);
        $crawl_found = $this->crawl_result_pages_for_price($result_urls);
        if ($crawl_found !== null) {
            return [
                'price' => $crawl_found['price'],
                'currency' => $crawl_found['currency'],
                'source' => 'duckduckgo-crawl',
                'url' => $search_url,
                'debug' => [
                    'stage' => 'ok_crawl',
                    'http_code' => $http_code,
                    'result_count' => count($result_urls),
                    'matched_url' => $crawl_found['url'],
                    'match' => $crawl_found['match'],
                ],
            ];
        }

        $found = $this->extract_first_price($html);
        if ($found === null) {
            return [
                'source' => 'duckduckgo-html',
                'url' => $search_url,
                'debug' => [
                    'stage' => 'price_not_found_after_crawl',
                    'http_code' => $http_code,
                    'result_count' => count($result_urls),
                    'crawl_urls' => array_slice($result_urls, 0, self::MAX_CRAWL_RESULTS),
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
                'stage' => 'ok_serp_fallback',
                'http_code' => $http_code,
                'result_count' => count($result_urls),
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

    /**
     * @param array<int,string> $urls
     * @return array{price: float, currency: string, match: string, url: string}|null
     */
    private function crawl_result_pages_for_price(array $urls): ?array
    {
        $urls = array_slice($urls, 0, self::MAX_CRAWL_RESULTS);
        foreach ($urls as $url) {
            $response = wp_remote_get($url, [
                'timeout' => 15,
                'redirection' => 5,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; NEBF-PriceLookup/1.0; +https://wordpress.org)',
                    'Accept-Language' => 'sv-SE,sv;q=0.9,en;q=0.8',
                ],
            ]);

            if (is_wp_error($response)) {
                continue;
            }

            $http_code = (int) wp_remote_retrieve_response_code($response);
            if ($http_code < 200 || $http_code >= 400) {
                continue;
            }

            $content_type = strtolower((string) wp_remote_retrieve_header($response, 'content-type'));
            if ($content_type !== '' && !str_contains($content_type, 'text/html')) {
                continue;
            }

            $html = (string) wp_remote_retrieve_body($response);
            if ($html === '') {
                continue;
            }

            $found = $this->extract_price_from_product_html($html);
            if ($found === null) {
                continue;
            }

            return [
                'price' => $found['price'],
                'currency' => $found['currency'],
                'match' => $found['match'],
                'url' => $url,
            ];
        }

        return null;
    }

    /**
     * @return array{price: float, currency: string, match: string}|null
     */
    private function extract_price_from_product_html(string $html): ?array
    {
        $from_json_ld = $this->extract_price_from_json_ld($html);
        if ($from_json_ld !== null) {
            return $from_json_ld;
        }

        $from_meta = $this->extract_price_from_meta($html);
        if ($from_meta !== null) {
            return $from_meta;
        }

        return $this->extract_first_price($html);
    }

    /**
     * @return array{price: float, currency: string, match: string}|null
     */
    private function extract_price_from_json_ld(string $html): ?array
    {
        if (!preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/isu', $html, $matches)) {
            return null;
        }

        foreach ((array) ($matches[1] ?? []) as $json_raw) {
            $json_raw = trim((string) $json_raw);
            if ($json_raw === '') {
                continue;
            }

            $decoded = json_decode($json_raw, true);
            if (!is_array($decoded)) {
                continue;
            }

            $found = $this->find_offer_in_json_ld($decoded);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $data
     * @return array{price: float, currency: string, match: string}|null
     */
    private function find_offer_in_json_ld(array $data): ?array
    {
        if (isset($data['offers'])) {
            $offer = $data['offers'];
            if (is_array($offer)) {
                $found = $this->extract_offer_price($offer);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $node) {
                if (!is_array($node)) {
                    continue;
                }
                $found = $this->find_offer_in_json_ld($node);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }

            $found = $this->find_offer_in_json_ld($value);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $offer
     * @return array{price: float, currency: string, match: string}|null
     */
    private function extract_offer_price(array $offer): ?array
    {
        if ($this->is_assoc($offer)) {
            $raw_price = (string) ($offer['price'] ?? $offer['lowPrice'] ?? $offer['highPrice'] ?? '');
            $price = $this->normalize_price($raw_price);
            if ($price !== null && $price > 0) {
                $currency_raw = strtoupper(trim((string) ($offer['priceCurrency'] ?? 'SEK')));
                $currency = $this->normalize_currency($currency_raw);

                return [
                    'price' => $price,
                    'currency' => $currency,
                    'match' => 'json-ld:' . $raw_price . ' ' . $currency,
                ];
            }
        }

        foreach ($offer as $item) {
            if (!is_array($item)) {
                continue;
            }

            $found = $this->extract_offer_price($item);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @return array{price: float, currency: string, match: string}|null
     */
    private function extract_price_from_meta(string $html): ?array
    {
        $amount = '';
        $currency = '';
        if (preg_match('/<meta[^>]+(?:property|name)=["\'](?:product:price:amount|og:price:amount|price)["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $m_amount)) {
            $amount = (string) ($m_amount[1] ?? '');
        }

        if (preg_match('/<meta[^>]+(?:property|name)=["\'](?:product:price:currency|og:price:currency)["\'][^>]+content=["\']([^"\']+)["\']/iu', $html, $m_currency)) {
            $currency = (string) ($m_currency[1] ?? '');
        }

        $price = $this->normalize_price($amount);
        if ($price === null || $price <= 0) {
            return null;
        }

        $currency = $this->normalize_currency($currency !== '' ? $currency : 'SEK');

        return [
            'price' => $price,
            'currency' => $currency,
            'match' => 'meta:' . sanitize_text_field($amount . ' ' . $currency),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function extract_result_urls(string $html): array
    {
        if (!preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/iu', $html, $matches)) {
            return [];
        }

        $urls = [];
        foreach ((array) ($matches[1] ?? []) as $href_raw) {
            $href = html_entity_decode((string) $href_raw, ENT_QUOTES, 'UTF-8');
            $url = $this->resolve_search_result_url($href);
            if ($url === '') {
                continue;
            }

            $urls[] = $url;
            if (count($urls) >= self::MAX_CRAWL_RESULTS) {
                break;
            }
        }

        return array_values(array_unique($urls));
    }

    private function resolve_search_result_url(string $href): string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, '#')) {
            return '';
        }

        if (str_starts_with($href, '//')) {
            $href = 'https:' . $href;
        }

        $parts = wp_parse_url($href);
        if (!is_array($parts)) {
            return '';
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');
        if (str_contains($host, 'duckduckgo.com') && str_starts_with($path, '/l/')) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $target = (string) ($query['uddg'] ?? '');
            if ($target !== '') {
                $decoded = urldecode($target);
                return wp_http_validate_url($decoded) ? $decoded : '';
            }
        }

        if (!in_array((string) ($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return '';
        }
        if (str_contains($host, 'duckduckgo.com')) {
            return '';
        }

        return wp_http_validate_url($href) ? $href : '';
    }

    private function normalize_currency(string $currency): string
    {
        $currency = strtoupper(trim($currency));
        if ($currency === 'KR' || $currency === 'SEK') {
            return 'SEK';
        }
        if ($currency === 'EUR' || $currency === '€') {
            return 'EUR';
        }
        if ($currency === 'USD' || $currency === '$') {
            return 'USD';
        }

        return $currency !== '' ? $currency : 'SEK';
    }

    /**
     * @param array<string,mixed>|array<int,mixed> $value
     */
    private function is_assoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
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
