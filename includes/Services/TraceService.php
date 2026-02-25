<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) exit;

class TraceService
{
    private const KEYS = [
        'create' => 'nebf_last_create_order_trace',
        'add'    => 'nebf_last_add_order_item_trace',
        'edit'   => 'nebf_last_edit_order_item_trace',
        'error'  => 'nebf_last_error_trace',
    ];

    public function save(string $type, array $data): void
    {
        if (!isset(self::KEYS[$type])) {
            return;
        }

        update_option(self::KEYS[$type], $data, false);
    }

    public function get(string $type): array
    {
        if (!isset(self::KEYS[$type])) {
            return [];
        }

        $trace = get_option(self::KEYS[$type], []);
        return is_array($trace) ? $trace : [];
    }

    public function clear_all(): void
    {
        foreach (self::KEYS as $key) {
            delete_option($key);
        }
    }

    public function get_all(): array
    {
        $result = [];

        foreach (self::KEYS as $type => $key) {
            $trace = get_option($key, []);
            $result[$type] = is_array($trace) ? $trace : [];
        }

        return $result;
    }
}