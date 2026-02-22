<?php

namespace NEBF\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues and processes web price lookup jobs in the background via WP-Cron.
 */
class WebPriceLookupQueueService
{
    public const CRON_HOOK = 'nebf_process_web_price_lookup_queue';
    private const OPTION_QUEUE = 'nebf_web_price_lookup_queue';
    private const OPTION_LAST_RUN = 'nebf_web_price_lookup_last_run';
    private const LOCK_KEY = 'nebf_web_price_lookup_lock';

    public function register_hooks(): void
    {
        add_action(self::CRON_HOOK, [$this, 'process_batch']);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     */
    public function enqueue_products(array $products): int
    {
        $bf_ids = [];

        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $bf_id = sanitize_text_field((string) ($product['bf_id'] ?? $product['sku'] ?? ''));
            if ($bf_id !== '') {
                $bf_ids[] = $bf_id;
            }
        }

        return $this->enqueue_bf_ids($bf_ids);
    }

    /**
     * @param array<int, string> $bf_ids
     */
    public function enqueue_bf_ids(array $bf_ids): int
    {
        if (empty($bf_ids)) {
            return 0;
        }

        $clean_ids = [];
        foreach ($bf_ids as $bf_id) {
            $bf_id = sanitize_text_field((string) $bf_id);
            if ($bf_id !== '') {
                $clean_ids[] = $bf_id;
            }
        }

        $clean_ids = array_values(array_unique($clean_ids));
        if (empty($clean_ids)) {
            return 0;
        }

        $queue = $this->get_queue();
        $known = array_fill_keys($queue, true);

        $added = 0;
        foreach ($clean_ids as $bf_id) {
            if (!isset($known[$bf_id])) {
                $queue[] = $bf_id;
                $known[$bf_id] = true;
                $added++;
            }
        }

        update_option(self::OPTION_QUEUE, $queue, false);
        $this->schedule_if_needed();

        return $added;
    }

    public function process_batch(): void
    {
        if (get_transient(self::LOCK_KEY)) {
            return;
        }

        set_transient(self::LOCK_KEY, 1, 90);

        try {
            $queue = $this->get_queue();
            if (empty($queue)) {
                return;
            }

            $batch_size = (int) apply_filters('nebf_web_price_lookup_batch_size', 20);
            $batch_size = max(1, min(200, $batch_size));

            $batch = array_slice($queue, 0, $batch_size);
            $remaining = array_slice($queue, $batch_size);

            $lookup_service = new WebPriceLookupService();
            $processed = 0;
            $failed = 0;

            foreach ($batch as $bf_id) {
                $ok = $lookup_service->lookup_and_store((string) $bf_id);
                if ($ok) {
                    $processed++;
                } else {
                    $failed++;
                }
            }

            update_option(self::OPTION_QUEUE, array_values($remaining), false);
            update_option(self::OPTION_LAST_RUN, [
                'timestamp' => time(),
                'processed' => $processed,
                'failed' => $failed,
                'remaining' => count($remaining),
            ], false);

            if (!empty($remaining)) {
                wp_schedule_single_event(time() + 20, self::CRON_HOOK);
            }
        } finally {
            delete_transient(self::LOCK_KEY);
        }
    }

    /**
     * @return array{queued:int,last_run_at:int,processed:int,failed:int,remaining:int}
     */
    public function get_status(): array
    {
        $queue = $this->get_queue();
        $last_run = get_option(self::OPTION_LAST_RUN, []);
        if (!is_array($last_run)) {
            $last_run = [];
        }

        return [
            'queued' => count($queue),
            'last_run_at' => (int) ($last_run['timestamp'] ?? 0),
            'processed' => (int) ($last_run['processed'] ?? 0),
            'failed' => (int) ($last_run['failed'] ?? 0),
            'remaining' => (int) ($last_run['remaining'] ?? count($queue)),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function get_queue(): array
    {
        $queue = get_option(self::OPTION_QUEUE, []);
        if (!is_array($queue)) {
            return [];
        }

        $clean = [];
        foreach ($queue as $bf_id) {
            $bf_id = sanitize_text_field((string) $bf_id);
            if ($bf_id !== '') {
                $clean[] = $bf_id;
            }
        }

        return array_values(array_unique($clean));
    }

    private function schedule_if_needed(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time() + 5, self::CRON_HOOK);
        }
    }
}
