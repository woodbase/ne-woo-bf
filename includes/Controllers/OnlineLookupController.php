<?php

namespace NEBF\Controllers;

use NEBF\Services\WebPriceLookupService;
use NEBF\Services\WebPriceLookupQueueService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controller for managing online lookup search-name overrides.
 */
class OnlineLookupController extends AbstractAdminController
{
    private const OPTION_NAME_OVERRIDES = 'nebf_web_price_lookup_name_overrides';

    public function handle(): void
    {
        $selected_bf_id = '';
        $lookup_result = null;

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_save_lookup_override']) &&
            check_admin_referer('nebf_save_lookup_override')
        ) {
            $this->handle_save_override();
            $selected_bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_delete_lookup_override']) &&
            check_admin_referer('nebf_delete_lookup_override')
        ) {
            $this->handle_delete_override();
            $selected_bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_queue_lookup_product']) &&
            check_admin_referer('nebf_queue_lookup_product')
        ) {
            $this->handle_queue_product();
            $selected_bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['nebf_run_lookup_now']) &&
            check_admin_referer('nebf_run_lookup_now')
        ) {
            $selected_bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
            $lookup_result = $this->handle_run_lookup_now();
        }

        $products = get_option('nebf_beautyfort_products', []);
        if (!is_array($products)) {
            $products = [];
        }

        uasort($products, static function ($a, $b) {
            $a_name = strtolower((string) ($a['fullname'] ?? ''));
            $b_name = strtolower((string) ($b['fullname'] ?? ''));
            return strcmp($a_name, $b_name);
        });

        $overrides = get_option(self::OPTION_NAME_OVERRIDES, []);
        if (!is_array($overrides)) {
            $overrides = [];
        }

        $this->render('online-lookup', [
            'products' => $products,
            'overrides' => $overrides,
            'selected_bf_id' => $selected_bf_id,
            'lookup_result' => $lookup_result,
        ]);
    }

    private function handle_save_override(): void
    {
        $bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        $search_name = sanitize_text_field((string) ($_POST['search_name_override'] ?? ''));

        if ($bf_id === '') {
            $this->notices->add(__('Select a product first.', 'nebf-mvc'), 'warning');
            return;
        }

        $overrides = get_option(self::OPTION_NAME_OVERRIDES, []);
        if (!is_array($overrides)) {
            $overrides = [];
        }

        if ($search_name === '') {
            unset($overrides[$bf_id]);
            update_option(self::OPTION_NAME_OVERRIDES, $overrides, false);
            $this->notices->add(__('Search-name override removed.', 'nebf-mvc'), 'success');
            return;
        }

        $overrides[$bf_id] = $search_name;
        update_option(self::OPTION_NAME_OVERRIDES, $overrides, false);
        $this->notices->add(__('Search-name override saved.', 'nebf-mvc'), 'success');
    }

    private function handle_delete_override(): void
    {
        $bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        if ($bf_id === '') {
            $this->notices->add(__('Missing BF ID for delete.', 'nebf-mvc'), 'warning');
            return;
        }

        $overrides = get_option(self::OPTION_NAME_OVERRIDES, []);
        if (!is_array($overrides)) {
            $overrides = [];
        }

        unset($overrides[$bf_id]);
        update_option(self::OPTION_NAME_OVERRIDES, $overrides, false);
        $this->notices->add(__('Override deleted.', 'nebf-mvc'), 'success');
    }

    private function handle_queue_product(): void
    {
        $bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        if ($bf_id === '') {
            $this->notices->add(__('Missing BF ID to queue.', 'nebf-mvc'), 'warning');
            return;
        }

        $queue = new WebPriceLookupQueueService();
        $added = $queue->enqueue_bf_ids([$bf_id]);
        if ($added > 0) {
            $this->notices->add(__('Product queued for online lookup.', 'nebf-mvc'), 'success');
        } else {
            $this->notices->add(__('Product is already queued or invalid.', 'nebf-mvc'), 'info');
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    private function handle_run_lookup_now(): ?array
    {
        $bf_id = sanitize_text_field((string) ($_POST['bf_id'] ?? ''));
        if ($bf_id === '') {
            $this->notices->add(__('Missing BF ID for direct lookup.', 'nebf-mvc'), 'warning');
            return null;
        }

        $lookup_service = new WebPriceLookupService();
        $ok = $lookup_service->lookup_and_store($bf_id);
        if (!$ok) {
            $this->notices->add(__('Could not run online lookup for this product.', 'nebf-mvc'), 'error');
            return null;
        }

        $products = get_option('nebf_beautyfort_products', []);
        if (!is_array($products) || !isset($products[$bf_id]) || !is_array($products[$bf_id])) {
            $this->notices->add(__('Lookup ran, but product result could not be loaded.', 'nebf-mvc'), 'warning');
            return null;
        }

        $product = $products[$bf_id];
        $status = (string) ($product['web_price_lookup_status'] ?? '');
        if ($status === 'ok') {
            $this->notices->add(__('Online lookup completed and found a price.', 'nebf-mvc'), 'success');
        } else {
            $this->notices->add(__('Online lookup completed but no price was found.', 'nebf-mvc'), 'info');
        }

        return [
            'bf_id' => $bf_id,
            'product' => $product,
        ];
    }
}
