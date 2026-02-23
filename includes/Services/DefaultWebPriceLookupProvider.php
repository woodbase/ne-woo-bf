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
    private const SEARCH_ENDPOINT_HTML = 'https://html.duckduckgo.com/html/';
    private const SEARCH_ENDPOINT_LITE = 'https://lite.duckduckgo.com/lite/';
    private const SEARCH_ENDPOINT_BING = 'https://www.bing.com/search';
    /** @var array<int,string> */
    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    ];

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

        $search_request = $this->fetch_search_response($query);
        $search_url = (string) ($search_request['url'] ?? '');
        $search_source = (string) ($search_request['source'] ?? 'duckduckgo');
        $search_attempts = is_array($search_request['attempts'] ?? null) ? $search_request['attempts'] : [];
        $response = $search_request['response'] ?? null;
        if (is_wp_error($response)) {
            return [
                'source' => $search_source,
                'url' => $search_url,
                'debug' => [
                    'stage' => 'http_error',
                    'error' => sanitize_text_field($response->get_error_message()),
                    'attempts' => $search_attempts,
                ],
            ];
        }

        if (!is_array($response)) {
            return [
                'source' => $search_source,
                'url' => $search_url,
                'debug' => [
                    'stage' => 'search_failed',
                    'attempts' => $search_attempts,
                ],
            ];
        }

        $http_code = (int) ($search_request['http_code'] ?? wp_remote_retrieve_response_code($response));
        $html = (string) ($search_request['body'] ?? wp_remote_retrieve_body($response));
        if ($html === '') {
            return [
                'source' => $search_source,
                'url' => $search_url,
                'debug' => [
                    'stage' => 'empty_body',
                    'http_code' => $http_code,
                    'attempts' => $search_attempts,
                ],
            ];
        }

        $result_urls = $this->extract_result_urls($html);
        if (empty($result_urls)) {
            return [
                'source' => $search_source,
                'url' => $search_url,
                'debug' => [
                    'stage' => 'search_results_empty',
                    'http_code' => $http_code,
                    'search_source' => $search_source,
                    'search_attempts' => $search_attempts,
                    'body_head' => sanitize_text_field(substr(wp_strip_all_tags($html), 0, 200)),
                ],
            ];
        }

        $crawl_stats = [
            'attempted' => 0,
            'http_403' => 0,
            'http_other_non_2xx' => 0,
            'http_error' => 0,
        ];
        $crawl_found = $this->crawl_result_pages_for_price($result_urls, $crawl_stats);
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
                    'search_source' => $search_source,
                    'search_attempts' => $search_attempts,
                    'crawl_stats' => $crawl_stats,
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
                    'search_source' => $search_source,
                    'search_attempts' => $search_attempts,
                    'crawl_stats' => $crawl_stats,
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
                'search_source' => $search_source,
                'search_attempts' => $search_attempts,
                'crawl_stats' => $crawl_stats,
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
     * @param array<string,int> $stats
     * @return array{price: float, currency: string, match: string, url: string}|null
     */
    private function crawl_result_pages_for_price(array $urls, array &$stats): ?array
    {
        $urls = array_slice($urls, 0, self::MAX_CRAWL_RESULTS);
        foreach ($urls as $url) {
            $stats['attempted'] = (int) ($stats['attempted'] ?? 0) + 1;
            $request = $this->request_with_retry($url, 15, self::SEARCH_ENDPOINT_HTML);
            $response = $request['response'] ?? null;

            if (is_wp_error($response)) {
                $stats['http_error'] = (int) ($stats['http_error'] ?? 0) + 1;
                continue;
            }
            if (!is_array($response)) {
                $stats['http_error'] = (int) ($stats['http_error'] ?? 0) + 1;
                continue;
            }

            $http_code = (int) ($request['http_code'] ?? wp_remote_retrieve_response_code($response));
            if ($http_code === 403) {
                $stats['http_403'] = (int) ($stats['http_403'] ?? 0) + 1;
                continue;
            }
            if ($http_code < 200 || $http_code >= 400) {
                $stats['http_other_non_2xx'] = (int) ($stats['http_other_non_2xx'] ?? 0) + 1;
                continue;
            }

            $content_type = strtolower((string) ($request['content_type'] ?? wp_remote_retrieve_header($response, 'content-type')));
            if ($content_type !== '' && !str_contains($content_type, 'text/html')) {
                continue;
            }

            $html = (string) ($request['body'] ?? wp_remote_retrieve_body($response));
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
     * @return array{source:string,url:string,response:mixed,http_code:int,body:string,content_type:string,attempts:array<int,array<string,mixed>>}
     */
    private function fetch_search_response(string $query): array
    {
        $endpoints = [
            [
                'source' => 'duckduckgo-html',
                'url' => (string) add_query_arg([
                    'q' => $query,
                    'kl' => 'se-sv',
                    'kp' => '-2',
                ], self::SEARCH_ENDPOINT_HTML),
            ],
            [
                'source' => 'duckduckgo-lite',
                'url' => (string) add_query_arg([
                    'q' => $query,
                    'kl' => 'se-sv',
                ], self::SEARCH_ENDPOINT_LITE),
            ],
            [
                'source' => 'bing',
                'url' => (string) add_query_arg([
                    'q' => $query,
                    'setlang' => 'sv-SE',
                    'cc' => 'SE',
                ], self::SEARCH_ENDPOINT_BING),
            ],
        ];

        $attempts = [];
        $last = [
            'source' => 'duckduckgo-html',
            'url' => (string) $endpoints[0]['url'],
            'response' => null,
            'http_code' => 0,
            'body' => '',
            'content_type' => '',
            'attempts' => [],
        ];

        foreach ($endpoints as $endpoint) {
            $source = (string) $endpoint['source'];
            $url = (string) $endpoint['url'];
            $referer = str_starts_with($source, 'duckduckgo') ? 'https://duckduckgo.com/' : 'https://www.bing.com/';
            $request = $this->request_with_retry($url, 20, $referer);
            $response = $request['response'] ?? null;
            $http_code = (int) ($request['http_code'] ?? 0);
            $body = (string) ($request['body'] ?? '');
            $content_type = (string) ($request['content_type'] ?? '');

            $attempts[] = [
                'source' => $source,
                'url' => $url,
                'http_code' => $http_code,
                'error' => is_wp_error($response) ? sanitize_text_field($response->get_error_message()) : '',
            ];

            $last = [
                'source' => $source,
                'url' => $url,
                'response' => $response,
                'http_code' => $http_code,
                'body' => $body,
                'content_type' => $content_type,
                'attempts' => $attempts,
            ];

            if (is_wp_error($response)) {
                continue;
            }

            if (
                $http_code >= 200 &&
                $http_code < 300 &&
                $body !== '' &&
                $this->is_search_results_page($body)
            ) {
                break;
            }
        }

        return $last;
    }

    /**
     * @return array{response:mixed,http_code:int,body:string,content_type:string}
     */
    private function request_with_retry(string $url, int $timeout, string $referer = ''): array
    {
        $last_response = null;
        $last_http_code = 0;
        $last_body = '';
        $last_content_type = '';

        foreach (self::USER_AGENTS as $index => $user_agent) {
            $response = wp_remote_get($url, [
                'timeout' => $timeout,
                'redirection' => 5,
                'headers' => $this->build_headers($user_agent, $referer),
            ]);

            if (is_wp_error($response)) {
                $last_response = $response;
                continue;
            }

            $http_code = (int) wp_remote_retrieve_response_code($response);
            $body = (string) wp_remote_retrieve_body($response);
            $content_type = strtolower((string) wp_remote_retrieve_header($response, 'content-type'));

            $last_response = $response;
            $last_http_code = $http_code;
            $last_body = $body;
            $last_content_type = $content_type;

            // Retry once on obvious anti-bot responses.
            if (($http_code === 403 || $http_code === 429) && $index === 0) {
                continue;
            }

            break;
        }

        return [
            'response' => $last_response,
            'http_code' => $last_http_code,
            'body' => $last_body,
            'content_type' => $last_content_type,
        ];
    }

    /**
     * @return array<string,string>
     */
    private function build_headers(string $user_agent, string $referer = ''): array
    {
        $headers = [
            'User-Agent' => $user_agent,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'sv-SE,sv;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
        ];

        if ($referer !== '') {
            $headers['Referer'] = $referer;
        }

        return $headers;
    }

    private function is_search_results_page(string $html): bool
    {
        // Detect that this is a result page and not a generic DDG landing/challenge page.
        $needles = [
            'result__a',
            'result-link',
            '/l/?uddg=',
            'uddg=',
            'web-result',
            'b_algo',
            'b_results',
        ];

        foreach ($needles as $needle) {
            if (stripos($html, $needle) !== false) {
                return true;
            }
        }

        return false;
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
        if (str_contains($host, 'bing.com') && str_starts_with($path, '/ck/')) {
            parse_str((string) ($parts['query'] ?? ''), $query);
            $encoded = (string) ($query['u'] ?? '');
            if ($encoded !== '') {
                $decoded = urldecode($encoded);
                if (str_starts_with($decoded, 'a1')) {
                    $decoded = substr($decoded, 2);
                    $candidate = base64_decode($decoded, true);
                    if (is_string($candidate) && wp_http_validate_url($candidate)) {
                        return $candidate;
                    }
                }
                if (wp_http_validate_url($decoded)) {
                    return $decoded;
                }
            }
        }

        if (!in_array((string) ($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return '';
        }
        if (str_contains($host, 'duckduckgo.com') || str_contains($host, 'bing.com')) {
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
