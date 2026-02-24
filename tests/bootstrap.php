<?php

define('ABSPATH', __DIR__);

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;

        public function __construct(string $code = '', string $message = '')
        {
            $this->code = $code;
            $this->message = $message;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

$GLOBALS['nebf_test_options'] = [];
$GLOBALS['nebf_test_last_remote_request'] = null;
$GLOBALS['nebf_test_remote_handler'] = null;

function get_option(string $name, $default = false)
{
    return $GLOBALS['nebf_test_options'][$name] ?? $default;
}

function update_option(string $name, $value, $autoload = false): bool
{
    $GLOBALS['nebf_test_options'][$name] = $value;
    return true;
}

function __(string $text, string $domain = ''): string
{
    return $text;
}

function esc_xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function is_wp_error($value): bool
{
    return $value instanceof WP_Error;
}

function wp_remote_post(string $url, array $args)
{
    $GLOBALS['nebf_test_last_remote_request'] = [
        'url' => $url,
        'args' => $args,
    ];

    if (is_callable($GLOBALS['nebf_test_remote_handler'])) {
        return call_user_func($GLOBALS['nebf_test_remote_handler'], $url, $args);
    }

    return new WP_Error('nebf_test_missing_http_stub', 'No HTTP stub configured for test.');
}

function wp_remote_retrieve_body($response): string
{
    return is_array($response) ? (string) ($response['body'] ?? '') : '';
}

function wp_remote_retrieve_response_code($response): int
{
    return is_array($response) ? (int) ($response['response']['code'] ?? 0) : 0;
}

function wp_remote_retrieve_headers($response): array
{
    return is_array($response) ? (array) ($response['headers'] ?? []) : [];
}

require_once dirname(__DIR__) . '/includes/Services/BeautyFortApiService.php';
